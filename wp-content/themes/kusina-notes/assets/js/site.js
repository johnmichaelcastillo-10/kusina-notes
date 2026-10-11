/**
 * Site-wide: shows how many recipes are on the visitor's shopping list next to "List" in
 * the header. The list itself lives in localStorage (key "kusina-list", written by the
 * recipe card and the list page in the Kusina Core plugin).
 */
function listCount() {
	try {
		const list = JSON.parse( window.localStorage.getItem( 'kusina-list' ) || 'null' );
		return list && Array.isArray( list.recipes ) ? list.recipes.length : 0;
	} catch {
		return 0;
	}
}

function render() {
	const count = listCount();
	for ( const badge of document.querySelectorAll( '[data-list-count]' ) ) {
		badge.textContent = String( count );
		badge.hidden = count === 0;
		badge.setAttribute( 'aria-label', `${ count } on your list` );
	}
}

render();
// Other tabs, and this tab's own list changes (the plugin dispatches this event).
window.addEventListener( 'storage', render );
window.addEventListener( 'kusina-list-change', render );
