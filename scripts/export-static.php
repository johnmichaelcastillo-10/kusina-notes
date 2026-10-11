<?php
/**
 * Exports the running local WordPress site to plain static files in dist/, ready for
 * Vercel (or any static host). Runs on the host's PHP, not in Docker:
 *
 *   php scripts/export-static.php [--url=https://kusina-notes.vercel.app] [--source=http://localhost:8092] [--out=dist]
 *
 * Adapted from jmcastillo-portfolio/scripts/export-static.php. What it does:
 * - Starts from the home page, the tool pages and the WordPress sitemap, then follows every
 *   internal link it finds (pagination included).
 * - Writes the recipe index (/wp-json/kusina/v1/index) to /recipes.json, points the pantry
 *   finder at it, and exports the thumbnails it references (no page links to those).
 * - Downloads every asset the pages reference (CSS, JS, fonts, images), including url(...)
 *   references inside CSS.
 * - Rewrites the local site URL to --url (absolute, for canonical, og and JSON-LD) or, without
 *   --url, to root-relative paths for local previews.
 * - Drops head links that point at things a static site doesn't have (REST API, feeds, RSD).
 * - Copies static/ (vercel.json) into the output and adds a Content-Security-Policy header
 *   that allows only the site's own inline scripts (by hash).
 * - Fails, so nothing broken gets published, if a page has a PHP warning, an inline event
 *   handler, or a leftover link to the local site.
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$opts   = getopt( '', array( 'source::', 'url::', 'out::' ) );
$source = rtrim( $opts['source'] ?? 'http://localhost:8092', '/' );
$target = rtrim( $opts['url'] ?? '', '/' ); // '' = root-relative output
$out    = $opts['out'] ?? dirname( __DIR__ ) . '/dist';

$src_parts = parse_url( $source );
$src_host  = $src_parts['host'] . ( isset( $src_parts['port'] ) ? ':' . $src_parts['port'] : '' );

/** GET a URL. Returns [status, body, content-type]. */
function fetch( string $url ): array {
	$ch = curl_init( $url );
	curl_setopt_array(
		$ch,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_TIMEOUT        => 60,
			CURLOPT_USERAGENT      => 'kusina-static-export/1.0',
		)
	);
	$body   = curl_exec( $ch );
	$status = curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
	$type   = (string) curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
	return array( $status, false === $body ? '' : $body, $type );
}

/** Path part of an internal URL, or null if the URL isn't on the local site. */
function internal_path( string $url, string $source, string $src_host ): ?string {
	$url = html_entity_decode( trim( $url ), ENT_QUOTES );
	if ( '' === $url || str_starts_with( $url, '#' ) || str_starts_with( $url, 'data:' )
		|| preg_match( '#^(mailto|tel|javascript):#i', $url ) ) {
		return null;
	}
	if ( str_starts_with( $url, '//' ) ) {
		$url = 'http:' . $url;
	}
	if ( preg_match( '#^https?://#i', $url ) ) {
		$p = parse_url( $url );
		$h = ( $p['host'] ?? '' ) . ( isset( $p['port'] ) ? ':' . $p['port'] : '' );
		if ( $h !== $src_host ) {
			return null;
		}
		$url = $p['path'] ?? '/';
	} elseif ( ! str_starts_with( $url, '/' ) ) {
		return null; // relative paths are resolved by the CSS handler only
	}
	$url = preg_replace( '/[?#].*$/', '', $url );
	return '' === $url ? '/' : $url;
}

/** Whether a path is something we never publish. */
function skipped( string $path ): bool {
	return (bool) preg_match( '#^/(wp-admin|wp-login\.php|wp-json|xmlrpc\.php|wp-cron\.php|feed|comments/feed|author/)|/feed/?$|/embed/?$|\.php$#', $path );
}

/** Where a path is written inside dist/. */
function out_file( string $out, string $path ): string {
	if ( str_ends_with( $path, '/' ) ) {
		$path .= 'index.html';
	}
	return $out . str_replace( '/', DIRECTORY_SEPARATOR, rawurldecode( $path ) );
}

function write_file( string $file, string $data ): void {
	if ( ! is_dir( dirname( $file ) ) ) {
		mkdir( dirname( $file ), 0777, true );
	}
	file_put_contents( $file, $data );
}

