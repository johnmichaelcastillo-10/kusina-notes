<?php
/**
 * Recipe index filters (docs/SPEC.md, section 4). Without JavaScript this is the plain course
 * links (kusina/course-chips). view.js swaps them for course, diet and time toggles, filters
 * the recipe index in the browser and shows the results in place of the page's Query Loop
 * (the one with the recipe-index class), so it works the same on the static site.
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$kusina_diets = get_terms(
	array(
		'taxonomy'   => 'diet',
		'hide_empty' => true,
	)
);
$kusina_diets = is_array( $kusina_diets ) ? array_column( array_map( static fn( WP_Term $t ): array => array( $t->slug, $t->name ), $kusina_diets ), 1, 0 ) : array();

$kusina_times = array(
	30 => __( '30 min or less', 'kusina-core' ),
	60 => __( '1 hr or less', 'kusina-core' ),
);

$kusina_messages = array(
	/* translators: %s: number of recipes */
	'count'   => __( '%s recipes', 'kusina-core' ),
	'one'     => __( '1 recipe', 'kusina-core' ),
	'none'    => __( 'Nothing fits all of that. Try one filter fewer.', 'kusina-core' ),
	'more'    => __( 'Show more', 'kusina-core' ),
	'courses' => array_map( static fn( array $c ): string => $c[0], kusina_courses() ),
	'diets'   => $kusina_diets,
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'recipe-filters' ) ); ?>
	data-index="<?php echo esc_url( rest_url( 'kusina/v1/index' ) ); ?>"
	data-base="<?php echo esc_attr( wp_make_link_relative( (string) get_post_type_archive_link( 'recipe' ) ) ); ?>"
	data-messages="<?php echo esc_attr( wp_json_encode( $kusina_messages ) ); ?>">
	<div class="recipe-filters-links"><?php echo render_block( array( 'blockName' => 'kusina/course-chips', 'attrs' => array() ) ); // Escaped in its render.php. ?></div>

	<div class="recipe-filters-panel" data-filters hidden>
		<div class="course-chips" role="group" aria-label="<?php esc_attr_e( 'Course', 'kusina-core' ); ?>">
			<ul>
				<?php foreach ( kusina_courses() as $kusina_slug => [ $kusina_name, $kusina_gloss ] ) : ?>
					<li><button type="button" class="course-chip" data-filter="course" data-value="<?php echo esc_attr( $kusina_slug ); ?>" aria-pressed="false"><span class="course-chip-name"><?php echo esc_html( $kusina_name ); ?></span> <span class="course-chip-gloss"><?php echo esc_html( $kusina_gloss ); ?></span></button></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div class="filter-row">
			<?php if ( $kusina_diets ) : ?>
				<div class="filter-group" role="group" aria-label="<?php esc_attr_e( 'Diet', 'kusina-core' ); ?>">
					<?php foreach ( $kusina_diets as $kusina_slug => $kusina_name ) : ?>
						<button type="button" class="filter-chip" data-filter="diet" data-value="<?php echo esc_attr( $kusina_slug ); ?>" aria-pressed="false"><?php echo esc_html( $kusina_name ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="filter-group" role="group" aria-label="<?php esc_attr_e( 'Time', 'kusina-core' ); ?>">
				<?php foreach ( $kusina_times as $kusina_max => $kusina_label ) : ?>
					<button type="button" class="filter-chip" data-filter="max" data-value="<?php echo esc_attr( (string) $kusina_max ); ?>" aria-pressed="false"><?php echo esc_html( $kusina_label ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="filter-status">
			<p class="filter-summary" data-filter-summary aria-live="polite"></p>
			<button type="button" class="filter-clear" data-filter-clear hidden><?php esc_html_e( 'Clear filters', 'kusina-core' ); ?></button>
		</div>
	</div>
</div>
