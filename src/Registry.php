<?php
/**
 * Stored per-form WebMCP annotation config.
 *
 * @package Siwmfa
 */

namespace Siwmfa;

defined( 'ABSPATH' ) || exit;

/**
 * Option-backed registry keyed as `{builder}:{id}`.
 */
final class Registry {

	public const OPTION_KEY = 'siwmfa_forms';

	/**
	 * Returns all saved form configs.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_all(): array {
		$stored = \get_option( self::OPTION_KEY, array() );
		return \is_array( $stored ) ? $stored : array();
	}

	/**
	 * Returns the config for one form.
	 *
	 * @param string $builder Builder slug.
	 * @param int    $id      Form ID.
	 * @return array{enabled: bool, toolname: string, tooldescription: string, params: array<string, string>}
	 */
	public static function get( string $builder, int $id ): array {
		$key   = self::make_key( $builder, $id );
		$all   = self::get_all();
		$saved = ( isset( $all[ $key ] ) && \is_array( $all[ $key ] ) ) ? $all[ $key ] : array();

		return self::normalize( $saved );
	}

	/**
	 * Whether annotation is enabled and a tool name is set.
	 *
	 * @param string $builder Builder slug.
	 * @param int    $id      Form ID.
	 * @return bool
	 */
	public static function is_enabled( string $builder, int $id ): bool {
		$config = self::get( $builder, $id );
		return $config['enabled'] && '' !== $config['toolname'];
	}

	/**
	 * Builds a registry key.
	 *
	 * @param string $builder Builder slug.
	 * @param int    $id      Form ID.
	 * @return string
	 */
	public static function make_key( string $builder, int $id ): string {
		return $builder . ':' . $id;
	}

	/**
	 * Persists sanitized form configs.
	 *
	 * @param array<string, mixed> $raw Raw POST rows keyed by registry key.
	 * @return void
	 */
	public static function save_all( array $raw ): void {
		$clean = array();

		foreach ( $raw as $key => $row ) {
			if ( ! \is_string( $key ) || ! \is_array( $row ) ) {
				continue;
			}

			$parts = self::parse_key( $key );
			if ( null === $parts ) {
				continue;
			}

			$clean[ $key ] = self::normalize( $row );
		}

		\update_option( self::OPTION_KEY, $clean, false );
	}

	/**
	 * Merges one sanitized form config into the stored registry.
	 *
	 * @param string               $key Registry key.
	 * @param array<string, mixed> $row Raw row.
	 * @return bool
	 */
	public static function save_one( string $key, array $row ): bool {
		$parts = self::parse_key( $key );
		if ( null === $parts ) {
			return false;
		}

		$all         = self::get_all();
		$all[ $key ] = self::normalize( $row );
		\update_option( self::OPTION_KEY, $all, false );

		return true;
	}

	/**
	 * Toggles annotation without wiping tool name or field descriptions.
	 *
	 * @param string $key     Registry key.
	 * @param bool   $enabled Whether the form is enabled.
	 * @param string $title   Form title used for suggestions when enabling a blank row.
	 * @return bool
	 */
	public static function set_enabled( string $key, bool $enabled, string $title = '' ): bool {
		$parts = self::parse_key( $key );
		if ( null === $parts ) {
			return false;
		}

		$current            = self::get( $parts['builder'], $parts['id'] );
		$current['enabled'] = $enabled;

		if ( $enabled && '' === $current['toolname'] ) {
			$current['toolname']        = self::suggest_toolname( $title );
			$current['tooldescription'] = self::suggest_description( $title );
		}

		return self::save_one( $key, $current );
	}

	/**
	 * Parses a registry key.
	 *
	 * @param string $key Key in `{builder}:{id}` form.
	 * @return array{builder: string, id: int}|null
	 */
	public static function parse_key( string $key ): ?array {
		if ( 1 !== \preg_match( '/^(cf7|fluent|wpforms|forminator|ninja|sureforms):(\d+)$/', $key, $m ) ) {
			return null;
		}

		return array(
			'builder' => $m[1],
			'id'      => (int) $m[2],
		);
	}

	/**
	 * Sanitizes a WebMCP tool name (lowercase snake_case).
	 *
	 * @param string $name Raw name.
	 * @return string
	 */
	public static function sanitize_toolname( string $name ): string {
		$name = \strtolower( $name );
		$name = (string) \preg_replace( '/[^a-z0-9_]+/', '_', $name );
		$name = \trim( $name, '_' );
		return \substr( $name, 0, 64 );
	}

	/**
	 * Normalizes a raw config row.
	 *
	 * @param array<string, mixed> $row Raw row.
	 * @return array{enabled: bool, toolname: string, tooldescription: string, params: array<string, string>}
	 */
	public static function normalize( array $row ): array {
		$params = array();
		if ( isset( $row['params'] ) && \is_array( $row['params'] ) ) {
			foreach ( $row['params'] as $field => $description ) {
				if ( ! \is_string( $field ) || ! \is_string( $description ) ) {
					continue;
				}
				$field = \sanitize_text_field( $field );
				if ( '' === $field ) {
					continue;
				}
				$params[ $field ] = \sanitize_text_field( $description );
			}
		}

		$toolname = '';
		if ( isset( $row['toolname'] ) && \is_string( $row['toolname'] ) ) {
			$toolname = self::sanitize_toolname( $row['toolname'] );
		}

		$description = '';
		if ( isset( $row['tooldescription'] ) && \is_string( $row['tooldescription'] ) ) {
			$description = \sanitize_textarea_field( $row['tooldescription'] );
		}

		$enabled = ! empty( $row['enabled'] );

		return array(
			'enabled'         => $enabled,
			'toolname'        => $toolname,
			'tooldescription' => $description,
			'params'          => $params,
		);
	}

	/**
	 * Suggests a tool name from a form title.
	 *
	 * @param string $title Form title.
	 * @return string
	 */
	public static function suggest_toolname( string $title ): string {
		$slug = self::sanitize_toolname( $title );
		if ( '' === $slug ) {
			return 'submit_form';
		}
		if ( 0 !== \strpos( $slug, 'submit_' ) && 0 !== \strpos( $slug, 'send_' ) && 0 !== \strpos( $slug, 'request_' ) ) {
			$slug = 'submit_' . $slug;
		}
		return \substr( $slug, 0, 64 );
	}

	/**
	 * Default description: fill only, never autosubmit.
	 *
	 * @param string $title Form title.
	 * @return string
	 */
	public static function suggest_description( string $title ): string {
		$title = \wp_strip_all_tags( $title );
		if ( '' === $title ) {
			$title = 'contact';
		}

		return \sprintf(
			/* translators: %s: form title */
			\__( 'Fills the "%s" form on this page. Use for lead, contact, or support requests. Do not submit the form — only fill the fields.', 'silvaitamar-webmcp-form-annotator' ),
			$title
		);
	}
}
