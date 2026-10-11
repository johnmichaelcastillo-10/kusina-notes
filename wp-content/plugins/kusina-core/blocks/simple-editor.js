/**
 * Editor side of the small server-rendered blocks (recipe tags, course chips). Plain JS on
 * the wp.* globals, no build step. Both render in PHP; the editor shows a preview.
 */
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, CheckboxControl, ToggleControl } = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const { __ } = wp.i18n;

	const TAG_KINDS = [
		[ 'time', __( 'Total time', 'kusina-core' ) ],
		[ 'difficulty', __( 'Difficulty', 'kusina-core' ) ],
		[ 'servings', __( 'Servings', 'kusina-core' ) ],
		[ 'diet', __( 'Diets', 'kusina-core' ) ],
	];

	// Tags depend on the current post (a Query Loop item, or the recipe being edited), which
	// ServerSideRender can't pass, so the editor shows sample tags of the chosen kinds.
	registerBlockType( 'kusina/recipe-tags', {
		edit( { attributes, setAttributes } ) {
			const show = attributes.show || [];
			const sample = { time: '1 hr', difficulty: __( 'Easy', 'kusina-core' ), servings: __( 'Serves 4', 'kusina-core' ), diet: __( 'Vegetarian', 'kusina-core' ) };

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Tags to show', 'kusina-core' ) },
						TAG_KINDS.map( ( [ kind, label ] ) =>
							el( CheckboxControl, {
								key: kind,
								__nextHasNoMarginBottom: true,
								label,
								checked: show.includes( kind ),
								onChange: ( on ) =>
									setAttributes( {
										show: on ? TAG_KINDS.map( ( k ) => k[ 0 ] ).filter( ( k ) => k === kind || show.includes( k ) ) : show.filter( ( k ) => k !== kind ),
									} ),
							} )
						)
					)
				),
				el(
					'ul',
					{ className: 'tags' },
					show.map( ( kind ) => el( 'li', { key: kind, className: `tag tag-${ kind }` }, sample[ kind ] ) )
				)
			);
		},
		save: () => null,
	} );

	// Pantry finder and shopping list run in the visitor's browser; the editor shows a note.
	const placeholder = ( text ) =>
		function Edit() {
			return el( 'div', useBlockProps( { className: 'kusina-placeholder' } ), el( 'p', null, text ) );
		};
	registerBlockType( 'kusina/pantry-finder', {
		edit: placeholder( __( 'Pantry finder: ingredient chips and ranked recipes appear here on the site.', 'kusina-core' ) ),
		save: () => null,
	} );
	registerBlockType( 'kusina/recipe-filters', {
		edit: placeholder( __( 'Recipe filters: course, diet and time toggles over the recipe list appear here on the site.', 'kusina-core' ) ),
		save: () => null,
	} );
	registerBlockType( 'kusina/shopping-list', {
		edit: placeholder( __( "Shopping list: the visitor's saved recipes and merged ingredients appear here on the site.", 'kusina-core' ) ),
		save: () => null,
	} );

	registerBlockType( 'kusina/course-chips', {
		edit( { attributes, setAttributes } ) {
			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Settings', 'kusina-core' ) },
						el( ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Show English gloss', 'kusina-core' ),
							checked: !! attributes.showGloss,
							onChange: ( value ) => setAttributes( { showGloss: value } ),
						} )
					)
				),
				el( ServerSideRender, { block: 'kusina/course-chips', attributes } )
			);
		},
		save: () => null,
	} );
} )( window.wp );
