#!/usr/bin/env node
/**
 * Copy `version` from package.json into style.css (Version: header).
 * Use after `npm version` in release prep.
 */
import { readFileSync, writeFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname( fileURLToPath( import.meta.url ) );
const root = join( __dirname, '..' );
const v = JSON.parse(
	readFileSync( join( root, 'package.json' ), 'utf8' )
).version;

const pathCss = join( root, 'style.css' );
let css = readFileSync( pathCss, 'utf8' );

if ( ! /^\s*Version:\s*[0-9.]+\s*$/m.test( css ) ) {
	console.error( 'Could not find Version line in style.css' );
	process.exit( 1 );
}
css = css.replace( /^(\s*Version:\s*)[0-9.]+/m, `$1${ v }` );
writeFileSync( pathCss, css );
console.log( `Synced style.css to ${ v }` );
