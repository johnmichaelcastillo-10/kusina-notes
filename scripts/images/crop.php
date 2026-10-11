<?php
/**
 * Centre-crops an image to 3:2 and writes a 1200×800 WebP (q80), or smaller when the
 * source is smaller (never upscaled past 960 wide).
 *
 *   php scripts/images/crop.php <source.jpg|png|webp> <slug>
 *
 * Writes scripts/images/<slug>.webp. Prints the output size.
 */

if ( $argc < 3 || ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $argv[2] ) ) {
	fwrite( STDERR, "usage: php crop.php <source> <slug>\n" );
	exit( 1 );
}

$data = file_get_contents( $argv[1] );
$src  = $data === false ? false : @imagecreatefromstring( $data );
if ( ! $src ) {
	fwrite( STDERR, "not an image: {$argv[1]}\n" );
	exit( 1 );
}

$w = imagesx( $src );
$h = imagesy( $src );
if ( $w < 900 ) {
	fwrite( STDERR, "too small ({$w}x{$h}): need at least 900 px wide\n" );
	exit( 1 );
}

// Largest 3:2 box in the middle of the frame.
if ( $w / $h > 1.5 ) {
	$cw = (int) round( $h * 1.5 );
	$ch = $h;
} else {
	$cw = $w;
	$ch = (int) round( $w / 1.5 );
}
$x = (int) floor( ( $w - $cw ) / 2 );
$y = (int) floor( ( $h - $ch ) / 2 );

$out_w = min( 1200, $cw );
$out_h = (int) round( $out_w / 1.5 );
$dst   = imagecreatetruecolor( $out_w, $out_h );
imagecopyresampled( $dst, $src, 0, 0, $x, $y, $out_w, $out_h, $cw, $ch );

$file = __DIR__ . '/' . $argv[2] . '.webp';
imagewebp( $dst, $file, 80 );
echo $file . ' ' . $out_w . 'x' . $out_h . ' ' . filesize( $file ) . " bytes\n";