/** Every URL referenced by an HTML document. */
function html_refs( string $html ): array {
	$refs = array();
	preg_match_all( '/\b(?:href|src|content)\s*=\s*(["\'])(.*?)\1/is', $html, $m );
	$refs = array_merge( $refs, $m[2] );
	preg_match_all( '/\bsrcset\s*=\s*(["\'])(.*?)\1/is', $html, $m );
	foreach ( $m[2] as $set ) {
		foreach ( explode( ',', $set ) as $candidate ) {
			$refs[] = preg_split( '/\s+/', trim( $candidate ) )[0];
		}
	}
	return array_merge( $refs, css_refs( $html ) );
}

/** url(...) references in CSS (also used on inline <style> in HTML). */
function css_refs( string $css ): array {
	preg_match_all( '/url\(\s*(["\']?)([^"\')]+)\1\s*\)/i', $css, $m );
	return $m[2];
}

// ---------------------------------------------------------------------------

if ( is_dir( $out ) ) {
	// Start clean so pages deleted in WordPress don't linger in the export.
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $out, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $it as $f ) {
		$f->isDir() ? rmdir( $f->getPathname() ) : unlink( $f->getPathname() );
	}
} else {
	mkdir( $out, 0777, true );
}

$pages  = array( '/', '/recipes/', '/pantry/', '/list/', '/about/' );
$assets = array();
$seen   = array();
$errors = array();

// Seed from the sitemap index and its child sitemaps.
[ $status, $index ] = fetch( "$source/wp-sitemap.xml" );
if ( 200 === $status ) {
	preg_match_all( '#<loc>(.*?)</loc>#', $index, $m );
	foreach ( $m[1] as $child ) {
		[ , $xml ] = fetch( html_entity_decode( $child ) );
		preg_match_all( '#<loc>(.*?)</loc>#', $xml, $mm );
		foreach ( $mm[1] as $loc ) {
			$p = internal_path( $loc, $source, $src_host );
			if ( null !== $p ) {
				$pages[] = $p;
			}
		}
	}
} else {
	$errors[] = "wp-sitemap.xml returned $status";
}

$rewrite = static function ( string $text ) use ( $source, $target ): string {
	// Plain and JSON-escaped forms of the local URL.
	$text = str_replace( $source, $target, $text );
	return str_replace( str_replace( '/', '\/', $source ), str_replace( '/', '\/', $target ), $text );
};

// Crawl pages.
while ( $pages ) {
	$path = array_shift( $pages );
	if ( isset( $seen[ $path ] ) || skipped( $path ) ) {
		continue;
	}
	$seen[ $path ] = true;

	[ $status, $html, $type ] = fetch( $source . $path );
	if ( 200 !== $status || ! str_contains( $type, 'text/html' ) ) {
		$errors[] = "$path returned $status";
		continue;
	}
	if ( preg_match( '#<b>(Warning|Notice|Deprecated|Fatal error)</b>#', $html ) ) {
		$errors[] = "$path contains a PHP warning";
	}

	foreach ( html_refs( $html ) as $ref ) {
		$p = internal_path( $ref, $source, $src_host );
		if ( null === $p || skipped( $p ) ) {
			continue;
		}
		if ( str_ends_with( $p, '/' ) ) {
			$pages[] = $p;
		} elseif ( preg_match( '/\.[a-z0-9]{2,5}$/i', $p ) ) {
			$assets[ $p ] = true;
		}
	}

	write_file( out_file( $out, $path ), $html ); // transformed below, after the crawl
	echo "page   $path\n";
}

// The 404 page: static hosts serve /404.html for any missing path.
[ $status, $html ] = fetch( "$source/__static-export-404__/" );
if ( 404 === $status ) {
	write_file( "$out/404.html", $html );
	foreach ( html_refs( $html ) as $ref ) {
		$p = internal_path( $ref, $source, $src_host );
		if ( null !== $p && ! skipped( $p ) && preg_match( '/\.[a-z0-9]{2,5}$/i', $p ) ) {
			$assets[ $p ] = true;
		}
	}
	echo "page   /404.html\n";
} else {
	$errors[] = "the 404 page returned $status instead of 404";
}

