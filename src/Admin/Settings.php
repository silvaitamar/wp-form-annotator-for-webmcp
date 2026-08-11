<?php
/**
 * Settings screen (scaffold — Phase A).
 *
 * @package Siwmfa
 */

namespace Siwmfa\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers a minimal settings page under Settings.
 */
final class Settings {

	/**
	 * Option key for plugin settings.
	 */
	public const OPTION_KEY = 'siwmfa_settings';

	/**
	 * Hook registrations.
	 *
	 * @return void
	 */
	public static function register(): void {
		\add_action( 'admin_menu', array( self::class, 'add_menu' ) );
		\add_action( 'admin_init', array( self::class, 'register_settings' ) );
	}

	/**
	 * Adds the settings submenu.
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		\add_options_page(
			\__( 'WebMCP Form Annotator', 'silvaitamar-webmcp-form-annotator' ),
			\__( 'WebMCP Forms', 'silvaitamar-webmcp-form-annotator' ),
			'manage_options',
			'siwmfa-settings',
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Registers the settings option.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		\register_setting(
			'siwmfa_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_settings' ),
				'default'           => array(
					'origin_trial_token' => '',
				),
			)
		);
	}

	/**
	 * Sanitizes settings before save.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, string>
	 */
	public static function sanitize_settings( $input ): array {
		$clean = array(
			'origin_trial_token' => '',
		);

		if ( ! \is_array( $input ) ) {
			return $clean;
		}

		if ( isset( $input['origin_trial_token'] ) && \is_string( $input['origin_trial_token'] ) ) {
			$clean['origin_trial_token'] = \sanitize_text_field( $input['origin_trial_token'] );
		}

		return $clean;
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = \get_option( self::OPTION_KEY, array() );
		$token    = '';

		if ( \is_array( $settings ) && isset( $settings['origin_trial_token'] ) && \is_string( $settings['origin_trial_token'] ) ) {
			$token = $settings['origin_trial_token'];
		}
		?>
		<div class="wrap">
			<h1><?php echo \esc_html( \get_admin_page_title() ); ?></h1>
			<p>
				<?php
				echo \esc_html__(
					'Scaffold (Phase A). Form adapters and per-form annotation arrive in the next development phase. Optional Origin Trial token can be stored now.',
					'silvaitamar-webmcp-form-annotator'
				);
				?>
			</p>
			<form method="post" action="options.php">
				<?php
				\settings_fields( 'siwmfa_settings_group' );
				?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="siwmfa_origin_trial_token">
								<?php echo \esc_html__( 'Chrome Origin Trial token', 'silvaitamar-webmcp-form-annotator' ); ?>
							</label>
						</th>
						<td>
							<input
								type="text"
								class="large-text"
								id="siwmfa_origin_trial_token"
								name="<?php echo \esc_attr( self::OPTION_KEY ); ?>[origin_trial_token]"
								value="<?php echo \esc_attr( $token ); ?>"
								autocomplete="off"
							/>
							<p class="description">
								<?php
								echo \esc_html__(
									'Optional. Used only while WebMCP is behind an Origin Trial. Leave empty when testing with chrome://flags.',
									'silvaitamar-webmcp-form-annotator'
								);
								?>
							</p>
						</td>
					</tr>
				</table>
				<?php \submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
