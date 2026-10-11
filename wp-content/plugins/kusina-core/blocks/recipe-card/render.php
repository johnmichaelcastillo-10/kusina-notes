<?php
/**
 * Front-end markup for kusina/recipe-card. Attributes are untrusted: kusina_normalize_recipe()
 * cleans them and everything is escaped on output. Quantities carry their raw values in
 * data-* attributes for the JS scaler (docs/SPEC.md, section 4).
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$kusina_post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
$kusina_recipe  = kusina_normalize_recipe( $attributes );
$kusina_meta    = kusina_recipe_meta( $kusina_post_id );

$kusina_facts = array();
if ( $kusina_meta['prep'] ) {
	$kusina_facts[ __( 'Prep', 'kusina-core' ) ] = esc_html( kusina_format_minutes( $kusina_meta['prep'] ) );
}
if ( $kusina_meta['cook'] ) {
	$kusina_facts[ __( 'Cook', 'kusina-core' ) ] = esc_html( kusina_format_minutes( $kusina_meta['cook'] ) );
}
if ( $kusina_meta['prep'] && $kusina_meta['cook'] ) {
	$kusina_facts[ __( 'Total', 'kusina-core' ) ] = esc_html( kusina_format_minutes( $kusina_meta['total'] ) );
}
$kusina_servings = '<span class="recipe-servings">' . esc_html( (string) $kusina_meta['servings'] ) . '</span>';
if ( '' !== $kusina_meta['yield'] ) {
	$kusina_facts[ __( 'Makes', 'kusina-core' ) ] = $kusina_servings . ' ' . esc_html( $kusina_meta['yield'] );
} else {
	$kusina_facts[ __( 'Serves', 'kusina-core' ) ] = $kusina_servings;
}
$kusina_facts[ __( 'Difficulty', 'kusina-core' ) ] = esc_html( kusina_difficulty_label( $kusina_meta['difficulty'] ) );

$kusina_qty_attr = static fn( ?float $value ): string => null === $value ? '' : (string) $value;
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'recipe-card' ) ); ?> data-servings="<?php echo esc_attr( (string) $kusina_meta['servings'] ); ?>">
	<dl class="recipe-facts">
		<?php foreach ( $kusina_facts as $kusina_label => $kusina_value ) : ?>
			<div class="recipe-fact"><dt><?php echo esc_html( $kusina_label ); ?></dt><dd><?php echo $kusina_value; // Escaped above. ?></dd></div>
		<?php endforeach; ?>
	</dl>

	<div class="recipe-body">
		<div class="recipe-ingredients">
			<h2><?php esc_html_e( 'Ingredients', 'kusina-core' ); ?></h2>
			<?php foreach ( $kusina_recipe['groups'] as $kusina_group ) : ?>
				<?php if ( '' !== $kusina_group['title'] ) : ?>
					<h3><?php echo esc_html( $kusina_group['title'] ); ?></h3>
				<?php endif; ?>
				<ul class="ingredient-list">
					<?php foreach ( $kusina_group['items'] as $kusina_item ) : ?>
						<?php $kusina_amount = kusina_format_qty( $kusina_item['qty'], $kusina_item['max'], $kusina_item['unit'] ); ?>
						<li class="ingredient">
							<?php if ( 'to-taste' !== $kusina_item['unit'] && '' !== $kusina_amount ) : ?>
								<span class="ingredient-qty" data-qty="<?php echo esc_attr( $kusina_qty_attr( $kusina_item['qty'] ) ); ?>" data-qty-max="<?php echo esc_attr( $kusina_qty_attr( $kusina_item['max'] ) ); ?>" data-unit="<?php echo esc_attr( $kusina_item['unit'] ); ?>"><?php echo esc_html( $kusina_amount ); ?></span>
							<?php endif; ?>
							<span class="ingredient-name"><?php echo esc_html( $kusina_item['name'] ); ?></span><?php if ( 'to-taste' === $kusina_item['unit'] ) : ?><span class="ingredient-note">, <?php echo esc_html( $kusina_amount ); ?></span><?php endif; ?><?php if ( '' !== $kusina_item['note'] ) : ?><span class="ingredient-note">, <?php echo esc_html( $kusina_item['note'] ); ?></span><?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
		</div>

		<div class="recipe-method">
			<h2><?php esc_html_e( 'Method', 'kusina-core' ); ?></h2>
			<ol class="recipe-steps">
				<?php foreach ( $kusina_recipe['steps'] as $kusina_step ) : ?>
					<li class="recipe-step"<?php echo $kusina_step['minutes'] ? ' data-minutes="' . esc_attr( (string) $kusina_step['minutes'] ) . '"' : ''; ?>>
						<p class="step-text"><?php echo $kusina_step['text']; // Filtered by wp_kses in kusina_normalize_recipe(). ?></p>
						<?php if ( $kusina_step['minutes'] ) : ?>
							<span class="step-time"><?php echo esc_html( kusina_format_minutes( $kusina_step['minutes'] ) ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>

			<?php if ( '' !== $kusina_recipe['tips'] ) : ?>
				<aside class="recipe-tips">
					<h2><?php esc_html_e( 'Tips', 'kusina-core' ); ?></h2>
					<p><?php echo $kusina_recipe['tips']; // Filtered by wp_kses in kusina_normalize_recipe(). ?></p>
				</aside>
			<?php endif; ?>
		</div>
	</div>
</section>
