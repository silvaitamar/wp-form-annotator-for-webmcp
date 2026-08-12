<?php
/**
 * Creates a minimal WPForms form for Phase C smoke, if none exist.
 */

$users = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( isset( $users[0] ) && $users[0] instanceof WP_User ) {
	wp_set_current_user( (int) $users[0]->ID );
}

$existing = get_posts(
	array(
		'post_type'      => 'wpforms',
		'posts_per_page' => 1,
		'post_status'    => 'any',
	)
);
if ( array() !== $existing ) {
	echo 'wpforms existing=' . (int) $existing[0]->ID . "\n";
	return;
}

if ( ! function_exists( 'wpforms' ) ) {
	echo "wpforms unavailable\n";
	return;
}

$handler = null;
$inst    = wpforms();
if ( is_object( $inst ) && method_exists( $inst, 'obj' ) ) {
	$handler = $inst->obj( 'form' );
}
if ( null === $handler && is_object( $inst ) && isset( $inst->form ) ) {
	$handler = $inst->form;
}

$data = array(
	'fields'   => array(
		'1' => array(
			'id'       => '1',
			'type'     => 'text',
			'label'    => 'Name',
			'required' => '1',
			'size'     => 'medium',
		),
		'2' => array(
			'id'       => '2',
			'type'     => 'email',
			'label'    => 'Email',
			'required' => '1',
			'size'     => 'medium',
		),
		'3' => array(
			'id'       => '3',
			'type'     => 'textarea',
			'label'    => 'Message',
			'required' => '1',
			'size'     => 'medium',
		),
	),
	'field_id' => '4',
	'settings' => array(
		'form_title'  => 'WebMCP Contact',
		'submit_text' => 'Send',
	),
);

$encoded = function_exists( 'wpforms_encode' ) ? wpforms_encode( $data ) : wp_json_encode( $data );

if ( is_object( $handler ) && method_exists( $handler, 'add' ) ) {
	$created = $handler->add(
		'WebMCP Contact',
		array(
			'post_content' => $encoded,
		)
	);
	echo 'created_via_api=';
	var_export( $created );
	echo "\n";
	if ( $created ) {
		return;
	}
}

$created = wp_insert_post(
	array(
		'post_type'    => 'wpforms',
		'post_status'  => 'publish',
		'post_title'   => 'WebMCP Contact',
		'post_content' => $encoded,
	),
	true
);
echo 'created_via_insert=';
var_export( $created );
echo "\n";
