<?php
/**
 * Uninstall cleanup.
 *
 * @package Siwmfa
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Deletes plugin options for the current site.
 *
 * @return void
 */
function siwmfa_uninstall_site(): void {
	delete_option( 'siwmfa_settings' );
	delete_option( 'siwmfa_forms' );
}

if ( \is_multisite() ) {
	$siwmfa_blog_ids = \get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $siwmfa_blog_ids as $siwmfa_blog_id ) {
		\switch_to_blog( (int) $siwmfa_blog_id );
		siwmfa_uninstall_site();
		\restore_current_blog();
	}
} else {
	siwmfa_uninstall_site();
}
