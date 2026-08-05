<?php 
use WP_Forge\WPUpdateHandler\ThemeUpdater;

// Updater
$theme = wp_get_theme( 'wasmo-theme' );
$url   = 'https://api.github.com/repos/circlecube/wasmo-theme/releases/latest';

// Handle theme updates
$wasmoThemeUpdater = new ThemeUpdater( $theme, $url );
$wasmoThemeUpdater->setDataMap(
	array(
		'download_link' => 'assets.0.browser_download_url',
		'last_updated'  => 'published_at',
		'version'       => 'tag_name',
	)
);

$wasmoThemeUpdater->setDataOverrides(
	array(
		'requires'      => '6.6',
		'requires_php'  => '8.2',
		'tested'        => '6.7',
	)
);
