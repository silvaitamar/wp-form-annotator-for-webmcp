<?php
/**
 * Bootstrap do plugin.
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
use Siwmfa\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Classe principal do plugin.
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
	}
}
