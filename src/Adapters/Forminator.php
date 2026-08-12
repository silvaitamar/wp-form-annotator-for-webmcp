<?php
/**
 * Declarative WebMCP adapter for Forminator.
 *
 * @package Siwmfa
 */

namespace Siwmfa\Adapters;

use Siwmfa\Annotator;
use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Soft-dep: loads only when Forminator is active.
 */
final class Forminator {

	public const BUILDER = 'forminator';

	/**
	 * Registers Forminator filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! self::is_available() ) {
			return;
		}

		\add_filter( 'forminator_render_form_markup', array( self::class, 'annotate_markup' ), 20, 6 );
	}

	/**
	 * Whether Forminator is active.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \class_exists( 'Forminator_API' );
	}

	/**
	 * Lists Forminator forms for the settings UI.
	 *
	 * @return list<array{builder: string, id: int, title: string, fields: array<string, string>}>
	 */
	public static function list_forms(): array {
		if ( ! self::is_available() ) {
			return array();
		}

		$forms = \Forminator_API::get_forms( null, 1, 100 );
		if ( ! \is_array( $forms ) ) {
			return array();
		}

		$out = array();
		foreach ( $forms as $form ) {
			$id    = 0;
			$title = '';
			if ( \is_object( $form ) ) {
				$id    = isset( $form->id ) ? (int) $form->id : 0;
				$title = isset( $form->name ) ? (string) $form->name : '';
				if ( '' === $title && isset( $form->settings['formName'] ) ) {
					$title = (string) $form->settings['formName'];
				}
			}
			if ( $id <= 0 ) {
				continue;
			}
			$out[] = array(
				'builder' => self::BUILDER,
				'id'      => $id,
				'title'   => '' !== $title ? $title : ( 'Form ' . $id ),
				'fields'  => self::list_fields( $id ),
			);
		}

		return $out;
	}

	/**
	 * Injects WebMCP attributes into Forminator markup.
	 *
	 * @param string               $html          Form HTML.
	 * @param array<int, mixed>    $form_fields   Fields (unused).
	 * @param string               $form_type     Form type.
	 * @param array<string, mixed> $form_settings Settings.
	 * @param mixed                $form_design   Design (unused).
	 * @param mixed                $render_id     Render id (unused).
	 * @return string
	 */
	public static function annotate_markup(
		string $html,
		$form_fields,
		string $form_type,
		$form_settings,
		$form_design,
		$render_id
	): string {
		unset( $form_fields, $form_type, $form_design, $render_id );

		$form_id = 0;
		if ( \is_array( $form_settings ) && isset( $form_settings['form_id'] ) ) {
			$form_id = (int) $form_settings['form_id'];
		}
		if ( $form_id <= 0 && \preg_match( '/\bdata-form-id=["\'](\d+)["\']/', $html, $m ) ) {
			$form_id = (int) $m[1];
		}
		if ( $form_id <= 0 && \preg_match( '/\bid=["\']forminator-module-(\d+)["\']/', $html, $m ) ) {
			$form_id = (int) $m[1];
		}

		if ( $form_id <= 0 || ! Registry::is_enabled( self::BUILDER, $form_id ) ) {
			return $html;
		}

		$config = Registry::get( self::BUILDER, $form_id );
		$html   = Annotator::inject_form_tag( $html, $config );

		return Annotator::inject_param_attrs( $html, $config['params'] );
	}

	/**
	 * Maps Forminator element ids to labels.
	 *
	 * @param int $form_id Form ID.
	 * @return array<string, string>
	 */
	private static function list_fields( int $form_id ): array {
		if ( ! \method_exists( 'Forminator_API', 'get_form_fields' ) ) {
			return array();
		}

		$fields = \Forminator_API::get_form_fields( $form_id );
		if ( ! \is_array( $fields ) ) {
			return array();
		}

		$skip = array( 'submit', 'captcha', 'html', 'page-break', 'section', 'hidden', 'honeypot', 'stripe', 'paypal' );
		$out  = array();

		foreach ( $fields as $field ) {
			$element_id = '';
			$type       = '';
			$label      = '';

			if ( \is_object( $field ) ) {
				$element_id = isset( $field->slug ) ? (string) $field->slug : '';
				if ( '' === $element_id && isset( $field->element_id ) ) {
					$element_id = (string) $field->element_id;
				}
				$type  = isset( $field->type ) ? (string) $field->type : '';
				$label = isset( $field->field_label ) ? (string) $field->field_label : '';
			} elseif ( \is_array( $field ) ) {
				$element_id = isset( $field['element_id'] ) ? (string) $field['element_id'] : '';
				$type       = isset( $field['type'] ) ? (string) $field['type'] : '';
				$label      = isset( $field['field_label'] ) ? (string) $field['field_label'] : '';
			}

			if ( '' === $element_id || \in_array( $type, $skip, true ) ) {
				continue;
			}

			$out[ $element_id ] = '' !== $label ? \wp_strip_all_tags( $label ) : $element_id;
		}

		return $out;
	}
}
