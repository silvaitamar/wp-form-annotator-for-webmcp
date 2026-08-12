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
				return 'Contact Form 7';
			case Fluent_Forms::BUILDER:
				return 'Fluent Forms';
			case WPForms::BUILDER:
				return 'WPForms';
			case Forminator::BUILDER:
				return 'Forminator';
			case Ninja_Forms::BUILDER:
				return 'Ninja Forms';
			case SureForms::BUILDER:
				return 'SureForms';
			default:
				return $builder;
		}
	}
}
