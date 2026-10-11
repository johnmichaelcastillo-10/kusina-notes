<?php
/**
 * Centre-crops an image to a ratio (default 4:5, the theme's photo shape) and writes a WebP
 * (q80) at most 1200 px wide; never upscales.
 *
 *   php scripts/images/crop.php <source.jpg|png|webp> <slug> [ratio=4:5]
 *
 * Writes scripts/images/<slug>.webp. Prints the output size.
 */

if ( $argc < 3 || ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $argv[2] ) ) {
	fwrite( STDERR, "usage: php crop.php <source> <slug> [w:h]\n" );
	exit( 1 );
}

[ $rw, $rh ] = array_map( 'floatval', explode( ':', $argv[3] ?? '4:5' ) ) + array( 4, 5 );
$ratio       = $rw / $rh;

$data = file_get_contents( $argv[1] );
$src  = false === $data ? false : @imagecreatefromstring( $data );
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

// Largest box of the target ratio in the middle of the frame.
if ( $w / $h > $ratio ) {
	$cw = (int) round( $h * $ratio );
	$ch = $h;
} else {
	$cw = $w;
	$ch = (int) round( $w / $ratio );
}
$x = (int) floor( ( $w - $cw ) / 2 );
$y = (int) floor( ( $h - $ch ) / 2 );

$out_w = min( 1200, $cw );
$out_h = (int) round( $out_w / $ratio );
$dst   = imagecreatetruecolor( $out_w, $out_h );
imagecopyresampled( $dst, $src, 0, 0, $x, $y, $out_w, $out_h, $cw, $ch );

$file = __DIR__ . '/' . $argv[2] . '.webp';
imagewebp( $dst, $file, 80 );
echo $file . ' ' . $out_w . 'x' . $out_h . ' ' . filesize( $file ) . " bytes\n";
