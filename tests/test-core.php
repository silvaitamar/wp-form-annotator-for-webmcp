<?php
/**
 * WordPress-free tests for Annotator and Registry helpers.
 *
 * Run: php tests/test-core.php
 *
 * @package Siwmfa
 */

define( 'ABSPATH', __DIR__ . '/' );

/**
 * Minimal esc_attr() stand-in.
 *
 * @param string $text Text.
 * @return string
 */
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * Minimal sanitize_text_field() stand-in.
 *
 * @param string $str String.
 * @return string
 */
function sanitize_text_field( $str ) {
	return trim( strip_tags( (string) $str ) );
}

/**
 * Minimal sanitize_textarea_field() stand-in.
 *
 * @param string $str String.
 * @return string
 */
function sanitize_textarea_field( $str ) {
	return trim( strip_tags( (string) $str ) );
}

require dirname( __DIR__ ) . '/src/Annotator.php';
require dirname( __DIR__ ) . '/src/Registry.php';

$siwmfa_failed = 0;
$siwmfa_passed = 0;

/**
 * Records one assertion.
 *
 * @param bool   $ok      Whether the check passed.
 * @param string $message Description.
 * @return void
 */
function siwmfa_assert( bool $ok, string $message ): void {
	global $siwmfa_failed, $siwmfa_passed;

	if ( $ok ) {
		++$siwmfa_passed;
		echo "ok  {$message}\n";
		return;
	}

	++$siwmfa_failed;
	echo "FAIL {$message}\n";
}

$html = '<form method="post"><input type="text" name="email"><input type="hidden" name="secret" value="x"><textarea name="message"></textarea></form>';

$config = array(
	'toolname'        => 'submit_contact',
	'tooldescription' => 'Fills the contact form. Do not submit.',
	'params'          => array(
		'email'   => 'Visitor email',
		'secret'  => 'Must not appear',
		'message' => 'Message body',
	),
);

$attrs = \Siwmfa\Annotator::form_attributes( $config );
siwmfa_assert( isset( $attrs['toolname'] ) && 'submit_contact' === $attrs['toolname'], 'form_attributes keeps toolname' );
siwmfa_assert( isset( $attrs['tooldescription'] ), 'form_attributes keeps tooldescription' );
siwmfa_assert( array() === \Siwmfa\Annotator::form_attributes( array( 'toolname' => '', 'tooldescription' => '' ) ), 'form_attributes skips empty values' );

$with_form = \Siwmfa\Annotator::inject_form_tag( $html, $config );
siwmfa_assert( false !== strpos( $with_form, 'toolname="submit_contact"' ), 'inject_form_tag adds toolname' );
siwmfa_assert( false !== strpos( $with_form, 'tooldescription="' ), 'inject_form_tag adds tooldescription' );
siwmfa_assert( 1 === substr_count( strtolower( $with_form ), 'toolname=' ), 'inject_form_tag does not duplicate' );

$again = \Siwmfa\Annotator::inject_form_tag( $with_form, $config );
siwmfa_assert( $again === $with_form, 'inject_form_tag is idempotent when toolname exists' );

$with_params = \Siwmfa\Annotator::inject_param_attrs( $with_form, $config['params'] );
siwmfa_assert( false !== strpos( $with_params, 'name="email" toolparamdescription="Visitor email"' ), 'inject_param_attrs annotates text input' );
siwmfa_assert( false !== strpos( $with_params, 'toolparamdescription="Message body"' ), 'inject_param_attrs annotates textarea' );
siwmfa_assert( false === strpos( $with_params, 'name="secret" toolparamdescription' ), 'inject_param_attrs skips hidden inputs' );

$wpforms_html = '<form><input type="text" name="wpforms[fields][1]"></form>';
$wpforms_out  = \Siwmfa\Annotator::inject_param_attrs(
	$wpforms_html,
	array( 'wpforms[fields][1]' => 'Full name' )
);
siwmfa_assert( false !== strpos( $wpforms_out, 'toolparamdescription="Full name"' ), 'inject_param_attrs matches bracket field names' );

siwmfa_assert( 'cf7:6' === \Siwmfa\Registry::make_key( 'cf7', 6 ), 'make_key joins builder and id' );
siwmfa_assert( array( 'builder' => 'fluent', 'id' => 4 ) === \Siwmfa\Registry::parse_key( 'fluent:4' ), 'parse_key accepts fluent:4' );
siwmfa_assert( null === \Siwmfa\Registry::parse_key( 'native:1' ), 'parse_key rejects unknown builder' );
siwmfa_assert( null === \Siwmfa\Registry::parse_key( 'cf7:nope' ), 'parse_key rejects non-numeric id' );

siwmfa_assert( 'submit_hello_world' === \Siwmfa\Registry::sanitize_toolname( 'Submit Hello World!' ), 'sanitize_toolname snake_cases' );
siwmfa_assert( 'submit_contact' === \Siwmfa\Registry::suggest_toolname( 'Contact' ), 'suggest_toolname prefixes submit_' );
siwmfa_assert( 'send_quote' === \Siwmfa\Registry::suggest_toolname( 'send_quote' ), 'suggest_toolname keeps send_ prefix' );
siwmfa_assert( 'submit_form' === \Siwmfa\Registry::suggest_toolname( '!!!' ), 'suggest_toolname falls back' );

$normalized = \Siwmfa\Registry::normalize(
	array(
		'enabled'         => '1',
		'toolname'        => 'Submit Contact',
		'tooldescription' => "Hello\nworld",
		'params'          => array(
			'email' => 'Visitor email',
			''      => 'skip',
			3       => 'skip-int-key',
		),
	)
);
siwmfa_assert( true === $normalized['enabled'], 'normalize casts enabled' );
siwmfa_assert( 'submit_contact' === $normalized['toolname'], 'normalize sanitizes toolname' );
siwmfa_assert( isset( $normalized['params']['email'] ), 'normalize keeps string param keys' );
siwmfa_assert( ! isset( $normalized['params'][''] ), 'normalize drops empty param keys' );

echo "\n{$siwmfa_passed} passed, {$siwmfa_failed} failed\n";
exit( $siwmfa_failed > 0 ? 1 : 0 );
