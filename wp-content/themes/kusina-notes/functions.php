<?php
/**
 * Kusina Notes theme setup.
 *
 * Build plan and design direction live in docs/SPEC.md at the repo root; tokens in
 * theme.json; templates in /templates and /parts.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$theme = wp_get_theme();
		wp_enqueue_style( 'kusina-notes', get_stylesheet_uri(), array(), $theme->get( 'Version' ) );
	}
);

// No comments anywhere on this site (docs/SPEC.md, section 2).
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
