<?php
/**
 * URL filter helpers for the full profiles directory.
 *
 * @package wasmo-theme
 */

/**
 * Base URL for the profiles directory page.
 *
 * @return string
 */
function wasmo_get_directory_base_url() {
	static $url = null;

	if ( null === $url ) {
		$page = get_page_by_path( 'profiles' );
		$url  = $page ? get_permalink( $page ) : home_url( '/profiles/' );
	}

	return $url;
}

/**
 * Human-readable label for the active sort.
 *
 * @param array $state Filter state.
 * @return string
 */
function wasmo_get_directory_sort_label( $state ) {
	return 'name' === $state['sort']
		? __( 'Name (A–Z)', 'wasmo-theme' )
		: __( 'Recently updated', 'wasmo-theme' );
}

/**
 * Whether the current sort differs from the default directory view.
 *
 * @param array $state Filter state.
 * @return bool
 */
function wasmo_directory_sort_is_non_default( $state ) {
	return 'name' === $state['sort'];
}

/**
 * Human-readable label for the active media filter.
 *
 * @param string $media Media filter value.
 * @return string
 */
function wasmo_get_directory_media_label( $media ) {
	switch ( $media ) {
		case 'video':
			return __( 'With video', 'wasmo-theme' );
		case 'photo':
			return __( 'With photo', 'wasmo-theme' );
		default:
			return '';
	}
}

/**
 * Parse and validate directory filter state from the current request.
 *
 * @return array{
 *   sort: string,
 *   media: string,
 *   imported: bool,
 *   shelf: string,
 *   spectrum: string,
 *   shelf_term_id: int,
 *   spectrum_term_id: int
 * }
 */
