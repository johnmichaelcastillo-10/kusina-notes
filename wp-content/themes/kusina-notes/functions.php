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
		wp_enqueue_style( 'kusina-notes', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
	}
);

// Headlines are Bricolage Grotesque: fetch it before the CSS asks, it is the LCP text.
add_action(
	'wp_head',
	static function (): void {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_theme_file_uri( 'assets/fonts/bricolage-grotesque.woff2' ) )
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
