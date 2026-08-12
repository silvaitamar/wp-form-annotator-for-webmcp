<?php
/**
 * Declarative WebMCP adapter for Contact Form 7.
 *
 * @package Siwmfa
 */

namespace Siwmfa\Adapters;

use Siwmfa\Annotator;
use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Soft-dep: loads only when CF7 is active.
 */
final class Contact_Form_7 {

	public const BUILDER = 'cf7';

	/**
	 * Registers CF7 filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! self::is_available() ) {
			return;
		}

		\add_filter( 'wpcf7_form_additional_atts', array( self::class, 'annotate_form' ) );
		\add_filter( 'wpcf7_form_elements', array( self::class, 'annotate_fields' ), 20 );
	}

	/**
	 * Whether Contact Form 7 is active.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return \class_exists( 'WPCF7_ContactForm' );
	}

	/**
	 * Lists CF7 forms for the settings UI.
	 *
	 * @return list<array{builder: string, id: int, title: string, fields: array<string, string>}>
	 */
	public static function list_forms(): array {
		if ( ! self::is_available() ) {
			return array();
		}

		$posts = \get_posts(
			array(
				'post_type'      => 'wpcf7_contact_form',
				'post_status'    => 'any',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
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
				'fields'  => self::list_fields( (int) $post->ID ),
			);
		}

		return $out;
	}

	/**
	 * Adds toolname and tooldescription to the form tag.
	 *
	 * @param array<string, mixed> $atts Existing form attributes.
	 * @return array<string, mixed>
	 */
	public static function annotate_form( array $atts ): array {
		$config = self::current_config();
		if ( null === $config ) {
			return $atts;
		}

		return \array_merge( $atts, Annotator::form_attributes( $config ) );
	}

	/**
	 * Injects toolparamdescription into CF7 field markup.
	 *
	 * @param string $html Inner form HTML.
	 * @return string
	 */
	public static function annotate_fields( string $html ): string {
		$config = self::current_config();
		if ( null === $config ) {
			return $html;
		}

		return Annotator::inject_param_attrs( $html, $config['params'] );
	}

	/**
	 * Config for the form currently being rendered, if enabled.
	 *
	 * @return array{enabled: bool, toolname: string, tooldescription: string, params: array<string, string>}|null
	 */
	private static function current_config(): ?array {
		if ( ! \function_exists( 'wpcf7_get_current_contact_form' ) ) {
			return null;
		}

		$current = \wpcf7_get_current_contact_form();
		if ( ! \is_object( $current ) || ! \method_exists( $current, 'id' ) ) {
			return null;
		}

		$id = (int) $current->id();
		if ( ! Registry::is_enabled( self::BUILDER, $id ) ) {
			return null;
		}

		return Registry::get( self::BUILDER, $id );
	}

	/**
	 * Maps CF7 tag names to labels.
	 *
	 * @param int $form_id Contact form post ID.
	 * @return array<string, string>
	 */
	private static function list_fields( int $form_id ): array {
		if ( ! \class_exists( 'WPCF7_ContactForm' ) ) {
			return array();
		}

		$form = \WPCF7_ContactForm::get_instance( $form_id );
		if ( ! \is_object( $form ) || ! \method_exists( $form, 'scan_form_tags' ) ) {
			return array();
		}

		$skip = array( 'submit', 'acceptance', 'recaptcha', 'hidden', 'honeypot', 'quiz' );
		$out  = array();

		foreach ( $form->scan_form_tags() as $tag ) {
			$name     = isset( $tag->name ) ? (string) $tag->name : '';
			$basetype = isset( $tag->basetype ) ? (string) $tag->basetype : '';
			if ( '' === $name || \in_array( $basetype, $skip, true ) ) {
				continue;
			}

			$label = $name;
			if ( isset( $tag->labels ) && \is_array( $tag->labels ) && isset( $tag->labels[0] ) ) {
				$label = (string) $tag->labels[0];
			}

			$out[ $name ] = \wp_strip_all_tags( $label );
		}

		return $out;
	}
}
