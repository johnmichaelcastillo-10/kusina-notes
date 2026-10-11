/**
 * Shopping list (docs/SPEC.md, section 4). Reads the list the recipe card saves in
 * localStorage, scales each recipe to its chosen servings, adds matching ingredients
 * together (same name and a compatible unit) and groups them by aisle.
 *
 * Quantity text mirrors kusina_format_qty() (includes/format.php) and the recipe card's
 * view.js; change all three together. One file, no imports: the static exporter only
 * collects files the HTML references.
 */

const LIST_KEY = 'kusina-list';

/* ---------- Quantity formatting (mirror of includes/format.php) ---------- */

const UNITS = {
	'': [ '', '' ],
	g: [ 'g', 'g' ],
	kg: [ 'kg', 'kg' ],
	ml: [ 'ml', 'ml' ],
	l: [ 'L', 'L' ],
	tsp: [ 'tsp', 'tsp' ],
	tbsp: [ 'tbsp', 'tbsp' ],
	cup: [ 'cup', 'cups' ],
	piece: [ 'piece', 'pieces' ],
	clove: [ 'clove', 'cloves' ],
	thumb: [ 'thumb', 'thumbs' ],
	pinch: [ 'pinch', 'pinches' ],
	'to-taste': [ 'to taste', 'to taste' ],
};
const FRACTION_UNITS = new Set( [ '', 'tsp', 'tbsp', 'cup', 'piece', 'clove', 'thumb', 'pinch' ] );
const FRACTIONS = [
	[ 1 / 8, '⅛' ], [ 1 / 4, '¼' ], [ 1 / 3, '⅓' ], [ 3 / 8, '⅜' ], [ 1 / 2, '½' ],
	[ 5 / 8, '⅝' ], [ 2 / 3, '⅔' ], [ 3 / 4, '¾' ], [ 7 / 8, '⅞' ],
];

function formatNumber( n, unit ) {
	if ( ( unit === 'g' || unit === 'ml' ) && n > 50 ) {
		return String( Math.round( n ) );
	}
	if ( FRACTION_UNITS.has( unit ) ) {
		const whole = Math.floor( n );
		const rest = n - whole;
		if ( rest > 0.96 ) {
			return String( whole + 1 );
		}
		if ( rest < 0.04 && whole > 0 ) {
			return String( whole );
		}
		let best = null;
		for ( const [ value, glyph ] of FRACTIONS ) {
			const distance = Math.abs( rest - value );
			if ( distance <= 0.04 && ( ! best || distance < best[ 0 ] ) ) {
				best = [ distance, glyph ];
			}
		}
		if ( best ) {
			return ( whole > 0 ? String( whole ) : '' ) + best[ 1 ];
		}
	}
	return String( Number( n.toFixed( 2 ) ) );
}

function formatQty( qty, max, unit ) {
	const labels = UNITS[ unit ] || UNITS[ '' ];
	let number = formatNumber( qty, unit );
	if ( max !== null ) {
		number += '–' + formatNumber( max, unit );
	}
	const label = ( max ?? qty ) > 1 ? labels[ 1 ] : labels[ 0 ];
	return label === '' ? number : number + ' ' + label;
}

// Nearest whole number or kitchen fraction (never rounds a small amount away to zero).
function snap( n ) {
	const whole = Math.floor( n );
	const rest = n - whole;
	let best = 0;
	let bestDistance = rest;
	if ( 1 - rest < bestDistance ) {
		best = 1;
		bestDistance = 1 - rest;
	}
	for ( const [ value ] of FRACTIONS ) {
		const distance = Math.abs( rest - value );
		if ( distance < bestDistance ) {
			best = value;
			bestDistance = distance;
		}
	}
	return whole + best > 0 ? whole + best : FRACTIONS[ 0 ][ 0 ];
}

/* ---------- Merging ---------- */

const MASS = { g: 1, kg: 1000 };
const VOLUME = { ml: 1, l: 1000, tsp: 5, tbsp: 15, cup: 240 };

// Water and ice aren't shopping.
const SKIP = /^(?:(?:hot|cold|warm|boiling|ice[- ]cold|room-temperature|lukewarm) )?water$|^(?:crushed |shaved )?ice(?: cubes)?$/i;

function family( unit ) {
	if ( unit in MASS ) {
		return 'mass';
	}
	if ( unit in VOLUME ) {
		return 'volume';
	}
	if ( unit === '' || unit === 'piece' ) {
		return 'count';
	}
	if ( unit === 'pinch' || unit === 'to-taste' ) {
		return 'taste';
	}
	return unit; // clove, thumb
}

const normName = ( name ) => name.toLowerCase().replace( /\s+/g, ' ' ).trim();

