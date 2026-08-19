<?php
/**
 * Settings screen: form list, editor, Origin Trial.
 *
 * @package Siwmfa
 */

namespace Siwmfa\Admin;

use Siwmfa\Form_Catalog;
use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Settings → Form Annotator.
 */
final class Settings {

	public const OPTION_KEY = 'siwmfa_settings';
	public const PAGE_SLUG  = 'siwmfa-settings';

	/**
	 * Hooks the settings screen.
	 *
	 * @return void
	 */
	public static function register(): void {
		\add_action( 'admin_menu', array( self::class, 'add_menu' ) );
		\add_action( 'admin_init', array( self::class, 'register_settings' ) );
		\add_action( 'admin_init', array( self::class, 'handle_form_save' ) );
		\add_action( 'admin_init', array( self::class, 'handle_bulk' ) );
		\add_action( 'admin_init', array( self::class, 'handle_toggle' ) );
		\add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/**
	 * Adds the settings submenu.
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		\add_options_page(
			\__( 'Form Annotator for WebMCP', 'silvaitamar-form-annotator-for-webmcp' ),
			\__( 'Form Annotator', 'silvaitamar-form-annotator-for-webmcp' ),
			'manage_options',
			self::PAGE_SLUG,
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Enqueues admin CSS on this plugin’s screen.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public static function enqueue( string $hook ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		\wp_enqueue_style(
			'siwmfa-admin',
			\SIWMFA_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			\SIWMFA_VERSION
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
				'show_in_rest'      => false,
				'default'           => array(
					'origin_trial_token' => '',
				),
			)
		);
	}

	/**
	 * Stored Origin Trial token, or empty.
	 *
	 * @return string
	 */
	public static function origin_trial_token(): string {
		$settings = \get_option( self::OPTION_KEY, array() );
		if ( ! \is_array( $settings ) || ! isset( $settings['origin_trial_token'] ) || ! \is_string( $settings['origin_trial_token'] ) ) {
			return '';
		}

		return \trim( $settings['origin_trial_token'] );
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
	 * Saves annotations for one form.
	 *
	 * @return void
	 */
	public static function handle_form_save(): void {
		if ( ! isset( $_POST['siwmfa_save_form'] ) ) {
			return;
		}

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Sorry, you are not allowed to manage these settings.', 'silvaitamar-form-annotator-for-webmcp' ) );
		}

		\check_admin_referer( 'siwmfa_save_form' );

		$key = isset( $_POST['siwmfa_form_key'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['siwmfa_form_key'] ) ) : '';
		$raw = array();
		if ( isset( $_POST['siwmfa_forms'] ) && \is_array( $_POST['siwmfa_forms'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Registry::save_one().
			$posted = \wp_unslash( $_POST['siwmfa_forms'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Registry::save_one().
			if ( \is_array( $posted ) && isset( $posted[ $key ] ) && \is_array( $posted[ $key ] ) ) {
				$raw = $posted[ $key ];
			}
		}

		if ( '' === $key || ! Registry::save_one( $key, $raw ) ) {
			return;
		}

		\wp_safe_redirect(
			\add_query_arg(
				array(
					'siwmfa_updated' => 'form',
					'siwmfa_form'    => $key,
					'siwmfa_tab'     => 'forms',
				),
				self::page_url()
			)
		);
		exit;
	}

	/**
	 * Bulk enable/disable.
	 *
	 * @return void
	 */
	public static function handle_bulk(): void {
		if ( ! isset( $_POST['siwmfa_bulk_apply'] ) ) {
			return;
		}

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Sorry, you are not allowed to manage these settings.', 'silvaitamar-form-annotator-for-webmcp' ) );
		}

		\check_admin_referer( 'siwmfa_bulk_forms' );

		$action = isset( $_POST['siwmfa_bulk'] ) ? \sanitize_key( \wp_unslash( (string) $_POST['siwmfa_bulk'] ) ) : '';
		if ( ! \in_array( $action, array( 'enable', 'disable' ), true ) ) {
			return;
		}

		$keys = array();
		if ( isset( $_POST['siwmfa_keys'] ) && \is_array( $_POST['siwmfa_keys'] ) ) {
			foreach ( \wp_unslash( $_POST['siwmfa_keys'] ) as $key ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per item.
				if ( \is_string( $key ) ) {
					$keys[] = \sanitize_text_field( $key );
				}
			}
		}

		$enable = 'enable' === $action;
		foreach ( $keys as $key ) {
			$parts = Registry::parse_key( $key );
			if ( null === $parts ) {
				continue;
			}
			$form  = Form_Catalog::find( $parts['builder'], $parts['id'] );
			$title = null !== $form ? $form['title'] : '';
			Registry::set_enabled( $key, $enable, $title );
		}

		\wp_safe_redirect( \add_query_arg( 'siwmfa_updated', 'bulk', self::page_url( array( 'siwmfa_tab' => 'forms' ) ) ) );
		exit;
	}

	/**
	 * Row-action enable/disable.
	 *
	 * @return void
	 */
	public static function handle_toggle(): void {
		if ( ! isset( $_GET['page'] ) || self::PAGE_SLUG !== \sanitize_key( \wp_unslash( (string) $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- page gate.
			return;
		}

		if ( ! isset( $_GET['siwmfa_toggle'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- checked below.
			return;
		}

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Sorry, you are not allowed to manage these settings.', 'silvaitamar-form-annotator-for-webmcp' ) );
		}

		\check_admin_referer( 'siwmfa_toggle' );

		$key = \sanitize_text_field( \wp_unslash( (string) $_GET['siwmfa_toggle'] ) );
		$on  = false;
		if ( isset( $_GET['siwmfa_on'] ) ) {
			$on = '1' === \sanitize_text_field( \wp_unslash( (string) $_GET['siwmfa_on'] ) );
		}
		$parts = Registry::parse_key( $key );
		if ( null === $parts ) {
			return;
		}

		$form  = Form_Catalog::find( $parts['builder'], $parts['id'] );
		$title = null !== $form ? $form['title'] : '';
		Registry::set_enabled( $key, $on, $title );

		\wp_safe_redirect( \add_query_arg( 'siwmfa_updated', 'toggle', self::page_url( array( 'siwmfa_tab' => 'forms' ) ) ) );
		exit;
	}

	/**
	 * Settings page URL.
	 *
	 * @param array<string, string> $args Extra query args.
	 * @return string
	 */
	public static function page_url( array $args = array() ): string {
		$args['page'] = self::PAGE_SLUG;
		return \add_query_arg( $args, \admin_url( 'options-general.php' ) );
	}

	/**
	 * Enable/disable row URL.
	 *
	 * @param string                                                                 $key     Registry key.
	 * @param bool                                                                   $enable  Target state.
	 * @param string                                                                 $title   Unused in URL; kept for callers.
	 * @param array{search?: string, builder?: string, status?: string, paged?: int} $filters List filters.
	 * @return string
	 */
	public static function toggle_url( string $key, bool $enable, string $title, array $filters = array() ): string {
		unset( $title );

		$args = array(
			'siwmfa_tab'    => 'forms',
			'siwmfa_toggle' => $key,
			'siwmfa_on'     => $enable ? '1' : '0',
		);
		if ( ! empty( $filters['search'] ) ) {
			$args['s'] = $filters['search'];
		}
		if ( ! empty( $filters['builder'] ) ) {
			$args['siwmfa_builder'] = $filters['builder'];
		}
		if ( ! empty( $filters['status'] ) ) {
			$args['siwmfa_status'] = $filters['status'];
		}

		return \wp_nonce_url( self::page_url( $args ), 'siwmfa_toggle' );
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

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- tab/editor routing.
		$tab = 'forms';
		if ( isset( $_GET['siwmfa_tab'] ) ) {
			$tab = \sanitize_key( \wp_unslash( (string) $_GET['siwmfa_tab'] ) );
		}
		if ( 'ot' !== $tab ) {
			$tab = 'forms';
		}

		$form_key = '';
		if ( isset( $_GET['siwmfa_form'] ) ) {
			$form_key = \sanitize_text_field( \wp_unslash( (string) $_GET['siwmfa_form'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap siwmfa-wrap">
			<h1><?php echo \esc_html( \get_admin_page_title() ); ?></h1>
			<?php self::notices(); ?>

			<?php if ( '' !== $form_key ) : ?>
				<?php Form_Editor::render( $form_key ); ?>
			<?php else : ?>
				<nav class="nav-tab-wrapper wp-clearfix">
					<a href="<?php echo \esc_url( self::page_url( array( 'siwmfa_tab' => 'forms' ) ) ); ?>" class="nav-tab<?php echo 'forms' === $tab ? ' nav-tab-active' : ''; ?>">
						<?php echo \esc_html__( 'Forms', 'silvaitamar-form-annotator-for-webmcp' ); ?>
					</a>
					<a href="<?php echo \esc_url( self::page_url( array( 'siwmfa_tab' => 'ot' ) ) ); ?>" class="nav-tab<?php echo 'ot' === $tab ? ' nav-tab-active' : ''; ?>">
						<?php echo \esc_html__( 'Origin Trial', 'silvaitamar-form-annotator-for-webmcp' ); ?>
					</a>
				</nav>

				<?php if ( 'ot' === $tab ) : ?>
					<?php self::render_origin_trial(); ?>
				<?php else : ?>
					<?php Form_List::render(); ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Success notices.
	 *
	 * @return void
	 */
	private static function notices(): void {
		if ( ! isset( $_GET['siwmfa_updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- notice flag.
			return;
		}

		$which = \sanitize_key( \wp_unslash( (string) $_GET['siwmfa_updated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- notice flag only.
		$map   = array(
			'form'   => \__( 'Annotations saved.', 'silvaitamar-form-annotator-for-webmcp' ),
			'bulk'   => \__( 'Selected forms updated.', 'silvaitamar-form-annotator-for-webmcp' ),
			'toggle' => \__( 'Form status updated.', 'silvaitamar-form-annotator-for-webmcp' ),
		);

		if ( isset( $map[ $which ] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . \esc_html( $map[ $which ] ) . '</p></div>';
		}
	}

	/**
	 * Origin Trial tab.
	 *
	 * @return void
	 */
	private static function render_origin_trial(): void {
		$token = self::origin_trial_token();
		?>
		<form method="post" action="options.php">
			<?php \settings_fields( 'siwmfa_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="siwmfa_origin_trial_token">
							<?php echo \esc_html__( 'Chrome Origin Trial token', 'silvaitamar-form-annotator-for-webmcp' ); ?>
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
							<?php echo \esc_html__( 'Optional. Leave empty when testing with chrome://flags/#enable-webmcp-testing.', 'silvaitamar-form-annotator-for-webmcp' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<?php \submit_button( \__( 'Save Origin Trial token', 'silvaitamar-form-annotator-for-webmcp' ) ); ?>
		</form>
		<?php
	}
}
