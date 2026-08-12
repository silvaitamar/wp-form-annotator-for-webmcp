<?php
/**
 * Smoke Phase C: installed builders only (no native product form).
 *
 * Usage: studio wp eval-file wp-content/plugins/silvaitamar-webmcp-form-annotator/scripts/smoke-phase-c.php
 */

$stored = get_option( 'siwmfa_forms', array() );
if ( ! is_array( $stored ) ) {
	$stored = array();
}

unset( $stored['native:1'] );

$stale = get_page_by_path( 'contato-nativo-webmcp' );
if ( $stale instanceof WP_Post ) {
	wp_delete_post( (int) $stale->ID, true );
}

$results = array();
$failed  = false;

update_option( 'siwmfa_forms', $stored, false );

if ( function_exists( 'wpforms' ) && post_type_exists( 'wpforms' ) ) {
	$forms = get_posts(
		array(
			'post_type'      => 'wpforms',
			'posts_per_page' => 1,
			'post_status'    => 'any',
		)
	);
	if ( isset( $forms[0] ) && $forms[0] instanceof WP_Post ) {
		$fid = (int) $forms[0]->ID;
		$stored[ 'wpforms:' . $fid ] = array(
			'enabled'         => true,
			'toolname'        => 'submit_contact',
			'tooldescription' => 'Fills the WPForms contact form. Do not submit — only fill the fields.',
			'params'          => array(
				'wpforms[fields][1]' => 'Full name of the visitor.',
				'wpforms[fields][2]' => 'Email address for a reply.',
				'wpforms[fields][3]' => 'Message body.',
			),
		);
		update_option( 'siwmfa_forms', $stored, false );
		$post = get_post( $fid );
		if ( $post instanceof WP_Post && function_exists( 'wpforms_decode' ) && function_exists( 'wpforms_encode' ) ) {
			$data = wpforms_decode( $post->post_content );
			if ( is_array( $data ) && empty( $data['id'] ) ) {
				$data['id'] = $fid;
				wp_update_post(
					array(
						'ID'           => $fid,
						'post_content' => wpforms_encode( $data ),
					)
				);
			}
		}
		$markup = do_shortcode( '[wpforms id="' . $fid . '"]' );
		$w_ok   = false !== strpos( $markup, 'toolname="submit_contact"' )
			&& false !== strpos( $markup, 'toolparamdescription="' );
		$results[] = sprintf(
			'wpforms form_id=%d toolname=%s params=%s',
			$fid,
			false !== strpos( $markup, 'toolname="submit_contact"' ) ? 'yes' : 'NO',
			false !== strpos( $markup, 'toolparamdescription="' ) ? 'yes' : 'NO'
		);
		if ( ! $w_ok ) {
			$failed = true;
		}
	} else {
		$results[] = 'wpforms SKIP (no form)';
	}
} else {
	$results[] = 'wpforms SKIP (inactive)';
}

if ( class_exists( 'Forminator_API' ) ) {
	$forms = Forminator_API::get_forms( null, 1, 5 );
	$first = ( is_array( $forms ) && isset( $forms[0] ) && is_object( $forms[0] ) ) ? $forms[0] : null;
	if ( $first && isset( $first->id ) ) {
		$fid = (int) $first->id;
		$stored[ 'forminator:' . $fid ] = array(
			'enabled'         => true,
			'toolname'        => 'submit_contact',
			'tooldescription' => 'Fills the Forminator contact form. Do not submit — only fill the fields.',
			'params'          => array(),
		);
		update_option( 'siwmfa_forms', $stored, false );
		$markup = do_shortcode( '[forminator_form id="' . $fid . '"]' );
		$f_ok   = false !== strpos( $markup, 'toolname="submit_contact"' );
		$results[] = sprintf( 'forminator form_id=%d toolname=%s', $fid, $f_ok ? 'yes' : 'NO' );
		if ( ! $f_ok ) {
			$failed = true;
		}
	} else {
		$results[] = 'forminator SKIP (no form)';
	}
} else {
	$results[] = 'forminator SKIP (inactive)';
}

if ( class_exists( 'Ninja_Forms' ) ) {
	$results[] = 'ninja JS-only (nfFormReady) — catalog=' . ( function_exists( 'Ninja_Forms' ) ? 'api-ok' : 'no-fn' );
}

if ( defined( 'SRFM_VER' ) || defined( 'SRFM_FORMS_POST_TYPE' ) ) {
	$pt    = defined( 'SRFM_FORMS_POST_TYPE' ) ? SRFM_FORMS_POST_TYPE : 'sureforms';
	$posts = get_posts(
		array(
			'post_type'      => $pt,
			'posts_per_page' => 1,
		)
	);
	if ( isset( $posts[0] ) && $posts[0] instanceof WP_Post ) {
		$fid = (int) $posts[0]->ID;
		$stored[ 'sureforms:' . $fid ] = array(
			'enabled'         => true,
			'toolname'        => 'submit_contact',
			'tooldescription' => 'Fills the SureForms contact form. Do not submit — only fill the fields.',
			'params'          => array(),
		);
		update_option( 'siwmfa_forms', $stored, false );
		$markup = do_shortcode( '[sureforms id="' . $fid . '"]' );
		$s_ok   = false !== strpos( $markup, 'toolname="submit_contact"' );
		$results[] = sprintf( 'sureforms form_id=%d toolname=%s', $fid, $s_ok ? 'yes' : 'NO' );
		if ( ! $s_ok ) {
			$failed = true;
		}
	} else {
		$results[] = 'sureforms SKIP (no form)';
	}
} else {
	$results[] = 'sureforms SKIP (inactive)';
}

echo implode( "\n", $results ) . "\n";

if ( $failed ) {
	fwrite( STDERR, "SMOKE_FAIL\n" );
	exit( 1 );
}

echo "SMOKE_OK\n";
