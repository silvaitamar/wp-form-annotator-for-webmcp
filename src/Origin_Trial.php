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
		$settings = \get_option( Settings::OPTION_KEY, array() );
		$token    = '';

		if ( \is_array( $settings ) && isset( $settings['origin_trial_token'] ) && \is_string( $settings['origin_trial_token'] ) ) {
			$token = \trim( $settings['origin_trial_token'] );
		}

		if ( '' === $token ) {
			return;
		}

		echo '<meta http-equiv="origin-trial" content="' . \esc_attr( $token ) . '" />' . "\n";
	}
}
