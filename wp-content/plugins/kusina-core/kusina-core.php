<?php
/**
 * Plugin Name:       Kusina Core
 * Description:       Recipes for the Kusina Notes theme: post type, taxonomies and recipe meta.
 * Version:           0.7.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            John Michael Castillo
 * License:           GPL-2.0-or-later
 * Text Domain:       kusina-core
 *
 * Content model: docs/SPEC.md, section 3.
 */

defined( 'ABSPATH' ) || exit;

const KUSINA_CORE_VERSION = '0.7.0';

require_once __DIR__ . '/includes/format.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/index-api.php';

add_action( 'init', 'kusina_register_content_model' );

add_action(
	'init',
	static function (): void {
		// One editor script for the small server-rendered blocks; their block.json names it.
		wp_register_script(
			'kusina-simple-blocks-editor',
			plugins_url( 'blocks/simple-editor.js', __FILE__ ),
			array( 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
			KUSINA_CORE_VERSION,
			true
		);

		register_block_type( __DIR__ . '/blocks/recipe-card' );
		register_block_type( __DIR__ . '/blocks/recipe-tags' );
		register_block_type( __DIR__ . '/blocks/course-chips' );
		register_block_type( __DIR__ . '/blocks/pantry-finder' );
		register_block_type( __DIR__ . '/blocks/shopping-list' );
		register_block_type( __DIR__ . '/blocks/recipe-filters' );
	}
);

// No users sitemap, so the static export never publishes an author archive (and with it the
// admin username).
add_filter(
	'wp_sitemaps_add_provider',
	static fn( $provider, string $name ) => 'users' === $name ? false : $provider,
	10,
	2
);

// Lets the theme colour a whole page by course: course-<slug> on <body> for a recipe or a
// course archive (Query Loop items already get it from post_class()).
add_filter(
	'body_class',
	static function ( array $classes ): array {
		$slug = '';
		if ( is_singular( 'recipe' ) ) {
			$slug = kusina_recipe_course( get_queried_object_id() );
		} elseif ( is_tax( 'course' ) ) {
			$slug = (string) get_queried_object()->slug;
		}
		if ( '' !== $slug ) {
			$classes[] = 'course-' . sanitize_html_class( $slug );
		}
		return $classes;
	}
);

// Recipe details panel (times, servings, difficulty) in the editor sidebar.
add_action(
	'enqueue_block_editor_assets',
	static function (): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'recipe' !== $screen->post_type ) {
			return;
		}

		$asset = require __DIR__ . '/assets/recipe-meta.asset.php';
		wp_enqueue_script(
			'kusina-recipe-meta',
			plugins_url( 'assets/recipe-meta.js', __FILE__ ),
			$asset['dependencies'],
			$asset['version'],
			true
		);
	}
);

function kusina_register_content_model(): void {
	register_post_type(
		'recipe',
		array(
			'labels'       => array(
				'name'          => __( 'Recipes', 'kusina-core' ),
				'singular_name' => __( 'Recipe', 'kusina-core' ),
				'add_new_item'  => __( 'Add new recipe', 'kusina-core' ),
				'edit_item'     => __( 'Edit recipe', 'kusina-core' ),
				'all_items'     => __( 'All recipes', 'kusina-core' ),
			),
			'public'       => true,
			'has_archive'  => 'recipes',
			'rewrite'      => array( 'slug' => 'recipes', 'with_front' => false ),
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-carrot',
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
			'template'     => array(
				array( 'core/paragraph', array( 'placeholder' => __( 'One or two short paragraphs: what this is and why it works.', 'kusina-core' ) ) ),
				array( 'kusina/recipe-card', array( 'align' => 'wide' ) ),
			),
		)
	);

	$taxonomies = array(
		'course'         => array( __( 'Courses', 'kusina-core' ), __( 'Course', 'kusina-core' ), 'course' ),
		'diet'           => array( __( 'Diets', 'kusina-core' ), __( 'Diet', 'kusina-core' ), 'diet' ),
		'key_ingredient' => array( __( 'Key ingredients', 'kusina-core' ), __( 'Key ingredient', 'kusina-core' ), 'ingredient' ),
	);

	foreach ( $taxonomies as $taxonomy => [ $plural, $singular, $slug ] ) {
		register_taxonomy(
			$taxonomy,
			'recipe',
			array(
				'labels'            => array(
					'name'          => $plural,
					'singular_name' => $singular,
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => $slug, 'with_front' => false ),
			)
		);
	}

	$meta = array(
		'prep_minutes' => array( 'integer', 0 ),
		'cook_minutes' => array( 'integer', 0 ),
		'servings'     => array( 'integer', 4 ),
		'yield_label'  => array( 'string', '' ),
		'difficulty'   => array( 'string', 'easy' ),
	);

	foreach ( $meta as $key => [ $type, $default ] ) {
		register_post_meta(
			'recipe',
			$key,
			array(
				'type'              => $type,
				'single'            => true,
				'default'           => $default,
				'show_in_rest'      => true,
				'sanitize_callback' => 'difficulty' === $key ? 'kusina_sanitize_difficulty' : null,
				'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
			)
		);
	}
}

function kusina_sanitize_difficulty( $value ): string {
	return in_array( $value, array( 'easy', 'medium', 'involved' ), true ) ? $value : 'easy';
}

// Flush rewrite rules once so /recipes/ works straight after activation.
register_activation_hook(
	__FILE__,
	static function (): void {
		kusina_register_content_model();
		flush_rewrite_rules();
	}
);

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
