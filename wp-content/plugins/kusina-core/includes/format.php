<?php
/**
 * Recipe data helpers shared by the recipe card render and the JSON-LD.
 *
 * kusina_format_qty() and kusina_units() define how quantities read on the page. The JS
 * scaler (docs/SPEC.md, section 4) rewrites quantities in the browser and must produce the
 * same text, so change both together.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Allowed units: slug => [ singular, plural ]. '' is a plain count ("2 eggs").
 * Mirrored in blocks/recipe-card/editor.js.
 */
function kusina_units(): array {
	return array(
		''         => array( '', '' ),
		'g'        => array( 'g', 'g' ),
		'kg'       => array( 'kg', 'kg' ),
		'ml'       => array( 'ml', 'ml' ),
		'l'        => array( 'L', 'L' ),
		'tsp'      => array( 'tsp', 'tsp' ),
		'tbsp'     => array( 'tbsp', 'tbsp' ),
		'cup'      => array( 'cup', 'cups' ),
		'piece'    => array( 'piece', 'pieces' ),
		'clove'    => array( 'clove', 'cloves' ),
		'thumb'    => array( 'thumb', 'thumbs' ),
		'pinch'    => array( 'pinch', 'pinches' ),
		'to-taste' => array( 'to taste', 'to taste' ),
	);
}

/** Inline HTML allowed in step text and tips. */
function kusina_inline_tags(): array {
	return array(
		'a'      => array( 'href' => true ),
		'strong' => array(),
		'em'     => array(),
		'br'     => array(),
	);
}

function kusina_difficulty_label( string $difficulty ): string {
	$labels = array(
		'easy'     => __( 'Easy', 'kusina-core' ),
		'medium'   => __( 'Medium', 'kusina-core' ),
		'involved' => __( 'Involved', 'kusina-core' ),
	);
	return $labels[ $difficulty ] ?? $labels['easy'];
}

/** A positive quantity rounded to 3 decimals, or null. */
function kusina_to_qty( mixed $value ): ?float {
	if ( ! is_numeric( $value ) || (float) $value <= 0 ) {
		return null;
	}
	return round( (float) $value, 3 );
}

/** Number shown for a quantity: kitchen fractions for spoon/cup/count units. */
function kusina_format_number( float $n, string $unit ): string {
	if ( in_array( $unit, array( 'g', 'ml' ), true ) && $n > 50 ) {
		return (string) (int) round( $n );
	}

	if ( in_array( $unit, array( '', 'tsp', 'tbsp', 'cup', 'piece', 'clove', 'thumb', 'pinch' ), true ) ) {
		$whole = (int) floor( $n );
		$rest  = $n - $whole;

		if ( $rest > 0.96 ) {
			return (string) ( $whole + 1 );
		}
		if ( $rest < 0.04 && $whole > 0 ) {
			return (string) $whole;
		}

		$fractions = array(
			array( 1 / 8, '⅛' ),
			array( 1 / 4, '¼' ),
			array( 1 / 3, '⅓' ),
			array( 3 / 8, '⅜' ),
			array( 1 / 2, '½' ),
			array( 5 / 8, '⅝' ),
			array( 2 / 3, '⅔' ),
			array( 3 / 4, '¾' ),
			array( 7 / 8, '⅞' ),
		);
		$best = null;
		foreach ( $fractions as [ $value, $glyph ] ) {
			$distance = abs( $rest - $value );
			if ( $distance <= 0.04 && ( null === $best || $distance < $best[0] ) ) {
				$best = array( $distance, $glyph );
			}
		}
		if ( $best ) {
			return ( $whole > 0 ? (string) $whole : '' ) . $best[1];
		}
	}

	return rtrim( rtrim( number_format( $n, 2, '.', '' ), '0' ), '.' );
}

/**
 * Quantity and unit as read on the page: "2 tbsp", "1½ cups", "1–2", "500 g", "pinch",
 * "to taste". Empty when there is nothing to show (e.g. "salt" with no amount).
 */
function kusina_format_qty( ?float $qty, ?float $max, string $unit ): string {
	$units  = kusina_units();
	$labels = $units[ $unit ] ?? $units[''];

	if ( 'to-taste' === $unit ) {
		return $labels[0];
	}
	if ( null === $qty ) {
		return 'pinch' === $unit ? $labels[0] : '';
	}

	$number = kusina_format_number( $qty, $unit );
	if ( null !== $max ) {
		$number .= '–' . kusina_format_number( $max, $unit );
	}

	$label = ( $max ?? $qty ) > 1 ? $labels[1] : $labels[0];
	return '' === $label ? $number : $number . ' ' . $label;
}

