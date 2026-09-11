<?php
/**
 * Declarative WebMCP adapter for Search & Filter (wp.org free).
 *
 * @package Siwmfa
 */

namespace Siwmfa\Adapters;

use Siwmfa\Annotator;
use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Soft-dep: Search & Filter shortcode forms (`[searchandfilter]`).
 *
 * One virtual registry row (`searchfilter:1`) covers shortcode instances.
 * Field names use the plugin prefix `of` (e.g. `ofsearch`, `ofcategory`).
 */
final class Search_Filter {

	public const BUILDER = 'searchfilter';

	/**
	 * Registry id for the primary Search & Filter surface.
	 */
	public const FORM_ID = 1;

	/**
	 * Registers shortcode output filter.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! self::is_available() ) {
			return;
		}

		\add_filter( 'do_shortcode_tag', array( self::class, 'annotate_shortcode' ), 20, 2 );
		\add_filter( 'the_content', array( self::class, 'annotate_html' ), 25 );
	}

	/**
	 * Whether Search & Filter is active.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \class_exists( 'SearchAndFilter' ) || \shortcode_exists( 'searchandfilter' );
	}

	/**
	 * Lists the virtual Search & Filter form.
	 *
	 * @return list<array{builder: string, id: int, title: string, fields: array<string, string>}>
	 */
	public static function list_forms(): array {
		if ( ! self::is_available() ) {
			return array();
		}

		$prefix = \defined( 'SF_FPRE' ) ? \SF_FPRE : 'of';

		return array(
			array(
				'builder' => self::BUILDER,
				'id'      => self::FORM_ID,
				'title'   => \__( 'Search & Filter', 'silvaitamar-form-annotator-for-webmcp' ),
				'fields'  => array(
					$prefix . 'search'   => \__( 'Full search phrase in one shot (do not split into multiple tool calls)', 'silvaitamar-form-annotator-for-webmcp' ),
					$prefix . 'category' => \__( 'Category term ID (0 = all)', 'silvaitamar-form-annotator-for-webmcp' ),
					$prefix . 'post_tag' => \__( 'Tag term ID (0 = all)', 'silvaitamar-form-annotator-for-webmcp' ),
				),
			),
		);
	}

	/**
	 * Annotates `[searchandfilter]` output.
	 *
	 * @param string $output Shortcode HTML.
	 * @param string $tag    Shortcode tag.
	 * @return string
	 */
	public static function annotate_shortcode( string $output, string $tag ): string {
		if ( 'searchandfilter' !== $tag ) {
			return $output;
		}

		return self::annotate_html( $output );
	}

	/**
	 * Injects WebMCP attrs into Search & Filter forms.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function annotate_html( string $html ): string {
		if ( '' === $html || ! Registry::is_enabled( self::BUILDER, self::FORM_ID ) ) {
			return $html;
		}

		if ( false === \stripos( $html, 'searchandfilter' ) || false === \stripos( $html, '<form' ) ) {
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
