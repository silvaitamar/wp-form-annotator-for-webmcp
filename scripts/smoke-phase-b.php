<?php
/**
 * Smoke Phase B: enable default CF7 form and assert WebMCP attrs in HTML.
 *
 * Usage: studio wp eval-file wp-content/plugins/silvaitamar-webmcp-form-annotator/scripts/smoke-phase-b.php
 */

if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
	fwrite( STDERR, "CF7 not active\n" );
	exit( 1 );
}

$posts = get_posts(
	array(
		'post_type'      => 'wpcf7_contact_form',
		'posts_per_page' => 1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

if ( array() === $posts ) {
	fwrite( STDERR, "No CF7 form found\n" );
	exit( 1 );
}

$form_id = (int) $posts[0]->ID;
$key     = 'cf7:' . $form_id;

update_option(
	'siwmfa_forms',
	array(
		$key => array(
			'enabled'         => true,
			'toolname'        => 'submit_contact',
			'tooldescription' => 'Fills the contact form on this page. Do not submit — only fill the fields.',
			'params'          => array(
				'your-name'    => 'Full name of the visitor.',
				'your-email'   => 'Email address for a reply.',
				'your-subject' => 'Short subject line.',
				'your-message' => 'Message body.',
			),
		),
	),
	false
);

$page = get_page_by_path( 'contato-webmcp' );
$content = sprintf( '[contact-form-7 id="%d"]', $form_id );
if ( $page instanceof WP_Post ) {
	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => $content,
			'post_status'  => 'publish',
		)
	);
	$page_id = (int) $page->ID;
} else {
	$page_id = (int) wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_title'   => 'Contato WebMCP',
			'post_name'    => 'contato-webmcp',
			'post_status'  => 'publish',
			'post_content' => $content,
		),
		true
	);
}

$html = apply_filters( 'the_content', $content );

$ok_form  = false !== strpos( $html, 'toolname="submit_contact"' );
$ok_desc  = false !== strpos( $html, 'tooldescription="' );
$ok_param = false !== strpos( $html, 'toolparamdescription="' );

printf(
	"form_id=%d page_id=%d toolname=%s tooldescription=%s toolparamdescription=%s\n",
	$form_id,
	$page_id,
	$ok_form ? 'yes' : 'NO',
	$ok_desc ? 'yes' : 'NO',
	$ok_param ? 'yes' : 'NO'
);

if ( ! $ok_form || ! $ok_desc || ! $ok_param ) {
	fwrite( STDERR, substr( $html, 0, 1200 ) . "\n" );
	exit( 1 );
}

echo "SMOKE_OK\n";