/** "45 min", "1 hr", "1 hr 15 min". */
function kusina_format_minutes( int $minutes ): string {
	$hours = intdiv( $minutes, 60 );
	$rest  = $minutes % 60;

	if ( 0 === $hours ) {
		/* translators: %d: minutes */
		return sprintf( __( '%d min', 'kusina-core' ), $rest );
	}
	if ( 0 === $rest ) {
		/* translators: %d: hours */
		return sprintf( __( '%d hr', 'kusina-core' ), $hours );
	}
	/* translators: 1: hours, 2: minutes */
	return sprintf( __( '%1$d hr %2$d min', 'kusina-core' ), $hours, $rest );
}

/** ISO 8601 duration for schema.org: PT45M, PT1H15M. Empty for 0. */
function kusina_iso_duration( int $minutes ): string {
	if ( $minutes <= 0 ) {
		return '';
	}
	$hours = intdiv( $minutes, 60 );
	$rest  = $minutes % 60;
	return 'PT' . ( $hours ? $hours . 'H' : '' ) . ( $rest ? $rest . 'M' : '' );
}

/** One ingredient as plain text: "2 tbsp soy sauce, more to taste". */
function kusina_ingredient_text( array $item ): string {
	$amount = kusina_format_qty( $item['qty'], $item['max'], $item['unit'] );
	// "to taste" reads after the name: "salt, to taste".
	if ( 'to-taste' === $item['unit'] ) {
		$text = $item['name'] . ', ' . $amount;
	} else {
		$text = trim( $amount . ' ' . $item['name'] );
	}
	return '' === $item['note'] ? $text : $text . ', ' . $item['note'];
}

/**
 * The recipe card's attributes cleaned up: untrusted input in, only whitelisted units,
 * positive numbers and plain or kses-filtered text out. Empty rows are dropped.
 */
function kusina_normalize_recipe( array $attributes ): array {
	$units  = kusina_units();
	$groups = array();

	foreach ( (array) ( $attributes['groups'] ?? array() ) as $group ) {
		if ( ! is_array( $group ) ) {
			continue;
		}
		$items = array();
		foreach ( (array) ( $group['items'] ?? array() ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$name = sanitize_text_field( (string) ( $item['name'] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}
			$unit = (string) ( $item['unit'] ?? '' );
			$qty  = kusina_to_qty( $item['qty'] ?? null );
			$max  = kusina_to_qty( $item['qtyMax'] ?? null );
			if ( null === $qty || ( null !== $max && $max <= $qty ) ) {
				$max = null;
			}
			$items[] = array(
				'qty'  => $qty,
				'max'  => $max,
				'unit' => isset( $units[ $unit ] ) ? $unit : '',
				'name' => $name,
				'note' => sanitize_text_field( (string) ( $item['note'] ?? '' ) ),
			);
		}
		if ( $items ) {
			$groups[] = array(
				'title' => sanitize_text_field( (string) ( $group['title'] ?? '' ) ),
				'items' => $items,
			);
		}
	}

	$steps = array();
	foreach ( (array) ( $attributes['steps'] ?? array() ) as $step ) {
		if ( ! is_array( $step ) ) {
			continue;
		}
		$text = trim( wp_kses( (string) ( $step['text'] ?? '' ), kusina_inline_tags() ) );
		if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
			continue;
		}
		$minutes = $step['minutes'] ?? null;
		$steps[] = array(
			'text'    => $text,
			'minutes' => is_numeric( $minutes ) && (int) $minutes > 0 ? (int) $minutes : null,
		);
	}

	$tips = trim( wp_kses( (string) ( $attributes['tips'] ?? '' ), kusina_inline_tags() ) );

	return array(
		'groups' => $groups,
		'steps'  => $steps,
		'tips'   => '' === trim( wp_strip_all_tags( $tips ) ) ? '' : $tips,
	);
}

/** Times, servings and difficulty from post meta, cast to safe values. */
function kusina_recipe_meta( int $post_id ): array {
	$prep = max( 0, (int) get_post_meta( $post_id, 'prep_minutes', true ) );
	$cook = max( 0, (int) get_post_meta( $post_id, 'cook_minutes', true ) );

	return array(
		'prep'       => $prep,
		'cook'       => $cook,
		'total'      => $prep + $cook,
		'servings'   => max( 1, (int) get_post_meta( $post_id, 'servings', true ) ),
		'yield'      => sanitize_text_field( (string) get_post_meta( $post_id, 'yield_label', true ) ),
		'difficulty' => kusina_sanitize_difficulty( get_post_meta( $post_id, 'difficulty', true ) ),
	);
}

/** "4 servings" or "12 pieces". */
function kusina_yield_text( array $meta ): string {
	if ( '' !== $meta['yield'] ) {
		return $meta['servings'] . ' ' . $meta['yield'];
	}
	/* translators: %d: number of servings */
	return sprintf( _n( '%d serving', '%d servings', $meta['servings'], 'kusina-core' ), $meta['servings'] );
}
