<?php
/**
 * Title: Header
 * Slug: kusina-notes/header
 * Categories: header
 * Inserter: no
 *
 * Custom HTML instead of the Navigation block (heavy inline CSS and the Interactivity API
 * for four links). Links are root-relative so the static export needs no rewriting. The
 * list count is filled in by assets/js/site.js from the visitor's saved list; the same file
 * runs the light/dark toggle, hidden until then.
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:html -->
<div class="site-header">
	<a class="wordmark" href="/"><span class="wordmark-kusina">Kusina</span> <span class="wordmark-notes">notes</span></a>
	<nav class="site-nav" aria-label="<?php esc_attr_e( 'Main', 'kusina-notes' ); ?>">
		<ul>
			<li><a href="/recipes/"><?php esc_html_e( 'Recipes', 'kusina-notes' ); ?></a></li>
			<li><a href="/pantry/"><?php esc_html_e( 'Pantry', 'kusina-notes' ); ?></a></li>
			<li><a href="/list/"><?php esc_html_e( 'List', 'kusina-notes' ); ?><span class="nav-count" data-list-count hidden></span></a></li>
			<li><a href="/about/"><?php esc_html_e( 'About', 'kusina-notes' ); ?></a></li>
		</ul>
	</nav>
	<button type="button" class="theme-toggle" data-theme-toggle hidden
		data-label-dark="<?php esc_attr_e( 'Switch to dark theme', 'kusina-notes' ); ?>"
		data-label-light="<?php esc_attr_e( 'Switch to light theme', 'kusina-notes' ); ?>"
		aria-label="<?php esc_attr_e( 'Switch to dark theme', 'kusina-notes' ); ?>">
		<svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z"/></svg>
		<svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4 6 18M18 6l1.4-1.4"/></svg>
	</button>
</div>
<!-- /wp:html -->
