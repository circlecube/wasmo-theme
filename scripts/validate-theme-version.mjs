#!/usr/bin/env node
/**
 * Ensure package.json and style.css Version: header agree.
 * Run before `npm version` in release prep, and after sync to confirm consistency.
 *
 * --warn  Emit GitHub Actions ::warning:: annotations and exit 0 instead of failing.
 *         Use for the pre-bump check where a manual version bump is acceptable.
 */
import { readFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const warnOnly = process.argv.includes( '--warn' );

const __dirname = dirname( fileURLToPath( import.meta.url ) );
const root = join( __dirname, '..' );
const pkg = JSON.parse( readFileSync( join( root, 'package.json' ), 'utf8' ) );
const pkgV = pkg.version;
const css = readFileSync( join( root, 'style.css' ), 'utf8' );

const cssM = css.match( /^\s*Version:\s*([0-9.]+)\s*$/m );
if ( ! cssM ) {
	console.error( 'Could not parse Version from style.css' );
	process.exit( 1 );
}
const cssV = cssM[ 1 ];

const bad = [];
if ( pkgV !== cssV ) {
	bad.push( [ 'style.css Version', pkgV, cssV ] );
}

if ( bad.length ) {
	const lines = [
		'Version mismatch — canonical source is package.json; then run:',
		'  node scripts/sync-theme-version-from-package.mjs',
		...bad.map(
			( [ label, expect, actual ] ) =>
				`  ${ label }: expected ${ expect }, got ${ actual }`
		),
	];
	if ( warnOnly ) {
		for ( const line of lines ) {
			console.log( `::warning::${ line }` );
		}
		console.log(
			'(--warn mode: continuing despite mismatch; sync step will fix this)'
		);
	} else {
		for ( const line of lines ) {
			console.error( line );
		}
		process.exit( 1 );
	}
}

console.log( 'OK: all versions match', pkgV );
