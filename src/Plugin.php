<?php
/**
 * Plugin bootstrap.
 *
 * @package Siwmfa
 */

namespace Siwmfa;

use Siwmfa\Adapters\Contact_Form_7;
use Siwmfa\Adapters\Filter_Everything;
use Siwmfa\Adapters\Fluent_Forms;
use Siwmfa\Adapters\Forminator;
use Siwmfa\Adapters\Jetpack_Forms;
use Siwmfa\Adapters\Ninja_Forms;
use Siwmfa\Adapters\Search_Filter;
use Siwmfa\Adapters\Search_Form;
use Siwmfa\Adapters\SureForms;
use Siwmfa\Adapters\WPForms;
use Siwmfa\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Inicializa hooks do plugin.
	 *
	 * @return void
	 */
	public static function init(): void {
		Settings::register();
		Origin_Trial::register();
		Annotator::register();
		\add_action( 'plugins_loaded', array( self::class, 'register_adapters' ), 20 );
	}

	/**
	 * Registers soft-dep builders after every plugin has loaded.
	 *
	 * @return void
	 */
	public static function register_adapters(): void {
		Contact_Form_7::register();
		Fluent_Forms::register();
		WPForms::register();
		Forminator::register();
		Ninja_Forms::register();
		SureForms::register();
		Jetpack_Forms::register();
		Search_Form::register();
		Filter_Everything::register();
		Search_Filter::register();
	}
}
