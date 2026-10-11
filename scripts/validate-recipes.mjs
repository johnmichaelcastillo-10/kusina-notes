#!/usr/bin/env node
/**
 * Checks recipe data files (scripts/recipes/README.md) before they are seeded.
 *
 *   node scripts/validate-recipes.mjs                 every scripts/recipes/*.json
 *   node scripts/validate-recipes.mjs a.json b.json   only these (duplicates still checked
 *                                                     against every other file)
 *   node scripts/validate-recipes.mjs --own a.json    only these, other files ignored
 *
 * Exit code 1 when any error is found. Warnings don't fail.
 */
import { readFileSync, readdirSync } from 'node:fs';
import { join, dirname, basename, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const DIR = join( dirname( fileURLToPath( import.meta.url ) ), 'recipes' );

const COURSES = [ 'ulam', 'sabaw', 'merienda', 'panghimagas', 'almusal', 'inumin' ];
const DIETS = [ 'vegetarian', 'pescatarian' ];
const UNITS = [ '', 'g', 'kg', 'ml', 'l', 'tsp', 'tbsp', 'cup', 'piece', 'clove', 'thumb', 'pinch', 'to-taste' ];
const DIFFICULTY = [ 'easy', 'medium', 'involved' ];

// Pantry finder vocabulary: key_ingredients must come from here.
export const KEY_INGREDIENTS = [
	// Meat and eggs
	'chicken', 'pork', 'pork belly', 'ground pork', 'pork offal', 'beef', 'ground beef', 'goat', 'duck',
	'chicken liver', 'egg', 'salted egg', 'quail egg', 'longganisa', 'tocino', 'corned beef', 'hotdog', 'ham', 'spam',
	// Fish and seafood
	'fish', 'bangus', 'tilapia', 'tuna', 'galunggong', 'salmon', 'dried fish', 'canned sardines', 'shrimp',
	'crab', 'squid', 'mussels', 'clams', 'oysters', 'octopus',
	// Staples
	'rice', 'glutinous rice', 'rice flour', 'flour', 'noodles', 'bread', 'pasta', 'tofu', 'mung beans', 'peanuts',
	// Vegetables
	'eggplant', 'squash', 'string beans', 'bitter gourd', 'okra', 'cabbage', 'bok choy', 'water spinach', 'malunggay',
	'chayote', 'bottle gourd', 'sponge gourd', 'bean sprouts', 'corn', 'green papaya', 'jackfruit', 'banana blossom',
	'taro leaves', 'potato', 'sweet potato', 'cassava', 'taro', 'ube', 'carrot', 'bell pepper', 'tomato', 'onion',
	'garlic', 'ginger', 'chili', 'lemongrass', 'mushrooms', 'spinach',
	// Fruit
	'banana', 'saba banana', 'mango', 'green mango', 'pineapple', 'young coconut', 'calamansi', 'tamarind', 'avocado',
	'melon', 'jackfruit (ripe)', 'guava', 'papaya',
	// Flavor and pantry
	'coconut milk', 'coconut', 'vinegar', 'soy sauce', 'fish sauce', 'shrimp paste', 'annatto', 'peanut butter',
	'brown sugar', 'muscovado', 'condensed milk', 'evaporated milk', 'milk', 'cheese', 'butter', 'cream',
	'chocolate', 'cocoa', 'coffee', 'tea', 'pandan', 'gelatin', 'sago', 'pinipig', 'oats',
];

const MEAT = /\b(pork|chicken|beef|goat|duck|ham|bacon|chorizo|longganisa|tocino|hotdog|sausage|spam|corned beef|liempo|lard|chicharon|chicharr[oó]n|bagnet|lechon|liver|tripe|oxtail|offal|blood|giniling|meat|broth|bouillon|stock cube|gelatin)\b/i;
const SEA = /\b(fish|bangus|tilapia|tuna|galunggong|salmon|sardine|sardines|anchov|dilis|tuyo|daing|tinapa|shrimp|prawn|hipon|crab|alimango|alimasag|squid|pusit|mussel|tahong|clam|halaan|oyster|talaba|octopus|bagoong|alamang|patis|fish sauce|oyster sauce|shrimp paste|seafood|dashi|bonito)\b/i;
// Words that look like meat/seafood but aren't.
const SAFE = /\b(vegetable broth|vegetable stock|mushroom broth|agar|gulaman|coconut meat|young coconut meat|buko meat|oyster mushrooms?|duck eggs?|salted duck eggs?)\b/gi;

const errors = [];
const warnings = [];
const err = ( where, msg ) => errors.push( `${ where }: ${ msg }` );
const warn = ( where, msg ) => warnings.push( `${ where }: ${ msg }` );

const isInt = ( n ) => Number.isInteger( n );
const isStr = ( s ) => typeof s === 'string';
const TAGS = /<(?!\/?(strong|em)>)[^>]*>/i;

function checkText( where, field, text, { min = 0, max = Infinity, html = false } = {} ) {
	if ( ! isStr( text ) ) {
		err( where, `${ field } must be a string` );
		return;
	}
	const plain = text.replace( /<\/?(strong|em)>/gi, '' );
	if ( plain.trim().length < min ) err( where, `${ field } is too short (${ plain.trim().length} < ${ min })` );
	if ( plain.length > max ) err( where, `${ field } is too long (${ plain.length } > ${ max })` );
	if ( ! html && /<[^>]+>/.test( text ) ) err( where, `${ field } may not contain HTML` );
	if ( html && TAGS.test( text ) ) err( where, `${ field } has HTML other than <strong>/<em>` );
	if ( /—/.test( text ) ) err( where, `${ field } has an em dash` );
	if ( /\n/.test( text ) ) err( where, `${ field } has a line break` );
}

function checkRecipe( r, where ) {
	if ( ! r || typeof r !== 'object' || Array.isArray( r ) ) {
		err( where, 'not an object' );
		return;
	}
	const known = [ 'slug', 'title', 'course', 'diets', 'key_ingredients', 'prep', 'cook', 'servings', 'yield_label', 'difficulty', 'intro', 'groups', 'steps', 'tips' ];
	for ( const key of Object.keys( r ) ) if ( ! known.includes( key ) ) err( where, `unknown field "${ key }"` );
	for ( const key of known ) if ( ! ( key in r ) ) err( where, `missing field "${ key }"` );

	if ( ! isStr( r.slug ) || ! /^[a-z0-9]+(-[a-z0-9]+)*$/.test( r.slug ) ) err( where, `bad slug "${ r.slug }"` );
	checkText( where, 'title', r.title, { min: 3, max: 60 } );
	if ( isStr( r.title ) && r.title[ 0 ] !== r.title[ 0 ].toUpperCase() ) err( where, 'title must start with a capital' );
	if ( ! COURSES.includes( r.course ) ) err( where, `bad course "${ r.course }"` );
	if ( ! Array.isArray( r.diets ) || r.diets.some( ( d ) => ! DIETS.includes( d ) ) ) err( where, `bad diets ${ JSON.stringify( r.diets ) }` );
	if ( Array.isArray( r.diets ) && r.diets.includes( 'vegetarian' ) && r.diets.includes( 'pescatarian' ) ) err( where, 'vegetarian and pescatarian together: use vegetarian only' );
	if ( ! Array.isArray( r.key_ingredients ) || r.key_ingredients.length < 1 || r.key_ingredients.length > 6 ) {
		err( where, 'key_ingredients must have 1 to 6 entries' );
	} else {
		for ( const k of r.key_ingredients ) if ( ! KEY_INGREDIENTS.includes( k ) ) err( where, `key ingredient "${ k }" is not in the vocabulary` );
		if ( new Set( r.key_ingredients ).size !== r.key_ingredients.length ) err( where, 'duplicate key ingredient' );
	}
	for ( const f of [ 'prep', 'cook' ] ) if ( ! isInt( r[ f ] ) || r[ f ] < 0 || r[ f ] > 600 ) err( where, `${ f } must be whole minutes 0 to 600` );
	if ( isInt( r.prep ) && isInt( r.cook ) && r.prep + r.cook === 0 ) err( where, 'prep and cook are both 0' );
	if ( ! isInt( r.servings ) || r.servings < 1 || r.servings > 60 ) err( where, 'servings must be 1 to 60' );
	if ( ! isStr( r.yield_label ) ) err( where, 'yield_label must be a string' );
	if ( ! DIFFICULTY.includes( r.difficulty ) ) err( where, `bad difficulty "${ r.difficulty }"` );
	checkText( where, 'intro', r.intro, { min: 40, max: 360 } );
	checkText( where, 'tips', r.tips, { max: 400, html: true } );

	const names = [];
	if ( ! Array.isArray( r.groups ) || ! r.groups.length ) {
		err( where, 'groups must be a non-empty array' );
	} else {
		let count = 0;
		r.groups.forEach( ( g, gi ) => {
			const gw = `${ where } group ${ gi + 1 }`;
			if ( ! isStr( g.title ) ) err( gw, 'title must be a string' );
			if ( ! Array.isArray( g.items ) || ! g.items.length ) {
				err( gw, 'items must be a non-empty array' );
				return;
			}
			g.items.forEach( ( it, ii ) => {
				const iw = `${ gw } item ${ ii + 1 }`;
				count++;
				for ( const key of Object.keys( it ) ) if ( ! [ 'qty', 'max', 'unit', 'name', 'note' ].includes( key ) ) err( iw, `unknown field "${ key }"` );
				const okNum = ( n ) => n === null || ( typeof n === 'number' && n > 0 && n < 100000 );
				if ( ! okNum( it.qty ) ) err( iw, `bad qty ${ it.qty }` );
				if ( ! okNum( it.max ) ) err( iw, `bad max ${ it.max }` );
				if ( it.max !== null && ( it.qty === null || it.max <= it.qty ) ) err( iw, 'max must be bigger than qty' );
				if ( ! UNITS.includes( it.unit ) ) err( iw, `bad unit "${ it.unit }"` );
				if ( it.unit === 'to-taste' && it.qty !== null ) err( iw, 'to-taste takes qty null' );
				if ( it.qty === null && ! [ 'to-taste', 'pinch', '' ].includes( it.unit ) ) err( iw, `unit "${ it.unit }" needs a qty` );
				if ( ! isStr( it.name ) || ! it.name.trim() ) err( iw, 'name is empty' );
				if ( ! isStr( it.note ) ) err( iw, 'note must be a string' );
				if ( isStr( it.name ) ) names.push( `${ it.name } ${ it.note || '' }` );
			} );
		} );
		if ( count < 2 ) err( where, 'fewer than 2 ingredients' );
	}

	if ( ! Array.isArray( r.steps ) || r.steps.length < 3 || r.steps.length > 14 ) {
		err( where, 'steps must have 3 to 14 entries' );
	} else {
		r.steps.forEach( ( s, si ) => {
			const sw = `${ where } step ${ si + 1 }`;
			checkText( sw, 'text', s.text, { min: 15, max: 500, html: true } );
			if ( s.minutes !== null && ( ! isInt( s.minutes ) || s.minutes < 1 || s.minutes > 480 ) ) err( sw, `bad minutes ${ s.minutes }` );
			for ( const key of Object.keys( s ) ) if ( ! [ 'text', 'minutes' ].includes( key ) ) err( sw, `unknown field "${ key }"` );
		} );
	}

	// Diet claims are checked against the ingredient list.
	const all = names.join( ' | ' ).replace( SAFE, '' );
	if ( Array.isArray( r.diets ) && r.diets.includes( 'vegetarian' ) ) {
		const m = all.match( MEAT ) || all.match( SEA );
		if ( m ) err( where, `marked vegetarian but has "${ m[ 0 ] }"` );
	}
	if ( Array.isArray( r.diets ) && r.diets.includes( 'pescatarian' ) ) {
		const m = all.match( MEAT );
		if ( m ) err( where, `marked pescatarian but has "${ m[ 0 ] }"` );
	}

	if ( isInt( r.prep ) && isInt( r.cook ) && Array.isArray( r.steps ) ) {
		const timed = r.steps.reduce( ( sum, s ) => sum + ( isInt( s.minutes ) ? s.minutes : 0 ), 0 );
		if ( timed > ( r.prep + r.cook ) * 1.5 + 10 ) warn( where, `step timers add up to ${ timed } min but prep + cook is ${ r.prep + r.cook }` );
	}
}

const norm = ( t ) => String( t ).toLowerCase().normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).replace( /[^a-z0-9]+/g, ' ' ).trim();

