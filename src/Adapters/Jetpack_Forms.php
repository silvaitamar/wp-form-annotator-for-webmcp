<?php
/**
 * Declarative WebMCP adapter for Jetpack Forms (synced CPT forms).
 *
 * @package Siwmfa
 */

namespace Siwmfa\Adapters;

use Siwmfa\Annotator;
use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Soft-dep: Jetpack contact-form module / jetpack-forms package.
 *
 * Only CPT `jetpack_form` (block/shortcode with `ref`) is supported in v1.1.
 * Inline page forms with computed IDs are out of scope.
 */
final class Jetpack_Forms {

	public const BUILDER = 'jetpack';

	/**
	 * Field block types that must not become WebMCP params.
	 *
	 * @var array<string, true>
	 */
	private const SKIP_BLOCKS = array(
		'jetpack/button'             => true,
		'jetpack/field-consent'      => true,
		'jetpack/field-hidden'       => true,
		'jetpack/field-file'         => true,
		'core/button'                => true,
		'jetpack/field-image-select' => true,
	);

	/**
	 * Registers Jetpack Forms filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! self::is_available() ) {
			return;
		}

		\add_filter( 'jetpack_contact_form_html', array( self::class, 'annotate_form_html' ), 20 );
		\add_filter( 'grunion_contact_form_field_html', array( self::class, 'annotate_field_html' ), 20, 3 );
	}

	/**
	 * Whether Jetpack Forms is available.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		if ( \post_type_exists( 'jetpack_form' ) ) {
			return true;
		}

		return \class_exists( '\Automattic\Jetpack\Forms\ContactForm\Contact_Form' );
	}

	/**
	 * Lists synced Jetpack forms for the settings UI.
	 *
	 * @return list<array{builder: string, id: int, title: string, fields: array<string, string>}>
	 */
	public static function list_forms(): array {
		if ( ! \post_type_exists( 'jetpack_form' ) ) {
			return array();
		}

		$posts = \get_posts(
			array(
				'post_type'              => 'jetpack_form',
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
				'title'   => '' !== $post->post_title ? $post->post_title : \__( '(untitled Jetpack form)', 'silvaitamar-form-annotator-for-webmcp' ),
				'fields'  => self::fields_from_content( (string) $post->post_content ),
			);
		}

		return $out;
	}

	/**
	 * Injects toolname / tooldescription into the form HTML.
	 *
	 * @param string $html Contact form HTML.
	 * @return string
	 */
	public static function annotate_form_html( string $html ): string {
		$config = self::current_config();
		if ( null === $config ) {
			return $html;
		}

		return Annotator::inject_form_tag( $html, $config );
	}

	/**
	 * Injects toolparamdescription into one field fragment.
	 *
	 * @param string   $html  Field HTML.
	 * @param string   $label Field label (unused).
	 * @param int|null $post  Post ID in the loop (unused).
	 * @return string
	 */
	public static function annotate_field_html( string $html, string $label, $post ): string {
		unset( $label, $post );

		$config = self::current_config();
		if ( null === $config || array() === $config['params'] ) {
			return $html;
		}

		return Annotator::inject_param_attrs( $html, $config['params'] );
	}

	/**
	 * Config for the synced form currently rendering, if enabled.
	 *
	 * @return array{enabled: bool, toolname: string, tooldescription: string, params: array<string, string>, toolautosubmit?: bool}|null
	 */
	private static function current_config(): ?array {
		$id = self::current_form_id();
		if ( $id <= 0 || ! Registry::is_enabled( self::BUILDER, $id ) ) {
			return null;
		}

		return Registry::get( self::BUILDER, $id );
	}

	/**
	 * Resolves the jetpack_form post ID for the render in progress.
	 *
	 * @return int
	 */
	private static function current_form_id(): int {
		if ( \class_exists( '\Automattic\Jetpack\Forms\ContactForm\Contact_Form' )
			&& \is_callable( array( '\Automattic\Jetpack\Forms\ContactForm\Contact_Form', 'get_ref_id' ) )
		) {
			$ref = \Automattic\Jetpack\Forms\ContactForm\Contact_Form::get_ref_id();
			if ( \is_int( $ref ) && $ref > 0 ) {
				return $ref;
			}
			if ( \is_numeric( $ref ) && (int) $ref > 0 ) {
				return (int) $ref;
			}
		}

		return 0;
	}

