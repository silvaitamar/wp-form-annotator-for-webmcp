<?php
/**
 * Seed UC3 lab: Site search + Filter Everything + Search & Filter.
 *
 * @package Siwmfa
 */

defined( 'ABSPATH' ) || exit;

// --- Site search page (core/search block) ---
$search_page_id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Search Lab UC3',
		'post_name'    => 'search-lab-uc3',
		'post_content' => '<!-- wp:search {"label":"Search","buttonText":"Search"} /-->',
	),
	true
);
if ( is_wp_error( $search_page_id ) ) {
	WP_CLI::error( $search_page_id->get_error_message() );
}

\Siwmfa\Registry::save_one(
	'search:1',
	array(
		'enabled'         => true,
		'toolname'        => 'search_site',
		'tooldescription' => 'Searches this site.',
		'toolautosubmit'  => true,
		'params'          => array(
			's' => 'Search query',
		),
	)
);

// --- Search & Filter page ---
$sf_page_id = 0;
if ( shortcode_exists( 'searchandfilter' ) ) {
	$sf_page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Search Filter Lab UC3',
			'post_name'    => 'search-filter-lab-uc3',
			'post_content' => '<!-- wp:shortcode -->[searchandfilter fields="search,category,post_tag"]<!-- /wp:shortcode -->',
		),
		true
	);
	if ( is_wp_error( $sf_page_id ) ) {
		WP_CLI::error( $sf_page_id->get_error_message() );
	}

	$prefix = defined( 'SF_FPRE' ) ? SF_FPRE : 'of';
	\Siwmfa\Registry::save_one(
		'searchfilter:1',
		array(
			'enabled'         => true,
			'toolname'        => 'search_and_filter',
			'tooldescription' => \Siwmfa\Registry::suggest_filter_description( 'searchfilter' ),
			'toolautosubmit'  => true,
			'params'          => array(
				$prefix . 'search'   => 'Full search phrase in one shot (do not split into multiple tool calls)',
				$prefix . 'category' => 'Category term ID (0 = all)',
				$prefix . 'post_tag' => 'Tag term ID (0 = all)',
			),
		)
	);
}

// --- Filter Everything set + page ---
$fe_set_id  = 0;
$fe_page_id = 0;
if ( post_type_exists( 'filter-set' ) ) {
	$fe_set_id = wp_insert_post(
		array(
			'post_type'   => 'filter-set',
			'post_status' => 'publish',
			'post_title'  => 'WebMCP Lab Filter Set UC3',
		),
		true
	);
	if ( is_wp_error( $fe_set_id ) ) {
		WP_CLI::error( $fe_set_id->get_error_message() );
	}

		\Siwmfa\Registry::save_one(
			\Siwmfa\Registry::make_key( 'filtereverything', (int) $fe_set_id ),
			array(
				'enabled'         => true,
				'toolname'        => 'filter_everything',
				'tooldescription' => \Siwmfa\Registry::suggest_filter_description( 'filtereverything' ),
				'toolautosubmit'  => true,
				'params'          => array(
					'srch' => 'Full search phrase in one shot (do not split into multiple tool calls)',
				),
			)
		);

	// Synthetic markup for CLI smoke (full widget needs location + filters).
	$fe_html = sprintf(
		'<div class="wpc-filters-main-wrap wpc-filter-set-%1$d" data-set="%1$d"><form method="get" class="wpc-filter-search-form"><input type="text" name="srch" value="" /></form></div>',
		(int) $fe_set_id
	);
	$fe_page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Filter Everything Lab UC3',
			'post_name'    => 'filter-everything-lab-uc3',
			'post_content' => '<!-- wp:html -->' . $fe_html . '<!-- /wp:html -->' . "\n" .
				'<!-- wp:shortcode -->[fe_widget id="' . (int) $fe_set_id . '"]<!-- /wp:shortcode -->',
		),
		true
	);
	if ( is_wp_error( $fe_page_id ) ) {
		WP_CLI::error( $fe_page_id->get_error_message() );
	}
}

$search_html = get_search_form( false );
$ok_search   = is_string( $search_html ) && false !== strpos( $search_html, 'toolname="search_site"' );

$ok_sf = true;
if ( $sf_page_id ) {
	$sf_html = do_shortcode( '[searchandfilter fields="search,category,post_tag"]' );
	$ok_sf   = is_string( $sf_html ) && false !== strpos( $sf_html, 'toolname="search_and_filter"' );
}

$ok_fe = true;
if ( $fe_set_id ) {
	$fe_check = sprintf(
		'<div class="wpc-filters-main-wrap wpc-filter-set-%1$d" data-set="%1$d"><form method="get" class="wpc-filter-search-form"><input type="text" name="srch" /></form></div>',
		(int) $fe_set_id
	);
	$fe_check = \Siwmfa\Adapters\Filter_Everything::annotate_html( $fe_check );
	$ok_fe    = false !== strpos( $fe_check, 'toolname="filter_everything"' );
}

WP_CLI::log( 'search_page_id=' . (int) $search_page_id );
WP_CLI::log( 'search_url=' . get_permalink( $search_page_id ) );
WP_CLI::log( 'sf_page_id=' . (int) $sf_page_id );
WP_CLI::log( 'fe_set_id=' . (int) $fe_set_id );
WP_CLI::log( 'fe_page_id=' . (int) $fe_page_id );
WP_CLI::log( 'search_attrs=' . ( $ok_search ? 'yes' : 'no' ) );
WP_CLI::log( 'searchfilter_attrs=' . ( $ok_sf ? 'yes' : 'no' ) );
WP_CLI::log( 'filtereverything_attrs=' . ( $ok_fe ? 'yes' : 'no' ) );

if ( $ok_search && $ok_sf && $ok_fe ) {
	WP_CLI::success( 'UC3 search + FE + SF annotation OK.' );
} else {
	WP_CLI::error( 'UC3 search/filter smoke failed.' );
}
