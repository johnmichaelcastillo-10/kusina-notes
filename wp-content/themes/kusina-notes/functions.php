<?php
/**
 * Kusina Notes theme setup.
 *
 * Look and voice: docs/DESIGN.md at the repo root. Tokens in theme.json and the :root block
 * of style.css (dark mode and course colours, which theme.json can't express); templates in
 * /templates, header and footer in /patterns.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$version = wp_get_theme()->get( 'Version' );
		wp_enqueue_style( 'kusina-notes', get_stylesheet_uri(), array(), $version );
		// A module with no dependencies, so WordPress prints no inline import map.
		wp_enqueue_script_module( 'kusina-notes-site', get_theme_file_uri( 'assets/js/site.js' ), array(), $version );
	}
);

// A theme chosen with the header toggle applies before first paint, so a dark-mode choice
// never flashes light. Blocking and inline on purpose; the static exporter hashes it into
// the CSP. site.js reads and writes the same key.
add_action(
	'wp_head',
	static function (): void {
		wp_print_inline_script_tag(
			"try{var t=localStorage.getItem('kusina-theme');if(t==='light'||t==='dark')document.documentElement.dataset.theme=t}catch(e){}"
		);
	},
	0
);

// Headlines are Instrument Serif and body text Figtree: fetch both before the CSS asks, the
// headline is the LCP text.
add_action(
	'wp_head',
	static function (): void {
		foreach ( array( 'instrument-serif.woff2', 'figtree.woff2' ) as $font ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( get_theme_file_uri( 'assets/fonts/' . $font ) )
			);
		}
		// A banana leaf; no Site Icon in the database, so it travels with the theme.
		printf(
			'<link rel="icon" href="%s" type="image/svg+xml">' . "\n",
			esc_url( get_theme_file_uri( 'assets/favicon.svg' ) )
		);
	},
	1
);

// No comments anywhere on this site (docs/SPEC.md, section 2).
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );

// The emoji loader is an inline script the static site's CSP would have to hash, for a
// feature the site doesn't use (docs/SPEC.md, section 7).
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
