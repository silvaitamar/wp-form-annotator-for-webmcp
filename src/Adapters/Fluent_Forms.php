<?php
/**
 * Declarative WebMCP adapter for Fluent Forms.
 *
 * @package Siwmfa
 */

namespace Siwmfa\Adapters;

use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Soft-dep: loads only when Fluent Forms is active.
 */
final class Fluent_Forms {

	public const BUILDER = 'fluent';

	/**
	 * Fluent field types that must not become WebMCP params.
	 *
	 * @var array<string, true>
	 */
	private const SKIP_ELEMENTS = array(
		'input_hidden'  => true,
		'custom_html'   => true,
		'section_break' => true,
		'recaptcha'     => true,
		'hcaptcha'      => true,
		'turnstile'     => true,
		'shortcode'     => true,
		'button'        => true,
		'submit'        => true,
	);

	/**
	 * Registers Fluent Forms filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! self::is_available() ) {
			return;
		}

		\add_filter( 'fluentform/html_attributes', array( self::class, 'annotate_form' ), 20, 2 );

		foreach ( array( 'input_text', 'input_email', 'input_url', 'input_number', 'input_date', 'select', 'textarea', 'input_textarea', 'phone', 'address' ) as $element ) {
			\add_filter(
				"fluentform/rendering_field_data_{$element}",
				array( self::class, 'annotate_field' ),
				20,
				2
			);
		}
	}

	/**
	 * Whether Fluent Forms is active.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \function_exists( 'wpFluent' ) || \defined( 'FLUENTFORM' );
	}

	/**
	 * Lists Fluent forms for the settings UI.
	 *
	 * @return list<array{builder: string, id: int, title: string, fields: array<string, string>}>
	 */
	public static function list_forms(): array {
		if ( ! \function_exists( 'wpFluent' ) ) {
			return array();
		}

		try {
			$rows = \wpFluent()->table( 'fluentform_forms' )->select( array( 'id', 'title', 'form_fields' ) )->orderBy( 'id', 'DESC' )->get();
		} catch ( \Throwable $exception ) {
			unset( $exception );
			return array();
		}

		if ( ! \is_iterable( $rows ) ) {
			return array();
		}

		$out = array();
		foreach ( $rows as $row ) {
			$id    = isset( $row->id ) ? (int) $row->id : 0;
			$title = isset( $row->title ) ? (string) $row->title : '';
			if ( $id <= 0 ) {
				continue;
			}
			$fields_json = isset( $row->form_fields ) ? (string) $row->form_fields : '';
			$out[]       = array(
				'builder' => self::BUILDER,
				'id'      => $id,
				'title'   => $title,
				'fields'  => self::fields_from_json( $fields_json ),
			);
		}

		return $out;
	}

	/**
	 * Adds toolname and tooldescription to the form tag.
	 *
	 * @param array<string, mixed> $attrs Existing attributes.
	 * @param mixed                $form  Fluent form object.
	 * @return array<string, mixed>
	 */
	public static function annotate_form( array $attrs, $form ): array {
		$config = self::config_for_form( $form );
		if ( null === $config ) {
			return $attrs;
		}

		$attrs['toolname']        = $config['toolname'];
		$attrs['tooldescription'] = $config['tooldescription'];

		return $attrs;
	}

	/**
	 * Adds toolparamdescription to a Fluent field.
	 *
	 * @param array<string, mixed> $data Field render data.
	 * @param mixed                $form Fluent form object.
	 * @return array<string, mixed>
	 */
	public static function annotate_field( array $data, $form ): array {
		$config = self::config_for_form( $form );
		if ( null === $config ) {
			return $data;
		}

		$name = '';
		if ( isset( $data['attributes'] ) && \is_array( $data['attributes'] ) && isset( $data['attributes']['name'] ) ) {
			$name = (string) $data['attributes']['name'];
		}

		if ( '' === $name || ! isset( $config['params'][ $name ] ) || '' === $config['params'][ $name ] ) {
			return $data;
		}

		if ( ! isset( $data['attributes'] ) || ! \is_array( $data['attributes'] ) ) {
			$data['attributes'] = array();
		}

		$data['attributes']['toolparamdescription'] = $config['params'][ $name ];

		return $data;
	}

	/**
	 * Config for a Fluent form object, if enabled.
	 *
	 * @param mixed $form Fluent form object.
	 * @return array{enabled: bool, toolname: string, tooldescription: string, params: array<string, string>}|null
	 */
	private static function config_for_form( $form ): ?array {
		$form_id = 0;
		if ( \is_object( $form ) && isset( $form->id ) ) {
			$form_id = (int) $form->id;
		}

		if ( $form_id <= 0 || ! Registry::is_enabled( self::BUILDER, $form_id ) ) {
			return null;
		}

		return Registry::get( self::BUILDER, $form_id );
	}

	/**
	 * Parses Fluent form_fields JSON into name => label.
	 *
	 * @param string $json Form fields JSON.
	 * @return array<string, string>
	 */
	private static function fields_from_json( string $json ): array {
		$decoded = \json_decode( $json, true );
		if ( ! \is_array( $decoded ) ) {
			return array();
		}

		$fields = array();
		if ( isset( $decoded['fields'] ) && \is_array( $decoded['fields'] ) ) {
			$fields = $decoded['fields'];
		}

		return self::collect_fields( $fields );
	}

	/**
	 * Walks nested Fluent field arrays.
	 *
	 * @param array<int|string, mixed> $fields Field nodes.
	 * @return array<string, string>
	 */
	private static function collect_fields( array $fields ): array {
		$out = array();

		foreach ( $fields as $field ) {
			if ( ! \is_array( $field ) ) {
				continue;
			}

			if ( isset( $field['columns'] ) && \is_array( $field['columns'] ) ) {
				foreach ( $field['columns'] as $column ) {
					if ( \is_array( $column ) && isset( $column['fields'] ) && \is_array( $column['fields'] ) ) {
						$out = \array_merge( $out, self::collect_fields( $column['fields'] ) );
					}
				}
				continue;
			}

			$element = isset( $field['element'] ) ? (string) $field['element'] : '';
			if ( isset( self::SKIP_ELEMENTS[ $element ] ) ) {
				continue;
			}

			$name = '';
			if ( isset( $field['attributes'] ) && \is_array( $field['attributes'] ) && isset( $field['attributes']['name'] ) ) {
				$name = (string) $field['attributes']['name'];
			}
			if ( '' === $name ) {
				continue;
			}

			$label = $name;
			if ( isset( $field['settings'] ) && \is_array( $field['settings'] ) ) {
				if ( isset( $field['settings']['label'] ) && \is_string( $field['settings']['label'] ) && '' !== $field['settings']['label'] ) {
					$label = $field['settings']['label'];
				} elseif ( isset( $field['settings']['admin_field_label'] ) && \is_string( $field['settings']['admin_field_label'] ) ) {
					$label = $field['settings']['admin_field_label'];
				}
			}

			$out[ $name ] = \wp_strip_all_tags( $label );
		}

		return $out;
	}
}
