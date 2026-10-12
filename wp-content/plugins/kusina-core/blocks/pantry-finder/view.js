/**
 * Pantry finder (docs/SPEC.md, section 4). Ranks recipes by how few key ingredients are
 * missing from what the visitor ticked. Reads the recipe index from data-index (the REST
 * route locally, /recipes.json on the static site). Choices persist in localStorage.
 *
 * One file, no imports: the static exporter only collects files the HTML references.
 */

const PANTRY_KEY = 'kusina-pantry';
const PAGE = 24;

// Mirrors kusina_format_minutes() in includes/format.php.
function formatMinutes( minutes ) {
	const h = Math.floor( minutes / 60 );
	const m = minutes % 60;
	if ( ! h ) {
		return `${ m } min`;
	}
	return m ? `${ h } hr ${ m } min` : `${ h } hr`;
}

function readSaved() {
	try {
		const saved = JSON.parse( window.localStorage.getItem( PANTRY_KEY ) || 'null' );
		return saved && Array.isArray( saved.keys ) ? saved : null;
	} catch {
		return null;
	}
}

function save( state ) {
	try {
		window.localStorage.setItem( PANTRY_KEY, JSON.stringify( state ) );
	} catch {
		// Not remembered; the finder still works.
	}
}

function el( tag, props = {}, children = [] ) {
	const node = document.createElement( tag );
	for ( const [ key, value ] of Object.entries( props ) ) {
		if ( key === 'text' ) {
			node.textContent = value;
		} else if ( key === 'className' ) {
			node.className = value;
		} else {
			node.setAttribute( key, value );
		}
	}
	for ( const child of children ) {
		node.append( child );
	}
	return node;
}

// Same markup as the theme's recipe tiles (templates/*.html), so the theme styles it.
function card( recipe, missing, msg ) {
	const media = el( 'div', { className: 'tile-media' } );
	if ( recipe.i ) {
		media.append(
			el( 'figure', { className: 'wp-block-post-featured-image' }, [
				el( 'img', { src: recipe.i, alt: '', loading: 'lazy', decoding: 'async' } ),
			] )
		);
	}
	const tags = el( 'ul', { className: 'tags' } );
	if ( recipe.m ) {
		tags.append( el( 'li', { className: 'tag tag-time', text: formatMinutes( recipe.m ) } ) );
	}
	// missing is null for the idea tiles shown before anything is ticked.
	if ( missing && ! missing.length ) {
		tags.append( el( 'li', { className: 'tag tag-diet', text: msg.all } ) );
	}
	const body = [];
	if ( recipe.c && msg.courses[ recipe.c ] ) {
		body.push( el( 'p', { className: 'tile-course', text: msg.courses[ recipe.c ] } ) );
	}
	body.push( el( 'h3', { className: 'tile-title' }, [ el( 'a', { href: recipe.u, text: recipe.t } ) ] ), tags );
	if ( missing && missing.length ) {
		body.push( el( 'p', { className: 'pantry-missing', text: msg.missing.replace( '%s', missing.join( ', ' ) ) } ) );
	}
	return el( 'li', {}, [ el( 'div', { className: 'tile' }, [ media, el( 'div', { className: 'tile-body' }, body ) ] ) ] );
}

