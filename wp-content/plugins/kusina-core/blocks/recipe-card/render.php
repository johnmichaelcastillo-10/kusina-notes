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

// What "Add to shopping list" stores: the cleaned ingredients at the default servings. JSON
// in a non-executable script tag, so the static site's CSP needs no hash for it.
$kusina_list_data = array(
	'slug'     => (string) get_post_field( 'post_name', $kusina_post_id ),
	'title'    => kusina_plain_text( get_the_title( $kusina_post_id ) ),
	'url'      => wp_make_link_relative( (string) get_permalink( $kusina_post_id ) ),
	'course'   => kusina_recipe_course( $kusina_post_id ),
	'servings' => $kusina_meta['servings'],
	'items'    => array(),
);
foreach ( $kusina_recipe['groups'] as $kusina_group ) {
	foreach ( $kusina_group['items'] as $kusina_item ) {
		$kusina_list_data['items'][] = array(
			'qty'  => $kusina_item['qty'],
			'max'  => $kusina_item['max'],
			'unit' => $kusina_item['unit'],
			'name' => $kusina_item['name'],
		);
	}
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'recipe-card' ) ); ?> data-servings="<?php echo esc_attr( (string) $kusina_meta['servings'] ); ?>">
	<script type="application/json" class="recipe-data"><?php echo wp_json_encode( $kusina_list_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>
	<dl class="recipe-facts">
		<?php foreach ( $kusina_facts as $kusina_label => $kusina_value ) : ?>
			<div class="recipe-fact"><dt><?php echo esc_html( $kusina_label ); ?></dt><dd><?php echo $kusina_value; // Escaped above. ?></dd></div>
		<?php endforeach; ?>
	</dl>

	<div class="recipe-body">
		<div class="recipe-ingredients">
			<h2><?php esc_html_e( 'Ingredients', 'kusina-core' ); ?></h2>
			<?php // Scaler and unit switch: hidden until view.js runs, so no-JS visitors never see dead controls. ?>
			<div class="recipe-scale" data-recipe-scale hidden
				data-noun-one="<?php echo esc_attr( '' !== $kusina_meta['yield'] ? $kusina_meta['yield'] : __( 'serving', 'kusina-core' ) ); ?>"
				data-noun-many="<?php echo esc_attr( '' !== $kusina_meta['yield'] ? $kusina_meta['yield'] : __( 'servings', 'kusina-core' ) ); ?>"
				data-msg="<?php /* translators: %s: e.g. "6 servings" */ echo esc_attr__( 'Ingredients updated for %s', 'kusina-core' ); ?>">
				<div class="servings-stepper" role="group" aria-labelledby="kusina-servings-label">
					<span class="tool-label" id="kusina-servings-label"><?php echo '' !== $kusina_meta['yield'] ? esc_html__( 'Makes', 'kusina-core' ) : esc_html__( 'Serves', 'kusina-core' ); ?></span>
					<button type="button" class="kn-icon-btn" data-scale="down" aria-label="<?php esc_attr_e( 'Fewer', 'kusina-core' ); ?>">&minus;</button>
					<input type="number" class="servings-input" min="1" max="99" step="1" inputmode="numeric" value="<?php echo esc_attr( (string) $kusina_meta['servings'] ); ?>" aria-labelledby="kusina-servings-label">
					<button type="button" class="kn-icon-btn" data-scale="up" aria-label="<?php esc_attr_e( 'More', 'kusina-core' ); ?>">+</button>
					<?php if ( '' !== $kusina_meta['yield'] ) : ?>
						<span class="tool-suffix"><?php echo esc_html( $kusina_meta['yield'] ); ?></span>
					<?php endif; ?>
				</div>
				<fieldset class="unit-switch">
					<legend class="kn-visually-hidden"><?php esc_html_e( 'Units', 'kusina-core' ); ?></legend>
					<?php
					$kusina_modes = array(
						'original' => __( 'As written', 'kusina-core' ),
						'metric'   => __( 'Metric', 'kusina-core' ),
						'us'       => __( 'US', 'kusina-core' ),
					);
					foreach ( $kusina_modes as $kusina_mode => $kusina_mode_label ) :
						?>
						<label><input type="radio" name="kusina-units" value="<?php echo esc_attr( $kusina_mode ); ?>"<?php checked( 'original', $kusina_mode ); ?>><span><?php echo esc_html( $kusina_mode_label ); ?></span></label>
					<?php endforeach; ?>
				</fieldset>
				<button type="button" class="kn-btn list-add" data-list-add aria-pressed="false"
					data-label-add="<?php esc_attr_e( 'Add to shopping list', 'kusina-core' ); ?>"
					data-label-added="<?php esc_attr_e( 'On your shopping list', 'kusina-core' ); ?>"
					data-msg-failed="<?php esc_attr_e( 'This browser blocked saving the list.', 'kusina-core' ); ?>"><?php esc_html_e( 'Add to shopping list', 'kusina-core' ); ?></button>
				<p class="list-add-note" data-list-note hidden><a href="/list/"><?php esc_html_e( 'View your list', 'kusina-core' ); ?></a></p>
				<p class="kn-visually-hidden" data-scale-status aria-live="polite"></p>
			</div>
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
			<div class="method-head">
				<h2><?php esc_html_e( 'Method', 'kusina-core' ); ?></h2>
				<?php if ( $kusina_recipe['steps'] ) : ?>
					<button type="button" class="kn-btn" data-cook-open hidden><?php esc_html_e( 'Cook mode', 'kusina-core' ); ?></button>
				<?php endif; ?>
			</div>
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
					<h2><?php esc_html_e( 'Tita’s note', 'kusina-core' ); ?></h2>
					<p><?php echo $kusina_recipe['tips']; // Filtered by wp_kses in kusina_normalize_recipe(). ?></p>
				</aside>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $kusina_recipe['steps'] ) : ?>
		<?php // Cook mode dialog, cloned into <body> by view.js. Labels live here so they go through __(). ?>
		<template class="cook-mode-template">
			<dialog class="cook-mode" aria-labelledby="kusina-cook-title"
				data-label-start="<?php esc_attr_e( 'Start timer', 'kusina-core' ); ?>"
				data-label-pause="<?php esc_attr_e( 'Pause', 'kusina-core' ); ?>"
				data-label-resume="<?php esc_attr_e( 'Resume', 'kusina-core' ); ?>"
				data-label-done="<?php esc_attr_e( 'Done', 'kusina-core' ); ?>"
				data-label-next="<?php esc_attr_e( 'Next step', 'kusina-core' ); ?>"
				data-label-finish="<?php esc_attr_e( 'Kain na!', 'kusina-core' ); ?>"
				data-label-step="<?php /* translators: %s: step number */ echo esc_attr__( 'Step %s', 'kusina-core' ); ?>"
				data-msg-done="<?php /* translators: %s: step number */ echo esc_attr__( 'Timer done for step %s', 'kusina-core' ); ?>"
				data-title-done="<?php esc_attr_e( 'Timer done', 'kusina-core' ); ?>">
				<div class="cook-mode-inner">
					<header class="cook-head">
						<div class="cook-head-text">
							<p class="cook-title" id="kusina-cook-title"><?php echo esc_html( get_the_title( $kusina_post_id ) ); ?></p>
							<p class="cook-progress">
								<?php
								printf(
									/* translators: 1: current step number, 2: number of steps */
									esc_html__( 'Step %1$s of %2$s', 'kusina-core' ),
									'<span data-cook="n"></span>',
									'<span data-cook="total"></span>'
								);
								?>
							</p>
						</div>
						<button type="button" class="kn-btn cook-ingredients-toggle" data-cook="toggle-ingredients" aria-expanded="false" aria-controls="kusina-cook-ingredients"><?php esc_html_e( 'Ingredients', 'kusina-core' ); ?></button>
						<button type="button" class="kn-btn" data-cook="close"><?php esc_html_e( 'Close', 'kusina-core' ); ?></button>
					</header>
					<aside class="cook-ingredients" id="kusina-cook-ingredients" data-cook="ingredients" hidden>
						<h2><?php esc_html_e( 'Ingredients', 'kusina-core' ); ?></h2>
						<div data-cook="ingredient-list"></div>
					</aside>
					<div class="cook-step" data-cook="step" aria-live="polite"></div>
					<div class="cook-timer" data-cook="timer" hidden>
						<p class="cook-timer-display" data-cook="time" role="timer"></p>
						<button type="button" class="kn-btn" data-cook="timer-start"></button>
						<button type="button" class="kn-btn kn-btn-quiet" data-cook="timer-reset"><?php esc_html_e( 'Reset', 'kusina-core' ); ?></button>
					</div>
					<div class="cook-running" data-cook="running" role="group" aria-label="<?php esc_attr_e( 'Other timers', 'kusina-core' ); ?>" hidden></div>
					<p class="kn-visually-hidden" data-cook="status" aria-live="assertive"></p>
					<footer class="cook-nav">
						<button type="button" class="kn-btn kn-btn-large" data-cook="prev"><?php esc_html_e( 'Previous', 'kusina-core' ); ?></button>
						<button type="button" class="kn-btn kn-btn-large kn-btn-primary" data-cook="next"></button>
					</footer>
				</div>
			</dialog>
		</template>
	<?php endif; ?>
</section>
