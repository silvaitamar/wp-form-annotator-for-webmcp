<?php
/**
 * Uninstall cleanup.
 *
 * @package Siwmfa
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'siwmfa_settings' );
delete_option( 'siwmfa_forms' );