async function init( root ) {
	const chips = [ ...root.querySelectorAll( '.pantry-chip' ) ];
	const basics = new Set( chips.filter( ( c ) => c.hasAttribute( 'data-basic' ) ).map( ( c ) => c.dataset.key ) );
	const summary = root.querySelector( '[data-pantry-summary]' );
	const hint = root.querySelector( '[data-pantry-hint]' );
	const list = root.querySelector( '[data-pantry-list]' );
	const more = root.querySelector( '[data-pantry-more]' );
	const course = root.querySelector( '[data-pantry-course]' );
	const clear = root.querySelector( '[data-pantry-clear]' );
	const msg = JSON.parse( root.dataset.messages );

	let recipes;
	try {
		const response = await fetch( root.dataset.index, { headers: { Accept: 'application/json' } } );
		if ( ! response.ok ) {
			throw new Error( String( response.status ) );
		}
		recipes = await response.json();
	} catch {
		summary.textContent = msg.loadError;
		return;
	}

	// First visit: the basics start ticked.
	const saved = readSaved();
	const selected = new Set( saved ? saved.keys : basics );
	course.value = saved && saved.course ? saved.course : '';
	let shown = PAGE;

	for ( const chip of [ ...chips, course, clear ] ) {
		chip.disabled = false;
	}

	function render() {
		for ( const chip of chips ) {
			chip.setAttribute( 'aria-pressed', String( selected.has( chip.dataset.key ) ) );
		}
		save( { keys: [ ...selected ], course: course.value } );

		const inCourse = recipes.filter( ( recipe ) => ! course.value || recipe.c === course.value );
		const chosen = [ ...selected ].filter( ( k ) => ! basics.has( k ) );
		list.replaceChildren();
		more.hidden = true;
		hint.hidden = true;

		// Nothing ticked at all: say what to do, and fill the space with the newest recipes
		// (the index is newest first) instead of leaving it blank.
		if ( ! selected.size ) {
			summary.textContent = msg.start;
			for ( const recipe of inCourse.slice( 0, 8 ) ) {
				list.append( card( recipe, null, msg ) );
			}
			return;
		}

		// A recipe qualifies when it uses at least one thing you ticked; once anything beyond
		// the basics is ticked, it must use one of those (chicken should bring chicken dishes,
		// not everything with garlic). With only basics ticked they are the picks, so
		// unticking one changes the list. Order: fewest missing, then most picks used
		// (chicken AND coconut milk beats chicken alone), then most matched, then quickest.
		const counts = chosen.length ? ( k ) => ! basics.has( k ) : () => true;
		hint.hidden = chosen.length > 0;
		const ranked = [];
		for ( const recipe of inCourse ) {
			const keys = recipe.k || [];
			const picks = keys.filter( ( k ) => selected.has( k ) && counts( k ) ).length;
			if ( ! picks ) {
				continue;
			}
			const missing = keys.filter( ( k ) => ! selected.has( k ) );
			ranked.push( { recipe, missing, picks, matched: keys.length - missing.length } );
		}
		ranked.sort(
			( a, b ) =>
				a.missing.length - b.missing.length ||
				b.picks - a.picks ||
				b.matched - a.matched ||
				( a.recipe.m || 999 ) - ( b.recipe.m || 999 )
		);

		summary.textContent = ranked.length
			? ( ranked.length === 1 ? msg.one : msg.count.replace( '%s', String( ranked.length ) ) )
			: msg.none;

		for ( const { recipe, missing } of ranked.slice( 0, shown ) ) {
			list.append( card( recipe, missing, msg ) );
		}
		if ( ranked.length > shown ) {
			more.hidden = false;
			more.textContent = `${ msg.more } (${ ranked.length - shown })`;
		}
	}

	root.addEventListener( 'click', ( event ) => {
		const chip = event.target.closest( '.pantry-chip' );
		if ( chip ) {
			const key = chip.dataset.key;
			if ( selected.has( key ) ) {
				selected.delete( key );
			} else {
				selected.add( key );
			}
			shown = PAGE;
			render();
		}
	} );
	course.addEventListener( 'change', () => {
		shown = PAGE;
		render();
	} );
	clear.addEventListener( 'click', () => {
		selected.clear();
		shown = PAGE;
		render();
	} );
	more.addEventListener( 'click', () => {
		const first = list.children.length;
		shown += PAGE;
		render();
		// Move focus to the first new result so keyboard users keep their place.
		const next = list.children[ first ];
		if ( next ) {
			next.querySelector( 'a' ).focus();
		}
	} );

	render();
}

const root = document.querySelector( '.pantry' );
if ( root ) {
	init( root );
}
