<?php
/**
 * Launch content (docs/SPEC.md, section 8). Idempotent by slug: re-running updates posts.
 *
 *   docker compose run --rm cli wp eval-file /scripts/seed-content.php --user=admin
 *
 * Run as an admin so kses leaves the block markup alone.
 */

defined( 'ABSPATH' ) || exit;

/** Ingredient row for the recipe card. */
function kusina_seed_item( ?float $qty, string $unit, string $name, string $note = '', ?float $max = null ): array {
	return array(
		'qty'    => $qty,
		'qtyMax' => $max,
		'unit'   => $unit,
		'name'   => $name,
		'note'   => $note,
	);
}

function kusina_seed_recipe( array $recipe ): int {
	$content = serialize_block(
		array(
			'blockName'    => 'core/paragraph',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => '<p>' . esc_html( $recipe['intro'] ) . '</p>',
			'innerContent' => array( '<p>' . esc_html( $recipe['intro'] ) . '</p>' ),
		)
	) . "\n\n" . serialize_block(
		array(
			'blockName'    => 'kusina/recipe-card',
			'attrs'        => array(
				'align'  => 'wide',
				'groups' => $recipe['groups'],
				'steps'  => $recipe['steps'],
				'tips'   => $recipe['tips'],
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	);

	$existing = get_page_by_path( $recipe['slug'], OBJECT, 'recipe' );
	// wp_insert_post() unslashes its input, which would eat the backslash in the unicode
	// escapes that serialize_block() writes into block attributes (e.g. for "<strong>").
	$post_id = wp_insert_post(
		wp_slash(
			array(
				'ID'           => $existing ? $existing->ID : 0,
				'post_type'    => 'recipe',
				'post_status'  => 'publish',
				'post_name'    => $recipe['slug'],
				'post_title'   => $recipe['title'],
				'post_content' => $content,
			)
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		WP_CLI::error( $post_id->get_error_message() );
	}

	foreach ( $recipe['meta'] as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
	}
	foreach ( $recipe['terms'] as $taxonomy => $names ) {
		wp_set_object_terms( $post_id, $names, $taxonomy );
	}

	WP_CLI::log( ( $existing ? 'Updated' : 'Created' ) . " {$recipe['slug']} (#{$post_id})" );
	return $post_id;
}

kusina_seed_recipe(
	array(
		'slug'   => 'chicken-adobo',
		'title'  => 'Chicken adobo',
		'intro'  => 'Chicken braised in soy sauce, vinegar and plenty of garlic until the sauce turns glossy and clings to every piece. This is the everyday version: one pot, pantry ingredients, about an hour, and it tastes even better the next day.',
		'meta'   => array(
			'prep_minutes' => 10,
			'cook_minutes' => 50,
			'servings'     => 4,
			'yield_label'  => '',
			'difficulty'   => 'easy',
		),
		'terms'  => array(
			'course'         => array( 'Ulam' ),
			'key_ingredient' => array( 'Chicken', 'Soy sauce', 'Vinegar', 'Garlic' ),
		),
		'groups' => array(
			array(
				'title' => 'For the chicken',
				'items' => array(
					kusina_seed_item( 1, 'kg', 'bone-in chicken thighs and drumsticks' ),
					kusina_seed_item( 1, 'tbsp', 'cooking oil' ),
					kusina_seed_item( 10, 'clove', 'garlic', 'crushed (about 1 head)' ),
					kusina_seed_item( 0.5, 'cup', 'soy sauce' ),
					kusina_seed_item( 1 / 3, 'cup', 'cane vinegar', 'or white vinegar' ),
					kusina_seed_item( 1, 'cup', 'water' ),
					kusina_seed_item( 3, '', 'dried bay leaves' ),
					kusina_seed_item( 1, 'tsp', 'whole black peppercorns', 'lightly cracked' ),
					kusina_seed_item( 1, 'tbsp', 'brown sugar', 'optional' ),
				),
			),
			array(
				'title' => 'To serve',
				'items' => array(
					kusina_seed_item( 4, 'cup', 'steamed white rice' ),
				),
			),
		),
		'steps'  => array(
			array(
				'text'    => 'Pat the chicken dry. Heat the oil in a wide, heavy pot over medium-high heat and brown the chicken skin side down in batches, about 5 minutes per batch. Set the pieces aside.',
				'minutes' => 5,
			),
			array(
				'text'    => 'Lower the heat to medium. Fry the garlic in the chicken fat until fragrant and just turning golden. Do not let it brown.',
				'minutes' => 1,
			),
			array(
				'text'    => 'Return the chicken to the pot with the soy sauce, water, bay leaves and peppercorns. Bring to a boil.',
				'minutes' => null,
			),
			array(
				'text'    => 'Pour in the vinegar and let it boil <strong>without stirring</strong>, so its sharp edge cooks off.',
				'minutes' => 3,
			),
			array(
				'text'    => 'Cover, lower the heat and simmer until the chicken is tender, turning the pieces once halfway.',
				'minutes' => 25,
			),
			array(
				'text'    => 'Uncover, stir in the sugar if using, and simmer until the sauce has reduced by about half and coats the chicken.',
				'minutes' => 10,
			),
			array(
				'text'    => 'Taste the sauce. Add a splash of vinegar if it needs brightness, or a splash of water if it is too salty. Remove the bay leaves.',
				'minutes' => null,
			),
			array(
				'text'    => 'Serve over steamed rice with plenty of sauce spooned over.',
				'minutes' => null,
			),
		),
		'tips'   => 'Adobo keeps for 4 days in the fridge and the flavour deepens overnight. Reheat gently with a splash of water. For a crisp finish, fry the cooked pieces in a hot pan for a few minutes a side and pour the sauce over at the table.',
	)
);