// The recipe index for the pantry finder, and the thumbnails only it refers to.
[ $status, $json ] = fetch( "$source/wp-json/kusina/v1/index" );
$recipes = 200 === $status ? json_decode( $json, true ) : null;
if ( ! is_array( $recipes ) || ! $recipes ) {
	$errors[] = "the recipe index returned $status or no recipes";
} else {
	foreach ( $recipes as $recipe ) {
		if ( ! empty( $recipe['i'] ) ) {
			$p = internal_path( $recipe['i'], $source, $src_host );
			if ( null !== $p ) {
				$assets[ $p ] = true;
			}
		}
	}
	write_file( "$out/recipes.json", $rewrite( $json ) );
	echo 'index  ' . count( $recipes ) . " recipes -> /recipes.json\n";
}

// Download assets; follow url(...) inside CSS.
$queue = array_keys( $assets );
$done  = array();
while ( $queue ) {
	$path = array_shift( $queue );
	if ( isset( $done[ $path ] ) ) {
		continue;
	}
	$done[ $path ] = true;
	[ $status, $body ] = fetch( $source . $path );
	if ( 200 !== $status ) {
		$errors[] = "asset $path returned $status";
		continue;
	}
	if ( str_ends_with( $path, '.css' ) ) {
		foreach ( css_refs( $body ) as $ref ) {
			if ( str_starts_with( $ref, 'data:' ) ) {
				continue;
			}
			$p = str_starts_with( $ref, '/' ) || preg_match( '#^https?://#', $ref )
				? internal_path( $ref, $source, $src_host )
				: internal_path( dirname( $path ) . '/' . $ref, $source, $src_host );
			if ( null !== $p ) {
				// Collapse ../ segments.
				$parts = array();
				foreach ( explode( '/', $p ) as $seg ) {
					if ( '..' === $seg ) {
						array_pop( $parts );
					} elseif ( '.' !== $seg ) {
						$parts[] = $seg;
					}
				}
				$queue[] = implode( '/', $parts );
			}
		}
		$body = $rewrite( $body );
	}
	write_file( out_file( $out, $path ), $body );
}
echo 'assets ' . count( $done ) . " files\n";

// robots.txt and sitemaps.
foreach ( array( '/robots.txt', '/wp-sitemap.xml', '/wp-sitemap.xsl', '/wp-sitemap-index.xsl' ) as $path ) {
	[ $status, $body ] = fetch( $source . $path );
	if ( 200 === $status ) {
		write_file( out_file( $out, $path ), $rewrite( $body ) );
	}
}
[ , $index ] = fetch( "$source/wp-sitemap.xml" );
preg_match_all( '#<loc>(.*?)</loc>#', $index, $m );
foreach ( $m[1] as $child ) {
	$p = internal_path( $child, $source, $src_host );
	[ $status, $body ] = fetch( html_entity_decode( $child ) );
	if ( null !== $p && 200 === $status ) {
		write_file( out_file( $out, $p ), $rewrite( $body ) );
	}
}

// Content-Security-Policy: every inline script on every page is allowed by its SHA-256 hash,
// collected here and written into the vercel.json header after the loop. Runs on final HTML.
$csp_hashes  = array();
$collect_csp = static function ( string $html, string $page ) use ( &$errors, &$csp_hashes ): void {
	// Inline event handlers and javascript: URLs can't be allowed by hash: fail instead of
	// publishing a page whose buttons silently stop working.
	if ( preg_match( '#<[^>]+\son[a-z]+\s*=|href\s*=\s*["\']\s*javascript:#i', $html ) ) {
		$errors[] = "$page has an inline event handler or javascript: URL, which the CSP would block";
	}
	preg_match_all( '#<script\b([^>]*)>(.*?)</script>#is', $html, $m, PREG_SET_ORDER );
	foreach ( $m as $s ) {
		$type = preg_match( '#\btype\s*=\s*["\']?([^"\'\s>]+)#i', $s[1], $t ) ? strtolower( $t[1] ) : '';
		// Data blocks (JSON-LD, the recipe card's ingredient JSON) never run, so CSP skips them.
		$runs = in_array( $type, array( '', 'text/javascript', 'module', 'speculationrules', 'importmap' ), true );
		if ( $runs && ! preg_match( '#\bsrc\s*=#i', $s[1] ) && '' !== $s[2] ) {
			$csp_hashes[ "'sha256-" . base64_encode( hash( 'sha256', $s[2], true ) ) . "'" ] = true;
		}
	}
};

