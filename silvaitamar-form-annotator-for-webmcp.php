<?php
/**
 * Plugin Name:       Form Annotator for WebMCP
 * Plugin URI:        https://github.com/silvaitamar/wp-form-annotator-for-webmcp
 * Description:       Annotate WordPress forms with declarative WebMCP attributes so browser AI agents can fill lead and support forms reliably.
 * Version:           1.0.1
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Itamar Silva
 * Author URI:        https://github.com/silvaitamar
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       silvaitamar-form-annotator-for-webmcp
 * Domain Path:       /languages
 *
 * @package Siwmfa
 */

defined( 'ABSPATH' ) || exit;

define( 'SIWMFA_VERSION', '1.0.1' );
define( 'SIWMFA_PLUGIN_FILE', __FILE__ );
define( 'SIWMFA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIWMFA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SIWMFA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$siwmfa_autoloader = SIWMFA_PLUGIN_DIR . 'vendor/autoload.php';

if ( is_readable( $siwmfa_autoloader ) ) {
	require_once $siwmfa_autoloader;
} else {
	spl_autoload_register(
		static function ( $class_name ) {
			$prefix   = 'Siwmfa\\';
			$base_dir = SIWMFA_PLUGIN_DIR . 'src/';

			if ( 0 !== strpos( $class_name, $prefix ) ) {
				return;
			}

			$relative = substr( $class_name, strlen( $prefix ) );
			$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

\Siwmfa\Plugin::init();
