<?php
/**
 * Bootstrap do plugin.
 *
 * @package Siwmfa
 */

namespace Siwmfa;

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
		if ( \is_admin() ) {
			Settings::register();
		}
	}
}
