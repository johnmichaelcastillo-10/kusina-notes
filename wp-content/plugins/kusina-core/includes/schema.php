<?php
/**
 * Recipe JSON-LD (docs/SPEC.md, section 5), built from the same recipe card data the page
 * shows. No aggregateRating or reviews: the site has none and must not invent them.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'kusina_print_recipe_schema' );

function kusina_print_recipe_schema(): void {
	if ( ! is_singular( 'recipe' ) ) {
		return;
	}

	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$data = kusina_recipe_schema( $post );
	if ( $data ) {
		wp_print_inline_script_tag(
			wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ),
			array( 'type' => 'application/ld+json' )
		);
	}
}

/** First block named $name in a parsed block tree, or null. */
function kusina_find_block( array $blocks, string $name ): ?array {
	foreach ( $blocks as $block ) {
		if ( $name === $block['blockName'] ) {
			return $block;
		}
		$inner = kusina_find_block( $block['innerBlocks'] ?? array(), $name );
		if ( $inner ) {
			return $inner;
		}
	}
	return null;
}

/** schema.org Recipe for a recipe post, or an empty array when it has no recipe card. */
function kusina_recipe_schema( WP_Post $post ): array {
	$blocks = parse_blocks( $post->post_content );
	$card   = kusina_find_block( $blocks, 'kusina/recipe-card' );
	if ( ! $card ) {
		return array();
	}

	$recipe = kusina_normalize_recipe( $card['attrs'] ?? array() );
	$meta   = kusina_recipe_meta( $post->ID );

	$data = array(
		'@context'      => 'https://schema.org',
		'@type'         => 'Recipe',
		'name'          => kusina_plain_text( get_the_title( $post ) ),
		'description'   => kusina_recipe_description( $post, $blocks ),
		'author'        => array(
			'@type' => 'Organization',
			'name'  => kusina_plain_text( get_bloginfo( 'name' ) ),
			'url'   => home_url( '/' ),
		),
		'datePublished' => get_the_date( 'c', $post ),
		'url'           => get_permalink( $post ),
	);

	$image = get_the_post_thumbnail_url( $post, 'full' );
	if ( $image ) {
		$data['image'] = array( $image );
	}

	foreach ( array( 'prepTime' => $meta['prep'], 'cookTime' => $meta['cook'], 'totalTime' => $meta['total'] ) as $key => $minutes ) {
		$duration = kusina_iso_duration( $minutes );
		if ( '' !== $duration ) {
			$data[ $key ] = $duration;
		}
	}

	$data['recipeYield'] = kusina_yield_text( $meta );

	$ingredients = array();
	foreach ( $recipe['groups'] as $group ) {
		foreach ( $group['items'] as $item ) {
			$ingredients[] = kusina_ingredient_text( $item );
		}
	}
	if ( $ingredients ) {
		$data['recipeIngredient'] = $ingredients;
	}

	if ( $recipe['steps'] ) {
		$data['recipeInstructions'] = array_map(
			static fn( array $step ): array => array(
				'@type' => 'HowToStep',
				'text'  => kusina_plain_text( $step['text'] ),
			),
			$recipe['steps']
		);
	}

	// English categories for search engines; the page shows the Tagalog course names.
	$course_terms = get_the_terms( $post->ID, 'course' );
	if ( is_array( $course_terms ) && $course_terms ) {
		$known                  = kusina_courses();
		$data['recipeCategory'] = implode(
			', ',
			array_map( static fn( WP_Term $term ): string => $known[ $term->slug ][2] ?? $term->name, $course_terms )
		);
	}

	$keywords = array_merge( kusina_term_names( $post->ID, 'diet' ), kusina_term_names( $post->ID, 'key_ingredient' ) );
	if ( $keywords ) {
		$data['keywords'] = implode( ', ', $keywords );
	}

	// Only diets schema.org has a value for: https://schema.org/RestrictedDiet
	$diets = array(
		'vegetarian'  => 'https://schema.org/VegetarianDiet',
		'gluten-free' => 'https://schema.org/GlutenFreeDiet',
	);
	$terms = get_the_terms( $post->ID, 'diet' );
	$suits = array();
	foreach ( is_array( $terms ) ? $terms : array() as $term ) {
		if ( isset( $diets[ $term->slug ] ) ) {
			$suits[] = $diets[ $term->slug ];
		}
	}
	if ( $suits ) {
		$data['suitableForDiet'] = $suits;
	}

	return $data;
}

/** Manual excerpt, else the text of the first paragraph before the card. */
function kusina_recipe_description( WP_Post $post, array $blocks ): string {
	if ( has_excerpt( $post ) ) {
		return kusina_plain_text( get_the_excerpt( $post ) );
	}
	$paragraph = kusina_find_block( $blocks, 'core/paragraph' );
	if ( ! $paragraph ) {
		return '';
	}
	return kusina_plain_text( $paragraph['innerHTML'] );
}

/** HTML (tags, entities, texturized quotes) to plain text for JSON. */
function kusina_plain_text( string $html ): string {
	return trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
}

function kusina_term_names( int $post_id, string $taxonomy ): array {
	$terms = get_the_terms( $post_id, $taxonomy );
	return is_array( $terms ) ? wp_list_pluck( $terms, 'name' ) : array();
}
