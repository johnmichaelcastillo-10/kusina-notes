<?php
/**
 * Recipe index for the pantry finder (docs/SPEC.md, section 4): one small record per
 * recipe. Served at /wp-json/kusina/v1/index; the static export writes the same payload to
 * /recipes.json and rewrites the pantry's data-index attribute to it.
 *
 * Short keys keep 1,000+ records small: s slug, t title, u URL (root-relative), c course
 * slug, k key ingredients (lowercase), m total minutes, d diet slugs, i thumbnail URL.
 */

defined( 'ABSPATH' ) || exit;

const KUSINA_INDEX_CACHE = 'kusina_recipe_index_v1';

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'kusina/v1',
			'/index',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static fn() => rest_ensure_response( kusina_recipe_index() ),
			)
		);
	}
);

// Any change to a recipe, its terms or its photo drops the cached index.
foreach ( array( 'save_post_recipe', 'deleted_post', 'set_object_terms', 'updated_post_meta', 'added_post_meta' ) as $kusina_hook ) {
	add_action( $kusina_hook, 'kusina_flush_recipe_index' );
}

function kusina_flush_recipe_index(): void {
	delete_transient( KUSINA_INDEX_CACHE );
}

function kusina_recipe_index(): array {
	$cached = get_transient( KUSINA_INDEX_CACHE );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'recipe',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);

	$index = array();
	foreach ( $query->posts as $post ) {
		$meta  = kusina_recipe_meta( $post->ID );
		$diets = get_the_terms( $post->ID, 'diet' );
		$thumb = get_the_post_thumbnail_url( $post, 'medium' );

		$index[] = array(
			's' => $post->post_name,
			't' => kusina_plain_text( get_the_title( $post ) ),
			'u' => wp_make_link_relative( (string) get_permalink( $post ) ),
			'c' => kusina_recipe_course( $post->ID ),
			'k' => array_map( 'strtolower', kusina_term_names( $post->ID, 'key_ingredient' ) ),
			'm' => $meta['total'],
			'd' => is_array( $diets ) ? wp_list_pluck( $diets, 'slug' ) : array(),
			'i' => $thumb ? wp_make_link_relative( $thumb ) : '',
		);
	}

	set_transient( KUSINA_INDEX_CACHE, $index, DAY_IN_SECONDS );
	return $index;
}

/**
 * Pantry chips, grouped. Mirrors the KEY_INGREDIENTS vocabulary in
 * scripts/validate-recipes.mjs; "basics" start ticked because most kitchens have them.
 */
function kusina_pantry_groups(): array {
	return array(
		'basics'     => array(
			'label' => __( 'Usually in the kitchen', 'kusina-core' ),
			'keys'  => array( 'rice', 'garlic', 'onion', 'ginger', 'tomato', 'soy sauce', 'vinegar', 'fish sauce', 'brown sugar', 'egg' ),
		),
		'meat'       => array(
			'label' => __( 'Meat', 'kusina-core' ),
			'keys'  => array( 'chicken', 'pork', 'pork belly', 'ground pork', 'pork offal', 'beef', 'ground beef', 'goat', 'duck', 'chicken liver', 'longganisa', 'tocino', 'corned beef', 'hotdog', 'ham', 'spam' ),
		),
		'seafood'    => array(
			'label' => __( 'Fish and seafood', 'kusina-core' ),
			'keys'  => array( 'fish', 'bangus', 'tilapia', 'tuna', 'galunggong', 'salmon', 'dried fish', 'canned sardines', 'shrimp', 'crab', 'squid', 'mussels', 'clams', 'oysters', 'octopus' ),
		),
		'vegetables' => array(
			'label' => __( 'Vegetables', 'kusina-core' ),
			'keys'  => array( 'eggplant', 'squash', 'string beans', 'bitter gourd', 'okra', 'cabbage', 'bok choy', 'water spinach', 'malunggay', 'chayote', 'bottle gourd', 'sponge gourd', 'bean sprouts', 'corn', 'green papaya', 'jackfruit', 'banana blossom', 'taro leaves', 'potato', 'sweet potato', 'cassava', 'taro', 'ube', 'carrot', 'bell pepper', 'chili', 'lemongrass', 'mushrooms', 'spinach' ),
		),
		'fruit'      => array(
			'label' => __( 'Fruit', 'kusina-core' ),
			'keys'  => array( 'banana', 'saba banana', 'mango', 'green mango', 'pineapple', 'young coconut', 'calamansi', 'tamarind', 'avocado', 'melon', 'jackfruit (ripe)', 'guava', 'papaya' ),
		),
		'pantry'     => array(
			'label' => __( 'Pantry and dairy', 'kusina-core' ),
			'keys'  => array( 'glutinous rice', 'rice flour', 'flour', 'noodles', 'bread', 'pasta', 'tofu', 'mung beans', 'peanuts', 'salted egg', 'quail egg', 'coconut milk', 'coconut', 'shrimp paste', 'annatto', 'peanut butter', 'muscovado', 'condensed milk', 'evaporated milk', 'milk', 'cheese', 'butter', 'cream', 'chocolate', 'cocoa', 'coffee', 'tea', 'pandan', 'gelatin', 'sago', 'pinipig', 'oats' ),
		),
	);
}
