<?php
/**
 * Pantry finder shell: ingredient chips (only ones some recipe uses), a course filter and
 * the results area. view.js fetches the recipe index and does the ranking in the browser,
 * so it works the same on the static site.
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$kusina_used = get_terms(
	array(
		'taxonomy'   => 'key_ingredient',
		'hide_empty' => true,
		'fields'     => 'names',
	)
);
$kusina_used = is_array( $kusina_used ) ? array_map( 'strtolower', $kusina_used ) : array();

$kusina_messages = array(
	/* translators: %s: number of recipes */
	'count'     => __( '%s recipes you can make', 'kusina-core' ),
	'one'       => __( '1 recipe you can make', 'kusina-core' ),
	'none'      => __( 'Nothing matches yet. Tick a few more things.', 'kusina-core' ),
	'start'     => __( 'Nothing yet. Add rice: there is always rice.', 'kusina-core' ),
	'basics'    => __( 'Now tick what is in the fridge: a meat, a vegetable, anything.', 'kusina-core' ),
	'all'       => __( 'You have everything', 'kusina-core' ),
	/* translators: %s: comma-separated ingredients */
	'missing'   => __( 'Missing: %s', 'kusina-core' ),
	'more'      => __( 'Show more', 'kusina-core' ),
	'loadError' => __( 'The recipe list did not load. Refresh to try again.', 'kusina-core' ),
	'courses'   => array_map( static fn( array $c ): string => $c[0], kusina_courses() ),
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pantry' ) ); ?>
	data-index="<?php echo esc_url( rest_url( 'kusina/v1/index' ) ); ?>"
	data-messages="<?php echo esc_attr( wp_json_encode( $kusina_messages ) ); ?>">
	<div class="pantry-picker">
		<?php foreach ( kusina_pantry_groups() as $kusina_group_id => $kusina_group ) : ?>
			<?php
			$kusina_keys = array_values( array_intersect( $kusina_group['keys'], $kusina_used ) );
			if ( ! $kusina_keys ) {
				continue;
			}
			?>
			<fieldset class="pantry-group" data-group="<?php echo esc_attr( $kusina_group_id ); ?>">
				<legend><?php echo esc_html( $kusina_group['label'] ); ?></legend>
				<div class="pantry-chips">
					<?php foreach ( $kusina_keys as $kusina_key ) : ?>
						<button type="button" class="pantry-chip" aria-pressed="false" data-key="<?php echo esc_attr( $kusina_key ); ?>"<?php echo 'basics' === $kusina_group_id ? ' data-basic' : ''; ?> disabled><?php echo esc_html( ucfirst( $kusina_key ) ); ?></button>
					<?php endforeach; ?>
				</div>
			</fieldset>
		<?php endforeach; ?>
		<div class="pantry-actions">
			<button type="button" class="kn-btn kn-btn-quiet" data-pantry-clear disabled><?php esc_html_e( 'Clear everything', 'kusina-core' ); ?></button>
		</div>
	</div>

	<div class="pantry-results">
		<div class="pantry-results-head">
			<p class="pantry-summary" data-pantry-summary aria-live="polite"><?php esc_html_e( 'Loading recipes…', 'kusina-core' ); ?></p>
			<label class="pantry-course">
				<span><?php esc_html_e( 'Course', 'kusina-core' ); ?></span>
				<select data-pantry-course disabled>
					<option value=""><?php esc_html_e( 'Any course', 'kusina-core' ); ?></option>
					<?php foreach ( kusina_courses() as $kusina_slug => [ $kusina_name, $kusina_gloss ] ) : ?>
						<option value="<?php echo esc_attr( $kusina_slug ); ?>"><?php echo esc_html( "{$kusina_name} ({$kusina_gloss})" ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>
		<div class="tiles pantry-tiles"><ul class="wp-block-post-template" data-pantry-list></ul></div>
		<button type="button" class="kn-btn pantry-more" data-pantry-more hidden></button>
	</div>

	<noscript><p class="pantry-noscript"><?php esc_html_e( 'The pantry finder needs JavaScript.', 'kusina-core' ); ?> <a href="/recipes/"><?php esc_html_e( 'Browse all recipes instead.', 'kusina-core' ); ?></a></p></noscript>
</div>
