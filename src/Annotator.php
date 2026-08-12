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
