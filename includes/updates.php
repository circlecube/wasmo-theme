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
		'requires'     => '6.2',
		'requires_php' => '8.0',
		'tested'       => '6.2',
	)
);

// GitHub release tags are prefixed with 'v' (e.g. v2.7.5). PHP's version_compare
// treats the leading 'v' as a special string ranked below all numerics, so
// version_compare('v2.7.5', '2.7.5', '>') returns false and updates are never
// detected. This filter runs after WP_Forge (priority 10) to normalize versions
// and re-classify any release that WP_Forge placed in no_update due to the prefix.
add_filter(
	'site_transient_update_themes',
	function ( $transient ) {
		if ( empty( $transient ) || ! is_object( $transient ) ) {
			return $transient;
		}

		$stylesheet        = 'wasmo-theme';
		$installed_version = wp_get_theme( $stylesheet )->get( 'Version' );

		foreach ( array( 'response', 'no_update' ) as $bucket ) {
			if ( ! empty( $transient->{$bucket}[ $stylesheet ] ) ) {
				$version                                        = ltrim( $transient->{$bucket}[ $stylesheet ]['version'] ?? '', 'v' );
				$transient->{$bucket}[ $stylesheet ]['version'] = $version;
				$transient->{$bucket}[ $stylesheet ]['new_version'] = $version;
			}
		}

		// WP_Forge may have placed the release in no_update because the v-prefixed
		// version compared as older. Re-evaluate now that versions are normalized.
		if ( ! empty( $transient->no_update[ $stylesheet ] ) ) {
			$release = $transient->no_update[ $stylesheet ];
			if ( version_compare( $release['version'], $installed_version, '>' ) ) {
				$transient->response[ $stylesheet ] = $release;
				unset( $transient->no_update[ $stylesheet ] );
			}
		}

		return $transient;
	},
	20
);
