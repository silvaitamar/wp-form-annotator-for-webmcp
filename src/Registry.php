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
	 * @return array{enabled: bool, toolname: string, tooldescription: string, params: array<string, string>, toolautosubmit: bool}
	 */
	public static function get( string $builder, int $id ): array {
		$key   = self::make_key( $builder, $id );
		$all   = self::get_all();
		$saved = ( isset( $all[ $key ] ) && \is_array( $all[ $key ] ) ) ? $all[ $key ] : array();

		return self::normalize( $saved, $builder );
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

			$clean[ $key ] = self::normalize( $row, $parts['builder'] );
		}

		\update_option( self::OPTION_KEY, $clean, false );
		Cache_Purge::after_annotation_change();
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
		$all[ $key ] = self::normalize( $row, $parts['builder'] );
		\update_option( self::OPTION_KEY, $all, false );
		Cache_Purge::after_annotation_change();

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
			if ( self::allows_autosubmit( $parts['builder'] ) ) {
				$current['toolname']        = self::suggest_filter_toolname( $parts['builder'] );
				$current['tooldescription'] = self::suggest_filter_description( $parts['builder'] );
				$current['toolautosubmit']  = true;
			} else {
				$current['toolname']        = self::suggest_toolname( $title );
				$current['tooldescription'] = self::suggest_description( $title );
			}
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
		if ( 1 !== \preg_match( '/^(cf7|fluent|wpforms|forminator|ninja|sureforms|jetpack|search|filtereverything|searchfilter):(\d+)$/', $key, $m ) ) {
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
	 * When `$builder` is known, `toolautosubmit` is forced off unless the builder
	 * allows idempotent GET autosubmit (search / filter). Lead builders never persist it.
	 *
	 * @param array<string, mixed> $row     Raw row.
	 * @param string               $builder Builder slug when known (enforces autosubmit policy).
	 * @return array{enabled: bool, toolname: string, tooldescription: string, params: array<string, string>, toolautosubmit: bool}
	 */
	public static function normalize( array $row, string $builder = '' ): array {
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

		$enabled        = ! empty( $row['enabled'] );
		$toolautosubmit = ! empty( $row['toolautosubmit'] );
		if ( '' !== $builder && ! self::allows_autosubmit( $builder ) ) {
			$toolautosubmit = false;
		}

		return array(
			'enabled'         => $enabled,
			'toolname'        => $toolname,
			'tooldescription' => $description,
			'params'          => $params,
			'toolautosubmit'  => $toolautosubmit,
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
			$title = \__( 'contact form', 'silvaitamar-form-annotator-for-webmcp' );
		}

		return \sprintf(
			/* translators: %s: form title */
			\__( 'Fills the "%s" form on this page. Use for lead, contact, or support requests. Do not submit the form — only fill the fields.', 'silvaitamar-form-annotator-for-webmcp' ),
			$title
		);
	}

	/**
	 * Whether this builder may use toolautosubmit (idempotent GET).
	 *
	 * @param string $builder Builder slug.
	 * @return bool
	 */
	public static function allows_autosubmit( string $builder ): bool {
		return \in_array( $builder, array( 'search', 'filtereverything', 'searchfilter' ), true );
	}

	/**
	 * Default tool name for search / filter builders.
	 *
	 * @param string $builder Builder slug.
	 * @return string
	 */
	public static function suggest_filter_toolname( string $builder ): string {
		switch ( $builder ) {
			case 'filtereverything':
				return 'filter_everything';
			case 'searchfilter':
				return 'search_and_filter';
			default:
				return 'search_site';
		}
	}

	/**
	 * Default description for search / filter builders (also used by lab seeds).
	 *
	 * @param string $builder Builder slug.
	 * @return string
	 */
	public static function suggest_filter_description( string $builder ): string {
		switch ( $builder ) {
			case 'filtereverything':
				return \__( 'HARD LIMIT: at most ONE call to this tool per user message. Fill srch once with the full search phrase (example: "WordPress performance"), then stop. Category and tag facets on the page are links, not tool parameters - do not try to drive them via this tool. A null or empty tool result after submit is normal because the page navigates - treat it as success and do NOT call again or try alternate keywords.', 'silvaitamar-form-annotator-for-webmcp' );
			case 'searchfilter':
				return \__( 'HARD LIMIT: at most ONE call to this tool per user message. Put the entire user intent into a single ofsearch phrase (example: "WordPress performance tutorials"), set ofcategory and ofpost_tag in that same call (term ID from the select options, or "0" for All; never ""), then stop. A null or empty tool result after submit is normal because the page navigates - treat it as success and do NOT call again, do NOT split the query into separate keywords, and do NOT try every category or tag.', 'silvaitamar-form-annotator-for-webmcp' );
			default:
				return \__( 'HARD LIMIT: at most ONE call per user message. Put the full search intent in s as a single phrase, submit once, then stop. A null result after navigation is normal success - do not retry with alternate keywords.', 'silvaitamar-form-annotator-for-webmcp' );
		}
	}
}
