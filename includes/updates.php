<?php
use WP_Forge\WPUpdateHandler\ThemeUpdater;

// Updater
$theme           = wp_get_theme( 'wasmo-theme' );
$url             = 'https://api.github.com/repos/circlecube/wasmo-theme/releases/latest';
$wasmo_cache_key = 'wasmo_theme_github_release';

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
		'requires'     => '6.6',
		'requires_php' => '8.1',
		'tested'       => '7.0',
	)
);

// WP_Forge 1.0.2 has a caching bug: it calls delete_transient() before
// get_transient() on every filter run, so every admin page load makes a live
// HTTP request to the GitHub API. The unauthenticated rate limit is 60 req/hr;
// ~30 admin page loads exhaust it and GitHub returns 403, causing updates to
// disappear. We layer our own transient cache around the HTTP call to fix this.
// WP_Forge only deletes its own key (wp_theme_update_wasmo-theme), so our key
// survives. We bust it when WordPress refreshes its update_themes transient so
// "Check again" in the admin still fetches a fresh GitHub release.

// Add Authorization header when WASMO_GITHUB_TOKEN is defined in wp-config.php.
// A token raises the GitHub API rate limit from 60 to 5,000 req/hr and prevents
// 403 responses from silently breaking update detection.
// Usage: define( 'WASMO_GITHUB_TOKEN', 'ghp_...' ); in wp-config.php
add_filter(
	'http_request_args',
	function ( $args, $request_url ) use ( $url ) {
		if ( $request_url !== $url ) {
			return $args;
		}
		if ( defined( 'WASMO_GITHUB_TOKEN' ) && WASMO_GITHUB_TOKEN ) {
			if ( ! isset( $args['headers'] ) || ! is_array( $args['headers'] ) ) {
				$args['headers'] = array();
			}
			$args['headers']['Authorization'] = 'Bearer ' . WASMO_GITHUB_TOKEN;
		}
		return $args;
	},
	10,
	2
);

add_filter(
	'pre_http_request',
	function ( $preempt, $parsed_args, $request_url ) use ( $url, $wasmo_cache_key ) {
		if ( $request_url !== $url ) {
			return $preempt;
		}
		$cached = get_transient( $wasmo_cache_key );
		if ( false !== $cached ) {
			return array(
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => $cached,
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => '',
			);
		}
		return $preempt;
	},
	10,
	3
);

add_filter(
	'http_response',
	function ( $response, $parsed_args, $request_url ) use ( $url, $wasmo_cache_key ) {
		if ( $request_url !== $url ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 === $code ) {
			$body = wp_remote_retrieve_body( $response );
			if ( $body ) {
				set_transient( $wasmo_cache_key, $body, HOUR_IN_SECONDS * 6 );
			}
		} elseif ( 403 === $code || 429 === $code ) {
			// Rate-limited: cache an empty-JSON marker for 10 minutes so subsequent
			// filter runs return immediately instead of hitting GitHub again. The
			// pre_http_request filter replays this as a synthetic 200 with body '{}',
			// which gives WP_Forge an empty payload and leaves the theme in no_update
			// rather than erroring. "Check again" in WP Admin busts this cache too.
			set_transient( $wasmo_cache_key, '{}', MINUTE_IN_SECONDS * 10 );
		}
		return $response;
	},
	10,
	3
);

// "Check again" in WP Admin deletes the update_themes site transient — bust our
// GitHub release cache at the same time so the next check fetches fresh data.
add_action(
	'delete_site_transient_update_themes',
	function () use ( $wasmo_cache_key ) {
		delete_transient( $wasmo_cache_key );
	}
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
				$version = ltrim( $transient->{$bucket}[ $stylesheet ]['version'] ?? '', 'v' );

				$transient->{$bucket}[ $stylesheet ]['version']     = $version;
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
