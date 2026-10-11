<?php
/**
 * Course links in menu order, each carrying course-<slug> so the theme gives it its colour.
 * Courses with no term yet (not seeded) are skipped.
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$kusina_terms = get_terms(
	array(
		'taxonomy'   => 'course',
		'hide_empty' => false,
		'slug'       => array_keys( kusina_courses() ),
	)
);
if ( is_wp_error( $kusina_terms ) || ! $kusina_terms ) {
	return;
}
$kusina_by_slug = array_column( array_map( static fn( WP_Term $t ): array => array( $t->slug, $t ), $kusina_terms ), 1, 0 );
$kusina_current = is_tax( 'course' ) ? (string) get_queried_object()->slug : '';
?>
<nav <?php echo get_block_wrapper_attributes( array( 'class' => 'course-chips' ) ); ?> aria-label="<?php esc_attr_e( 'Courses', 'kusina-core' ); ?>">
	<ul>
		<?php foreach ( kusina_courses() as $kusina_slug => [ $kusina_name, $kusina_gloss ] ) : ?>
			<?php
			if ( ! isset( $kusina_by_slug[ $kusina_slug ] ) ) {
				continue;
			}
			$kusina_link = get_term_link( $kusina_by_slug[ $kusina_slug ] );
			if ( is_wp_error( $kusina_link ) ) {
				continue;
			}
			?>
			<li>
				<a class="course-chip course-<?php echo esc_attr( $kusina_slug ); ?>" href="<?php echo esc_url( $kusina_link ); ?>"<?php echo $kusina_current === $kusina_slug ? ' aria-current="page"' : ''; ?>>
					<span class="course-chip-name"><?php echo esc_html( $kusina_by_slug[ $kusina_slug ]->name ); ?></span>
					<?php if ( ! empty( $attributes['showGloss'] ) ) : ?>
						<span class="course-chip-gloss"><?php echo esc_html( $kusina_gloss ); ?></span>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
