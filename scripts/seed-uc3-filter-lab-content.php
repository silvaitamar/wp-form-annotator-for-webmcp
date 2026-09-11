<?php
/**
 * Populate UC3 lab with real posts + taxonomy filters for FE and Search & Filter.
 *
 * Idempotent: reuses terms/pages/set by slug or title when present.
 *
 * @package Siwmfa
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ensure a category or tag exists.
 *
 * @param string               $taxonomy Taxonomy.
 * @param string               $slug     Slug.
 * @param string               $name     Name.
 * @param string               $desc     Description.
 * @return int Term ID.
 */
function siwmfa_uc3_ensure_term( $taxonomy, $slug, $name, $desc = '' ) {
	$existing = get_term_by( 'slug', $slug, $taxonomy );
	if ( $existing && ! is_wp_error( $existing ) ) {
		return (int) $existing->term_id;
	}
	$result = wp_insert_term(
		$name,
		$taxonomy,
		array(
			'slug'        => $slug,
			'description' => $desc,
		)
	);
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}
	return (int) $result['term_id'];
}

/**
 * Find page by slug or create/update it.
 *
 * @param string $slug    Slug.
 * @param string $title   Title.
 * @param string $content Block content.
 * @return int Page ID.
 */
function siwmfa_uc3_upsert_page( $slug, $title, $content ) {
	$existing = get_page_by_path( $slug );
	if ( $existing instanceof WP_Post ) {
		$updated = wp_update_post(
			array(
				'ID'           => (int) $existing->ID,
				'post_title'   => $title,
				'post_status'  => 'publish',
				'post_content' => $content,
			),
			true
		);
		if ( is_wp_error( $updated ) ) {
			WP_CLI::error( $updated->get_error_message() );
		}
		return (int) $existing->ID;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	return (int) $id;
}

/**
 * Create or update a demo post with taxonomies.
 *
 * @param string        $slug       Slug.
 * @param string        $title      Title.
 * @param string        $content    Content.
 * @param array<int>    $cat_ids    Category term IDs.
 * @param array<int>    $tag_ids    Tag term IDs.
 * @return int Post ID.
 */
function siwmfa_uc3_upsert_post( $slug, $title, $content, array $cat_ids, array $tag_ids ) {
	$existing = get_page_by_path( $slug, OBJECT, 'post' );
	$payload  = array(
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_excerpt' => wp_trim_words( wp_strip_all_tags( $content ), 24 ),
	);

	if ( $existing instanceof WP_Post ) {
		$payload['ID'] = (int) $existing->ID;
		$id            = wp_update_post( $payload, true );
	} else {
		$id = wp_insert_post( $payload, true );
	}
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}

	wp_set_post_terms( (int) $id, $cat_ids, 'category', false );
	wp_set_post_terms( (int) $id, $tag_ids, 'post_tag', false );
	return (int) $id;
}

/**
 * Query loop block markup listing recent posts.
 *
 * @return string
 */
function siwmfa_uc3_posts_query_blocks() {
	return <<<'BLOCKS'
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Sample posts</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Use the filters above; this list shows the content available in the lab.</p>
<!-- /wp:paragraph -->

<!-- wp:query {"queryId":31,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"list"}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:post-title {"isLink":true} /-->
<!-- wp:post-excerpt /-->
<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:post-terms {"term":"category"} /--><!-- wp:post-terms {"term":"post_tag"} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>No posts match.</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->
BLOCKS;
}

// --- Taxonomies ---
$cat_guides    = siwmfa_uc3_ensure_term( 'category', 'guides', 'Guides', 'How-to articles and walkthroughs.' );
$cat_news      = siwmfa_uc3_ensure_term( 'category', 'news', 'News', 'Product and ecosystem updates.' );
$cat_tutorials = siwmfa_uc3_ensure_term( 'category', 'tutorials', 'Tutorials', 'Step-by-step lab tutorials.' );

$tag_webmcp      = siwmfa_uc3_ensure_term( 'post_tag', 'webmcp', 'WebMCP' );
$tag_wordpress   = siwmfa_uc3_ensure_term( 'post_tag', 'wordpress', 'WordPress' );
$tag_forms       = siwmfa_uc3_ensure_term( 'post_tag', 'forms', 'Forms' );
$tag_performance = siwmfa_uc3_ensure_term( 'post_tag', 'performance', 'Performance' );
$tag_filters     = siwmfa_uc3_ensure_term( 'post_tag', 'filters', 'Filters' );

