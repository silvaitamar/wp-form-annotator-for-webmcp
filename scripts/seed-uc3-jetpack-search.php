<?php
/**
 * Seed a synced Jetpack form + page, enable annotations, print HTML checks.
 *
 * @package Siwmfa
 */

defined( 'ABSPATH' ) || exit;

$content = <<<'HTML'
<!-- wp:jetpack/contact-form -->
<div class="wp-block-jetpack-contact-form">
<!-- wp:jetpack/field-name {"required":true,"id":"name","label":"Name"} /-->
<!-- wp:jetpack/field-email {"required":true,"id":"email","label":"Email"} /-->
<!-- wp:jetpack/field-textarea {"id":"message","label":"Message"} /-->
<!-- wp:button {"tagName":"button","type":"submit"} -->
<div class="wp-block-button"><button type="submit" class="wp-block-button__link wp-element-button">Submit</button></div>
<!-- /wp:button -->
</div>
<!-- /wp:jetpack/contact-form -->
HTML;

$form_id = wp_insert_post(
	array(
		'post_type'    => 'jetpack_form',
		'post_status'  => 'publish',
		'post_title'   => 'WebMCP Lab Contact UC3',
		'post_content' => $content,
	),
	true
);

if ( is_wp_error( $form_id ) ) {
	WP_CLI::error( $form_id->get_error_message() );
}

$page_content = sprintf(
	'<!-- wp:jetpack/contact-form {"ref":%d} /-->',
	(int) $form_id
);

$page_id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Jetpack Form Lab UC3',
		'post_name'    => 'jetpack-form-lab-uc3',
		'post_content' => $page_content,
	),
	true
);

if ( is_wp_error( $page_id ) ) {
	WP_CLI::error( $page_id->get_error_message() );
}

$key = \Siwmfa\Registry::make_key( 'jetpack', (int) $form_id );
\Siwmfa\Registry::save_one(
	$key,
	array(
		'enabled'         => true,
		'toolname'        => 'submit_contact',
		'tooldescription' => 'Fills the contact form on this page. Do not submit.',
		'params'          => array(
			'name'    => 'Full name',
			'email'   => 'E-mail',
			'message' => 'Message',
		),
	)
);

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

$listed = \Siwmfa\Adapters\Jetpack_Forms::list_forms();
$found  = false;
foreach ( $listed as $row ) {
	if ( (int) $row['id'] === (int) $form_id ) {
		$found = true;
		break;
	}
}

// Filter-level check (WP-CLI is not Request::is_frontend()).
\Automattic\Jetpack\Forms\ContactForm\Contact_Form::set_ref_id( (int) $form_id );
$filtered = apply_filters(
	'jetpack_contact_form_html',
	'<form method="post"><input type="email" name="email" /></form>'
);
$field_filtered = apply_filters(
	'grunion_contact_form_field_html',
	'<input type="email" name="email" />',
	'Email',
	null
);
\Automattic\Jetpack\Forms\ContactForm\Contact_Form::clear_ref_id( (int) $form_id );

$search = get_search_form( false );

$ok_list   = $found;
$ok_form   = is_string( $filtered ) && false !== strpos( $filtered, 'toolname="submit_contact"' );
$ok_param  = is_string( $field_filtered ) && false !== strpos( $field_filtered, 'toolparamdescription="E-mail"' );
$ok_search = is_string( $search ) && false !== strpos( $search, 'toolname="search_site"' ) && false !== strpos( $search, 'toolautosubmit="true"' );

WP_CLI::log( 'jetpack_form_id=' . (int) $form_id );
WP_CLI::log( 'page_id=' . (int) $page_id );
WP_CLI::log( 'page_url=' . get_permalink( $page_id ) );
WP_CLI::log( 'jetpack_listed=' . ( $ok_list ? 'yes' : 'no' ) );
WP_CLI::log( 'jetpack_toolname=' . ( $ok_form ? 'yes' : 'no' ) );
WP_CLI::log( 'jetpack_param=' . ( $ok_param ? 'yes' : 'no' ) );
WP_CLI::log( 'search_attrs=' . ( $ok_search ? 'yes' : 'no' ) );

if ( ! $ok_list || ! $ok_form || ! $ok_param || ! $ok_search ) {
	WP_CLI::error( 'Annotation checks failed.' );
}

WP_CLI::success( 'Jetpack + search annotation OK.' );