function wasmo_get_directory_filter_state() {
	static $state = null;

	if ( null !== $state ) {
		return $state;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only public GET filter params; nonces are inappropriate for bookmarkable URLs.
	$sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'updated';
	if ( ! in_array( $sort, array( 'updated', 'name' ), true ) ) {
		$sort = 'updated';
	}

	$video = ! empty( $_GET['video'] ) && '1' === sanitize_key( wp_unslash( $_GET['video'] ) );
	$photo = ! empty( $_GET['photo'] ) && '1' === sanitize_key( wp_unslash( $_GET['photo'] ) );

	$media = 'all';
	if ( isset( $_GET['media'] ) ) {
		$media_param = sanitize_key( wp_unslash( $_GET['media'] ) );
		if ( in_array( $media_param, array( 'video', 'photo' ), true ) ) {
			$media = $media_param;
		}
	} elseif ( $video ) {
		$media = 'video';
	} elseif ( $photo ) {
		$media = 'photo';
	}

	$imported = ! empty( $_GET['imported'] ) && '1' === sanitize_key( wp_unslash( $_GET['imported'] ) );

	$shelf_slug    = isset( $_GET['shelf'] ) ? sanitize_title( wp_unslash( $_GET['shelf'] ) ) : '';
	$spectrum_slug = isset( $_GET['spectrum'] ) ? sanitize_title( wp_unslash( $_GET['spectrum'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$shelf_term_id    = 0;
	$spectrum_term_id = 0;

	if ( $shelf_slug ) {
		$shelf_term = get_term_by( 'slug', $shelf_slug, 'shelf' );
		if ( $shelf_term && ! is_wp_error( $shelf_term ) ) {
			$shelf_term_id = (int) $shelf_term->term_id;
		} else {
			$shelf_slug = '';
		}
	}

	if ( $spectrum_slug ) {
		$spectrum_term = get_term_by( 'slug', $spectrum_slug, 'spectrum' );
		if ( $spectrum_term && ! is_wp_error( $spectrum_term ) ) {
			$spectrum_term_id = (int) $spectrum_term->term_id;
		} else {
			$spectrum_slug = '';
		}
	}

	$state = array(
		'sort'             => $sort,
		'media'            => $media,
		'imported'         => $imported,
		'shelf'            => $shelf_slug,
		'spectrum'         => $spectrum_slug,
		'shelf_term_id'    => $shelf_term_id,
		'spectrum_term_id' => $spectrum_term_id,
	);

	return $state;
}

/**
 * Whether any directory URL filter is active.
 *
 * @param array|null $state Optional filter state.
 * @return bool
 */
function wasmo_directory_filter_is_active( $state = null ) {
	if ( null === $state ) {
		$state = wasmo_get_directory_filter_state();
	}

	return 'all' !== $state['media']
		|| ! empty( $state['imported'] )
		|| ! empty( $state['shelf'] )
		|| ! empty( $state['spectrum'] )
		|| wasmo_directory_sort_is_non_default( $state );
}

/**
 * Query args for pagination and canonical filter URLs.
 *
 * @param array|null $state Optional filter state.
 * @return array<string, string>
 */
function wasmo_get_directory_filter_query_args( $state = null ) {
	if ( null === $state ) {
		$state = wasmo_get_directory_filter_state();
	}

	$args = array();

	if ( 'name' === $state['sort'] ) {
		$args['sort'] = 'name';
	}

	if ( in_array( $state['media'], array( 'video', 'photo' ), true ) ) {
		$args['media'] = $state['media'];
	}
	if ( ! empty( $state['imported'] ) ) {
		$args['imported'] = '1';
	}
	if ( ! empty( $state['shelf'] ) ) {
		$args['shelf'] = $state['shelf'];
	}
	if ( ! empty( $state['spectrum'] ) ) {
		$args['spectrum'] = $state['spectrum'];
	}

	return $args;
}

/**
 * Transient key suffix for a filter state.
 *
 * @param array|null $state Optional filter state.
 * @return string
 */
function wasmo_directory_filter_transient_suffix( $state = null ) {
	if ( null === $state ) {
		$state = wasmo_get_directory_filter_state();
	}

	$parts = array(
		'sort-' . $state['sort'],
	);

	if ( in_array( $state['media'], array( 'video', 'photo' ), true ) ) {
		$parts[] = 'media-' . $state['media'];
	}
	if ( ! empty( $state['imported'] ) ) {
		$parts[] = 'imported';
	}
	if ( ! empty( $state['shelf'] ) ) {
		$parts[] = 'shelf-' . $state['shelf'];
	}
	if ( ! empty( $state['spectrum'] ) ) {
		$parts[] = 'spectrum-' . $state['spectrum'];
	}

	return 'filters-' . implode( '_', $parts );
}

/**
 * Build a directory URL with filter overrides or removals.
 *
 * @param array<string, mixed> $overrides Values to set.
 * @param array<int, string>   $remove    Filter keys to clear.
 * @return string
 */
function wasmo_directory_filter_url( $overrides = array(), $remove = array() ) {
	$state = wasmo_get_directory_filter_state();

	foreach ( $remove as $key ) {
		switch ( $key ) {
			case 'sort':
				$state['sort'] = 'updated';
				break;
			case 'media':
			case 'video':
			case 'photo':
				$state['media'] = 'all';
				break;
			case 'imported':
				$state['imported'] = false;
				break;
			case 'shelf':
				$state['shelf']         = '';
				$state['shelf_term_id'] = 0;
				break;
			case 'spectrum':
				$state['spectrum']         = '';
				$state['spectrum_term_id'] = 0;
				break;
		}
	}

	foreach ( $overrides as $key => $value ) {
		$state[ $key ] = $value;
	}

	if ( isset( $overrides['shelf'] ) || in_array( 'shelf', $remove, true ) ) {
		$state['shelf_term_id'] = 0;
		if ( ! empty( $state['shelf'] ) ) {
			$shelf_term = get_term_by( 'slug', $state['shelf'], 'shelf' );
			if ( $shelf_term && ! is_wp_error( $shelf_term ) ) {
				$state['shelf_term_id'] = (int) $shelf_term->term_id;
			} else {
				$state['shelf'] = '';
			}
		}
	}

	if ( isset( $overrides['spectrum'] ) || in_array( 'spectrum', $remove, true ) ) {
		$state['spectrum_term_id'] = 0;
		if ( ! empty( $state['spectrum'] ) ) {
			$spectrum_term = get_term_by( 'slug', $state['spectrum'], 'spectrum' );
			if ( $spectrum_term && ! is_wp_error( $spectrum_term ) ) {
				$state['spectrum_term_id'] = (int) $spectrum_term->term_id;
			} else {
				$state['spectrum'] = '';
			}
		}
	}

	if ( ! in_array( $state['sort'], array( 'updated', 'name' ), true ) ) {
		$state['sort'] = 'updated';
	}

	if ( ! in_array( $state['media'], array( 'all', 'video', 'photo' ), true ) ) {
		$state['media'] = 'all';
	}

	$args = wasmo_get_directory_filter_query_args( $state );
	$base = wasmo_get_directory_base_url();

	if ( empty( $args ) ) {
		return $base;
	}

	return add_query_arg( $args, $base );
}

/**
 * Whether a user profile was imported (has source URL or import note).
 *
 * @param int $user_id User ID.
 * @return bool
 */
function wasmo_user_is_imported_profile( $user_id ) {
	$import_source = get_field( 'import_source', 'user_' . $user_id );
	$import_text   = get_field( 'import_text', 'user_' . $user_id );

	return ! empty( $import_source ) || ! empty( $import_text );
}

/**
 * Filter callback: profile is an imported story.
 *
 * @param WP_User $user User object.
 * @return bool
 */
function wasmo_filter_directory_is_imported( $user ) {
	return wasmo_user_is_imported_profile( $user->ID );
}

/**
 * Filter callback: profile has a photo.
 *
 * @param WP_User $user User object.
 * @return bool
 */
function wasmo_filter_directory_has_photo( $user ) {
	return (bool) wasmo_user_has_image( $user->ID );
}

/**
 * Filter callback: profile has selected shelf term from URL filter.
 *
 * @param WP_User $user User object.
 * @return bool
 */
function wasmo_filter_directory_for_filter_shelf( $user ) {
	$termid = (int) get_query_var( 'directory_filter_shelf_term_id' );
	if ( ! $termid ) {
		return true;
	}

	$userterms = get_field( 'my_shelf', 'user_' . $user->ID );
	if ( empty( $userterms ) ) {
		return false;
	}

	foreach ( $userterms as $userterm ) {
		if ( (int) $userterm->term_id === $termid ) {
			return true;
		}
	}

	return false;
}

/**
 * Filter callback: profile has selected spectrum term from URL filter.
 *
 * @param WP_User $user User object.
 * @return bool
 */
function wasmo_filter_directory_for_filter_spectrum( $user ) {
	$termid = (int) get_query_var( 'directory_filter_spectrum_term_id' );
	if ( ! $termid ) {
		return true;
	}

	$userterms = get_field( 'mormon_spectrum', 'user_' . $user->ID );
	if ( empty( $userterms ) ) {
		return false;
	}

	foreach ( $userterms as $userterm ) {
		if ( (int) $userterm->term_id === $termid ) {
			return true;
		}
	}

	return false;
}

/**
 * Apply URL filter state to a list of directory users.
 *
 * @param WP_User[]  $users User list.
 * @param array|null $state Optional filter state.
 * @return WP_User[]
 */
function wasmo_apply_directory_url_filters( $users, $state = null ) {
	if ( null === $state ) {
		$state = wasmo_get_directory_filter_state();
	}

	if ( 'video' === $state['media'] ) {
		$users = array_filter( $users, 'wasmo_filter_directory_has_video' );
	}
	if ( 'photo' === $state['media'] ) {
		$users = array_filter( $users, 'wasmo_filter_directory_has_photo' );
	}
	if ( ! empty( $state['imported'] ) ) {
		$users = array_filter( $users, 'wasmo_filter_directory_is_imported' );
	}
	if ( $state['shelf_term_id'] ) {
		set_query_var( 'directory_filter_shelf_term_id', $state['shelf_term_id'] );
		$users = array_filter( $users, 'wasmo_filter_directory_for_filter_shelf' );
	}
	if ( $state['spectrum_term_id'] ) {
		set_query_var( 'directory_filter_spectrum_term_id', $state['spectrum_term_id'] );
		$users = array_filter( $users, 'wasmo_filter_directory_for_filter_spectrum' );
	}

	return $users;
}

/**
 * noindex filtered directory URLs; taxonomy archives stay indexed separately.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function wasmo_directory_filtered_urls_noindex( $robots ) {
	if ( ! is_page( 'profiles' ) ) {
		return $robots;
	}

	$state = wasmo_get_directory_filter_state();
	if ( wasmo_directory_filter_is_active( $state ) ) {
		$robots['noindex'] = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'wasmo_directory_filtered_urls_noindex', 25 );
