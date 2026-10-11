/**
 * Recipe card editor. Plain JS on the wp.* globals, no build step. Attributes and
 * registration details come from block.json; the front end is rendered by render.php.
 */
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const { useBlockProps, RichText } = wp.blockEditor;
	const { Button, TextControl, SelectControl } = wp.components;
	const { __, sprintf } = wp.i18n;

	// Mirrors kusina_units() in includes/format.php.
	const UNITS = [
		[ '', __( 'no unit', 'kusina-core' ) ],
		[ 'g', 'g' ],
		[ 'kg', 'kg' ],
		[ 'ml', 'ml' ],
		[ 'l', 'L' ],
		[ 'tsp', 'tsp' ],
		[ 'tbsp', 'tbsp' ],
		[ 'cup', 'cup' ],
		[ 'piece', 'piece' ],
		[ 'clove', 'clove' ],
		[ 'thumb', 'thumb' ],
		[ 'pinch', 'pinch' ],
		[ 'to-taste', __( 'to taste', 'kusina-core' ) ],
	].map( ( [ value, label ] ) => ( { value, label } ) );

	const FORMATS = [ 'core/bold', 'core/italic', 'core/link' ];

	// Shared props that opt into the current component styles (avoids deprecation notices).
	const FIELD = { __nextHasNoMarginBottom: true, __next40pxDefaultSize: true };

	const toNumber = ( value ) => {
		if ( value === '' || value === null || value === undefined ) {
			return null;
		}
		const n = parseFloat( value );
		return Number.isFinite( n ) && n > 0 ? n : null;
	};

	const replaceAt = ( list, index, value ) => list.map( ( item, i ) => ( i === index ? value : item ) );
	const removeAt = ( list, index ) => list.filter( ( _, i ) => i !== index );
	const moveAt = ( list, index, delta ) => {
		const target = index + delta;
		if ( target < 0 || target >= list.length ) {
			return list;
		}
		const next = list.slice();
		const [ item ] = next.splice( index, 1 );
		next.splice( target, 0, item );
		return next;
	};

	const emptyItem = () => ( { qty: null, qtyMax: null, unit: '', name: '', note: '' } );

	function RowButtons( { index, length, onMove, onRemove, what } ) {
		return el(
			'div',
			{ className: 'kusina-row-buttons' },
			el( Button, {
				icon: 'arrow-up-alt2',
				size: 'small',
				label: sprintf( __( 'Move %s up', 'kusina-core' ), what ),
				disabled: index === 0,
				onClick: () => onMove( -1 ),
			} ),
			el( Button, {
				icon: 'arrow-down-alt2',
				size: 'small',
				label: sprintf( __( 'Move %s down', 'kusina-core' ), what ),
				disabled: index === length - 1,
				onClick: () => onMove( 1 ),
			} ),
			el( Button, {
				icon: 'trash',
				size: 'small',
				isDestructive: true,
				label: sprintf( __( 'Remove %s', 'kusina-core' ), what ),
				onClick: onRemove,
			} )
		);
	}

	function IngredientRow( { item, index, length, onChange, onMove, onRemove } ) {
		const set = ( key, value ) => onChange( { ...item, [ key ]: value } );
		const n = index + 1;

		return el(
			'div',
			{ className: 'kusina-ingredient-row' },
			el( TextControl, {
				...FIELD,
				label: __( 'Amount', 'kusina-core' ),
				hideLabelFromVision: index > 0,
				type: 'number',
				min: 0,
				step: 'any',
				value: item.qty ?? '',
				onChange: ( value ) => set( 'qty', toNumber( value ) ),
			} ),
			el( TextControl, {
				...FIELD,
				label: __( 'To (range)', 'kusina-core' ),
				hideLabelFromVision: index > 0,
				type: 'number',
				min: 0,
				step: 'any',
				value: item.qtyMax ?? '',
				onChange: ( value ) => set( 'qtyMax', toNumber( value ) ),
			} ),
			el( SelectControl, {
				...FIELD,
				label: __( 'Unit', 'kusina-core' ),
				hideLabelFromVision: index > 0,
				value: item.unit || '',
				options: UNITS,
				onChange: ( value ) => set( 'unit', value ),
			} ),
			el( TextControl, {
				...FIELD,
				label: __( 'Ingredient', 'kusina-core' ),
				hideLabelFromVision: index > 0,
				placeholder: index === 0 ? __( 'soy sauce', 'kusina-core' ) : '',
				value: item.name || '',
				onChange: ( value ) => set( 'name', value ),
			} ),
			el( TextControl, {
				...FIELD,
				label: __( 'Note', 'kusina-core' ),
				hideLabelFromVision: index > 0,
				placeholder: index === 0 ? __( 'finely chopped', 'kusina-core' ) : '',
				value: item.note || '',
				onChange: ( value ) => set( 'note', value ),
			} ),
			el( RowButtons, {
				index,
				length,
				onMove,
				onRemove,
				what: sprintf( __( 'ingredient %d', 'kusina-core' ), n ),
			} )
		);
	}

	function IngredientGroup( { group, index, count, onChange, onRemove } ) {
		const items = group.items || [];
		const setItems = ( next ) => onChange( { ...group, items: next } );

		return el(
			'fieldset',
			{ className: 'kusina-group' },
			el( 'legend', null, sprintf( __( 'Ingredient group %d', 'kusina-core' ), index + 1 ) ),
			el( TextControl, {
				...FIELD,
				label: __( 'Group title (optional)', 'kusina-core' ),
				placeholder: __( 'For the sauce', 'kusina-core' ),
				value: group.title || '',
				onChange: ( value ) => onChange( { ...group, title: value } ),
			} ),
			items.map( ( item, i ) =>
				el( IngredientRow, {
					key: i,
					item,
					index: i,
					length: items.length,
					onChange: ( value ) => setItems( replaceAt( items, i, value ) ),
					onMove: ( delta ) => setItems( moveAt( items, i, delta ) ),
					onRemove: () => setItems( removeAt( items, i ) ),
				} )
			),
			el(
				'div',
				{ className: 'kusina-actions' },
				el(
					Button,
					{ variant: 'secondary', onClick: () => setItems( [ ...items, emptyItem() ] ) },
					__( 'Add ingredient', 'kusina-core' )
				),
				count > 1 &&
					el(
						Button,
						{ variant: 'tertiary', isDestructive: true, onClick: onRemove },
						__( 'Remove group', 'kusina-core' )
					)
			)
		);
	}

	function Step( { step, index, length, onChange, onMove, onRemove } ) {
		const n = index + 1;

		return el(
			'li',
			{ className: 'kusina-step' },
			el( RichText, {
				tagName: 'p',
				className: 'kusina-step-text',
				'aria-label': sprintf( __( 'Step %d', 'kusina-core' ), n ),
				placeholder: __( 'Describe this step', 'kusina-core' ),
				allowedFormats: FORMATS,
				value: step.text || '',
				onChange: ( value ) => onChange( { ...step, text: value } ),
			} ),
			el(
				'div',
				{ className: 'kusina-step-controls' },
				el( TextControl, {
					...FIELD,
					label: __( 'Timer in minutes (optional)', 'kusina-core' ),
					type: 'number',
					min: 0,
					value: step.minutes ?? '',
					onChange: ( value ) => {
						const minutes = toNumber( value );
						onChange( { ...step, minutes: minutes === null ? null : Math.round( minutes ) } );
					},
				} ),
				el( RowButtons, {
					index,
					length,
					onMove,
					onRemove,
					what: sprintf( __( 'step %d', 'kusina-core' ), n ),
				} )
			)
		);
	}

	function Edit( { attributes, setAttributes } ) {
		const groups = attributes.groups && attributes.groups.length ? attributes.groups : [ { title: '', items: [] } ];
		const steps = attributes.steps || [];
		const setGroups = ( next ) => setAttributes( { groups: next } );
		const setSteps = ( next ) => setAttributes( { steps: next } );

		return el(
			'div',
			useBlockProps( { className: 'kusina-recipe-editor' } ),
			el( 'p', { className: 'kusina-editor-hint' }, __( 'Times, servings and difficulty are in the Recipe details panel of the sidebar.', 'kusina-core' ) ),
			el( 'h2', null, __( 'Ingredients', 'kusina-core' ) ),
			groups.map( ( group, g ) =>
				el( IngredientGroup, {
					key: g,
					group,
					index: g,
					count: groups.length,
					onChange: ( value ) => setGroups( replaceAt( groups, g, value ) ),
					onRemove: () => setGroups( removeAt( groups, g ) ),
				} )
			),
			el(
				Button,
				{ variant: 'secondary', onClick: () => setGroups( [ ...groups, { title: '', items: [ emptyItem() ] } ] ) },
				__( 'Add ingredient group', 'kusina-core' )
			),
			el( 'h2', null, __( 'Method', 'kusina-core' ) ),
			el(
				'ol',
				{ className: 'kusina-steps' },
				steps.map( ( step, s ) =>
					el( Step, {
						key: s,
						step,
						index: s,
						length: steps.length,
						onChange: ( value ) => setSteps( replaceAt( steps, s, value ) ),
						onMove: ( delta ) => setSteps( moveAt( steps, s, delta ) ),
						onRemove: () => setSteps( removeAt( steps, s ) ),
					} )
				)
			),
			el(
				Button,
				{ variant: 'secondary', onClick: () => setSteps( [ ...steps, { text: '', minutes: null } ] ) },
				__( 'Add step', 'kusina-core' )
			),
			el( 'h2', null, __( 'Tips', 'kusina-core' ) ),
			el( RichText, {
				tagName: 'p',
				className: 'kusina-tips',
				'aria-label': __( 'Tips', 'kusina-core' ),
				placeholder: __( 'Optional: storage, swaps, what to watch for', 'kusina-core' ),
				allowedFormats: FORMATS,
				value: attributes.tips || '',
				onChange: ( value ) => setAttributes( { tips: value } ),
			} )
		);
	}

	registerBlockType( 'kusina/recipe-card', {
		edit: Edit,
		save: () => null,
	} );
} )( window.wp );
