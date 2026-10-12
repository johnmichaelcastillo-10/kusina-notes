/**
 * Site-wide: the light/dark toggle (below), and how many recipes are on the visitor's
 * shopping list next to "List" in the header. The list itself lives in localStorage (key
 * "kusina-list", written by the recipe card and the list page in the Kusina Core plugin).
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

/*
 * Light/dark toggle. With no choice saved the site follows the system; a choice is stored
 * as "kusina-theme" and applied as data-theme on <html> (before paint, by the inline script
 * in functions.php). Flipping back to what the system already shows forgets the choice, so
 * the site follows the system again.
 */
const THEME_KEY = 'kusina-theme';
const systemDark = window.matchMedia( '(prefers-color-scheme: dark)' );
const toggle = document.querySelector( '[data-theme-toggle]' );

function currentTheme() {
	const chosen = document.documentElement.dataset.theme;
	return chosen === 'light' || chosen === 'dark' ? chosen : systemDark.matches ? 'dark' : 'light';
}

function labelToggle() {
	toggle.setAttribute( 'aria-label', currentTheme() === 'dark' ? toggle.dataset.labelLight : toggle.dataset.labelDark );
}

if ( toggle ) {
	toggle.addEventListener( 'click', () => {
		const next = currentTheme() === 'dark' ? 'light' : 'dark';
		const system = systemDark.matches ? 'dark' : 'light';
		try {
			if ( next === system ) {
				window.localStorage.removeItem( THEME_KEY );
			} else {
				window.localStorage.setItem( THEME_KEY, next );
			}
		} catch {
			// Not remembered; this page still switches.
		}
		if ( next === system ) {
			delete document.documentElement.dataset.theme;
		} else {
			document.documentElement.dataset.theme = next;
		}
		labelToggle();
	} );
	systemDark.addEventListener( 'change', labelToggle );
	// Another tab changed the theme.
	window.addEventListener( 'storage', ( event ) => {
		if ( event.key === THEME_KEY ) {
			if ( event.newValue === 'light' || event.newValue === 'dark' ) {
				document.documentElement.dataset.theme = event.newValue;
			} else {
				delete document.documentElement.dataset.theme;
			}
			labelToggle();
		}
	} );
	labelToggle();
	toggle.hidden = false;
}
