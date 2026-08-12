<?php
/**
 * Generate WordPress.org icon/banner PNGs (no display name on the art).
 *
 * Usage: php scripts/generate-wporg-assets.php
 */

declare(strict_types=1);

if ( ! function_exists( 'imagecreatetruecolor' ) ) {
	$python = dirname( __DIR__ ) . '/scripts/generate-wporg-assets.py';
	passthru( 'python3 ' . escapeshellarg( $python ), $code );
	exit( $code );
}

$root = dirname( __DIR__ ) . '/.wordpress-org';
if ( ! is_dir( $root ) ) {
	mkdir( $root, 0755, true );
}

/**
 * @param int $size Icon edge.
 */
function siwmfa_icon( int $size ): GdImage {
	$im   = imagecreatetruecolor( $size, $size );
	$bg   = imagecolorallocate( $im, 11, 61, 74 );
	$gold = imagecolorallocate( $im, 240, 180, 41 );
	$line = imagecolorallocate( $im, 232, 244, 247 );
	imagefilledrectangle( $im, 0, 0, $size, $size, $bg );

	$pad  = (int) round( $size * 0.18 );
	$box  = $size - ( 2 * $pad );
	$x1   = $pad;
	$y1   = $pad;
	$x2   = $pad + $box;
	$y2   = $pad + $box;
	imagesetthickness( $im, max( 2, (int) round( $size / 32 ) ) );
	imagerectangle( $im, $x1, $y1, $x2, $y2, $line );

	$ly     = (int) round( $y1 + $box * 0.28 );
	$gap    = (int) round( $box * 0.18 );
	$lx1    = $x1 + (int) round( $box * 0.14 );
	$lx2    = $x2 - (int) round( $box * 0.14 );
	$thick  = max( 2, (int) round( $size / 28 ) );
	imagesetthickness( $im, $thick );
	for ( $i = 0; $i < 3; $i++ ) {
		$y = $ly + ( $i * $gap );
		imageline( $im, $lx1, $y, $lx2, $y, $i === 0 ? $gold : $line );
	}

	$spark = (int) round( $size * 0.09 );
	imagefilledellipse( $im, $x2 - $spark, $y1 + $spark, $spark * 2, $spark * 2, $gold );

	return $im;
}

/**
 * @param int $w Width.
 * @param int $h Height.
 */
function siwmfa_banner( int $w, int $h ): GdImage {
	$im   = imagecreatetruecolor( $w, $h );
	$bg   = imagecolorallocate( $im, 11, 61, 74 );
	$gold = imagecolorallocate( $im, 240, 180, 41 );
	$line = imagecolorallocate( $im, 232, 244, 247 );
	$dim  = imagecolorallocate( $im, 18, 82, 96 );
	imagefilledrectangle( $im, 0, 0, $w, $h, $bg );
	imagefilledrectangle( $im, 0, 0, (int) round( $w * 0.38 ), $h, $dim );

	$pad  = (int) round( $h * 0.22 );
	$boxw = (int) round( $w * 0.22 );
	$x1   = (int) round( $w * 0.08 );
	$y1   = $pad;
	$x2   = $x1 + $boxw;
	$y2   = $h - $pad;
	imagesetthickness( $im, max( 3, (int) round( $h / 40 ) ) );
	imagerectangle( $im, $x1, $y1, $x2, $y2, $line );

	$ly    = $y1 + (int) round( ( $y2 - $y1 ) * 0.28 );
	$gap   = (int) round( ( $y2 - $y1 ) * 0.18 );
	$lx1   = $x1 + (int) round( $boxw * 0.14 );
	$lx2   = $x2 - (int) round( $boxw * 0.14 );
	imagesetthickness( $im, max( 3, (int) round( $h / 28 ) ) );
	for ( $i = 0; $i < 3; $i++ ) {
		$y = $ly + ( $i * $gap );
		imageline( $im, $lx1, $y, $lx2, $y, $i === 0 ? $gold : $line );
	}

	$spark = (int) round( $h * 0.08 );
	imagefilledellipse( $im, $x2 - $spark, $y1 + $spark, $spark * 2, $spark * 2, $gold );

	return $im;
}

function siwmfa_save( GdImage $im, string $path ): void {
	imagepng( $im, $path, 9 );
	imagedestroy( $im );
	echo $path . "\n";
}

siwmfa_save( siwmfa_icon( 128 ), $root . '/icon-128x128.png' );
siwmfa_save( siwmfa_icon( 256 ), $root . '/icon-256x256.png' );
siwmfa_save( siwmfa_banner( 772, 250 ), $root . '/banner-772x250.png' );
siwmfa_save( siwmfa_banner( 1544, 500 ), $root . '/banner-1544x500.png' );

echo "OK\n";