$posts_seed = array(
	array(
		'slug'    => 'uc3-webmcp-form-annotation',
		'title'   => 'WebMCP form annotation basics',
		'content' => "<!-- wp:paragraph --><p>Declarative WebMCP attributes let an agent discover and fill forms on a WordPress page without custom glue code. This guide covers toolname, toolparamdescription, and toolautosubmit for idempotent GET surfaces.</p><!-- /wp:paragraph -->",
		'cats'    => array( $cat_guides ),
		'tags'    => array( $tag_webmcp, $tag_forms, $tag_wordpress ),
	),
	array(
		'slug'    => 'uc3-jetpack-forms-lab',
		'title'   => 'Jetpack Forms in the annotation lab',
		'content' => "<!-- wp:paragraph --><p>Jetpack Forms expose contact fields that the annotator maps from the synced CPT. Use this post when validating lead forms alongside search and filter tools.</p><!-- /wp:paragraph -->",
		'cats'    => array( $cat_tutorials ),
		'tags'    => array( $tag_forms, $tag_wordpress ),
	),
	array(
		'slug'    => 'uc3-filter-everything-facets',
		'title'   => 'Filter Everything facets and GET forms',
		'content' => "<!-- wp:paragraph --><p>Filter Everything free builds taxonomy facets mostly as links. Search, range, date, and sorting still emit real GET forms that WebMCP can annotate. Category and tag filters remain first-class filtering, not only keyword search.</p><!-- /wp:paragraph -->",
		'cats'    => array( $cat_guides ),
		'tags'    => array( $tag_filters, $tag_webmcp, $tag_wordpress ),
	),
	array(
		'slug'    => 'uc3-search-and-filter-fields',
		'title'   => 'Search &amp; Filter multi-field forms',
		'content' => "<!-- wp:paragraph --><p>Search &amp; Filter free renders a single GET form with keyword, category, and tag fields. That is the best Chrome extension surface for multi-parameter filter tools.</p><!-- /wp:paragraph -->",
		'cats'    => array( $cat_tutorials ),
		'tags'    => array( $tag_filters, $tag_forms, $tag_webmcp ),
	),
	array(
		'slug'    => 'uc3-performance-notes',
		'title'   => 'Performance notes for annotated pages',
		'content' => "<!-- wp:paragraph --><p>Keep annotations SSR-only and avoid shipping unused builder adapters. Soft-deps load only when the third-party plugin is active.</p><!-- /wp:paragraph -->",
		'cats'    => array( $cat_news ),
		'tags'    => array( $tag_performance, $tag_wordpress ),
	),
	array(
		'slug'    => 'uc3-release-1-1-0',
		'title'   => 'Release notes: annotator 1.1.0',
		'content' => "<!-- wp:paragraph --><p>Version 1.1.0 adds Jetpack Forms, native site search, Filter Everything, and Search &amp; Filter adapters. toolautosubmit stays limited to idempotent search and filter GET forms.</p><!-- /wp:paragraph -->",
		'cats'    => array( $cat_news ),
		'tags'    => array( $tag_webmcp, $tag_wordpress, $tag_filters ),
	),
	array(
		'slug'    => 'uc3-category-only-guides',
		'title'   => 'Choosing categories for directory-style filters',
		'content' => "<!-- wp:paragraph --><p>Categories model broad buckets (Guides, News, Tutorials). Pair them with tags for cross-cutting topics such as WebMCP or Performance.</p><!-- /wp:paragraph -->",
		'cats'    => array( $cat_guides ),
		'tags'    => array( $tag_filters ),
	),
	array(
		'slug'    => 'uc3-tag-only-webmcp',
		'title'   => 'Tagging posts for agent discovery',
		'content' => "<!-- wp:paragraph --><p>Tags help agents narrow results when the filter form exposes post_tag. Prefer a short controlled vocabulary in the lab.</p><!-- /wp:paragraph -->",
		'cats'    => array( $cat_tutorials ),
		'tags'    => array( $tag_webmcp, $tag_forms ),
	),
);

$created_posts = array();
foreach ( $posts_seed as $row ) {
	$created_posts[] = siwmfa_uc3_upsert_post( $row['slug'], $row['title'], $row['content'], $row['cats'], $row['tags'] );
}

// Keep Hello world out of Uncategorized-only noise: assign News.
wp_set_post_terms( 1, array( $cat_news ), 'category', false );
wp_set_post_terms( 1, array( $tag_wordpress ), 'post_tag', false );