function mergeLines( list ) {
	const lines = new Map();
	for ( const recipe of list.recipes ) {
		const factor = ( recipe.servings || recipe.base || 1 ) / ( recipe.base || 1 );
		for ( const item of recipe.items || [] ) {
			if ( ! item || typeof item.name !== 'string' || SKIP.test( item.name.trim() ) ) {
				continue;
			}
			const fam = family( item.unit );
			const key = `${ normName( item.name ) }|${ fam }`;
			let line = lines.get( key );
			if ( ! line ) {
				line = { key, name: item.name.trim(), fam, units: new Set(), total: 0, totalMax: 0, hasQty: false, ranged: false, metric: false, recipes: [] };
				lines.set( key, line );
			}
			if ( ! line.recipes.includes( recipe.title ) ) {
				line.recipes.push( recipe.title );
			}
			if ( item.qty === null || fam === 'taste' ) {
				continue;
			}
			const toBase = fam === 'mass' ? MASS[ item.unit ] : fam === 'volume' ? VOLUME[ item.unit ] : 1;
			line.hasQty = true;
			line.total += item.qty * factor * toBase;
			line.totalMax += ( item.max ?? item.qty ) * factor * toBase;
			line.ranged = line.ranged || item.max !== null;
			line.metric = line.metric || item.unit === 'ml' || item.unit === 'l';
			line.units.add( item.unit );
		}
	}
	return [ ...lines.values() ];
}

// The summed amount in a unit a shopper reads easily.
function amountText( line ) {
	if ( ! line.hasQty ) {
		return '';
	}
	let qty = line.total;
	let max = line.ranged ? line.totalMax : null;
	let unit;
	const scale = ( by ) => {
		qty /= by;
		max = max === null ? null : max / by;
	};

	if ( line.fam === 'mass' ) {
		unit = qty >= 1000 ? 'kg' : 'g';
		if ( unit === 'kg' ) {
			scale( 1000 );
		}
	} else if ( line.fam === 'volume' ) {
		if ( line.metric ) {
			unit = qty >= 1000 ? 'l' : 'ml';
			if ( unit === 'l' ) {
				scale( 1000 );
			}
		} else if ( qty >= 60 ) {
			unit = 'cup';
			scale( 240 );
		} else if ( qty >= 15 ) {
			unit = 'tbsp';
			scale( 15 );
		} else {
			unit = 'tsp';
			scale( 5 );
		}
	} else if ( line.fam === 'count' ) {
		unit = line.units.has( '' ) ? '' : 'piece';
	} else {
		unit = line.fam;
	}

	if ( FRACTION_UNITS.has( unit ) ) {
		qty = snap( qty );
		max = max === null ? null : snap( max );
	}
	if ( max !== null && max <= qty ) {
		max = null;
	}
	return formatQty( qty, max, unit );
}

/* ---------- Aisles ---------- */

