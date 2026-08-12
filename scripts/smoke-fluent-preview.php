<?php
/**
 * Assert preview seed: Fluent form annotated on /contact/.
 *
 * @package Siwmfa
 */

$form_id = (int) get_option( 'siwmfa_preview_fluent_form_id', 0 );
$saved   = get_option( 'siwmfa_forms', array() );
$key     = 'fluent:' . $form_id;

if ( $form_id <= 0 || ! is_array( $saved ) || empty( $saved[ $key ]['enabled'] ) ) {
	fwrite( STDERR, "SEED_FAIL registry\n" );
	exit( 1 );
}

$page = get_page_by_path( 'contact' );
if ( ! $page instanceof WP_Post ) {
	fwrite( STDERR, "SEED_FAIL page\n" );
	exit( 1 );
}

$html = do_shortcode( '[fluentform id="' . $form_id . '"]' );
$ok   = ( false !== strpos( $html, 'toolname' ) )
	&& ( false !== strpos( $html, 'submit_contact' ) )
	&& ( false !== strpos( $html, 'tooldescription' ) )
	&& ( false !== strpos( $html, 'toolparamdescription' ) )
	&& ( false === strpos( $html, 'toolautosubmit' ) );

if ( ! $ok ) {
	fwrite( STDERR, "SEED_FAIL markup\n" );
	exit( 1 );
}

$others = 0;
foreach ( array_keys( $saved ) as $row_key ) {
	if ( 0 !== strpos( (string) $row_key, 'fluent:' ) ) {
		++$others;
	}
}

echo 'form_id=' . $form_id . ' page_id=' . (int) $page->ID . ' others=' . $others . "\n";
echo "SMOKE_OK\n";
