/**
 * Recipe card behaviour (docs/SPEC.md, section 4): servings scaler, unit switch and cook
 * mode. Progressive enhancement: without this file the card is complete at its default
 * servings, and the controls stay hidden.
 *
 * Quantity text must match kusina_format_qty() in includes/format.php: at the default
 * servings and "As written" the server's text is restored as-is.
 *
 * One file on purpose: the static exporter only collects assets the HTML references.
 */

// Mirrors kusina_units() in includes/format.php, plus oz/lb which only the US conversion makes.
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
	oz: [ 'oz', 'oz' ],
	lb: [ 'lb', 'lb' ],
};

const FRACTION_UNITS = new Set( [ '', 'tsp', 'tbsp', 'cup', 'piece', 'clove', 'thumb', 'pinch', 'oz', 'lb' ] );

const FRACTIONS = [
	[ 1 / 8, '⅛' ],
	[ 1 / 4, '¼' ],
	[ 1 / 3, '⅓' ],
	[ 3 / 8, '⅜' ],
	[ 1 / 2, '½' ],
	[ 5 / 8, '⅝' ],
	[ 2 / 3, '⅔' ],
	[ 3 / 4, '¾' ],
	[ 7 / 8, '⅞' ],
];

const UNITS_KEY = 'kusina-units';
const MAX_SERVINGS = 99;

/* ---------- Quantity formatting (mirror of includes/format.php) ---------- */

