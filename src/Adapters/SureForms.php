<?php
/**
 * Declarative WebMCP adapter for SureForms.
 *
 * @package Siwmfa
 */

namespace Siwmfa\Adapters;

use Siwmfa\Annotator;
use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Soft-dep: loads only when SureForms is active.
 */
final class SureForms {

	public const BUILDER = 'sureforms';

	/**
	 * Registers SureForms filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! self::is_available() ) {
			return;
		}

		\add_filter( 'do_shortcode_tag', array( self::class, 'annotate_shortcode' ), 20, 3 );
		\add_filter( 'render_block', array( self::class, 'annotate_embed_block' ), 20, 2 );
		\add_filter( 'render_block', array( self::class, 'annotate_field_block' ), 20, 2 );
		\add_filter( 'the_content', array( self::class, 'annotate_content' ), 20 );
	}

	/**
	 * Whether SureForms is active.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \defined( 'SRFM_VER' ) || \defined( 'SRFM_FORMS_POST_TYPE' );
	}

	/**
	 * Lists SureForms forms for the settings UI.
	 *
	 * @return list<array{builder: string, id: int, title: string, fields: array<string, string>}>
	 */
	public static function list_forms(): array {
		$post_type = \defined( 'SRFM_FORMS_POST_TYPE' ) ? \SRFM_FORMS_POST_TYPE : 'sureforms';
		if ( ! \post_type_exists( $post_type ) ) {
			return array();
		}

		$posts = \get_posts(
			array(
				'post_type'              => $post_type,
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
		foreach ( $posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$out[] = array(
				'builder' => self::BUILDER,
				'id'      => (int) $post->ID,
				'title'   => $post->post_title,
				'fields'  => self::list_fields( $post ),
			);
		}

		return $out;
	}

	/**
	 * Annotates SureForms shortcode output.
	 *
	 * @param string|false         $output Shortcode HTML.
	 * @param string               $tag    Shortcode tag.
	 * @param array<string, mixed> $attr   Shortcode attributes.
	 * @return string|false
	 */
	public static function annotate_shortcode( $output, string $tag, array $attr ) {
		if ( 'sureforms' !== $tag || ! \is_string( $output ) ) {
			return $output;
		}

		$id = isset( $attr['id'] ) ? (int) $attr['id'] : 0;
		if ( $id <= 0 || ! Registry::is_enabled( self::BUILDER, $id ) ) {
			return $output;
		}

		return self::annotate_form_html( $output, Registry::get( self::BUILDER, $id ) );
	}

	/**
	 * Annotates the wrapping <form> when the form is embedded as a Gutenberg block.
	 *
	 * SureForms prints the <form> in get_form_markup(); field blocks already went
	 * through render_block (params). The shortcode filter never sees this path.
	 *
	 * @param string               $block_content Block HTML.
	 * @param array<string, mixed> $block         Parsed block.
	 * @return string
	 */
	public static function annotate_embed_block( string $block_content, array $block ): string {
		$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
		if ( 'srfm/form' !== $name || '' === $block_content ) {
			return $block_content;
		}

		$attrs = isset( $block['attrs'] ) && \is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$id    = isset( $attrs['id'] ) ? (int) $attrs['id'] : 0;
		if ( $id <= 0 ) {
			$id = self::form_id_from_markup( $block_content );
		}

		if ( $id <= 0 || ! Registry::is_enabled( self::BUILDER, $id ) ) {
			return $block_content;
		}

		return self::annotate_form_html( $block_content, Registry::get( self::BUILDER, $id ) );
	}

	/**
	 * Last-resort annotation for embeds that skip both the shortcode and srfm/form block
	 * (classic the_content wrappers). Idempotent when toolname is already present.
	 *
	 * @param string $content Post content HTML.
	 * @return string
	 */
	public static function annotate_content( string $content ): string {
		if ( '' === $content || false === \stripos( $content, 'srfm-form-' ) ) {
			return $content;
		}

		if ( ! \preg_match_all( '/\bid=["\']srfm-form-(\d+)["\']/', $content, $matches ) ) {
			return $content;
		}

		$ids = \array_unique( \array_map( 'intval', $matches[1] ) );
		foreach ( $ids as $id ) {
			if ( $id <= 0 || ! Registry::is_enabled( self::BUILDER, $id ) ) {
				continue;
			}
			$content = self::annotate_form_html( $content, Registry::get( self::BUILDER, $id ) );
		}

		return $content;
	}

	/**
	 * Adds toolparamdescription to SureForms field blocks.
	 *
	 * @param string               $block_content Block HTML.
	 * @param array<string, mixed> $block         Parsed block.
	 * @return string
	 */
	public static function annotate_field_block( string $block_content, array $block ): string {
		$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
		if ( 0 !== \strpos( $name, 'srfm/' ) || '' === $block_content ) {
			return $block_content;
		}

		$attrs = isset( $block['attrs'] ) && \is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$slug  = isset( $attrs['slug'] ) ? (string) $attrs['slug'] : '';
		if ( '' === $slug ) {
			return $block_content;
		}

		$desc = self::description_for_slug( $slug );
		if ( '' === $desc ) {
			return $block_content;
		}

		$attr = ' toolparamdescription="' . \esc_attr( $desc ) . '"';

		return (string) \preg_replace_callback(
			'/(<(?:input|select|textarea)\b)([^>]*?)(\/?>)/i',
			static function ( array $m ) use ( $attr ): string {
				if ( false !== \stripos( $m[0], 'type="hidden"' ) || false !== \stripos( $m[0], "type='hidden'" ) ) {
					return $m[0];
				}
				if ( false !== \stripos( $m[0], 'toolparamdescription=' ) ) {
					return $m[0];
				}
				return $m[1] . $m[2] . $attr . $m[3];
			},
			$block_content
		);
	}

	/**
	 * Injects form attrs and strips hidden/Tom Select noise that duplicates schema.
	 *
	 * @param string                                                                                         $html   Markup.
	 * @param array{enabled: bool, toolname: string, tooldescription: string, params: array<string, string>} $config Form config.
	 * @return string
	 */
	public static function annotate_form_html( string $html, array $config ): string {
		$id = self::form_id_from_markup( $html );
		if ( $id > 0 ) {
			$html = Annotator::inject_form_tag_by_id( $html, 'srfm-form-' . $id, $config );
		} else {
			$html = Annotator::inject_form_tag( $html, $config );
		}

		$html = (string) \preg_replace(
			'/(<input\b[^>]*\btype=(["\'])hidden\2[^>]*)\s+toolparamdescription=(["\'])[^"\']*\3/i',
			'$1',
			$html
		);

		$html = (string) \preg_replace( '/\saria-hidden=(["\'])true\1/i', '', $html );
		$html = \str_replace( 'srfm-dropdown-common', 'srfm-dropdown-native', $html );
		$html = (string) \preg_replace(
			'/(<input\b[^>]*class="[^"]*srfm-input-dropdown-hidden[^"]*"[^>]*)\s+name=(["\'])[^"\']*\2/i',
			'$1',
			$html
		);

		return Annotator::inject_param_attrs( $html, $config['params'] );
	}

	/**
	 * Reads the SureForms form ID from markup.
	 *
	 * @param string $html Markup.
	 * @return int
	 */
	private static function form_id_from_markup( string $html ): int {
		if ( \preg_match( '/\bid=["\']srfm-form-(\d+)["\']/', $html, $m ) ) {
			return (int) $m[1];
		}
		if ( \preg_match( '/\bform-id=["\'](\d+)["\']/', $html, $m ) ) {
			return (int) $m[1];
		}

		return 0;
	}

	/**
	 * Finds a param description for a SureForms field slug.
	 *
	 * @param string $slug Field slug.
	 * @return string
	 */
	private static function description_for_slug( string $slug ): string {
		foreach ( Registry::get_all() as $key => $row ) {
			unset( $row );
			$parts = Registry::parse_key( $key );
			if ( null === $parts || self::BUILDER !== $parts['builder'] ) {
				continue;
			}
			if ( ! Registry::is_enabled( self::BUILDER, $parts['id'] ) ) {
				continue;
			}
			$config = Registry::get( self::BUILDER, $parts['id'] );
			if ( isset( $config['params'][ $slug ] ) && '' !== $config['params'][ $slug ] ) {
				return $config['params'][ $slug ];
			}
		}

		return '';
	}

	/**
	 * Maps SureForms block slugs to labels.
	 *
	 * @param \WP_Post $post Form post.
	 * @return array<string, string>
	 */
	private static function list_fields( \WP_Post $post ): array {
		$blocks = \parse_blocks( (string) $post->post_content );
		$out    = array();
		self::walk_blocks( $blocks, $out );
		return $out;
	}

	/**
	 * Recursively collects srfm/* field slugs.
	 *
	 * @param array<int, mixed>     $blocks Parsed blocks.
	 * @param array<string, string> $out    Accumulator.
	 * @return void
	 */
	private static function walk_blocks( array $blocks, array &$out ): void {
		foreach ( $blocks as $block ) {
			if ( ! \is_array( $block ) ) {
				continue;
			}
			$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
			if ( 0 === \strpos( $name, 'srfm/' ) && 'srfm/form' !== $name ) {
				$attrs = isset( $block['attrs'] ) && \is_array( $block['attrs'] ) ? $block['attrs'] : array();
				$slug  = isset( $attrs['slug'] ) ? (string) $attrs['slug'] : '';
				if ( '' !== $slug ) {
					$label        = isset( $attrs['label'] ) ? (string) $attrs['label'] : $slug;
					$out[ $slug ] = \wp_strip_all_tags( $label );
				}
			}
			if ( ! empty( $block['innerBlocks'] ) && \is_array( $block['innerBlocks'] ) ) {
				self::walk_blocks( $block['innerBlocks'], $out );
			}
		}
	}
}
