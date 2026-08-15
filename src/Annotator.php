<?php
/**
 * HTML helpers for declarative WebMCP attributes.
 *
 * @package Siwmfa
 */

namespace Siwmfa;

defined( 'ABSPATH' ) || exit;

/**
 * Injects toolname / tooldescription / toolparamdescription.
 */
final class Annotator {

	/**
	 * Allows WebMCP attributes through wp_kses so later sanitization does not strip them.
	 *
	 * @return void
	 */
	public static function register(): void {
		\add_filter( 'wp_kses_allowed_html', array( self::class, 'allow_webmcp_attrs' ), 10, 2 );
	}

	/**
	 * Adds toolname / tooldescription / toolparamdescription to allowed HTML.
	 *
	 * @param array<string, mixed> $tags    Allowed tags.
	 * @param string|array<mixed>  $context Kses context.
	 * @return array<string, mixed>
	 */
	public static function allow_webmcp_attrs( array $tags, $context ): array {
		unset( $context );

		foreach ( array( 'form', 'input', 'select', 'textarea' ) as $tag ) {
			if ( ! isset( $tags[ $tag ] ) || ! \is_array( $tags[ $tag ] ) ) {
				continue;
			}
			if ( 'form' === $tag ) {
				$tags[ $tag ]['toolname']        = true;
				$tags[ $tag ]['tooldescription'] = true;
			}
			$tags[ $tag ]['toolparamdescription'] = true;
		}

		return $tags;
	}

	/**
	 * Builds toolname and tooldescription attributes for a form tag.
	 *
	 * @param array{toolname: string, tooldescription: string, params?: array<string, string>} $config Form config.
	 * @return array<string, string>
	 */
	public static function form_attributes( array $config ): array {
		$attrs = array();
		if ( isset( $config['toolname'] ) && \is_string( $config['toolname'] ) && '' !== $config['toolname'] ) {
			$attrs['toolname'] = $config['toolname'];
		}
		if ( isset( $config['tooldescription'] ) && \is_string( $config['tooldescription'] ) && '' !== $config['tooldescription'] ) {
			$attrs['tooldescription'] = $config['tooldescription'];
		}
		return $attrs;
	}

	/**
	 * Injects toolname and tooldescription into the first <form> tag.
	 *
	 * @param string                                                                           $html   Markup.
	 * @param array{toolname: string, tooldescription: string, params?: array<string, string>} $config Form config.
	 * @return string
	 */
	public static function inject_form_tag( string $html, array $config ): string {
		if ( false !== \stripos( $html, 'toolname=' ) ) {
			return $html;
		}

		$attrs = self::form_attributes( $config );
		if ( array() === $attrs ) {
			return $html;
		}

		$inject = '';
		foreach ( $attrs as $name => $value ) {
			$inject .= \sprintf( ' %s="%s"', $name, \esc_attr( $value ) );
		}

		return (string) \preg_replace( '/<form\b/i', '<form' . $inject, $html, 1 );
	}

	/**
	 * Injects toolname / tooldescription on the <form> whose id is `$form_html_id`.
	 *
	 * @param string                                                                           $html         Markup that may contain several forms.
	 * @param string                                                                           $form_html_id Value of the form id attribute.
	 * @param array{toolname: string, tooldescription: string, params?: array<string, string>} $config       Form config.
	 * @return string
	 */
	public static function inject_form_tag_by_id( string $html, string $form_html_id, array $config ): string {
		$attrs = self::form_attributes( $config );
		if ( array() === $attrs || '' === $form_html_id ) {
			return $html;
		}

		$inject = '';
		foreach ( $attrs as $name => $value ) {
			$inject .= \sprintf( ' %s="%s"', $name, \esc_attr( $value ) );
		}

		$quoted = \preg_quote( $form_html_id, '/' );

		$replaced = \preg_replace(
			'/<form\b(?![^>]*\btoolname=)([^>]*\bid=(["\'])' . $quoted . '\2)/is',
			'<form' . $inject . '$1',
			$html,
			1
		);

		return \is_string( $replaced ) ? $replaced : $html;
	}

	/**
	 * Adds toolparamdescription to inputs/selects/textareas matched by name.
	 *
	 * @param string                $html   Markup fragment.
	 * @param array<string, string> $params Field name => description.
	 * @return string
	 */
	public static function inject_param_attrs( string $html, array $params ): string {
		foreach ( $params as $name => $description ) {
			$name        = (string) $name;
			$description = (string) $description;
			if ( '' === $name || '' === $description ) {
				continue;
			}

			$quoted_name = \preg_quote( $name, '/' );
			$attr        = ' toolparamdescription="' . \esc_attr( $description ) . '"';

			$html = (string) \preg_replace_callback(
				'/(<(?:input|select|textarea)\b[^>]*\bname=(["\'])' . $quoted_name . '\2)([^>]*>)/i',
				static function ( array $m ) use ( $attr ): string {
					if ( false !== \stripos( $m[0], 'toolparamdescription=' ) ) {
						return $m[0];
					}
					if ( false !== \stripos( $m[0], 'type="hidden"' ) || false !== \stripos( $m[0], "type='hidden'" ) ) {
						return $m[0];
					}
					return $m[1] . $attr . $m[3];
				},
				$html
			);
		}

		return $html;
	}
}
