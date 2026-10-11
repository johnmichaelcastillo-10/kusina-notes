/**
 * "Bahala na": opens a random recipe from the recipe index (data-index: the REST route
 * locally, /recipes.json on the static site), never the one you're on. The index is fetched
 * when the button is about to be used, not on every page load. If it can't load, the button
 * goes to the recipe index instead.
 *
 * One file, no imports: the static exporter only collects files the HTML references.
 */

let index = null;

function load( url ) {
	index =
		index ||
		fetch( url, { headers: { Accept: 'application/json' } } ).then( ( response ) => {
			if ( ! response.ok ) {
				throw new Error( String( response.status ) );
			}
			return response.json();
		} );
	// A failed load can be retried on the next press.
	index.catch( () => {
		index = null;
	} );
	return index;
}

for ( const button of document.querySelectorAll( '.bahala-na' ) ) {
	const warm = () => load( button.dataset.index ).catch( () => {} );
	button.addEventListener( 'pointerenter', warm, { once: true } );
	button.addEventListener( 'focus', warm, { once: true } );

	button.addEventListener( 'click', async () => {
		button.setAttribute( 'aria-busy', 'true' );
		let target = button.dataset.fallback;
		try {
			const here = window.location.pathname;
			const choices = ( await load( button.dataset.index ) ).filter( ( recipe ) => recipe.u && recipe.u !== here );
			if ( choices.length ) {
				target = choices[ Math.floor( Math.random() * choices.length ) ].u;
			}
		} catch {
			// Keep the fallback.
		}
		window.location.assign( target );
	} );

	button.hidden = false;
}
