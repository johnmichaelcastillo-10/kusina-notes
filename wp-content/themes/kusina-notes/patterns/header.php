<?php
/**
 * Title: Header
 * Slug: kusina-notes/header
 * Categories: header
 * Inserter: no
 *
 * Custom HTML instead of the Navigation block (heavy inline CSS and the Interactivity API
 * for four links). Links are root-relative so the static export needs no rewriting. The
 * list count is filled in by assets/js/site.js from the visitor's saved list.
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:html -->
<div class="site-header">
	<a class="wordmark" href="/" aria-label="<?php esc_attr_e( 'Kusina Notes, home', 'kusina-notes' ); ?>"><span class="wordmark-kusina">Kusina</span><span class="wordmark-notes" aria-hidden="true">notes</span></a>
	<nav class="site-nav" aria-label="<?php esc_attr_e( 'Main', 'kusina-notes' ); ?>">
		<ul>
			<li><a href="/recipes/"><?php esc_html_e( 'Recipes', 'kusina-notes' ); ?></a></li>
			<li><a href="/pantry/"><?php esc_html_e( 'Pantry', 'kusina-notes' ); ?></a></li>
			<li><a href="/list/"><?php esc_html_e( 'List', 'kusina-notes' ); ?><span class="nav-count" data-list-count hidden></span></a></li>
			<li><a href="/about/"><?php esc_html_e( 'About', 'kusina-notes' ); ?></a></li>
		</ul>
	</nav>
</div>
<!-- /wp:html -->
