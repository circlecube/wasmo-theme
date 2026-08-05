// @ts-check
const { defineConfig, devices } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: './tests/e2e',
	timeout: 30_000,
	retries: process.env.CI ? 1 : 0,
	use: {
		baseURL: 'http://localhost:8888',
		...devices[ 'Desktop Chrome' ],
	},
	reporter: process.env.CI ? 'github' : 'list',
} );
