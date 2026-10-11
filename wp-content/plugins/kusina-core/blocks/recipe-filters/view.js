/**
 * Recipe index filters (docs/SPEC.md, section 4). Reads the recipe index from data-index
 * (the REST route locally, /recipes.json on the static site), filters it by course, diet and
 * time, and shows the results in place of the server's paginated list. State lives in the
 * query string (?course=ulam&diet=vegetarian&max=30), so a filtered view can be shared.
 * If the index doesn't load, the plain course links stay and nothing else changes.
 *
 * One file, no imports: the static exporter only collects files the HTML references.
 */

const PAGE = 24;
const FILTERS = [ 'course', 'diet', 'max' ];

// Mirrors kusina_format_minutes() in includes/format.php.
function formatMinutes( minutes ) {
	const h = Math.floor( minutes / 60 );
	const m = minutes % 60;
	if ( ! h ) {
		return `${ m } min`;
	}
	return m ? `${ h } hr ${ m } min` : `${ h } hr`;
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

// Same markup as the archive's Query Loop tiles (templates/archive-recipe.html).
function card( recipe, msg ) {
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
	for ( const diet of recipe.d || [] ) {
		if ( msg.diets[ diet ] ) {
			tags.append( el( 'li', { className: 'tag tag-diet', text: msg.diets[ diet ] } ) );
		}
	}
	const body = [];
	if ( recipe.c && msg.courses[ recipe.c ] ) {
		body.push( el( 'p', { className: 'tile-course', text: msg.courses[ recipe.c ] } ) );
	}
	body.push( el( 'h2', { className: 'tile-title' }, [ el( 'a', { href: recipe.u, text: recipe.t } ) ] ) );
	if ( tags.children.length ) {
		body.push( tags );
	}
	return el( 'li', {}, [ el( 'div', { className: 'tile' }, [ media, el( 'div', { className: 'tile-body' }, body ) ] ) ] );
}

// Pescatarian means no meat, so vegetarian dishes count too.
function matches( recipe, filters ) {
	if ( filters.course && recipe.c !== filters.course ) {
		return false;
	}
	if ( filters.diet ) {
		const diets = recipe.d || [];
		if ( ! diets.includes( filters.diet ) && ! ( filters.diet === 'pescatarian' && diets.includes( 'vegetarian' ) ) ) {
			return false;
		}
	}
	return ! filters.max || ( recipe.m > 0 && recipe.m <= Number( filters.max ) );
}

async function init( root ) {
	const serverList = document.querySelector( '.recipe-index' );
	const links = root.querySelector( '.recipe-filters-links' );
	const panel = root.querySelector( '[data-filters]' );
	const buttons = [ ...panel.querySelectorAll( '[data-filter]' ) ];
	const summary = panel.querySelector( '[data-filter-summary]' );
	const clear = panel.querySelector( '[data-filter-clear]' );
	const msg = JSON.parse( root.dataset.messages );
	if ( ! serverList ) {
		return;
	}

	let recipes;
	try {
		const response = await fetch( root.dataset.index, { headers: { Accept: 'application/json' } } );
		if ( ! response.ok ) {
			throw new Error( String( response.status ) );
		}
		recipes = await response.json();
	} catch {
		return;
	}

	// Results sit next to the server's list and replace it while any filter is on.
	const list = el( 'ul', { className: 'wp-block-post-template' } );
	const more = el( 'button', { type: 'button', className: 'filter-more' } );
	const results = el( 'div', { className: 'tiles tiles-4 recipe-filter-results' }, [ list, more ] );
	results.hidden = true;
	more.hidden = true;
	serverList.after( results );

	// Only values that have a button count, so a hand-edited URL can't wedge the page.
	const valid = ( key, value ) => buttons.some( ( b ) => b.dataset.filter === key && b.dataset.value === value );
	const params = new URLSearchParams( window.location.search );
	const filters = {};
	for ( const key of FILTERS ) {
		const value = params.get( key );
		filters[ key ] = value && valid( key, value ) ? value : '';
	}
	// Unfiltered, the URL goes back to the page that was loaded (it may be /recipes/page/2/).
	const startPath = window.location.pathname;
	let shown = PAGE;

	function render() {
		for ( const button of buttons ) {
			button.setAttribute( 'aria-pressed', String( filters[ button.dataset.filter ] === button.dataset.value ) );
		}
		const active = FILTERS.some( ( key ) => filters[ key ] );
		const query = new URLSearchParams( FILTERS.filter( ( key ) => filters[ key ] ).map( ( key ) => [ key, filters[ key ] ] ) );
		const url = active ? `${ root.dataset.base }?${ query }` : startPath;
		if ( url !== window.location.pathname + window.location.search ) {
			window.history.replaceState( null, '', url + window.location.hash );
		}

		serverList.hidden = active;
		results.hidden = ! active;
		clear.hidden = ! active;
		list.replaceChildren();
		more.hidden = true;
		if ( ! active ) {
			summary.textContent = '';
			return;
		}

		const found = recipes.filter( ( recipe ) => matches( recipe, filters ) );
		summary.textContent = found.length
			? ( found.length === 1 ? msg.one : msg.count.replace( '%s', String( found.length ) ) )
			: msg.none;
		for ( const recipe of found.slice( 0, shown ) ) {
			list.append( card( recipe, msg ) );
		}
		if ( found.length > shown ) {
			more.hidden = false;
			more.textContent = `${ msg.more } (${ found.length - shown })`;
		}
	}

	panel.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( '[data-filter]' );
		if ( button ) {
			const key = button.dataset.filter;
			filters[ key ] = filters[ key ] === button.dataset.value ? '' : button.dataset.value;
			shown = PAGE;
			render();
		}
	} );
	clear.addEventListener( 'click', () => {
		for ( const key of FILTERS ) {
			filters[ key ] = '';
		}
		shown = PAGE;
		render();
		// The button just hid itself: keep keyboard users at the start of the filters.
		buttons[ 0 ].focus();
	} );
	more.addEventListener( 'click', () => {
		const first = list.children.length;
		shown += PAGE;
		render();
		const next = list.children[ first ];
		if ( next ) {
			next.querySelector( 'a' ).focus();
		}
	} );

	links.hidden = true;
	panel.hidden = false;
	render();
}

const root = document.querySelector( '.recipe-filters' );
if ( root ) {
	init( root );
}