// --- Filter Everything set + fields ---
$fe_set_id = 0;
if ( post_type_exists( 'filter-set' ) ) {
	$sets = get_posts(
		array(
			'post_type'      => 'filter-set',
			'post_status'    => 'any',
			'posts_per_page' => 20,
			's'              => 'WebMCP Lab Filter Set UC3',
		)
	);
	foreach ( $sets as $set_post ) {
		if ( 'WebMCP Lab Filter Set UC3' === $set_post->post_title ) {
			$fe_set_id = (int) $set_post->ID;
			break;
		}
	}
	if ( ! $fe_set_id ) {
		$fe_set_id = (int) wp_insert_post(
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
	}

	$set_fields = array(
		'hide_empty'               => 'no',
		'show_count'               => 'yes',
		'wp_page_type'             => 'common___common',
		'wp_filter_query'          => '-1',
		'use_search_field'         => 'yes',
		'search_field_menu_order'  => '3',
		'search_field_label'       => ' Search',
		'search_field_placeholder' => 'Keyword…',
		'use_apply_button'         => 'no',
		'horizontal_view_priority' => 'filter_set',
	);

	remove_filter( 'content_save_pre', 'wp_targeted_link_rel' );
	add_filter( 'pre_wp_unique_post_slug', 'flrt_force_non_unique_slug', 10, 2 );

	wp_update_post(
		array(
			'ID'           => $fe_set_id,
			'post_status'  => 'publish',
			'post_title'   => 'WebMCP Lab Filter Set UC3',
			'post_excerpt' => 'post',
			'post_name'    => '1',
			'post_content' => maybe_serialize( $set_fields ),
		),
		true
	);
	update_post_meta( $fe_set_id, 'wpc_filter_set_post_type', 'post' );

	$existing_fields = get_posts(
		array(
			'post_type'      => 'filter-field',
			'post_parent'    => $fe_set_id,
			'posts_per_page' => -1,
			'post_status'    => 'any',
		)
	);
	foreach ( $existing_fields as $field_post ) {
		wp_delete_post( (int) $field_post->ID, true );
	}

	$filter_defs = array(
		array(
			'label'      => 'Categories',
			'slug'       => '_pcat',
			'menu_order' => 1,
			'entity'     => 'taxonomy_category',
			'data'       => array(
				'entity'          => 'taxonomy',
				'e_name'          => 'category',
				'view'            => 'checkboxes',
				'logic'           => 'or',
				'orderby'         => 'name',
				'in_path'         => 'yes',
				'show_term_names' => 'yes',
				'show_chips'      => 'yes',
				'more_less'       => 'yes',
				'collapse'        => 'no',
				'hierarchy'       => 'yes',
				'search'          => 'no',
				'parent_filter'   => '-1',
			),
		),
		array(
			'label'      => 'Tags',
			'slug'       => '_ptag',
			'menu_order' => 2,
			'entity'     => 'taxonomy_post_tag',
			'data'       => array(
				'entity'          => 'taxonomy',
				'e_name'          => 'post_tag',
				'view'            => 'checkboxes',
				'logic'           => 'or',
				'orderby'         => 'name',
				'in_path'         => 'yes',
				'show_term_names' => 'yes',
				'show_chips'      => 'yes',
				'more_less'       => 'yes',
				'collapse'        => 'no',
				'hierarchy'       => 'no',
				'search'          => 'no',
				'parent_filter'   => '-1',
			),
		),
	);

	$permalinks = get_option( 'wpc_filter_permalinks', array() );
	if ( ! is_array( $permalinks ) ) {
		$permalinks = array();
	}

	foreach ( $filter_defs as $def ) {
		$field_id = wp_insert_post(
			array(
				'post_type'    => 'filter-field',
				'post_status'  => 'publish',
				'post_title'   => $def['label'],
				'post_parent'  => $fe_set_id,
				'menu_order'   => $def['menu_order'],
				'post_name'    => $def['slug'],
				'post_excerpt' => $def['entity'],
				'post_content' => maybe_serialize( $def['data'] ),
			),
			true
		);
		if ( is_wp_error( $field_id ) ) {
			WP_CLI::error( $field_id->get_error_message() );
		}
		$permalinks[ $def['entity'] ] = $def['slug'];
	}
	update_option( 'wpc_filter_permalinks', $permalinks, true );

	remove_filter( 'pre_wp_unique_post_slug', 'flrt_force_non_unique_slug', 10 );

	\Siwmfa\Registry::save_one(
		\Siwmfa\Registry::make_key( 'filtereverything', $fe_set_id ),
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

	update_option( 'siwmfa_uc3_fe_set_id', $fe_set_id, false );
}

// --- Pages ---
$sf_shortcode = '[searchandfilter fields="search,category,post_tag" types="text,select,select" headings="Keyword,Category,Tag" operators="and,and,and"]';
$sf_content   = '<!-- wp:heading -->' . "\n" .
	'<h2 class="wp-block-heading">Search &amp; Filter lab</h2>' . "\n" .
	'<!-- /wp:heading -->' . "\n\n" .
	'<!-- wp:paragraph -->' . "\n" .
	'<p>Real multi-field filter form: keyword plus category and tag. Submit filters the main query (Search &amp; Filter free redirects with those GET params).</p>' . "\n" .
	'<!-- /wp:paragraph -->' . "\n\n" .
	'<!-- wp:shortcode -->' . "\n" .
	$sf_shortcode . "\n" .
	'<!-- /wp:shortcode -->' . "\n\n" .
	siwmfa_uc3_posts_query_blocks();

$sf_page_id = siwmfa_uc3_upsert_page( 'search-filter-lab-uc3', 'Search Filter Lab UC3', $sf_content );

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

$home_url = home_url( '/' );
$fe_content = '<!-- wp:heading -->' . "\n" .
	'<h2 class="wp-block-heading">Filter Everything lab</h2>' . "\n" .
	'<!-- /wp:heading -->' . "\n\n" .
	'<!-- wp:paragraph -->' . "\n" .
	'<p>Free Filter Everything only attaches the set to post archives / the posts index (location <code>1</code>), not to this singular page. Validate in Chrome on the <a href="' . esc_url( $home_url ) . '">site home</a> (latest posts): Categories + Tags facets, plus the Search GET form (<code>srch</code>) that WebMCP annotates.</p>' . "\n" .
	'<!-- /wp:paragraph -->' . "\n\n" .
	'<!-- wp:paragraph -->' . "\n" .
	'<p>Filter set ID: <strong>' . (int) $fe_set_id . '</strong>. Taxonomy facets are links/AJAX (filters in general). The annotatable form field is keyword search.</p>' . "\n" .
	'<!-- /wp:paragraph -->' . "\n\n" .
	siwmfa_uc3_posts_query_blocks();
$fe_page_id = siwmfa_uc3_upsert_page( 'filter-everything-lab-uc3', 'Filter Everything Lab UC3', $fe_content );

// Smoke: shortcodes produce expected markup.
$ok_sf = true;
if ( shortcode_exists( 'searchandfilter' ) ) {
	$sf_html = do_shortcode( $sf_shortcode );
	// wp_dropdown_categories may emit name='ofcategory' or name="ofcategory".
	$ok_sf   = is_string( $sf_html )
		&& false !== stripos( $sf_html, '<form' )
		&& false !== strpos( $sf_html, 'toolname="search_and_filter"' )
		&& (bool) preg_match( '/name=(["\'])' . preg_quote( $prefix . 'category', '/' ) . '\1/', $sf_html )
		&& (bool) preg_match( '/name=(["\'])' . preg_quote( $prefix . 'post_tag', '/' ) . '\1/', $sf_html )
		&& false !== stripos( $sf_html, 'Guides' )
		&& false !== stripos( $sf_html, 'WebMCP' );
}

// FE widget only renders on the posts index (see mu-plugin); CLI shortcode smoke is not representative.
$ok_fe = (bool) $fe_set_id && (bool) get_posts(
	array(
		'post_type'      => 'filter-field',
		'post_parent'    => $fe_set_id,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	)
);

WP_CLI::log( 'posts=' . implode( ',', $created_posts ) );
WP_CLI::log( 'cats=guides:' . $cat_guides . ',news:' . $cat_news . ',tutorials:' . $cat_tutorials );
WP_CLI::log( 'sf_page_id=' . $sf_page_id );
WP_CLI::log( 'sf_url=' . get_permalink( $sf_page_id ) );
WP_CLI::log( 'fe_set_id=' . $fe_set_id );
WP_CLI::log( 'fe_page_id=' . $fe_page_id );
WP_CLI::log( 'fe_url=' . get_permalink( $fe_page_id ) );
WP_CLI::log( 'searchfilter_ok=' . ( $ok_sf ? 'yes' : 'no' ) );
WP_CLI::log( 'filtereverything_ok=' . ( $ok_fe ? 'yes' : 'no' ) );

if ( $ok_sf && $ok_fe ) {
	WP_CLI::success( 'UC3 filter lab content ready.' );
} else {
	WP_CLI::warning( 'Lab content saved; front smoke partial — check FE widget on the page in Chrome.' );
}