$index_url = "$source/wp-json/kusina/v1/index";
$it        = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $out, FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $f ) {
	if ( 'html' !== $f->getExtension() ) {
		continue;
	}
	$html = file_get_contents( $f->getPathname() );
	// The pantry reads the index from a file instead of the REST API (before the URL rewrite).
	$html = str_replace( 'data-index="' . $index_url . '"', 'data-index="/recipes.json"', $html );
	$html = preg_replace( '#<link[^>]+(wp-json|xmlrpc\.php|/feed/|EditURI|rel=["\']shortlink)[^>]*>\s*#i', '', $html );
	$html = preg_replace( '#<meta name="generator"[^>]*>\s*#i', '', $html );
	$html = $rewrite( $html );
	if ( str_contains( $html, 'class="wp-block-kusina-pantry-finder' ) && ! str_contains( $html, 'data-index="/recipes.json"' ) ) {
		$errors[] = 'the pantry finder still points at the REST API (its data-index markup changed)';
	}
	$collect_csp( $html, substr( $f->getPathname(), strlen( $out ) ) );
	file_put_contents( $f->getPathname(), $html );
}

// Host config: copy static/ (vercel.json) in, then add the CSP header to its catch-all rule.
// Styles stay 'unsafe-inline': WordPress prints several <style> blocks and style=""
// attributes. In script-src, 'unsafe-inline' is only a fallback for very old browsers; any
// browser that understands hashes ignores it.
$static_dir = dirname( __DIR__ ) . '/static';
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $static_dir, FilesystemIterator::SKIP_DOTS ) ) as $f ) {
	write_file( $out . substr( $f->getPathname(), strlen( $static_dir ) ), file_get_contents( $f->getPathname() ) );
}
$csp = implode(
	'; ',
	array(
		"default-src 'self'",
		"script-src 'self' 'unsafe-inline' " . implode( ' ', array_keys( $csp_hashes ) ),
		"style-src 'self' 'unsafe-inline'",
		"img-src 'self' data:",
		"font-src 'self'",
		"connect-src 'self'",
		"form-action 'self'",
		"frame-ancestors 'none'",
		"base-uri 'self'",
		"object-src 'none'",
	)
);
$vercel_file = "$out/vercel.json";
$vercel      = json_decode( (string) @file_get_contents( $vercel_file ), true );
$catch_all   = is_array( $vercel ) ? array_search( '/(.*)', array_column( $vercel['headers'] ?? array(), 'source' ), true ) : false;
if ( false === $catch_all ) {
	$errors[] = 'static/vercel.json needs a headers rule with source "/(.*)" to carry the Content-Security-Policy';
} else {
	$vercel['headers'][ $catch_all ]['headers'][] = array( 'key' => 'Content-Security-Policy', 'value' => $csp );
	file_put_contents( $vercel_file, json_encode( $vercel, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
}
echo 'csp    ' . count( $csp_hashes ) . " inline script hashes\n";

// Final check: nothing may still point at the local site.
$left = array();
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $out, FilesystemIterator::SKIP_DOTS ) ) as $f ) {
	if ( preg_match( '/\.(html|css|js|json|xml|xsl|txt)$/', $f->getFilename() ) && str_contains( file_get_contents( $f->getPathname() ), $src_host ) ) {
		$left[] = substr( $f->getPathname(), strlen( $out ) );
	}
}
if ( $left ) {
	$errors[] = 'still references ' . $src_host . ': ' . implode( ', ', array_slice( $left, 0, 10 ) ) . ( count( $left ) > 10 ? ' ...' : '' );
}

if ( $errors ) {
	fwrite( STDERR, "\nExport finished with problems:\n - " . implode( "\n - ", $errors ) . "\n" );
	exit( 1 );
}
echo "\nExported " . count( $seen ) . ' pages to ' . realpath( $out ) . ( $target ? " for $target" : ' (root-relative links)' ) . "\n";
