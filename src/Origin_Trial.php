<?php
/**
 * Optional Chrome Origin Trial meta tag.
 *
 * @package Siwmfa
 */

namespace Siwmfa;

use Siwmfa\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Prints origin-trial token when configured.
 */
final class Origin_Trial {

	/**
	 * Hooks wp_head.
	 *
	 * @return void
	 */
	public static function register(): void {
		\add_action( 'wp_head', array( self::class, 'print_meta' ), 1 );
	}

	/**
	 * Prints the origin-trial meta tag when a token is stored.
	 *
	 * @return void
	 */
	public static function print_meta(): void {
		$token = Settings::origin_trial_token();
		if ( '' === $token ) {
			return;
		}

		echo '<meta http-equiv="origin-trial" content="' . \esc_attr( $token ) . '" />' . "\n";
	}
}
