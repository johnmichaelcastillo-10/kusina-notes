<?php
/**
 * Launch content (docs/SPEC.md, section 8): courses, the About page, and every recipe in
 * scripts/recipes/*.json. Idempotent by slug: re-running updates posts in place.
 *
 *   node scripts/validate-recipes.mjs                                   check the data first
 *   docker compose run --rm cli wp eval-file /scripts/seed-content.php --user=admin
 *
 * Run as an admin so kses leaves the block markup alone.
 */

defined( 'ABSPATH' ) || exit;

/** Builds a recipe post from one JSON record (shape: scripts/recipes/README.md). */
function kusina_seed_recipe( array $recipe, string $post_date ): array {
	$groups = array_map(
		static fn( array $group ): array => array(
			'title' => (string) $group['title'],
			'items' => array_map(
				static fn( array $item ): array => array(
					'qty'    => $item['qty'],
					'qtyMax' => $item['max'],
					'unit'   => (string) $item['unit'],
					'name'   => (string) $item['name'],
					'note'   => (string) $item['note'],
				),
				$group['items']
			),
		),
		$recipe['groups']
	);

	$intro   = '<p>' . esc_html( $recipe['intro'] ) . '</p>';
	$content = serialize_block(
		array(
			'blockName'    => 'core/paragraph',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => $intro,
			'innerContent' => array( $intro ),
		)
	) . "\n\n" . serialize_block(
		array(
			'blockName'    => 'kusina/recipe-card',
			'attrs'        => array(
				'align'  => 'wide',
				'groups' => $groups,
				'steps'  => $recipe['steps'],
				'tips'   => (string) $recipe['tips'],
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
				'ID'            => $existing ? $existing->ID : 0,
				'post_type'     => 'recipe',
				'post_status'   => 'publish',
				'post_name'     => $recipe['slug'],
				'post_title'    => $recipe['title'],
				'post_content'  => $content,
				'post_date'     => $post_date,
				'post_date_gmt' => get_gmt_from_date( $post_date ),
			)
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		return array( 0, $post_id->get_error_message() );
	}

	$meta = array(
		'prep_minutes' => (int) $recipe['prep'],
		'cook_minutes' => (int) $recipe['cook'],
		'servings'     => (int) $recipe['servings'],
		'yield_label'  => (string) $recipe['yield_label'],
		'difficulty'   => (string) $recipe['difficulty'],
	);
	foreach ( $meta as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
	}

	$course = get_term_by( 'slug', $recipe['course'], 'course' );
	wp_set_object_terms( $post_id, $course ? array( $course->term_id ) : array(), 'course' );
	wp_set_object_terms( $post_id, array_map( 'ucfirst', $recipe['diets'] ), 'diet' );
	wp_set_object_terms( $post_id, array_map( 'ucfirst', $recipe['key_ingredients'] ), 'key_ingredient' );

	return array( $post_id, $existing ? 'updated' : 'created' );
}

// Courses in Tagalog (docs/DESIGN.md); the description is the English gloss shown on the
// course page.
$kusina_course_descriptions = array(
	'ulam'        => 'Mains: what goes on the rice.',
	'sabaw'       => 'Soups and stews, for rainy days and every other day.',
	'merienda'    => 'Afternoon snacks, sweet or savory.',
	'panghimagas' => 'Desserts.',
	'almusal'     => 'Breakfast, usually with garlic rice.',
	'inumin'      => 'Drinks.',
);
foreach ( kusina_courses() as $kusina_slug => [ $kusina_name ] ) {
	$kusina_term = get_term_by( 'slug', $kusina_slug, 'course' );
	$kusina_args = array(
		'name'        => $kusina_name,
		'description' => $kusina_course_descriptions[ $kusina_slug ],
	);
	if ( $kusina_term ) {
		wp_update_term( $kusina_term->term_id, 'course', $kusina_args );
	} else {
		wp_insert_term( $kusina_name, 'course', $kusina_args + array( 'slug' => $kusina_slug ) );
	}
}
WP_CLI::log( 'Courses: ' . implode( ', ', wp_list_pluck( kusina_courses(), 0 ) ) );

// 12 per page fills the plate grid evenly at 2, 3, 4 and 6 columns (archives inherit this).
update_option( 'posts_per_page', 12 );

// Only these two diet labels exist: allergen claims (gluten-free, dairy-free) are not made.
foreach ( array( 'vegetarian' => 'Vegetarian', 'pescatarian' => 'Pescatarian' ) as $kusina_slug => $kusina_name ) {
	if ( ! get_term_by( 'slug', $kusina_slug, 'diet' ) ) {
		wp_insert_term( $kusina_name, 'diet', array( 'slug' => $kusina_slug ) );
	}
}

/** Creates or updates a page by slug from paragraphs (plain text) and trailing block markup. */
function kusina_seed_page( string $slug, string $title, array $paragraphs, string $blocks = '' ): void {
	$existing = get_page_by_path( $slug );
	$content  = implode(
		"\n\n",
		array_map( static fn( string $p ): string => "<!-- wp:paragraph -->\n<p>" . esc_html( $p ) . "</p>\n<!-- /wp:paragraph -->", $paragraphs )
	) . ( '' !== $blocks ? "\n\n" . $blocks : '' );

	$id = wp_insert_post(
		wp_slash(
			array(
				'ID'           => $existing ? $existing->ID : 0,
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => $slug,
				'post_title'   => $title,
				'post_content' => $content,
			)
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	WP_CLI::log( ( $existing ? 'Updated' : 'Created' ) . " page {$slug} (#{$id})" );
}

// Pages, in the site's voice but only true statements.
kusina_seed_page(
	'about',
	'About',
	array(
		'Kusina Notes is a recipe site for everyday Filipino cooking: the ulam, sabaw and merienda people actually make on a weeknight.',
		'Every recipe opens straight onto the recipe. Change the servings and the amounts follow, switch between metric and US measures, and press Cook mode to get one step at a time with timers, in big type, with the screen kept awake.',
		'It is a personal project built on WordPress and published as a static site. The food photos are AI-generated illustrations of each dish, not photos of these exact recipes.',
	)
);
kusina_seed_page(
	'pantry',
	'What can I cook?',
	array( 'Tick what you have. Recipes that need the fewest extra ingredients come first. The usual basics start ticked; untick any you are out of.' ),
	'<!-- wp:kusina/pantry-finder {"align":"wide"} /-->'
);
kusina_seed_page(
	'list',
	'Shopping list',
	array( 'Everything from the recipes you added, scaled to your servings, added together and sorted by aisle. It is saved in this browser only.' ),
	'<!-- wp:kusina/shopping-list {"align":"wide"} /-->'
);

// Recipes. The validator (scripts/validate-recipes.mjs) is the real check; this only refuses
// records missing the fields the seed reads.
$kusina_recipes = array();
foreach ( glob( __DIR__ . '/recipes/*.json' ) as $kusina_file ) {
	$kusina_data = json_decode( (string) file_get_contents( $kusina_file ), true );
	if ( ! is_array( $kusina_data ) ) {
		WP_CLI::error( basename( $kusina_file ) . ': invalid JSON. Run node scripts/validate-recipes.mjs' );
	}
	foreach ( $kusina_data as $kusina_recipe ) {
		foreach ( array( 'slug', 'title', 'course', 'diets', 'key_ingredients', 'prep', 'cook', 'servings', 'yield_label', 'difficulty', 'intro', 'groups', 'steps', 'tips' ) as $kusina_key ) {
			if ( ! array_key_exists( $kusina_key, (array) $kusina_recipe ) ) {
				WP_CLI::error( basename( $kusina_file ) . ": a recipe is missing \"{$kusina_key}\". Run node scripts/validate-recipes.mjs" );
			}
		}
		$kusina_recipes[ $kusina_recipe['slug'] ] = $kusina_recipe;
	}
}

// Publish dates: one hour apart. Recipes with a photo are newest, so the home page and the
// first index pages lead with photos; within each group a hash of the slug mixes the
// courses. Stable across re-runs. Chicken adobo stays newest (the home page's "Tonight").
$kusina_has_photo = static fn( string $slug ): bool => is_file( __DIR__ . "/images/{$slug}.webp" );
$kusina_order     = array_keys( $kusina_recipes );
usort(
	$kusina_order,
	static fn( string $a, string $b ): int => array( $kusina_has_photo( $a ), crc32( $a ) ) <=> array( $kusina_has_photo( $b ), crc32( $b ) )
);
$kusina_order = array_values( array_diff( $kusina_order, array( 'chicken-adobo' ) ) );
if ( isset( $kusina_recipes['chicken-adobo'] ) ) {
	$kusina_order[] = 'chicken-adobo';
}
$kusina_newest = strtotime( '2026-10-11 08:00:00' );
$kusina_count  = count( $kusina_order );

wp_defer_term_counting( true );
$kusina_stats = array( 'created' => 0, 'updated' => 0, 'failed' => 0 );
foreach ( $kusina_order as $kusina_i => $kusina_slug ) {
	$kusina_date            = gmdate( 'Y-m-d H:i:s', $kusina_newest - ( $kusina_count - 1 - $kusina_i ) * HOUR_IN_SECONDS );
	[ $kusina_id, $kusina_r ] = kusina_seed_recipe( $kusina_recipes[ $kusina_slug ], $kusina_date );
	if ( ! $kusina_id ) {
		++$kusina_stats['failed'];
		WP_CLI::warning( "{$kusina_slug}: {$kusina_r}" );
		continue;
	}
	++$kusina_stats[ $kusina_r ];
	if ( 0 === ( $kusina_i + 1 ) % 100 ) {
		WP_CLI::log( ( $kusina_i + 1 ) . " / {$kusina_count}" );
	}
}
wp_defer_term_counting( false );

WP_CLI::success( "Recipes: {$kusina_stats['created']} created, {$kusina_stats['updated']} updated, {$kusina_stats['failed']} failed." );

// Photos: scripts/images/<slug>.webp becomes the recipe's featured image (AI-generated,
// see scripts/images/README.md; sources in generated.json).
// changed, tracked by a hash in the attachment's meta.
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$kusina_photos = array( 'added' => 0, 'kept' => 0, 'skipped' => 0 );
foreach ( glob( __DIR__ . '/images/*.webp' ) as $kusina_photo ) {
	$kusina_slug = basename( $kusina_photo, '.webp' );
	$kusina_post = get_page_by_path( $kusina_slug, OBJECT, 'recipe' );
	if ( ! $kusina_post ) {
		++$kusina_photos['skipped'];
		WP_CLI::warning( "photo {$kusina_slug}.webp has no recipe" );
		continue;
	}
	$kusina_hash    = md5_file( $kusina_photo );
	$kusina_current = (int) get_post_thumbnail_id( $kusina_post );
	if ( $kusina_current && get_post_meta( $kusina_current, '_kusina_source_hash', true ) === $kusina_hash ) {
		++$kusina_photos['kept'];
		continue;
	}

	// media_handle_sideload() moves the file, and /scripts is read-only: work on a copy.
	$kusina_tmp = wp_tempnam( $kusina_slug . '.webp' );
	copy( $kusina_photo, $kusina_tmp );
	$kusina_attachment = media_handle_sideload(
		array(
			'name'     => $kusina_slug . '.webp',
			'tmp_name' => $kusina_tmp,
		),
		$kusina_post->ID,
		get_the_title( $kusina_post )
	);
	if ( is_wp_error( $kusina_attachment ) ) {
		@unlink( $kusina_tmp );
		++$kusina_photos['skipped'];
		WP_CLI::warning( "{$kusina_slug}: " . $kusina_attachment->get_error_message() );
		continue;
	}
	update_post_meta( $kusina_attachment, '_kusina_source_hash', $kusina_hash );
	update_post_meta( $kusina_attachment, '_wp_attachment_image_alt', get_the_title( $kusina_post ) );
	set_post_thumbnail( $kusina_post, $kusina_attachment );
	if ( $kusina_current ) {
		wp_delete_attachment( $kusina_current, true );
	}
	++$kusina_photos['added'];
}
WP_CLI::log( "Photos: {$kusina_photos['added']} added, {$kusina_photos['kept']} unchanged, {$kusina_photos['skipped']} skipped." );

kusina_flush_recipe_index();
