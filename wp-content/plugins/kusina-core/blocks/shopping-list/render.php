<?php
/**
 * Shopping list shell. The list lives in the visitor's browser (localStorage "kusina-list",
 * written by the recipe card); view.js merges the ingredients and groups them by aisle.
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$kusina_messages = array(
	/* translators: %s: number of servings */
	'servings' => __( 'for %s', 'kusina-core' ),
	/* translators: %s: recipe title */
	'remove'   => __( 'Remove %s', 'kusina-core' ),
	'removeText' => __( 'Remove', 'kusina-core' ),
	/* translators: %s: recipe title */
	'fewer'    => __( 'Fewer servings of %s', 'kusina-core' ),
	/* translators: %s: recipe title */
	'more'     => __( 'More servings of %s', 'kusina-core' ),
	/* translators: %s: comma-separated recipe titles */
	'for'      => __( 'For %s', 'kusina-core' ),
	'confirm'  => __( 'Tap again to clear', 'kusina-core' ),
	'clear'    => __( 'Clear list', 'kusina-core' ),
	'aisles'   => array(
		'meat'    => __( 'Meat and poultry', 'kusina-core' ),
		'seafood' => __( 'Fish and seafood', 'kusina-core' ),
		'produce' => __( 'Fruit and vegetables', 'kusina-core' ),
		'dairy'   => __( 'Eggs, dairy and tofu', 'kusina-core' ),
		'dry'     => __( 'Rice, noodles and baking', 'kusina-core' ),
		'sauces'  => __( 'Sauces, spices and oils', 'kusina-core' ),
		'other'   => __( 'Everything else', 'kusina-core' ),
	),
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'shopping-list' ) ); ?> data-messages="<?php echo esc_attr( wp_json_encode( $kusina_messages ) ); ?>">
	<div class="list-empty" data-list-empty>
		<p class="list-empty-title"><?php esc_html_e( 'Your list is empty. Suspicious.', 'kusina-core' ); ?></p>
		<p><?php esc_html_e( 'Open a recipe and press "Add to shopping list". Amounts follow the servings you picked, and matching ingredients are added together.', 'kusina-core' ); ?></p>
		<p><a class="btn btn-primary" href="/recipes/"><?php esc_html_e( 'Find recipes', 'kusina-core' ); ?></a></p>
	</div>

	<div class="list-full" data-list-full hidden>
		<section class="list-recipes" aria-labelledby="kusina-list-cooking">
			<h2 id="kusina-list-cooking"><?php esc_html_e( 'Cooking', 'kusina-core' ); ?></h2>
			<ul data-list-recipes></ul>
		</section>

		<section class="list-items" aria-labelledby="kusina-list-buy">
			<div class="list-head">
				<h2 id="kusina-list-buy"><?php esc_html_e( 'To buy', 'kusina-core' ); ?></h2>
				<div class="list-actions">
					<button type="button" class="kn-btn" data-list-print><?php esc_html_e( 'Print', 'kusina-core' ); ?></button>
					<button type="button" class="kn-btn kn-btn-quiet" data-list-uncheck><?php esc_html_e( 'Untick all', 'kusina-core' ); ?></button>
					<button type="button" class="kn-btn kn-btn-quiet" data-list-clear><?php esc_html_e( 'Clear list', 'kusina-core' ); ?></button>
				</div>
			</div>
			<div data-list-aisles></div>
		</section>
	</div>

	<noscript><p><?php esc_html_e( 'The shopping list needs JavaScript; it is kept in your browser.', 'kusina-core' ); ?></p></noscript>
</div>
