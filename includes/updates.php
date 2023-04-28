<?php 
use WP_Forge\WPUpdateHandler\ThemeUpdater;

// Updater
$theme = wp_get_theme( 'wasmo-theme' );
$url   = 'https://api.github.com/repos/circlecube/wasmo-theme/releases/latest';

// Handle plugin updates
$wasmoThemeUpdater = new ThemeUpdater( $theme, $url );
$wasmoThemeUpdater->setDataMap(
	array(
		'version'       => 'tag_name',
		'download_link' => 'zipball_url',
		'last_updated'  => 'published_at',
	)
);

$wasmoThemeUpdater->setDataOverrides(
	array(
		'requires'      => '6.2',
		'requires_php'  => '8.0',
		'tested'        => '6.2',
	)
);