// --own: check only the named files, ignoring every other file (for parallel writers).
const own = process.argv.includes( '--own' );
const targets = process.argv.slice( 2 ).filter( ( a ) => a !== '--own' ).map( ( f ) => resolve( f ) );
const allFiles = own ? [] : readdirSync( DIR ).filter( ( f ) => f.endsWith( '.json' ) ).sort();
const checkSet = new Set( targets.length ? targets : allFiles.map( ( f ) => join( DIR, f ) ) );

const slugs = new Map();
const titles = new Map();
let total = 0;
let checked = 0;

for ( const file of new Set( [ ...allFiles.map( ( f ) => join( DIR, f ) ), ...checkSet ] ) ) {
	const name = basename( file );
	const inScope = checkSet.has( file );
	let data;
	try {
		data = JSON.parse( readFileSync( file, 'utf8' ) );
	} catch ( e ) {
		if ( inScope ) err( name, `invalid JSON: ${ e.message }` );
		continue;
	}
	if ( ! Array.isArray( data ) ) {
		if ( inScope ) err( name, 'top level must be an array' );
		continue;
	}
	data.forEach( ( r, i ) => {
		const where = `${ name } #${ i + 1 } ${ r && r.slug ? r.slug : '' }`;
		total++;
		if ( inScope ) {
			checked++;
			checkRecipe( r, where );
		}
		if ( r && r.slug ) {
			if ( slugs.has( r.slug ) && ( inScope || checkSet.has( slugs.get( r.slug ).file ) ) ) err( where, `duplicate slug, also in ${ slugs.get( r.slug ).where }` );
			else slugs.set( r.slug, { where, file } );
		}
		if ( r && r.title ) {
			const t = norm( r.title );
			if ( titles.has( t ) && ( inScope || checkSet.has( titles.get( t ).file ) ) ) err( where, `duplicate title "${ r.title }", also in ${ titles.get( t ).where }` );
			else titles.set( t, { where, file } );
		}
	} );
}

for ( const w of warnings ) console.log( `warning  ${ w }` );
for ( const e of errors ) console.log( `ERROR    ${ e }` );
console.log( `\n${ checked } recipes checked (${ total } in all files): ${ errors.length } errors, ${ warnings.length } warnings` );
process.exit( errors.length ? 1 : 0 );
