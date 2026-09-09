<?php
/**
 * Ask page-cache plugins to drop stale HTML after annotation changes.
 *
 * @package Siwmfa
 */

namespace Siwmfa;

defined( 'ABSPATH' ) || exit;

/**
 * Full-page caches can serve form markup rendered before annotations existed.
 */
final class Cache_Purge {

	/**
	 * Invalidates known page caches after the registry changes.
	 *
	 * Soft-deps only: missing plugins are no-ops. Prefer page-cache purge over
	 * flushing the entire object cache.
	 *
	 * @return void
	 */
	public static function after_annotation_change(): void {
		/**
		 * Fires before this plugin requests a page-cache purge.
		 *
		 * @since 1.0.2
		 */
		\do_action( 'siwmfa_before_purge_caches' );

		// LiteSpeed Cache (LSCWP) — documented public action.
		\do_action( 'litespeed_purge_all' );

		if ( \function_exists( 'rocket_clean_domain' ) ) {
			\rocket_clean_domain();
		}

		if ( \function_exists( 'wpfc_clear_all_cache' ) ) {
			\wpfc_clear_all_cache( true );
		}

		if ( \function_exists( 'w3tc_flush_posts' ) ) {
			\w3tc_flush_posts();
		}

		/**
		 * Fires after built-in purge hooks. Hosts / MU-plugins can listen here.
		 *
		 * @since 1.0.2
		 */
		\do_action( 'siwmfa_purge_caches' );
	}
}