function formatNumber( n, unit ) {
	if ( ( unit === 'g' || unit === 'ml' ) && n > 50 ) {
		return String( Math.round( n ) );
	}
	if ( unit === 'oz' || unit === 'lb' ) {
		n = Math.round( n * 4 ) / 4;
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
	if ( unit === 'to-taste' ) {
		return labels[ 0 ];
	}
	if ( qty === null ) {
		return unit === 'pinch' ? labels[ 0 ] : '';
	}
	let number = formatNumber( qty, unit );
	if ( max !== null ) {
		number += '–' + formatNumber( max, unit );
	}
	const label = ( max ?? qty ) > 1 ? labels[ 1 ] : labels[ 0 ];
	return label === '' ? number : number + ' ' + label;
}

/* ---------- Unit conversion ---------- */

// Kitchen conventions: 1 cup = 240 ml, 1 tbsp = 15 ml, 1 tsp = 5 ml.
function toUs( qty, unit ) {
	if ( unit === 'g' || unit === 'kg' ) {
		const oz = ( unit === 'kg' ? qty * 1000 : qty ) / 28.3495;
		return oz >= 16 ? [ oz / 16, 'lb' ] : [ oz, 'oz' ];
	}
	if ( unit === 'ml' || unit === 'l' ) {
		const ml = unit === 'l' ? qty * 1000 : qty;
		if ( ml >= 60 ) {
			return [ ml / 240, 'cup' ];
		}
		return ml >= 15 ? [ ml / 15, 'tbsp' ] : [ ml / 5, 'tsp' ];
	}
	return [ qty, unit ];
}

// Keeps metric amounts in a readable unit after scaling: 1500 g -> 1.5 kg, 0.5 kg -> 500 g.
function tidyMetric( qty, unit ) {
	if ( unit === 'g' && qty >= 1000 ) {
		return [ qty / 1000, 'kg' ];
	}
	if ( unit === 'kg' && qty < 1 ) {
		return [ qty * 1000, 'g' ];
	}
	if ( unit === 'ml' && qty >= 1000 ) {
		return [ qty / 1000, 'l' ];
	}
	if ( unit === 'l' && qty < 1 ) {
		return [ qty * 1000, 'ml' ];
	}
	return [ qty, unit ];
}

function convert( qty, unit, mode ) {
	if ( mode === 'us' ) {
		return toUs( qty, unit );
	}
	if ( mode === 'metric' && unit === 'cup' ) {
		return tidyMetric( qty * 240, 'ml' );
	}
	return tidyMetric( qty, unit );
}

// After scaling, move between spoons and cups the way a cook would: ¼ tbsp -> ¾ tsp,
// 6 tsp -> 2 tbsp, 8 tbsp -> ½ cup. Metric mode has no cups, so big spoon amounts go to ml.
function tidySpoons( qty, unit, mode ) {
	for ( let pass = 0; pass < 3; pass++ ) {
		if ( unit === 'tbsp' && qty < 1 ) {
			[ qty, unit ] = [ qty * 3, 'tsp' ];
		} else if ( unit === 'tsp' && qty >= 3 ) {
			[ qty, unit ] = [ qty / 3, 'tbsp' ];
		} else if ( unit === 'tbsp' && qty >= 4 ) {
			[ qty, unit ] = mode === 'metric' ? [ qty * 15, 'ml' ] : [ qty / 16, 'cup' ];
		} else if ( unit === 'cup' && qty < 0.25 ) {
			[ qty, unit ] = [ qty * 16, 'tbsp' ];
		} else {
			break;
		}
	}
	return [ qty, unit ];
}

// Nearest whole number or kitchen fraction, so 2.08 cups reads "2 cups". A small amount
// never rounds away to zero.
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

const SNAP_UNITS = new Set( [ '', 'tsp', 'tbsp', 'cup', 'piece', 'clove', 'thumb', 'pinch' ] );

// Ranges convert as a pair so both ends share the unit chosen for the lower one.
function scaledText( item, factor, mode ) {
	const qty = item.qty * factor;
	const [ converted, convertedUnit ] = convert( qty, item.unit, mode );
	const [ tidy, unit ] = tidySpoons( converted, convertedUnit, mode );
	const ratio = tidy / qty;
	const round = ( n ) => ( SNAP_UNITS.has( unit ) ? snap( n ) : n );
	const max = item.max === null ? null : round( item.max * factor * ratio );
	return formatQty( round( tidy ), max, unit );
}

/* ---------- Small helpers ---------- */

function toNumber( value ) {
	if ( value === undefined || value === null || value === '' ) {
		return null;
	}
	const n = Number( value );
	return Number.isFinite( n ) && n > 0 ? n : null;
}

function readStored( key ) {
	try {
		return window.localStorage.getItem( key );
	} catch {
		return null;
	}
}

function writeStored( key, value ) {
	try {
		window.localStorage.setItem( key, value );
	} catch {
		// Private mode or blocked storage: the choice just isn't remembered.
	}
}

function clock( ms ) {
	const total = Math.ceil( ms / 1000 );
	const h = Math.floor( total / 3600 );
	const m = Math.floor( ( total % 3600 ) / 60 );
	const s = String( total % 60 ).padStart( 2, '0' );
	return h ? `${ h }:${ String( m ).padStart( 2, '0' ) }:${ s }` : `${ m }:${ s }`;
}

/* ---------- Servings scaler and unit switch ---------- */

function initScaler( card ) {
	const tools = card.querySelector( '[data-recipe-scale]' );
	if ( ! tools ) {
		return;
	}

	const base = parseInt( card.dataset.servings, 10 ) || 1;
	const input = tools.querySelector( '.servings-input' );
	const down = tools.querySelector( '[data-scale="down"]' );
	const up = tools.querySelector( '[data-scale="up"]' );
	const status = tools.querySelector( '[data-scale-status]' );
	const servingsText = card.querySelectorAll( '.recipe-servings' );
	const items = Array.from( card.querySelectorAll( '.ingredient-qty' ), ( el ) => ( {
		el,
		qty: toNumber( el.dataset.qty ),
		max: toNumber( el.dataset.qtyMax ),
		unit: el.dataset.unit || '',
		text: el.textContent,
	} ) );

	let servings = base;
	let mode = [ 'original', 'metric', 'us' ].includes( readStored( UNITS_KEY ) ) ? readStored( UNITS_KEY ) : 'original';

	const radio = tools.querySelector( `input[name="kusina-units"][value="${ mode }"]` );
	if ( radio ) {
		radio.checked = true;
	}

	function render( announce ) {
		const factor = servings / base;
		for ( const item of items ) {
			item.el.textContent =
				item.qty === null || ( factor === 1 && mode === 'original' ) ? item.text : scaledText( item, factor, mode );
		}
		servingsText.forEach( ( el ) => {
			el.textContent = String( servings );
		} );
		input.value = String( servings );
		down.disabled = servings <= 1;
		up.disabled = servings >= MAX_SERVINGS;

		if ( announce ) {
			const noun = servings === 1 ? tools.dataset.nounOne : tools.dataset.nounMany;
			status.textContent = tools.dataset.msg.replace( '%s', `${ servings } ${ noun }` );
		}
	}

	function setServings( value ) {
		const next = Math.min( MAX_SERVINGS, Math.max( 1, Math.round( value ) ) );
		if ( next !== servings ) {
			servings = next;
			render( true );
		} else {
			input.value = String( servings );
		}
	}

	down.addEventListener( 'click', () => setServings( servings - 1 ) );
	up.addEventListener( 'click', () => setServings( servings + 1 ) );
	input.addEventListener( 'change', () => {
		const value = parseInt( input.value, 10 );
		if ( Number.isFinite( value ) ) {
			setServings( value );
		} else {
			input.value = String( servings );
		}
	} );
	tools.addEventListener( 'change', ( event ) => {
		if ( event.target.name === 'kusina-units' ) {
			mode = event.target.value;
			writeStored( UNITS_KEY, mode );
			render( false );
		}
	} );

	render( false );
	tools.hidden = false;
}

/* ---------- Cook mode ---------- */

function initCookMode( card ) {
	const opener = card.querySelector( '[data-cook-open]' );
	const template = card.querySelector( '.cook-mode-template' );
	const steps = Array.from( card.querySelectorAll( '.recipe-step' ), ( li ) => ( {
		text: li.querySelector( '.step-text' ),
		minutes: parseInt( li.dataset.minutes, 10 ) || 0,
	} ) );
	if ( ! opener || ! template || ! steps.length ) {
		return;
	}

	// Timer state per step (null when the step has no time). endAt is a timestamp, so a
	// throttled background tab can't make a timer drift.
	const timers = steps.map( ( step ) =>
		step.minutes ? { total: step.minutes * 60000, remaining: step.minutes * 60000, endAt: 0, state: 'idle' } : null
	);
	const baseTitle = document.title;
	const chips = new Map();
	let dialog = null;
	let ui = null;
	let labels = null;
	let index = 0;
	let ticker = 0;
	let wakeLock = null;
	let audio = null;

	const left = ( timer ) => ( timer.state === 'running' ? Math.max( 0, timer.endAt - Date.now() ) : timer.remaining );
	const stepLabel = ( i ) => labels.step.replace( '%s', String( i + 1 ) );

	function build() {
		dialog = template.content.firstElementChild.cloneNode( true );
		document.body.append( dialog );

		const part = ( name ) => dialog.querySelector( `[data-cook="${ name }"]` );
		ui = {
			n: part( 'n' ),
			total: part( 'total' ),
			step: part( 'step' ),
			ingredients: part( 'ingredients' ),
			ingredientList: part( 'ingredient-list' ),
			toggle: part( 'toggle-ingredients' ),
			timer: part( 'timer' ),
			time: part( 'time' ),
			start: part( 'timer-start' ),
			running: part( 'running' ),
			status: part( 'status' ),
			prev: part( 'prev' ),
			next: part( 'next' ),
		};
		const d = dialog.dataset;
		labels = {
			start: d.labelStart,
			pause: d.labelPause,
			resume: d.labelResume,
			done: d.labelDone,
			next: d.labelNext,
			finish: d.labelFinish,
			step: d.labelStep,
			msgDone: d.msgDone,
			titleDone: d.titleDone,
		};
		ui.total.textContent = String( steps.length );

		dialog.addEventListener( 'click', onClick );
		// On the document, not the dialog: when a step without a timer hides the focused
		// "Start timer" button, focus falls to <body> and a dialog listener would go deaf.
		document.addEventListener( 'keydown', ( event ) => {
			if ( dialog.open ) {
				onKey( event );
			}
		} );
		dialog.addEventListener( 'close', releaseWakeLock );

		// Swipe left/right on the step text (touch and pen; mice use the buttons).
		let startX = null;
		let startY = 0;
		ui.step.addEventListener( 'pointerdown', ( event ) => {
			if ( event.pointerType !== 'mouse' ) {
				startX = event.clientX;
				startY = event.clientY;
			}
		} );
		ui.step.addEventListener( 'pointerup', ( event ) => {
			if ( startX === null ) {
				return;
			}
			const dx = event.clientX - startX;
			const dy = event.clientY - startY;
			startX = null;
			if ( Math.abs( dx ) > 60 && Math.abs( dx ) > Math.abs( dy ) * 1.5 ) {
				go( dx < 0 ? 1 : -1 );
			}
		} );
		ui.step.addEventListener( 'pointercancel', () => {
			startX = null;
		} );
	}

	function onClick( event ) {
		const button = event.target.closest( '[data-cook]' );
		if ( ! button || button.disabled ) {
			return;
		}
		switch ( button.dataset.cook ) {
			case 'close':
				dialog.close();
				break;
			case 'prev':
				go( -1 );
				break;
			case 'next':
				if ( index === steps.length - 1 ) {
					dialog.close();
				} else {
					go( 1 );
				}
				break;
			case 'toggle-ingredients': {
				const open = ui.ingredients.hidden;
				ui.ingredients.hidden = ! open;
				ui.toggle.setAttribute( 'aria-expanded', String( open ) );
				break;
			}
			case 'timer-start':
				toggleTimer( index );
				break;
			case 'timer-reset':
				resetTimer( index );
				break;
			case 'goto':
				show( parseInt( button.dataset.step, 10 ) );
				break;
		}
	}

	function onKey( event ) {
		if ( event.altKey || event.ctrlKey || event.metaKey ) {
			return;
		}
		if ( event.key === 'ArrowRight' ) {
			event.preventDefault();
			go( 1 );
		} else if ( event.key === 'ArrowLeft' ) {
			event.preventDefault();
			go( -1 );
		}
	}

	function go( delta ) {
		show( index + delta );
	}

	function show( i ) {
		index = Math.max( 0, Math.min( steps.length - 1, i ) );
		ui.n.textContent = String( index + 1 );
		ui.step.replaceChildren( steps[ index ].text.cloneNode( true ) );
		ui.prev.disabled = index === 0;
		ui.next.textContent = index === steps.length - 1 ? labels.finish : labels.next;
		renderTimers();

		// Keep keyboard focus in the dialog if the focused control was just hidden.
		if ( dialog.open && ! dialog.contains( document.activeElement ) ) {
			ui.next.focus();
		}
	}

	// The drawer copies the card's ingredient lists as they are now (scaled, converted).
	function fillIngredients() {
		const copy = document.createDocumentFragment();
		for ( const el of card.querySelector( '.recipe-ingredients' ).children ) {
			if ( ! el.matches( 'h2, [data-recipe-scale]' ) ) {
				copy.append( el.cloneNode( true ) );
			}
		}
		ui.ingredientList.replaceChildren( copy );
	}

	function renderTimers() {
		const timer = timers[ index ];
		ui.timer.hidden = ! timer;
		if ( timer ) {
			ui.timer.dataset.state = timer.state;
			ui.time.textContent = timer.state === 'done' ? labels.done : clock( left( timer ) );
			ui.start.textContent = { idle: labels.start, running: labels.pause, paused: labels.resume, done: labels.done }[
				timer.state
			];
			ui.start.disabled = timer.state === 'done';
		}

		// Chips for timers on other steps. Updated in place, so a focused chip keeps focus.
		const active = new Set();
		timers.forEach( ( t, i ) => {
			if ( t && i !== index && ( t.state === 'running' || t.state === 'done' ) ) {
				active.add( i );
			}
		} );
		for ( const [ i, chip ] of chips ) {
			if ( ! active.has( i ) ) {
				chip.remove();
				chips.delete( i );
			}
		}
		for ( const i of active ) {
			let chip = chips.get( i );
			if ( ! chip ) {
				chip = document.createElement( 'button' );
				chip.type = 'button';
				chip.className = 'cook-chip';
				chip.dataset.cook = 'goto';
				chip.dataset.step = String( i );
				chips.set( i, chip );
				ui.running.append( chip );
			}
			const t = timers[ i ];
			chip.dataset.state = t.state;
			chip.textContent = `${ stepLabel( i ) } · ${ t.state === 'done' ? labels.done : clock( left( t ) ) }`;
		}
		ui.running.hidden = chips.size === 0;
	}

	function updateTitle() {
		const running = timers.filter( ( t ) => t && t.state === 'running' );
		if ( running.length ) {
			document.title = `${ clock( Math.min( ...running.map( left ) ) ) } · ${ baseTitle }`;
		} else if ( timers.some( ( t ) => t && t.state === 'done' ) ) {
			document.title = `${ labels.titleDone } · ${ baseTitle }`;
		} else {
			document.title = baseTitle;
		}
	}

	function toggleTimer( i ) {
		const timer = timers[ i ];
		if ( timer.state === 'running' ) {
			timer.remaining = left( timer );
			timer.state = 'paused';
		} else if ( timer.state !== 'done' ) {
			unlockAudio();
			timer.endAt = Date.now() + timer.remaining;
			timer.state = 'running';
			startTicker();
		}
		renderTimers();
		updateTitle();
	}

	function resetTimer( i ) {
		const timer = timers[ i ];
		timer.state = 'idle';
		timer.remaining = timer.total;
		renderTimers();
		updateTitle();
	}

	function startTicker() {
		if ( ! ticker ) {
			ticker = window.setInterval( tick, 250 );
		}
	}

	function tick() {
		timers.forEach( ( timer, i ) => {
			if ( timer && timer.state === 'running' && left( timer ) <= 0 ) {
				timer.state = 'done';
				timer.remaining = 0;
				chime();
				ui.status.textContent = labels.msgDone.replace( '%s', String( i + 1 ) );
			}
		} );
		if ( ! timers.some( ( t ) => t && t.state === 'running' ) ) {
			window.clearInterval( ticker );
			ticker = 0;
		}
		renderTimers();
		updateTitle();
	}

	// Browsers only allow sound after a user gesture: the context is made on "Start timer".
	function unlockAudio() {
		try {
			audio = audio || new ( window.AudioContext || window.webkitAudioContext )();
			if ( audio.state === 'suspended' ) {
				audio.resume();
			}
		} catch {
			audio = null;
		}
	}

	function chime() {
		if ( navigator.vibrate ) {
			navigator.vibrate( [ 200, 100, 200, 100, 200 ] );
		}
		if ( ! audio ) {
			return;
		}
		const now = audio.currentTime;
		[ 0, 0.35, 0.7 ].forEach( ( offset ) => {
			const osc = audio.createOscillator();
			const gain = audio.createGain();
			osc.type = 'sine';
			osc.frequency.value = 880;
			gain.gain.setValueAtTime( 0.0001, now + offset );
			gain.gain.exponentialRampToValueAtTime( 0.3, now + offset + 0.02 );
			gain.gain.exponentialRampToValueAtTime( 0.0001, now + offset + 0.3 );
			osc.connect( gain ).connect( audio.destination );
			osc.start( now + offset );
			osc.stop( now + offset + 0.32 );
		} );
	}

	// Keep the screen on while cooking. The lock drops when the tab is hidden, so it is
	// requested again on return.
	async function requestWakeLock() {
		if ( ! ( 'wakeLock' in navigator ) || ! dialog || ! dialog.open || wakeLock ) {
			return;
		}
		try {
			wakeLock = await navigator.wakeLock.request( 'screen' );
			wakeLock.addEventListener( 'release', () => {
				wakeLock = null;
			} );
		} catch {
			wakeLock = null;
		}
	}

	function releaseWakeLock() {
		if ( wakeLock ) {
			wakeLock.release().catch( () => {} );
			wakeLock = null;
		}
	}

	document.addEventListener( 'visibilitychange', () => {
		if ( document.visibilityState === 'visible' ) {
			requestWakeLock();
		}
	} );

	opener.addEventListener( 'click', () => {
		if ( ! dialog ) {
			build();
		}
		fillIngredients();
		show( index );
		dialog.showModal();
		requestWakeLock();
	} );

	opener.hidden = false;
}

const card = document.querySelector( '.recipe-card' );
if ( card ) {
	initScaler( card );
	initCookMode( card );
}
