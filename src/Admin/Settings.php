<?php
/**
 * Settings screen: Origin Trial token + per-form opt-in.
 *
 * @package Siwmfa
 */

namespace Siwmfa\Admin;

use Siwmfa\Form_Catalog;
use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Settings → WebMCP Forms.
 */
final class Settings {

	public const OPTION_KEY = 'siwmfa_settings';

	/**
	 * Hooks the settings screen.
	 *
	 * @return void
	 */
	public static function register(): void {
		\add_action( 'admin_menu', array( self::class, 'add_menu' ) );
		\add_action( 'admin_init', array( self::class, 'register_settings' ) );
		\add_action( 'admin_init', array( self::class, 'handle_forms_save' ) );
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
	 * Registers the Origin Trial option.
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
	 * Sanitizes global plugin settings.
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
	 * Saves per-form annotation rows.
	 *
	 * @return void
	 */
	public static function handle_forms_save(): void {
		if ( ! isset( $_POST['siwmfa_save_forms'] ) ) {
			return;
		}

		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		\check_admin_referer( 'siwmfa_save_forms' );

		$raw = array();
		if ( isset( $_POST['siwmfa_forms'] ) && \is_array( $_POST['siwmfa_forms'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Registry::save_all().
			$raw = \wp_unslash( $_POST['siwmfa_forms'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Registry::save_all().
			if ( ! \is_array( $raw ) ) {
				$raw = array();
			}
		}

		Registry::save_all( $raw );

		\wp_safe_redirect(
			\add_query_arg(
				array(
					'page'           => 'siwmfa-settings',
					'siwmfa_updated' => '1',
				),
				\admin_url( 'options-general.php' )
			)
		);
		exit;
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

		$forms = Form_Catalog::discover();
		?>
		<div class="wrap">
			<h1><?php echo \esc_html( \get_admin_page_title() ); ?></h1>
			<?php if ( isset( $_GET['siwmfa_updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php echo \esc_html__( 'Form annotations saved.', 'silvaitamar-webmcp-form-annotator' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php \settings_fields( 'siwmfa_settings_group' ); ?>
				<h2><?php echo \esc_html__( 'Origin Trial', 'silvaitamar-webmcp-form-annotator' ); ?></h2>
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
								<?php echo \esc_html__( 'Optional. Leave empty when testing with chrome://flags/#enable-webmcp-testing.', 'silvaitamar-webmcp-form-annotator' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php \submit_button( \__( 'Save Origin Trial token', 'silvaitamar-webmcp-form-annotator' ) ); ?>
			</form>

			<hr />

			<h2><?php echo \esc_html__( 'Forms', 'silvaitamar-webmcp-form-annotator' ); ?></h2>
			<p>
				<?php echo \esc_html__( 'Enable a form to inject declarative WebMCP attributes. Lead and support forms never auto-submit — the visitor confirms.', 'silvaitamar-webmcp-form-annotator' ); ?>
			</p>

			<?php if ( array() === $forms ) : ?>
				<p>
					<?php echo \esc_html__( 'No supported forms found. Install and create a form in Contact Form 7, Fluent Forms, WPForms, Forminator, Ninja Forms, or SureForms, then return here.', 'silvaitamar-webmcp-form-annotator' ); ?>
				</p>
			<?php else : ?>
				<form method="post" action="<?php echo \esc_url( \admin_url( 'options-general.php?page=siwmfa-settings' ) ); ?>">
					<?php \wp_nonce_field( 'siwmfa_save_forms' ); ?>
					<?php foreach ( $forms as $form ) : ?>
						<?php
						$key      = Registry::make_key( $form['builder'], $form['id'] );
						$config   = Registry::get( $form['builder'], $form['id'] );
						$toolname = '' !== $config['toolname'] ? $config['toolname'] : Registry::suggest_toolname( $form['title'] );
						$desc     = '' !== $config['tooldescription'] ? $config['tooldescription'] : Registry::suggest_description( $form['title'] );
						?>
						<div class="siwmfa-form-card" style="max-width:52rem;margin:0 0 1.5rem;padding:1rem 1.25rem;border:1px solid #c3c4c7;background:#fff;">
							<h3>
								<label>
									<input type="checkbox" name="<?php echo \esc_attr( 'siwmfa_forms[' . $key . '][enabled]' ); ?>" value="1" <?php \checked( $config['enabled'] ); ?> />
									<?php echo \esc_html( $form['title'] ); ?>
								</label>
								<span class="description"> — <?php echo \esc_html( Form_Catalog::builder_label( $form['builder'] ) ); ?> #<?php echo \esc_html( (string) $form['id'] ); ?></span>
							</h3>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><label for="<?php echo \esc_attr( 'siwmfa_toolname_' . $key ); ?>"><?php echo \esc_html__( 'Tool name', 'silvaitamar-webmcp-form-annotator' ); ?></label></th>
									<td>
										<input class="regular-text" id="<?php echo \esc_attr( 'siwmfa_toolname_' . $key ); ?>" name="<?php echo \esc_attr( 'siwmfa_forms[' . $key . '][toolname]' ); ?>" value="<?php echo \esc_attr( $toolname ); ?>" pattern="[a-z0-9_]+" />
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="<?php echo \esc_attr( 'siwmfa_desc_' . $key ); ?>"><?php echo \esc_html__( 'Tool description', 'silvaitamar-webmcp-form-annotator' ); ?></label></th>
									<td>
										<textarea class="large-text" rows="3" id="<?php echo \esc_attr( 'siwmfa_desc_' . $key ); ?>" name="<?php echo \esc_attr( 'siwmfa_forms[' . $key . '][tooldescription]' ); ?>"><?php echo \esc_textarea( $desc ); ?></textarea>
									</td>
								</tr>
							</table>
							<?php if ( array() !== $form['fields'] ) : ?>
								<h4><?php echo \esc_html__( 'Field descriptions', 'silvaitamar-webmcp-form-annotator' ); ?></h4>
								<table class="widefat striped" style="max-width:48rem;">
									<thead>
										<tr>
											<th><?php echo \esc_html__( 'Field', 'silvaitamar-webmcp-form-annotator' ); ?></th>
											<th><?php echo \esc_html__( 'toolparamdescription', 'silvaitamar-webmcp-form-annotator' ); ?></th>
										</tr>
									</thead>
									<tbody>
									<?php foreach ( $form['fields'] as $field_name => $field_label ) : ?>
										<?php
										$param = $config['params'][ $field_name ] ?? $field_label;
										?>
										<tr>
											<td><code><?php echo \esc_html( $field_name ); ?></code></td>
											<td>
												<input class="large-text" name="<?php echo \esc_attr( 'siwmfa_forms[' . $key . '][params][' . $field_name . ']' ); ?>" value="<?php echo \esc_attr( $param ); ?>" />
											</td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<?php \submit_button( \__( 'Save form annotations', 'silvaitamar-webmcp-form-annotator' ), 'primary', 'siwmfa_save_forms' ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}
}
