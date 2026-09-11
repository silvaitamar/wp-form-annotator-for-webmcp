<?php
/**
 * Declarative WebMCP adapter for Filter Everything (wp.org free).
 *
 * @package Siwmfa
 */

namespace Siwmfa\Adapters;

use Siwmfa\Annotator;
use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Soft-dep: Filter Everything filter sets (`filter-set` CPT).
 *
 * Free Filter Everything is mostly link/AJAX facets. WebMCP annotates real
 * GET `<form>` nodes inside a set (search field, range, date, sorting).
 * FacetWP / SearchWP / Woo-only filters stay out of v1.1.
 */
final class Filter_Everything {

	public const BUILDER = 'filtereverything';

	/**
	 * Registers shortcode / widget / content hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! self::is_available() ) {
			return;
		}

		// Prefer `[fe_widget]` / post content. Classic sidebar widgets have early
		// returns before `wpc_after_filters_widget`, so output buffering is unsafe.
		\add_filter( 'do_shortcode_tag', array( self::class, 'annotate_shortcode' ), 20, 2 );
		\add_filter( 'the_content', array( self::class, 'annotate_html' ), 25 );
	}

	/**
	 * Whether Filter Everything is active.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \defined( 'FLRT_FILTERS_SET_POST_TYPE' ) || \post_type_exists( 'filter-set' );
	}

	/**
	 * Lists Filter Everything filter sets.
	 *
	 * @return list<array{builder: string, id: int, title: string, fields: array<string, string>}>
	 */
	public static function list_forms(): array {
		if ( ! self::is_available() ) {
			return array();
		}

		$post_type = \defined( 'FLRT_FILTERS_SET_POST_TYPE' ) ? \FLRT_FILTERS_SET_POST_TYPE : 'filter-set';
		$posts     = \get_posts(
			array(
				'post_type'              => $post_type,
				'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
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
		foreach ( $posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$out[] = array(
				'builder' => self::BUILDER,
				'id'      => (int) $post->ID,
				'title'   => '' !== $post->post_title
					? $post->post_title
					: \__( '(untitled Filter Everything set)', 'silvaitamar-form-annotator-for-webmcp' ),
				'fields'  => array(
					'srch' => \__( 'Full search phrase in one shot (do not split into multiple tool calls)', 'silvaitamar-form-annotator-for-webmcp' ),
				),
			);
		}

		return $out;
	}

	/**
	 * Annotates `[fe_widget]` output.
	 *
	 * @param string $output Shortcode HTML.
	 * @param string $tag    Shortcode tag.
	 * @return string
	 */
	public static function annotate_shortcode( string $output, string $tag ): string {
		if ( 'fe_widget' !== $tag ) {
			return $output;
		}

		return self::annotate_html( $output );
	}

	/**
	 * Injects WebMCP attrs into Filter Everything GET forms for enabled sets.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function annotate_html( string $html ): string {
		if ( '' === $html || false === \stripos( $html, '<form' ) ) {
			return $html;
		}

		foreach ( self::list_forms() as $form ) {
			$id = (int) $form['id'];
			if ( ! Registry::is_enabled( self::BUILDER, $id ) ) {
				continue;
			}
			if ( ! self::markup_matches_set( $html, $id ) ) {
				continue;
			}

			$config = Registry::get( self::BUILDER, $id );
			$html   = self::annotate_set_fragment( $html, $id, $config );
		}

		return $html;
	}

	/**
	 * Whether markup belongs to a given filter set.
	 *
	 * @param string $html   Markup.
	 * @param int    $set_id Filter set post ID.
	 * @return bool
	 */
	private static function markup_matches_set( string $html, int $set_id ): bool {
		return null !== self::set_marker_offset( $html, $set_id );
	}

	/**
	 * Annotates the first GET form after this set's marker (not an earlier form on the page).
	 *
	 * @param string               $html   Markup.
	 * @param int                  $set_id Filter set post ID.
	 * @param array<string, mixed> $config Registry config.
	 * @return string
	 */
	private static function annotate_set_fragment( string $html, int $set_id, array $config ): string {
		$offset = self::set_marker_offset( $html, $set_id );
		if ( null === $offset ) {
			return $html;
		}

		$before = \substr( $html, 0, $offset );
		$after  = \substr( $html, $offset );
		$after  = Annotator::inject_form_tag( $after, $config );
		if ( array() !== $config['params'] ) {
			$after = Annotator::inject_param_attrs( $after, $config['params'] );
		}

		return $before . $after;
	}

	/**
	 * Byte offset of the first Filter Everything set marker, or null.
	 *
	 * @param string $html   Markup.
	 * @param int    $set_id Filter set post ID.
	 * @return int|null
	 */
	private static function set_marker_offset( string $html, int $set_id ): ?int {
		$needles = array(
			'wpc-filter-set-' . $set_id,
			'data-set="' . $set_id . '"',
			"data-set='" . $set_id . "'",
		);

		$found = null;
		foreach ( $needles as $needle ) {
			$pos = \stripos( $html, $needle );
			if ( false === $pos ) {
				continue;
			}
			if ( null === $found || $pos < $found ) {
				$found = $pos;
			}
		}

		return $found;
	}
}
