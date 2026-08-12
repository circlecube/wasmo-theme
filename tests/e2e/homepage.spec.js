// @ts-check
const { test, expect } = require( '@playwright/test' );

test( 'homepage loads with HTTP 200', async ( { page } ) => {
	const response = await page.goto( '/' );
	expect( response?.status() ).toBe( 200 );
} );

test( 'homepage has a title', async ( { page } ) => {
	await page.goto( '/' );
	await expect( page ).toHaveTitle( /.+/ );
} );

test( 'homepage has no uncaught JS errors', async ( { page } ) => {
	const errors = [];
	page.on( 'pageerror', ( err ) => errors.push( err.message ) );
	await page.goto( '/' );
	expect( errors ).toHaveLength( 0 );
} );
