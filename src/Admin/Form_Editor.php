<?php
/**
 * Single-form annotation editor.
 *
 * @package Siwmfa
 */

namespace Siwmfa\Admin;

use Siwmfa\Form_Catalog;
use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Edit tool name, description, and field params for one form.
 */
final class Form_Editor {

	/**
	 * Renders the editor for a registry key.
	 *
	 * @param string $key Registry key (`builder:id`).
	 * @return void
	 */
	public static function render( string $key ): void {
		$parts = Registry::parse_key( $key );
		if ( null === $parts ) {
			echo '<div class="notice notice-error"><p>' . \esc_html__( 'Unknown form.', 'silvaitamar-webmcp-form-annotator' ) . '</p></div>';
			return;
		}

		$form = Form_Catalog::find( $parts['builder'], $parts['id'] );
		if ( null === $form ) {
			echo '<div class="notice notice-error"><p>' . \esc_html__( 'This form is no longer available. It may have been deleted in the form plugin.', 'silvaitamar-webmcp-form-annotator' ) . '</p></div>';
			return;
		}

		$config   = Registry::get( $parts['builder'], $parts['id'] );
		$toolname = '' !== $config['toolname'] ? $config['toolname'] : Registry::suggest_toolname( $form['title'] );
		$desc     = '' !== $config['tooldescription'] ? $config['tooldescription'] : Registry::suggest_description( $form['title'] );
		$back     = Settings::page_url( array( 'siwmfa_tab' => 'forms' ) );
		?>
		<p>
			<a href="<?php echo \esc_url( $back ); ?>">&larr; <?php echo \esc_html__( 'Back to forms', 'silvaitamar-webmcp-form-annotator' ); ?></a>
		</p>

		<h2>
			<?php echo \esc_html( $form['title'] ); ?>
			<span class="description">— <?php echo \esc_html( Form_Catalog::builder_label( $form['builder'] ) ); ?> #<?php echo \esc_html( (string) $form['id'] ); ?></span>
		</h2>

		<form method="post" action="<?php echo \esc_url( Settings::page_url( array( 'siwmfa_tab' => 'forms' ) ) ); ?>" class="siwmfa-form-editor">
			<?php \wp_nonce_field( 'siwmfa_save_form' ); ?>
			<input type="hidden" name="siwmfa_form_key" value="<?php echo \esc_attr( $key ); ?>" />

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php echo \esc_html__( 'Annotate this form', 'silvaitamar-webmcp-form-annotator' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo \esc_attr( 'siwmfa_forms[' . $key . '][enabled]' ); ?>" value="1" <?php \checked( $config['enabled'] ); ?> />
							<?php echo \esc_html__( 'Inject WebMCP attributes when this form is rendered.', 'silvaitamar-webmcp-form-annotator' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="siwmfa_toolname"><?php echo \esc_html__( 'Tool name', 'silvaitamar-webmcp-form-annotator' ); ?></label>
					</th>
					<td>
						<input class="regular-text" id="siwmfa_toolname" name="<?php echo \esc_attr( 'siwmfa_forms[' . $key . '][toolname]' ); ?>" value="<?php echo \esc_attr( $toolname ); ?>" pattern="[a-z0-9_]+" required />
						<p class="description"><?php echo \esc_html__( 'Lowercase letters, numbers, and underscores. Shown to the browser agent.', 'silvaitamar-webmcp-form-annotator' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="siwmfa_tooldescription"><?php echo \esc_html__( 'Tool description', 'silvaitamar-webmcp-form-annotator' ); ?></label>
					</th>
					<td>
						<textarea class="large-text" rows="4" id="siwmfa_tooldescription" name="<?php echo \esc_attr( 'siwmfa_forms[' . $key . '][tooldescription]' ); ?>"><?php echo \esc_textarea( $desc ); ?></textarea>
						<p class="description"><?php echo \esc_html__( 'Tell the agent what the form is for. Lead and support forms must not auto-submit.', 'silvaitamar-webmcp-form-annotator' ); ?></p>
					</td>
				</tr>
			</table>

			<?php if ( array() !== $form['fields'] ) : ?>
				<h3><?php echo \esc_html__( 'Field descriptions', 'silvaitamar-webmcp-form-annotator' ); ?></h3>
				<p class="description"><?php echo \esc_html__( 'These become toolparamdescription on each control. Use the HTML name as shown.', 'silvaitamar-webmcp-form-annotator' ); ?></p>
				<table class="widefat striped siwmfa-fields">
					<thead>
						<tr>
							<th><?php echo \esc_html__( 'Field', 'silvaitamar-webmcp-form-annotator' ); ?></th>
							<th><?php echo \esc_html__( 'Label', 'silvaitamar-webmcp-form-annotator' ); ?></th>
							<th><?php echo \esc_html__( 'toolparamdescription', 'silvaitamar-webmcp-form-annotator' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $form['fields'] as $field_name => $field_label ) : ?>
						<?php
						$param = isset( $config['params'][ $field_name ] ) && '' !== $config['params'][ $field_name ]
							? $config['params'][ $field_name ]
							: $field_label;
						?>
						<tr>
							<td><code><?php echo \esc_html( $field_name ); ?></code></td>
							<td><?php echo \esc_html( $field_label ); ?></td>
							<td>
								<input class="large-text" name="<?php echo \esc_attr( 'siwmfa_forms[' . $key . '][params][' . $field_name . ']' ); ?>" value="<?php echo \esc_attr( $param ); ?>" />
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php \submit_button( \__( 'Save annotations', 'silvaitamar-webmcp-form-annotator' ), 'primary', 'siwmfa_save_form' ); ?>
		</form>
		<?php
	}
}
