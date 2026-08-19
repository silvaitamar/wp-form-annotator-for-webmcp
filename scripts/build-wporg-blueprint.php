<?php
/**
 * Write `.wordpress-org/blueprints/blueprint.json` for the wp.org Live Preview.
 *
 * Usage: php scripts/build-wporg-blueprint.php
 *
 * @package Siwmfa
 */

declare(strict_types=1);

$root = dirname( __DIR__ );
$seed = file_get_contents( $root . '/scripts/playground-seed.php' );
if ( false === $seed ) {
	fwrite( STDERR, "Missing scripts/playground-seed.php\n" );
	exit( 1 );
}

$seed = preg_replace( '/^\xEF\xBB\xBF/', '', $seed );
$seed = str_replace( "\r\n", "\n", $seed );
$seed = preg_replace( '/^<\?php\s*/', '', $seed );

// Playground runPHP may already have WordPress loaded. Force the preview
// homepage/login demo path regardless of ABSPATH.
$wrapped  = "<?php\n";
$wrapped .= "\$siwmfa_playground = true;\n";
$wrapped .= "if ( ! defined( 'ABSPATH' ) ) {\n";
$wrapped .= "\trequire_once 'wordpress/wp-load.php';\n";
$wrapped .= "}\n";
$wrapped .= $seed;
$seed     = $wrapped;

$blueprint = array(
	'$schema'           => 'https://playground.wordpress.net/blueprint-schema.json',
	'meta'              => array(
		'title'       => 'SilvaItamar Form Annotator for WebMCP',
		'description' => 'Logged-in admin, Fluent Forms contact page already annotated with WebMCP. Open Settings → Form Annotator to edit.',
		'author'      => 'silvaitamar',
		'categories'  => array( 'webmcp', 'forms', 'ai' ),
	),
	'landingPage'       => '/',
	'preferredVersions' => array(
		'php' => '8.2',
		'wp'  => 'latest',
	),
	'phpExtensionBundles' => array( 'kitchen-sink' ),
	'features'            => array(
		'networking' => true,
	),
	'steps'               => array(
		array(
			'step'     => 'login',
			'username' => 'admin',
			'password' => 'password',
		),
		array(
			'step'       => 'installPlugin',
			'pluginData' => array(
				'resource' => 'wordpress.org/plugins',
				'slug'     => 'fluentform',
			),
			'options'    => array(
				'activate' => true,
			),
			'progress'   => array(
				'caption' => 'Installing Fluent Forms',
			),
		),
		array(
			'step'     => 'runPHP',
			'code'     => $seed,
			'progress' => array(
				'caption' => 'Creating the annotated contact form',
			),
		),
	),
);

$out  = $root . '/.wordpress-org/blueprints/blueprint.json';
$json = json_encode( $blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
if ( false === $json ) {
	fwrite( STDERR, "json_encode failed\n" );
	exit( 1 );
}

file_put_contents( $out, $json . "\n" );
echo $out . "\n";
