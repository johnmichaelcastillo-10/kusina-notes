/**
 * "Recipe details" panel in the editor sidebar: times, servings and difficulty, stored in
 * post meta (registered in kusina-core.php). Plain JS on the wp.* globals, no build step.
 */
( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const { createElement: el } = wp.element;
	const { useSelect } = wp.data;
	const { useEntityProp } = wp.coreData;
	const { TextControl, SelectControl } = wp.components;
	const { __ } = wp.i18n;

	const Panel =
		( wp.editor && wp.editor.PluginDocumentSettingPanel ) ||
		( wp.editPost && wp.editPost.PluginDocumentSettingPanel );

	if ( ! Panel ) {
		return;
	}

	const FIELD = { __nextHasNoMarginBottom: true, __next40pxDefaultSize: true };

	function RecipeDetails() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const [ meta, setMeta ] = useEntityProp( 'postType', 'recipe', 'meta' );

		if ( postType !== 'recipe' || ! meta ) {
			return null;
		}

		const set = ( key, value ) => setMeta( { ...meta, [ key ]: value } );
		const minutes = ( key, label ) =>
			el( TextControl, {
				...FIELD,
				label,
				type: 'number',
				min: 0,
				value: meta[ key ] ?? 0,
				onChange: ( value ) => set( key, Math.max( 0, parseInt( value, 10 ) || 0 ) ),
			} );

		return el(
			Panel,
			{ name: 'kusina-recipe-details', title: __( 'Recipe details', 'kusina-core' ), className: 'kusina-recipe-details' },
			el(
				'div',
				{ style: { display: 'grid', gap: '16px' } },
				minutes( 'prep_minutes', __( 'Prep minutes', 'kusina-core' ) ),
				minutes( 'cook_minutes', __( 'Cook minutes', 'kusina-core' ) ),
				el( TextControl, {
					...FIELD,
					label: __( 'Servings', 'kusina-core' ),
					type: 'number',
					min: 1,
					value: meta.servings ?? 4,
					onChange: ( value ) => set( 'servings', Math.max( 1, parseInt( value, 10 ) || 1 ) ),
				} ),
				el( TextControl, {
					...FIELD,
					label: __( 'Yield label', 'kusina-core' ),
					help: __( "Leave empty for servings, e.g. 'pieces'", 'kusina-core' ),
					value: meta.yield_label || '',
					onChange: ( value ) => set( 'yield_label', value ),
				} ),
				el( SelectControl, {
					...FIELD,
					label: __( 'Difficulty', 'kusina-core' ),
					value: meta.difficulty || 'easy',
					options: [
						{ value: 'easy', label: __( 'Easy', 'kusina-core' ) },
						{ value: 'medium', label: __( 'Medium', 'kusina-core' ) },
						{ value: 'involved', label: __( 'Involved', 'kusina-core' ) },
					],
					onChange: ( value ) => set( 'difficulty', value ),
				} )
			)
		);
	}

	registerPlugin( 'kusina-recipe-details', { render: RecipeDetails } );
} )( window.wp );
