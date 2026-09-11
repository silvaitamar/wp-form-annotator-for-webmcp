<?php
/**
 * Declarative WebMCP adapter for the theme / core search form.
 *
 * @package Siwmfa
 */

namespace Siwmfa\Adapters;

use Siwmfa\Annotator;
use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Virtual builder for `<form role="search">` (classic + core/search block).
 *
 * Site search may set `toolautosubmit` (idempotent GET), same policy as Filter Everything / Search & Filter.
 */
final class Search_Form {

	public const BUILDER = 'search';

	/**
	 * Registry id for the primary site search form.
	 */
	public const FORM_ID = 1;

	/**
	 * Registers search-form filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		\add_filter( 'get_search_form', array( self::class, 'annotate_html' ), 20 );
		\add_filter( 'render_block_core/search', array( self::class, 'annotate_html' ), 20 );
	}

	/**
	 * Search is always available (core).
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return true;
	}

	/**
	 * Lists the virtual site search form.
	 *
	 * @return list<array{builder: string, id: int, title: string, fields: array<string, string>}>
	 */
	public static function list_forms(): array {
		return array(
			array(
				'builder' => self::BUILDER,
				'id'      => self::FORM_ID,
				'title'   => \__( 'Site search', 'silvaitamar-form-annotator-for-webmcp' ),
				'fields'  => array(
					's' => \__( 'Search query', 'silvaitamar-form-annotator-for-webmcp' ),
				),
			),
		);
	}

	/**
	 * Injects WebMCP attrs into search markup.
	 *
	 * @param string $html Search form HTML.
	 * @return string
	 */
	public static function annotate_html( string $html ): string {
		if ( '' === $html || ! Registry::is_enabled( self::BUILDER, self::FORM_ID ) ) {
			return $html;
		}

		$config = Registry::get( self::BUILDER, self::FORM_ID );
		$html   = Annotator::inject_form_tag( $html, $config );
		if ( array() !== $config['params'] ) {
			$html = Annotator::inject_param_attrs( $html, $config['params'] );
		}

		return $html;
	}
}
