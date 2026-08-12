<?php
/**
 * Compact forms list for the settings screen.
 *
 * @package Siwmfa
 */

namespace Siwmfa\Admin;

use Siwmfa\Form_Catalog;
use Siwmfa\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress-native table: search, builder/status filters, pagination.
 */
final class Form_List {

	public const PER_PAGE = 20;

	/**
	 * Renders the list screen.
	 *
	 * @return void
	 */
	public static function render(): void {
		$all       = Form_Catalog::discover();
		$filters   = self::current_filters();
		$filtered  = self::filter_forms( $all, $filters );
		$total     = \count( $filtered );
		$pages     = (int) \max( 1, (int) \ceil( $total / self::PER_PAGE ) );
		$paged     = \min( $filters['paged'], $pages );
		$offset    = ( $paged - 1 ) * self::PER_PAGE;
		$page_rows = \array_slice( $filtered, $offset, self::PER_PAGE );
		$builders  = Form_Catalog::builders_in( $all );
		$counts    = self::status_counts( $all );

		$base_url = Settings::page_url( array( 'siwmfa_tab' => 'forms' ) );
		?>
		<p>
			<?php echo \esc_html__( 'Enable a form, then open it to set the tool name, description, and field annotations. Lead and support forms never auto-submit — the visitor confirms.', 'silvaitamar-webmcp-form-annotator' ); ?>
		</p>

		<?php if ( array() === $all ) : ?>
			<p>
				<?php echo \esc_html__( 'No supported forms found. Install and create a form in Contact Form 7, Fluent Forms, WPForms, Forminator, Ninja Forms, or SureForms, then return here.', 'silvaitamar-webmcp-form-annotator' ); ?>
			</p>
			<?php
			return;
		endif;
		?>

		<ul class="subsubsub">
			<?php self::view_link( $base_url, '', \__( 'All', 'silvaitamar-webmcp-form-annotator' ), $counts['all'], '' === $filters['status'] ); ?>
			<?php self::view_link( $base_url, 'enabled', \__( 'Enabled', 'silvaitamar-webmcp-form-annotator' ), $counts['enabled'], 'enabled' === $filters['status'] ); ?>
			<?php self::view_link( $base_url, 'disabled', \__( 'Disabled', 'silvaitamar-webmcp-form-annotator' ), $counts['disabled'], 'disabled' === $filters['status'] ); ?>
		</ul>

		<form method="get" action="<?php echo \esc_url( \admin_url( 'options-general.php' ) ); ?>">
			<input type="hidden" name="page" value="siwmfa-settings" />
			<input type="hidden" name="siwmfa_tab" value="forms" />
			<?php if ( '' !== $filters['status'] ) : ?>
				<input type="hidden" name="siwmfa_status" value="<?php echo \esc_attr( $filters['status'] ); ?>" />
			<?php endif; ?>

			<p class="search-box">
				<label class="screen-reader-text" for="siwmfa-form-search"><?php echo \esc_html__( 'Search forms', 'silvaitamar-webmcp-form-annotator' ); ?></label>
				<input type="search" id="siwmfa-form-search" name="s" value="<?php echo \esc_attr( $filters['search'] ); ?>" />
				<?php \submit_button( \__( 'Search forms', 'silvaitamar-webmcp-form-annotator' ), '', '', false, array( 'id' => 'search-submit' ) ); ?>
			</p>

			<div class="tablenav top">
				<div class="alignleft actions">
					<label for="siwmfa_builder" class="screen-reader-text"><?php echo \esc_html__( 'Filter by builder', 'silvaitamar-webmcp-form-annotator' ); ?></label>
					<select name="siwmfa_builder" id="siwmfa_builder">
						<option value=""><?php echo \esc_html__( 'All builders', 'silvaitamar-webmcp-form-annotator' ); ?></option>
						<?php foreach ( $builders as $slug => $label ) : ?>
							<option value="<?php echo \esc_attr( $slug ); ?>" <?php \selected( $filters['builder'], $slug ); ?>><?php echo \esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php \submit_button( \__( 'Filter', 'silvaitamar-webmcp-form-annotator' ), '', 'filter_action', false ); ?>
				</div>
				<?php self::pagination( $total, $paged, $pages, $filters ); ?>
				<br class="clear" />
			</div>
		</form>

		<form method="post" action="<?php echo \esc_url( $base_url ); ?>">
			<?php \wp_nonce_field( 'siwmfa_bulk_forms' ); ?>
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<label for="bulk-action-selector-top" class="screen-reader-text"><?php echo \esc_html__( 'Select bulk action', 'silvaitamar-webmcp-form-annotator' ); ?></label>
					<select name="siwmfa_bulk" id="bulk-action-selector-top">
						<option value="-1"><?php echo \esc_html__( 'Bulk actions', 'silvaitamar-webmcp-form-annotator' ); ?></option>
						<option value="enable"><?php echo \esc_html__( 'Enable', 'silvaitamar-webmcp-form-annotator' ); ?></option>
						<option value="disable"><?php echo \esc_html__( 'Disable', 'silvaitamar-webmcp-form-annotator' ); ?></option>
					</select>
					<?php \submit_button( \__( 'Apply', 'silvaitamar-webmcp-form-annotator' ), 'action', 'siwmfa_bulk_apply', false ); ?>
				</div>
			</div>

			<table class="wp-list-table widefat fixed striped table-view-list">
				<thead>
					<tr>
						<td class="manage-column column-cb check-column">
							<input id="cb-select-all-1" type="checkbox" />
						</td>
						<th scope="col"><?php echo \esc_html__( 'Form', 'silvaitamar-webmcp-form-annotator' ); ?></th>
						<th scope="col"><?php echo \esc_html__( 'Builder', 'silvaitamar-webmcp-form-annotator' ); ?></th>
						<th scope="col"><?php echo \esc_html__( 'Status', 'silvaitamar-webmcp-form-annotator' ); ?></th>
						<th scope="col"><?php echo \esc_html__( 'Tool name', 'silvaitamar-webmcp-form-annotator' ); ?></th>
						<th scope="col" class="column-fields"><?php echo \esc_html__( 'Fields', 'silvaitamar-webmcp-form-annotator' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( array() === $page_rows ) : ?>
						<tr>
							<td colspan="6"><?php echo \esc_html__( 'No forms match this search.', 'silvaitamar-webmcp-form-annotator' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $page_rows as $form ) : ?>
							<?php self::row( $form, $filters ); ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</form>

		<div class="tablenav bottom">
			<?php self::pagination( $total, $paged, $pages, $filters ); ?>
		</div>
		<?php
	}

	/**
	 * Current list filters from the request.
	 *
	 * @return array{search: string, builder: string, status: string, paged: int}
	 */
	public static function current_filters(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only list filters.
		$search = '';
		if ( isset( $_GET['s'] ) ) {
			$search = \sanitize_text_field( \wp_unslash( (string) $_GET['s'] ) );
		}

		$builder = '';
		if ( isset( $_GET['siwmfa_builder'] ) ) {
			$builder = \sanitize_key( \wp_unslash( (string) $_GET['siwmfa_builder'] ) );
		}

		$status = '';
		if ( isset( $_GET['siwmfa_status'] ) ) {
			$status = \sanitize_key( \wp_unslash( (string) $_GET['siwmfa_status'] ) );
			if ( ! \in_array( $status, array( 'enabled', 'disabled' ), true ) ) {
				$status = '';
			}
		}

		$paged = 1;
		if ( isset( $_GET['paged'] ) ) {
			$paged = \max( 1, (int) $_GET['paged'] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return array(
			'search'  => $search,
			'builder' => $builder,
			'status'  => $status,
			'paged'   => $paged,
		);
	}

	/**
	 * Applies search, builder, and status filters.
	 *
	 * @param list<array{builder: string, id: int, title: string, fields: array<string, string>}> $forms   Forms.
	 * @param array{search: string, builder: string, status: string, paged: int}                  $filters Filters.
	 * @return list<array{builder: string, id: int, title: string, fields: array<string, string>}>
	 */
	private static function filter_forms( array $forms, array $filters ): array {
		$out = array();

		foreach ( $forms as $form ) {
			if ( '' !== $filters['builder'] && $form['builder'] !== $filters['builder'] ) {
				continue;
			}

			$config  = Registry::get( $form['builder'], $form['id'] );
			$enabled = $config['enabled'];
			if ( 'enabled' === $filters['status'] && ! $enabled ) {
				continue;
			}
			if ( 'disabled' === $filters['status'] && $enabled ) {
				continue;
			}

			if ( '' !== $filters['search'] ) {
				$hay = \strtolower( $form['title'] . ' ' . $form['builder'] . ' ' . (string) $form['id'] . ' ' . $config['toolname'] );
				if ( false === \strpos( $hay, \strtolower( $filters['search'] ) ) ) {
					continue;
				}
			}

			$out[] = $form;
		}

		return $out;
	}

	/**
	 * Counts enabled vs disabled forms.
	 *
	 * @param list<array{builder: string, id: int, title: string, fields: array<string, string>}> $forms Forms.
	 * @return array{all: int, enabled: int, disabled: int}
	 */
	private static function status_counts( array $forms ): array {
		$enabled = 0;
		foreach ( $forms as $form ) {
			if ( Registry::get( $form['builder'], $form['id'] )['enabled'] ) {
				++$enabled;
			}
		}

		$all = \count( $forms );

		return array(
			'all'      => $all,
			'enabled'  => $enabled,
			'disabled' => $all - $enabled,
		);
	}

	/**
	 * Prints a subsubsub view link.
	 *
	 * @param string $base_url Base settings URL.
	 * @param string $status   Status slug or empty for all.
	 * @param string $label    Link label.
	 * @param int    $count    Count.
	 * @param bool   $current  Whether this view is active.
	 * @return void
	 */
	private static function view_link( string $base_url, string $status, string $label, int $count, bool $current ): void {
		$url   = '' === $status ? $base_url : \add_query_arg( 'siwmfa_status', $status, $base_url );
		$class = $current ? ' class="current"' : '';
		$sep   = 'disabled' === $status ? '' : ' |';
		printf(
			'<li class="%s"><a href="%s"%s>%s <span class="count">(%s)</span></a>%s</li>' . "\n",
			\esc_attr( '' === $status ? 'all' : $status ),
			\esc_url( $url ),
			$class, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- empty or class="current".
			\esc_html( $label ),
			\esc_html( \number_format_i18n( $count ) ),
			\esc_html( $sep )
		);
	}

	/**
	 * Prints one table row.
	 *
	 * @param array{builder: string, id: int, title: string, fields: array<string, string>} $form    Form.
	 * @param array{search: string, builder: string, status: string, paged: int}            $filters Filters.
	 * @return void
	 */
	private static function row( array $form, array $filters ): void {
		$key      = Registry::make_key( $form['builder'], $form['id'] );
		$config   = Registry::get( $form['builder'], $form['id'] );
		$edit_url = Settings::page_url(
			array(
				'siwmfa_tab'  => 'forms',
				'siwmfa_form' => $key,
			)
		);
		$toggle   = Settings::toggle_url( $key, ! $config['enabled'], $form['title'], $filters );
		?>
		<tr>
			<th scope="row" class="check-column">
				<input type="checkbox" name="siwmfa_keys[]" value="<?php echo \esc_attr( $key ); ?>" />
			</th>
			<td class="column-title has-row-actions column-primary">
				<strong><a class="row-title" href="<?php echo \esc_url( $edit_url ); ?>"><?php echo \esc_html( $form['title'] ); ?></a></strong>
				<div class="row-actions">
					<span class="edit"><a href="<?php echo \esc_url( $edit_url ); ?>"><?php echo \esc_html__( 'Annotate', 'silvaitamar-webmcp-form-annotator' ); ?></a> | </span>
					<span class="inline">
						<a href="<?php echo \esc_url( $toggle ); ?>">
							<?php echo $config['enabled'] ? \esc_html__( 'Disable', 'silvaitamar-webmcp-form-annotator' ) : \esc_html__( 'Enable', 'silvaitamar-webmcp-form-annotator' ); ?>
						</a>
					</span>
				</div>
			</td>
			<td><?php echo \esc_html( Form_Catalog::builder_label( $form['builder'] ) ); ?> <span class="description">#<?php echo \esc_html( (string) $form['id'] ); ?></span></td>
			<td>
				<?php if ( $config['enabled'] ) : ?>
					<span class="siwmfa-status siwmfa-status--on"><?php echo \esc_html__( 'Enabled', 'silvaitamar-webmcp-form-annotator' ); ?></span>
				<?php else : ?>
					<span class="siwmfa-status siwmfa-status--off"><?php echo \esc_html__( 'Off', 'silvaitamar-webmcp-form-annotator' ); ?></span>
				<?php endif; ?>
			</td>
			<td><?php echo '' !== $config['toolname'] ? '<code>' . \esc_html( $config['toolname'] ) . '</code>' : '<span class="description">—</span>'; ?></td>
			<td><?php echo \esc_html( (string) \count( $form['fields'] ) ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Prints list pagination.
	 *
	 * @param int                                                                $total   Total rows.
	 * @param int                                                                $paged   Current page.
	 * @param int                                                                $pages   Page count.
	 * @param array{search: string, builder: string, status: string, paged: int} $filters Filters.
	 * @return void
	 */
	private static function pagination( int $total, int $paged, int $pages, array $filters ): void {
		if ( $total <= self::PER_PAGE ) {
			return;
		}

		$args = array(
			'siwmfa_tab' => 'forms',
		);
		if ( '' !== $filters['search'] ) {
			$args['s'] = $filters['search'];
		}
		if ( '' !== $filters['builder'] ) {
			$args['siwmfa_builder'] = $filters['builder'];
		}
		if ( '' !== $filters['status'] ) {
			$args['siwmfa_status'] = $filters['status'];
		}

		$base  = \add_query_arg( $args, \admin_url( 'options-general.php?page=siwmfa-settings' ) );
		$links = \paginate_links(
			array(
				'base'      => \add_query_arg( 'paged', '%#%', $base ),
				'format'    => '',
				'total'     => $pages,
				'current'   => $paged,
				'type'      => 'plain',
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
			)
		);

		printf(
			'<div class="tablenav-pages"><span class="displaying-num">%s</span> <span class="pagination-links">%s</span></div>',
			\esc_html(
				\sprintf(
					/* translators: %s: number of forms */
					\_n( '%s form', '%s forms', $total, 'silvaitamar-webmcp-form-annotator' ),
					\number_format_i18n( $total )
				)
			),
			$links ? $links : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links() HTML.
		);
	}
}