// First match wins, so the order matters: "fish sauce" is a sauce before it is fish,
// "coconut milk" is a can before it is milk, "black pepper" is a spice before a vegetable.
const AISLE_RULES = [
	[ 'sauces', /\b(black pepper|white pepper|peppercorns?|ground pepper|bay leaves|laurel|tomato sauce|tomato paste|banana ketchup|ketchup|catsup|fish sauce|patis|shrimp paste|bagoong|alamang|oyster sauce|peanut butter|liver spread|tausi|fermented black beans|broth|stock|bouillon|chicken powder|lard)\b/i ],
	[ 'dry', /\b(coconut milk|coconut cream|kakang gata|gata|egg noodles|egg roll wrappers?)\b/i ],
	[ 'meat', /\b(chicken|pork|beef|goat|duck|liempo|ham|bacon|longganisa|tocino|chorizo|hot ?dogs?|sausages?|spam|corned beef|oxtail|tripe|liver|gizzards?|offal|lengua|ox tongue|giniling|ground meat|pata|chicharon|lechon)\b/i ],
	[ 'seafood', /\b(fish|bangus|milkfish|tilapia|tuna|galunggong|salmon|maya-maya|lapu-lapu|tanigue|dalag|hito|shrimps?|prawns?|hipon|crabs?|alimango|alimasag|squid|pusit|mussels?|tahong|clams?|halaan|tulya|oysters?|octopus|dilis|anchov\w*|tuyo|tinapa|daing|sardines?|danggit|dulong|fishballs?|seafood)\b/i ],
	[ 'dairy', /\b(eggs?|egg yolks?|egg whites?|milk|butter|margarine|cheese|kesong puti|cream|yogurt|tofu|tokwa|taho)\b/i ],
	[ 'produce', /\b(garlic|onions?|shallots?|scallions?|spring onions?|leeks?|ginger|luya|turmeric root|tomato(?:es)?|potato(?:es)?|carrots?|cabbage|repolyo|pechay|bok choy|eggplants?|talong|squash|kalabasa|string beans|sitaw|green beans|baguio beans|okra|bitter gourd|ampalaya|water spinach|kangkong|spinach|malunggay|moringa|chayote|sayote|bottle gourd|upo|sponge gourd|patola|radish|labanos|cucumbers?|lettuce|corn|mais|mushrooms?|bean sprouts|togue|chilies|chili|chillies|siling|bell peppers?|green peppers?|red peppers?|lemongrass|tanglad|pandan|basil|cilantro|coriander|celery|parsley|leaves|calamansi|kalamansi|lemons?|limes?|dayap|mangoes|mango|bananas?|saba|plantains?|pineapple|papaya|jackfruit|langka|young coconut|buko|grated coconut|fresh coconut|coconut meat|avocados?|melons?|watermelon|guava|bayabas|tamarind|sampalok|kamias|santol|batuan|ube|purple yam|taro|gabi|cassava|kamoteng kahoy|sweet potato(?:es)?|kamote|heart of palm|ubod|banana blossom|puso ng saging|fern|pako|sigarilyas|winged beans|jicama|singkamas|fruit|vegetables?)\b/i ],
	[ 'dry', /\b(rice|flour|starch|cornstarch|noodles?|bihon|canton|misua|miki|sotanghon|lomi|pasta|macaroni|spaghetti|bread|pandesal|crumbs|oats|sago|tapioca|gulaman|agar|gelatin|baking|yeast|cocoa|chocolate|tablea|coffee|tea|mung beans|monggo|peanuts|cashews?|nuts|raisins|wrappers?|graham|crackers|biscuits|cookies|condensed|evaporated|canned|pinipig|cornflakes|sesame seeds)\b/i ],
	[ 'sauces', /\b(soy sauce|toyo|vinegar|suka|ketchup|catsup|tomato sauce|tomato paste|hoisin|seasoning|worcestershire|hot sauce|chili sauce|mayonnaise|mustard|oil|salt|asin|pepper|paprika|cumin|curry|annatto|achuete|atsuete|bay leaves|laurel|star anise|cinnamon|nutmeg|cloves|sugar|muscovado|panutsa|syrup|honey|vanilla|extract|sesame)\b/i ],
];

function aisleOf( name ) {
	for ( const [ aisle, rule ] of AISLE_RULES ) {
		if ( rule.test( name ) ) {
			return aisle;
		}
	}
	return 'other';
}

/* ---------- Storage ---------- */

function readList() {
	try {
		const list = JSON.parse( window.localStorage.getItem( LIST_KEY ) || 'null' );
		if ( list && Array.isArray( list.recipes ) ) {
			return { recipes: list.recipes, checked: Array.isArray( list.checked ) ? list.checked : [] };
		}
	} catch {
		// Unreadable or blocked: empty list.
	}
	return { recipes: [], checked: [] };
}

function writeList( list ) {
	try {
		window.localStorage.setItem( LIST_KEY, JSON.stringify( list ) );
	} catch {
		// Not saved; the page still shows the change until it is closed.
	}
	window.dispatchEvent( new Event( 'kusina-list-change' ) );
}

/* ---------- Rendering ---------- */

function el( tag, props = {}, children = [] ) {
	const node = document.createElement( tag );
	for ( const [ key, value ] of Object.entries( props ) ) {
		if ( key === 'text' ) {
			node.textContent = value;
		} else if ( key === 'className' ) {
			node.className = value;
		} else if ( key === 'checked' ) {
			node.checked = value;
		} else {
			node.setAttribute( key, value );
		}
	}
	for ( const child of children ) {
		node.append( child );
	}
	return node;
}