	/**
	 * Maps Jetpack field blocks to name => label.
	 *
	 * Prefers the block `id` attribute (HTML name). Falls back to a slug from the label.
	 *
	 * @param string $content Post content.
	 * @return array<string, string>
	 */
	private static function fields_from_content( string $content ): array {
		if ( '' === $content || ! \function_exists( 'parse_blocks' ) ) {
			return array();
		}

		$out = array();
		self::walk_blocks( \parse_blocks( $content ), $out );
		return $out;
	}

	/**
	 * Recursively collects field blocks.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @param array<string, string>            $out    Accumulator.
	 * @return void
	 */
	private static function walk_blocks( array $blocks, array &$out ): void {
		foreach ( $blocks as $block ) {
			if ( ! \is_array( $block ) ) {
				continue;
			}

			$name = isset( $block['blockName'] ) && \is_string( $block['blockName'] ) ? $block['blockName'] : '';
			if ( '' !== $name && isset( self::SKIP_BLOCKS[ $name ] ) ) {
				continue;
			}

			if ( '' !== $name && 0 === \strpos( $name, 'jetpack/field-' ) ) {
				$attrs = isset( $block['attrs'] ) && \is_array( $block['attrs'] ) ? $block['attrs'] : array();
				$label = '';
				if ( isset( $attrs['label'] ) && \is_string( $attrs['label'] ) ) {
					$label = \wp_strip_all_tags( $attrs['label'] );
				}
				if ( '' === $label ) {
					$label = self::label_from_inner_blocks( isset( $block['innerBlocks'] ) && \is_array( $block['innerBlocks'] ) ? $block['innerBlocks'] : array() );
				}
				if ( '' === $label ) {
					$label = \str_replace( 'jetpack/field-', '', $name );
				}

				$field_name = '';
				if ( isset( $attrs['id'] ) && \is_string( $attrs['id'] ) && '' !== $attrs['id'] ) {
					$field_name = \sanitize_key( $attrs['id'] );
				}
				if ( '' === $field_name ) {
					$field_name = \sanitize_key( $label );
				}
				if ( '' === $field_name ) {
					$field_name = \sanitize_key( \str_replace( 'jetpack/field-', '', $name ) );
				}
				if ( '' !== $field_name && ! isset( $out[ $field_name ] ) ) {
					$out[ $field_name ] = $label;
				}
			}

			if ( isset( $block['innerBlocks'] ) && \is_array( $block['innerBlocks'] ) ) {
				self::walk_blocks( $block['innerBlocks'], $out );
			}
		}
	}

	/**
	 * Reads label text from a nested jetpack/label block.
	 *
	 * @param array<int, array<string, mixed>> $inner Inner blocks.
	 * @return string
	 */
	private static function label_from_inner_blocks( array $inner ): string {
		foreach ( $inner as $block ) {
			if ( ! \is_array( $block ) ) {
				continue;
			}
			$name = isset( $block['blockName'] ) && \is_string( $block['blockName'] ) ? $block['blockName'] : '';
			if ( 'jetpack/label' !== $name ) {
				continue;
			}
			$attrs = isset( $block['attrs'] ) && \is_array( $block['attrs'] ) ? $block['attrs'] : array();
			if ( isset( $attrs['label'] ) && \is_string( $attrs['label'] ) && '' !== $attrs['label'] ) {
				return \wp_strip_all_tags( $attrs['label'] );
			}
			if ( isset( $attrs['defaultLabel'] ) && \is_string( $attrs['defaultLabel'] ) && '' !== $attrs['defaultLabel'] ) {
				return \wp_strip_all_tags( $attrs['defaultLabel'] );
			}
		}

		return '';
	}
}
