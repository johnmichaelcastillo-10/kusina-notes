<?php
/**
 * Plugin Name:       Kusina Core
 * Description:       Recipes for the Kusina Notes theme: post type, taxonomies and recipe meta.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            John Michael Castillo
 * License:           GPL-2.0-or-later
 * Text Domain:       kusina-core
 *
 * Content model: docs/SPEC.md, section 3.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'kusina_register_content_model' );

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
