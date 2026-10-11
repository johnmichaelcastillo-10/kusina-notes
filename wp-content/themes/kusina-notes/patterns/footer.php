<?php
/**
 * Title: Footer
 * Slug: kusina-notes/footer
 * Categories: footer
 * Inserter: no
 *
 * Gingham oilcloth band (one of only two places it appears, docs/DESIGN.md).
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"className":"site-footer gingham","layout":{"type":"constrained","contentSize":"74rem"}} -->
<div class="wp-block-group site-footer gingham"><!-- wp:group {"className":"footer-card","layout":{"type":"default"}} -->
<div class="wp-block-group footer-card"><!-- wp:html -->
<div class="footer-top">
	<a class="wordmark" href="/" aria-label="<?php esc_attr_e( 'Kusina Notes, home', 'kusina-notes' ); ?>"><span class="wordmark-kusina">Kusina</span><span class="wordmark-notes" aria-hidden="true">notes</span></a>
	<p class="footer-line"><?php esc_html_e( 'Recipes for everyday Filipino cooking. Kain na.', 'kusina-notes' ); ?></p>
</div>
<!-- /wp:html -->

<!-- wp:kusina/course-chips {"showGloss":false} /-->

<!-- wp:html -->
<div class="footer-bottom">
	<p><?php esc_html_e( 'A personal WordPress project. Food photos are public domain.', 'kusina-notes' ); ?></p>
	<ul class="footer-links">
		<li><a href="/recipes/"><?php esc_html_e( 'All recipes', 'kusina-notes' ); ?></a></li>
		<li><a href="/about/"><?php esc_html_e( 'About', 'kusina-notes' ); ?></a></li>
	</ul>
</div>
<!-- /wp:html --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
