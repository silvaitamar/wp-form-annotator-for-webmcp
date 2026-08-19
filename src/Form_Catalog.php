<?php
/**
 * Discovers annotatable forms from active builders.
 *
 * @package Siwmfa
 */

namespace Siwmfa;

use Siwmfa\Adapters\Contact_Form_7;
use Siwmfa\Adapters\Fluent_Forms;
use Siwmfa\Adapters\Forminator;
use Siwmfa\Adapters\Ninja_Forms;
use Siwmfa\Adapters\SureForms;
use Siwmfa\Adapters\WPForms;

defined( 'ABSPATH' ) || exit;

/**
 * Merges adapter inventories for the settings UI.
 */
final class Form_Catalog {

	/**
	 * Lists forms from every available adapter.
	 *
	 * @return list<array{builder: string, id: int, title: string, fields: array<string, string>}>
	 */
	public static function discover(): array {
		$forms = array();

		foreach ( self::adapters() as $adapter ) {
			if ( $adapter::is_available() ) {
				$forms = \array_merge( $forms, $adapter::list_forms() );
			}
		}

		return $forms;
	}

	/**
	 * Finds one discovered form.
	 *
	 * @param string $builder Builder slug.
	 * @param int    $id      Form ID.
	 * @return array{builder: string, id: int, title: string, fields: array<string, string>}|null
	 */
	public static function find( string $builder, int $id ): ?array {
		foreach ( self::discover() as $form ) {
			if ( $form['builder'] === $builder && $form['id'] === $id ) {
				return $form;
			}
		}

		return null;
	}

	/**
	 * Builder slugs present in a form list.
	 *
	 * @param list<array{builder: string, id: int, title: string, fields: array<string, string>}> $forms Forms.
	 * @return array<string, string> Slug => label.
	 */
	public static function builders_in( array $forms ): array {
		$out = array();
		foreach ( $forms as $form ) {
			$slug = $form['builder'];
			if ( ! isset( $out[ $slug ] ) ) {
				$out[ $slug ] = self::builder_label( $slug );
			}
		}

		return $out;
	}

	/**
	 * Adapter classes that can contribute forms.
	 *
	 * @return list<class-string>
	 */
	private static function adapters(): array {
		return array(
			Contact_Form_7::class,
			Fluent_Forms::class,
			WPForms::class,
			Forminator::class,
			Ninja_Forms::class,
			SureForms::class,
		);
	}

	/**
	 * Human-readable builder label.
	 *
	 * @param string $builder Builder slug.
	 * @return string
	 */
	public static function builder_label( string $builder ): string {
		switch ( $builder ) {
			case Contact_Form_7::BUILDER:
				return \__( 'Contact Form 7', 'silvaitamar-form-annotator-for-webmcp' );
			case Fluent_Forms::BUILDER:
				return \__( 'Fluent Forms', 'silvaitamar-form-annotator-for-webmcp' );
			case WPForms::BUILDER:
				return \__( 'WPForms', 'silvaitamar-form-annotator-for-webmcp' );
			case Forminator::BUILDER:
				return \__( 'Forminator', 'silvaitamar-form-annotator-for-webmcp' );
			case Ninja_Forms::BUILDER:
				return \__( 'Ninja Forms', 'silvaitamar-form-annotator-for-webmcp' );
			case SureForms::BUILDER:
				return \__( 'SureForms', 'silvaitamar-form-annotator-for-webmcp' );
			default:
				return $builder;
		}
	}
}