function init( root ) {
	const msg = JSON.parse( root.dataset.messages );
	const empty = root.querySelector( '[data-list-empty]' );
	const full = root.querySelector( '[data-list-full]' );
	const recipesEl = root.querySelector( '[data-list-recipes]' );
	const aislesEl = root.querySelector( '[data-list-aisles]' );
	const clear = root.querySelector( '[data-list-clear]' );
	let list = readList();
	let confirming = false;

	function render() {
		empty.hidden = list.recipes.length > 0;
		full.hidden = list.recipes.length === 0;
		recipesEl.replaceChildren();
		aislesEl.replaceChildren();
		if ( ! list.recipes.length ) {
			return;
		}

		list.recipes.forEach( ( recipe, index ) => {
			const servings = recipe.servings || recipe.base;
			recipesEl.append(
				el( 'li', { className: `list-recipe${ recipe.course ? ` course-${ recipe.course }` : '' }` }, [
					el( 'a', { href: recipe.url, text: recipe.title } ),
					el( 'span', { className: 'list-recipe-servings' }, [
						el( 'button', { type: 'button', className: 'kn-icon-btn', 'data-servings': `${ index }:-1`, 'aria-label': msg.fewer.replace( '%s', recipe.title ), text: '−' } ),
						el( 'span', { text: msg.servings.replace( '%s', String( servings ) ) } ),
						el( 'button', { type: 'button', className: 'kn-icon-btn', 'data-servings': `${ index }:1`, 'aria-label': msg.more.replace( '%s', recipe.title ), text: '+' } ),
					] ),
					el( 'button', { type: 'button', className: 'kn-btn kn-btn-quiet list-remove', 'data-remove': String( index ), 'aria-label': msg.remove.replace( '%s', recipe.title ), text: msg.removeText } ),
				] )
			);
		} );

		const lines = mergeLines( list );
		const groups = new Map( Object.keys( msg.aisles ).map( ( a ) => [ a, [] ] ) );
		for ( const line of lines ) {
			groups.get( aisleOf( line.name ) ).push( line );
		}
		let n = 0;
		for ( const [ aisle, items ] of groups ) {
			if ( ! items.length ) {
				continue;
			}
			items.sort( ( a, b ) => a.name.localeCompare( b.name ) );
			const ul = el( 'ul', { className: 'list-lines' } );
			for ( const line of items ) {
				const id = `kusina-line-${ n++ }`;
				const amount = amountText( line );
				const label = el( 'label', { for: id } );
				if ( amount ) {
					label.append( el( 'span', { className: 'list-qty', text: amount } ), ' ' );
				}
				label.append( el( 'span', { className: 'list-name', text: line.fam === 'taste' ? `${ line.name }, to taste` : line.name } ) );
				label.append( el( 'span', { className: 'list-for', text: msg.for.replace( '%s', line.recipes.join( ', ' ) ) } ) );
				ul.append(
					el( 'li', { className: 'list-line' }, [
						el( 'input', { type: 'checkbox', id, 'data-key': line.key, checked: list.checked.includes( line.key ) } ),
						label,
					] )
				);
			}
			aislesEl.append( el( 'section', { className: 'list-aisle' }, [ el( 'h3', { text: msg.aisles[ aisle ] } ), ul ] ) );
		}
	}

	root.addEventListener( 'click', ( event ) => {
		const target = event.target.closest( 'button' );
		if ( ! target ) {
			return;
		}
		if ( target.dataset.remove !== undefined ) {
			list.recipes.splice( Number( target.dataset.remove ), 1 );
			writeList( list );
			render();
			( recipesEl.querySelector( 'a' ) || root.querySelector( '.list-empty a' ) )?.focus();
		} else if ( target.dataset.servings ) {
			const [ index, delta ] = target.dataset.servings.split( ':' ).map( Number );
			const recipe = list.recipes[ index ];
			recipe.servings = Math.min( 99, Math.max( 1, ( recipe.servings || recipe.base ) + delta ) );
			writeList( list );
			render();
			recipesEl.querySelector( `[data-servings="${ index }:${ delta }"]` )?.focus();
		} else if ( target.hasAttribute( 'data-list-print' ) ) {
			window.print();
		} else if ( target.hasAttribute( 'data-list-uncheck' ) ) {
			list.checked = [];
			writeList( list );
			render();
		} else if ( target.hasAttribute( 'data-list-clear' ) ) {
			// Two taps instead of a confirm() dialog.
			if ( ! confirming ) {
				confirming = true;
				clear.textContent = msg.confirm;
				window.setTimeout( () => {
					confirming = false;
					clear.textContent = msg.clear;
				}, 4000 );
				return;
			}
			confirming = false;
			clear.textContent = msg.clear;
			list = { recipes: [], checked: [] };
			writeList( list );
			render();
			root.querySelector( '.list-empty a' )?.focus();
		}
	} );

	root.addEventListener( 'change', ( event ) => {
		const box = event.target;
		if ( box.matches( 'input[type="checkbox"][data-key]' ) ) {
			const key = box.dataset.key;
			list.checked = box.checked ? [ ...new Set( [ ...list.checked, key ] ) ] : list.checked.filter( ( k ) => k !== key );
			writeList( list );
		}
	} );

	// Another tab added or removed a recipe.
	window.addEventListener( 'storage', ( event ) => {
		if ( event.key === LIST_KEY ) {
			list = readList();
			render();
		}
	} );

	render();
}

const root = document.querySelector( '.shopping-list' );
if ( root ) {
	init( root );
}
