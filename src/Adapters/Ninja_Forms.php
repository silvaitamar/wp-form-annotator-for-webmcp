<?php
/**
 * Declarative WebMCP adapter for Ninja Forms.
 *
 * Ninja renders the <form> via Backbone. Annotation runs after nfFormReady.
 *
 * @package Siwmfa
 */

namespace Siwmfa\Adapters;

use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Soft-dep: loads only when Ninja Forms is active.
 */
final class Ninja_Forms {

	public const BUILDER = 'ninja';

	/**
	 * Registers Ninja Forms hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! self::is_available() ) {
			return;
		}

		\add_filter( 'ninja_forms_field_template_file_paths', array( self::class, 'template_paths' ) );
		\add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ), 30 );
	}

	/**
	 * Whether Ninja Forms is active.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \class_exists( 'Ninja_Forms' );
	}

	/**
	 * Lists Ninja forms for the settings UI.
	 *
	 * @return list<array{builder: string, id: int, title: string, fields: array<string, string>}>
	 */
	public static function list_forms(): array {
		if ( ! self::is_available() || ! \function_exists( 'Ninja_Forms' ) ) {
			return array();
		}

		try {
			$forms = \Ninja_Forms()->form()->get_forms();
		} catch ( \Throwable $exception ) {
			unset( $exception );
			return array();
		}

		if ( ! \is_array( $forms ) ) {
			return array();
		}

		$out = array();
		foreach ( $forms as $form ) {
			if ( ! \is_object( $form ) || ! \method_exists( $form, 'get_id' ) ) {
				continue;
			}
			$id    = (int) $form->get_id();
			$title = '';
			if ( \method_exists( $form, 'get_setting' ) ) {
				$title = (string) $form->get_setting( 'title' );
			}
			if ( $id <= 0 ) {
				continue;
			}
			$out[] = array(
				'builder' => self::BUILDER,
				'id'      => $id,
				'title'   => '' !== $title ? $title : \sprintf(
					/* translators: %d: form ID */
					\__( 'Form %d', 'silvaitamar-form-annotator-for-webmcp' ),
					$id
				),
				'fields'  => self::list_fields( $id ),
			);
		}

		return $out;
	}

	/**
	 * Prioritizes templates that honor custom_name_attribute on select/textarea.
	 *
	 * @param array<int, string> $paths Template directories.
	 * @return array<int, string>
	 */
	public static function template_paths( array $paths ): array {
		\array_unshift( $paths, \SIWMFA_PLUGIN_DIR . 'templates/ninja/' );
		return $paths;
	}

	/**
	 * Enqueues post-render annotation for enabled Ninja forms.
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		$configs = array();

		foreach ( Registry::get_all() as $key => $row ) {
			unset( $row );
			$parts = Registry::parse_key( $key );
			if ( null === $parts || self::BUILDER !== $parts['builder'] ) {
				continue;
			}
			if ( ! Registry::is_enabled( self::BUILDER, $parts['id'] ) ) {
				continue;
			}
			$config                           = Registry::get( self::BUILDER, $parts['id'] );
			$configs[ (string) $parts['id'] ] = array(
				'toolname'        => $config['toolname'],
				'tooldescription' => $config['tooldescription'],
				'params'          => $config['params'],
			);
		}

		if ( array() === $configs ) {
			return;
		}

		\wp_enqueue_script(
			'siwmfa-ninja-annotate',
			\SIWMFA_PLUGIN_URL . 'assets/js/ninja-annotate.js',
			array( 'jquery' ),
			\SIWMFA_VERSION,
			true
		);

		$json = \wp_json_encode( $configs );
		if ( ! \is_string( $json ) ) {
			return;
		}

		\wp_add_inline_script( 'siwmfa-ninja-annotate', 'window.siwmfaNinja = ' . $json . ';', 'before' );
	}

	/**
	 * Maps Ninja field HTML names to labels.
	 *
	 * @param int $form_id Form ID.
	 * @return array<string, string>
	 */
	private static function list_fields( int $form_id ): array {
		if ( ! \function_exists( 'Ninja_Forms' ) ) {
			return array();
		}

		try {
			$fields = \Ninja_Forms()->form( $form_id )->get_fields();
		} catch ( \Throwable $exception ) {
			unset( $exception );
			return array();
		}

		if ( ! \is_array( $fields ) ) {
			return array();
		}

		$skip = array( 'submit', 'html', 'hr', 'recaptcha', 'spam', 'hidden', 'honeypot', 'confirm' );
		$out  = array();

		foreach ( $fields as $field ) {
			if ( ! \is_object( $field ) || ! \method_exists( $field, 'get_id' ) || ! \method_exists( $field, 'get_setting' ) ) {
				continue;
			}

			$type = (string) $field->get_setting( 'type' );
			if ( \in_array( $type, $skip, true ) ) {
				continue;
			}

			$custom       = (string) $field->get_setting( 'custom_name_attribute' );
			$name         = '' !== $custom ? $custom : ( 'nf-field-' . (int) $field->get_id() );
			$label        = (string) $field->get_setting( 'label' );
			$out[ $name ] = '' !== $label ? \wp_strip_all_tags( $label ) : $name;
		}

		return $out;
	}
}
