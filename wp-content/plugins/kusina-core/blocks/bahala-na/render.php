<?php
/**
 * "Bahala na" (come what may): opens a random recipe from the recipe index, never the page
 * you're on (docs/SPEC.md, section 4). Hidden until view.js runs: without JavaScript there
 * is nothing it can do. Styled by the theme's .btn.
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;
?>
<button type="button" <?php echo get_block_wrapper_attributes( array( 'class' => 'btn bahala-na' ) ); ?>
	data-index="<?php echo esc_url( rest_url( 'kusina/v1/index' ) ); ?>"
	data-fallback="<?php echo esc_attr( wp_make_link_relative( (string) get_post_type_archive_link( 'recipe' ) ) ); ?>"
	hidden><?php esc_html_e( 'Bahala na', 'kusina-core' ); ?> <span class="bahala-na-gloss"><?php esc_html_e( 'pick for me', 'kusina-core' ); ?></span></button>
