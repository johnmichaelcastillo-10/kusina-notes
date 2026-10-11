<?php
/**
 * Market-sign tags for one recipe (docs/DESIGN.md, "Market tags"). The theme styles .tag;
 * each tag type has a fixed tilt so layouts and screenshots stay stable.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$kusina_post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( ! $kusina_post_id || 'recipe' !== get_post_type( $kusina_post_id ) ) {
	return;
}

$kusina_show = array_intersect( (array) ( $attributes['show'] ?? array() ), array( 'time', 'difficulty', 'servings', 'diet' ) );
$kusina_meta = kusina_recipe_meta( $kusina_post_id );
$kusina_tags = array();

foreach ( $kusina_show as $kusina_kind ) {
	switch ( $kusina_kind ) {
		case 'time':
			if ( $kusina_meta['total'] ) {
				$kusina_tags[] = array( 'time', kusina_format_minutes( $kusina_meta['total'] ) );
			}
			break;
		case 'difficulty':
			$kusina_tags[] = array( 'difficulty', kusina_difficulty_label( $kusina_meta['difficulty'] ) );
			break;
		case 'servings':
			$kusina_tags[] = array(
				'servings',
				'' !== $kusina_meta['yield']
					/* translators: %s: e.g. "12 pieces" */
					? sprintf( __( 'Makes %s', 'kusina-core' ), kusina_yield_text( $kusina_meta ) )
					/* translators: %d: number of servings */
					: sprintf( __( 'Serves %d', 'kusina-core' ), $kusina_meta['servings'] ),
			);
			break;
		case 'diet':
			foreach ( kusina_term_names( $kusina_post_id, 'diet' ) as $kusina_diet ) {
				$kusina_tags[] = array( 'diet', $kusina_diet );
			}
			break;
	}
}

if ( ! $kusina_tags ) {
	return;
}
?>
<ul <?php echo get_block_wrapper_attributes( array( 'class' => 'tags' ) ); ?>>
	<?php foreach ( $kusina_tags as [ $kusina_kind, $kusina_text ] ) : ?>
		<li class="tag tag-<?php echo esc_attr( $kusina_kind ); ?>"><?php echo esc_html( $kusina_text ); ?></li>
	<?php endforeach; ?>
</ul>
