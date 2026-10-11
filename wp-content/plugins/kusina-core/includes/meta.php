<?php
/**
 * Sharing and search meta (docs/SPEC.md, section 5): meta description, canonical for the
 * pages WordPress gives none (home, archives), Open Graph and a Twitter card. URLs are
 * absolute local ones; the static exporter rewrites them to the live domain and downloads
 * the share image (it follows content="" attributes).
 *
 * Share image: the recipe's photo cropped to 1200 x 630 (size kusina-og); every other page
 * uses the newest recipe's photo, which is the home page's "Tonight" pick.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function (): void {
		add_image_size( 'kusina-og', 1200, 630, true );
	}
);

add_action( 'wp_head', 'kusina_print_share_meta', 5 );

function kusina_print_share_meta(): void {
	if ( is_404() || is_search() ) {
		return;
	}
	$meta = kusina_share_meta();

	$tags = array(
		array( 'name', 'description', $meta['description'] ),
		array( 'property', 'og:site_name', get_bloginfo( 'name' ) ),
		array( 'property', 'og:locale', get_locale() ),
		array( 'property', 'og:type', is_singular( 'recipe' ) ? 'article' : 'website' ),
		array( 'property', 'og:title', $meta['title'] ),
		array( 'property', 'og:description', $meta['description'] ),
		array( 'property', 'og:url', $meta['url'] ),
	);
	if ( $meta['image'] ) {
		[ $src, $width, $height ] = $meta['image'];
		array_push(
			$tags,
			array( 'property', 'og:image', $src ),
			array( 'property', 'og:image:width', (string) $width ),
			array( 'property', 'og:image:height', (string) $height ),
			array( 'property', 'og:image:alt', $meta['image_alt'] )
		);
	}
	$tags[] = array( 'name', 'twitter:card', $meta['image'] ? 'summary_large_image' : 'summary' );

	// WordPress prints a canonical on singular pages only.
	if ( ! is_singular() ) {
		printf( "<link rel=\"canonical\" href=\"%s\" />\n", esc_url( $meta['url'] ) );
	}
	foreach ( $tags as [ $attr, $key, $value ] ) {
		if ( '' !== $value ) {
			printf( "<meta %s=\"%s\" content=\"%s\" />\n", $attr, esc_attr( $key ), esc_attr( $value ) );
		}
	}
}

/** Title, description, URL and image for the current page. */
function kusina_share_meta(): array {
	$site  = get_bloginfo( 'name' );
	$title = $site;
	$desc  = __( 'Everyday Filipino cooking: fifty favorites from adobo to ube halaya, with times, servings, a cook mode and a pantry finder. Dinner in under an hour, no life story first.', 'kusina-core' );
	$post  = null;

	if ( is_singular() ) {
		$post  = get_queried_object();
		$title = kusina_plain_text( get_the_title( $post ) );
		$text  = kusina_recipe_description( $post, parse_blocks( $post->post_content ) );
		$desc  = '' !== $text ? $text : $desc;
	} elseif ( is_post_type_archive( 'recipe' ) ) {
		$title = __( 'All recipes', 'kusina-core' );
		$desc  = __( 'Every recipe on Kusina Notes: everyday Filipino ulam, sabaw, merienda, desserts, breakfasts and drinks, newest first.', 'kusina-core' );
	} elseif ( is_tax() ) {
		$term  = get_queried_object();
		$title = kusina_plain_text( $term->name );
		$gloss = 'course' === $term->taxonomy ? ( kusina_courses()[ $term->slug ][1] ?? '' ) : '';
		if ( '' !== trim( $term->description ) ) {
			// Term descriptions are one short line ("Mains: what goes on the rice.").
			$desc = kusina_plain_text( $term->description ) . ' ' . __( 'Everyday Filipino recipes from Kusina Notes, with times and servings.', 'kusina-core' );
		} elseif ( '' !== $gloss ) {
			/* translators: 1: course name, e.g. Ulam, 2: English gloss, e.g. Mains */
			$desc = sprintf( __( '%1$s (%2$s): everyday Filipino recipes from Kusina Notes, with times and servings.', 'kusina-core' ), $title, strtolower( $gloss ) );
		} else {
			/* translators: %s: term name, e.g. Vegetarian */
			$desc = sprintf( __( '%s recipes from Kusina Notes: everyday Filipino cooking, with times and servings.', 'kusina-core' ), $title );
		}
	}

	// Recipes share their own photo, everything else the newest recipe's.
	$image_post = $post && 'recipe' === $post->post_type ? $post : kusina_newest_recipe_with_photo();
	$image      = null;
	$alt        = '';
	if ( $image_post && has_post_thumbnail( $image_post ) ) {
		$id    = get_post_thumbnail_id( $image_post );
		$src   = wp_get_attachment_image_src( $id, 'kusina-og' );
		$image = $src ? array( $src[0], $src[1], $src[2] ) : null;
		$alt   = kusina_plain_text( get_the_title( $image_post ) );
	}

	return array(
		'title'       => $title,
		'description' => wp_html_excerpt( $desc, 300, '…' ),
		'url'         => kusina_current_url(),
		'image'       => $image,
		'image_alt'   => $alt,
	);
}

function kusina_newest_recipe_with_photo(): ?WP_Post {
	$posts = get_posts(
		array(
			'post_type'   => 'recipe',
			'numberposts' => 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'meta_key'    => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery -- one row, 50 recipes.
		)
	);
	return $posts[0] ?? null;
}

/** The page's own URL without a query string (paged archives keep their /page/n/). */
function kusina_current_url(): string {
	if ( is_singular() ) {
		return (string) get_permalink( get_queried_object() );
	}
	global $wp;
	return trailingslashit( home_url( $wp->request ) );
}
