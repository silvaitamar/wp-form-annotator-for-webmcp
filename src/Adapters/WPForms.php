<?php
/**
 * Declarative WebMCP adapter for WPForms.
 *
 * @package Siwmfa
 */

namespace Siwmfa\Adapters;

use Siwmfa\Annotator;
use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Soft-dep: loads only when WPForms is active.
 */
final class WPForms {

	public const BUILDER = 'wpforms';

	/**
	 * Field types that must not become WebMCP params.
	 *
	 * @var array<string, true>
	 */
	private const SKIP_TYPES = array(
		'hidden'        => true,
		'html'          => true,
		'pagebreak'     => true,
		'divider'       => true,
		'captcha'       => true,
		'recaptcha'     => true,
		'hcaptcha'      => true,
		'turnstile'     => true,
		'honeypot'      => true,
		'entry-preview' => true,
	);

	/**
	 * Registers WPForms filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! self::is_available() ) {
			return;
		}

		\add_filter( 'wpforms_frontend_form_atts', array( self::class, 'annotate_form' ), 20, 2 );
		\add_filter( 'wpforms_field_properties', array( self::class, 'annotate_field' ), 20, 3 );
	}

	/**
	 * Whether WPForms is active.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \function_exists( 'wpforms' );
	}

	/**
	 * Lists WPForms forms for the settings UI.
	 *
	 * @return list<array{builder: string, id: int, title: string, fields: array<string, string>}>
	 */
	public static function list_forms(): array {
		if ( ! self::is_available() || ! \post_type_exists( 'wpforms' ) ) {
			return array();
		}

		$forms = \get_posts(
			array(
				'post_type'              => 'wpforms',
				'post_status'            => 'any',
				'posts_per_page'         => 100,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$out = array();
		foreach ( $forms as $form ) {
			if ( ! $form instanceof \WP_Post ) {
				continue;
			}
			$id    = (int) $form->ID;
			$out[] = array(
				'builder' => self::BUILDER,
				'id'      => $id,
				'title'   => $form->post_title,
				'fields'  => self::list_fields( $id, (string) $form->post_content ),
			);
		}

		return $out;
	}

	/**
	 * Adds toolname and tooldescription to the form tag.
	 *
	 * @param array<string, mixed> $form_atts Existing form attributes wrapper.
	 * @param array<string, mixed> $form_data Form data.
	 * @return array<string, mixed>
	 */
	public static function annotate_form( array $form_atts, array $form_data ): array {
		$id = self::resolve_form_id( $form_data, $form_atts );
		if ( $id <= 0 || ! Registry::is_enabled( self::BUILDER, $id ) ) {
			return $form_atts;
		}

		$config = Registry::get( self::BUILDER, $id );

		if ( ! isset( $form_atts['atts'] ) || ! \is_array( $form_atts['atts'] ) ) {
			$form_atts['atts'] = array();
		}

		$form_atts['atts'] = \array_merge( $form_atts['atts'], Annotator::form_attributes( $config ) );

		return $form_atts;
	}

	/**
	 * Adds toolparamdescription to a WPForms field.
	 *
	 * @param array<string, mixed> $properties Field properties.
	 * @param array<string, mixed> $field      Field settings.
	 * @param array<string, mixed> $form_data  Form data.
	 * @return array<string, mixed>
	 */
	public static function annotate_field( array $properties, array $field, array $form_data ): array {
		$id = self::resolve_form_id( $form_data );
		if ( $id <= 0 || ! Registry::is_enabled( self::BUILDER, $id ) ) {
			return $properties;
		}

		$config = Registry::get( self::BUILDER, $id );

		$field_id = isset( $field['id'] ) ? (int) $field['id'] : 0;
		if ( $field_id <= 0 ) {
			return $properties;
		}

		$name = 'wpforms[fields][' . $field_id . ']';
		if ( ! isset( $config['params'][ $name ] ) || '' === $config['params'][ $name ] ) {
			return $properties;
		}

		$desc = $config['params'][ $name ];
		$type = isset( $field['type'] ) ? (string) $field['type'] : '';

		if ( \in_array( $type, array( 'select', 'radio', 'checkbox' ), true ) ) {
			if ( ! isset( $properties['input_container'] ) || ! \is_array( $properties['input_container'] ) ) {
				$properties['input_container'] = array();
			}
			if ( ! isset( $properties['input_container']['attr'] ) || ! \is_array( $properties['input_container']['attr'] ) ) {
				$properties['input_container']['attr'] = array();
			}
			$properties['input_container']['attr']['toolparamdescription'] = $desc;

			return $properties;
		}

		if ( empty( $properties['inputs']['primary'] ) || ! \is_array( $properties['inputs']['primary'] ) ) {
			return $properties;
		}

		if ( ! isset( $properties['inputs']['primary']['attr'] ) || ! \is_array( $properties['inputs']['primary']['attr'] ) ) {
			$properties['inputs']['primary']['attr'] = array();
		}

		$properties['inputs']['primary']['attr']['toolparamdescription'] = $desc;

		return $properties;
	}

	/**
	 * Resolves the form post ID from WPForms payloads (JSON id is optional in 2.x).
	 *
	 * @param array<string, mixed>      $form_data Form data.
	 * @param array<string, mixed>|null $form_atts Form attributes wrapper.
	 * @return int
	 */
	private static function resolve_form_id( array $form_data, ?array $form_atts = null ): int {
		if ( isset( $form_data['id'] ) ) {
			$id = (int) $form_data['id'];
			if ( $id > 0 ) {
				return $id;
			}
		}

		if ( \is_array( $form_atts ) && isset( $form_atts['data']['formid'] ) ) {
			$id = (int) $form_atts['data']['formid'];
			if ( $id > 0 ) {
				return $id;
			}
		}

		if ( \is_array( $form_atts ) && isset( $form_atts['id'] ) && \is_string( $form_atts['id'] ) && \preg_match( '/(\d+)$/', $form_atts['id'], $m ) ) {
			return (int) $m[1];
		}

		return 0;
	}

	/**
	 * Maps WPForms field HTML names to labels.
	 *
	 * @param int    $form_id      Form post ID.
	 * @param string $post_content Form JSON.
	 * @return array<string, string>
	 */
	private static function list_fields( int $form_id, string $post_content ): array {
		unset( $form_id );

		$data = \json_decode( $post_content, true );
		if ( ! \is_array( $data ) && \function_exists( 'wpforms_decode' ) ) {
			$decoded = \wpforms_decode( $post_content );
			$data    = \is_array( $decoded ) ? $decoded : array();
		}
		if ( ! \is_array( $data ) || empty( $data['fields'] ) || ! \is_array( $data['fields'] ) ) {
			return array();
		}

		$out = array();
		foreach ( $data['fields'] as $field ) {
			if ( ! \is_array( $field ) ) {
				continue;
			}
			$type = isset( $field['type'] ) ? (string) $field['type'] : '';
			if ( isset( self::SKIP_TYPES[ $type ] ) ) {
				continue;
			}
			$id = isset( $field['id'] ) ? (int) $field['id'] : 0;
			if ( $id <= 0 ) {
				continue;
			}
			$label        = isset( $field['label'] ) ? (string) $field['label'] : '';
			$name         = 'wpforms[fields][' . $id . ']';
			$out[ $name ] = '' !== $label ? \wp_strip_all_tags( $label ) : $name;
		}

		return $out;
	}
}
