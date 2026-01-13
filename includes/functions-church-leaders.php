<?php
/**
 * Church Leaders Helper Functions
 * 
 * Functions for computed data, seniority calculations, and relationship logic
 * for the church-leader custom post type.
 *
 * @package wasmo
 */

// ============================================
// AGE & TENURE CALCULATIONS
// ============================================

/**
 * Get a leader's age (current if living, at death if deceased)
 *
 * @param int $leader_id The church leader post ID.
 * @return int|null Age in years, or null if birthdate not set.
 */
function wasmo_get_leader_age( $leader_id ) {
	$birthdate = get_field( 'birthdate', $leader_id );
	if ( ! $birthdate ) {
		return null;
	}

	$birth = new DateTime( $birthdate );
	$deathdate = get_field( 'deathdate', $leader_id );
	
	if ( $deathdate ) {
		$end = new DateTime( $deathdate );
	} else {
		$end = new DateTime();
	}

	$diff = $birth->diff( $end );
	return $diff->y;
}

/**
 * Get a leader's years of service as an apostle
 *
 * Uses ordain_end if service ended early (excommunication/removal),
 * otherwise deathdate, or current date if still serving.
 *
 * @param int $leader_id The church leader post ID.
 * @return int|null Years served, or null if ordained_date not set.
 */
function wasmo_get_leader_years_served( $leader_id ) {
	$ordained_date = get_field( 'ordained_date', $leader_id );
	if ( ! $ordained_date ) {
		return null;
	}

	$ordained = new DateTime( $ordained_date );
	$ordain_end = get_field( 'ordain_end', $leader_id );
	$deathdate = get_field( 'deathdate', $leader_id );
	
	// Service ended early (excommunication, resignation, removal)
	if ( $ordain_end ) {
		$end = new DateTime( $ordain_end );
	} elseif ( $deathdate ) {
		$end = new DateTime( $deathdate );
	} else {
		$end = new DateTime();
	}

	// Calculate precise years with decimal (to nearest tenth)
	$diff = $ordained->diff( $end );
	$years = $diff->y + ( $diff->m / 12 ) + ( $diff->d / 365 );
	return round( $years, 1 );
}

/**
 * Get a leader's age when they were called as an apostle
 *
 * @param int $leader_id The church leader post ID.
 * @return int|null Age at call, or null if dates not set.
 */
function wasmo_get_leader_age_at_call( $leader_id ) {
	$birthdate = get_field( 'birthdate', $leader_id );
	$ordained_date = get_field( 'ordained_date', $leader_id );
	
	if ( ! $birthdate || ! $ordained_date ) {
		return null;
	}

	$birth = new DateTime( $birthdate );
	$ordained = new DateTime( $ordained_date );

	$diff = $birth->diff( $ordained );
	return $diff->y;
}

/**
 * Get a prophet's years as church president
 *
 * @param int $leader_id The church leader post ID.
 * @return int|null Years as president, or null if date not set.
 */
function wasmo_get_leader_years_as_president( $leader_id ) {
	$president_date = get_field( 'became_president_date', $leader_id );
	if ( ! $president_date ) {
		return null;
	}

	$start = new DateTime( $president_date );
	$deathdate = get_field( 'deathdate', $leader_id );
	
	if ( $deathdate ) {
		$end = new DateTime( $deathdate );
	} else {
		$end = new DateTime();
	}

	$diff = $start->diff( $end );
	$years = $diff->y + ( $diff->m / 12 ) + ( $diff->d / 365 );
	return round( $years, 1 );
}

// ============================================
// SENIORITY (for apostles)
// ============================================

/**
 * Get list of apostles ordered by seniority (ordained_date)
 *
 * @param bool $include_deceased Whether to include deceased apostles.
 * @return array Array of apostle post IDs ordered by ordained_date.
 */
function wasmo_get_apostle_seniority_list( $include_deceased = false ) {
	$transient_key = $include_deceased ? 'wasmo_apostle_seniority_all' : 'wasmo_apostle_seniority';
	$cached = get_transient( $transient_key );
	
	if ( false !== $cached ) {
		return $cached;
	}

	$apostle_term = get_term_by( 'slug', 'apostle', 'leader-role' );
	if ( ! $apostle_term ) {
		return array();
	}

	$args = array(
		'post_type'      => 'church-leader',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'leader-role',
				'field'    => 'term_id',
				'terms'    => $apostle_term->term_id,
			),
		),
		'meta_key'       => 'ordained_date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
	);

	// Filter for living only if not including deceased
	if ( ! $include_deceased ) {
		$args['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'     => 'deathdate',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => 'deathdate',
				'value'   => '',
				'compare' => '=',
			),
		);
	}

	$apostle_posts = get_posts( $args );
	
	// Build array with ordained datetime for precise sorting
	$apostles_with_dates = array();
	foreach ( $apostle_posts as $apostle ) {
		$ordained_date = get_field( 'ordained_date', $apostle->ID );
		// Normalize to datetime - if no time provided, append midnight
		// This ensures backwards compatibility with date-only values
		if ( $ordained_date && strlen( $ordained_date ) === 10 ) {
			// Date only (Y-m-d), append midnight
			$ordained_date .= ' 00:00:00';
		}
		$apostles_with_dates[] = array(
			'id' => $apostle->ID,
			'ordained_datetime' => $ordained_date ? strtotime( $ordained_date ) : PHP_INT_MAX,
		);
	}
	
	// Sort by ordained datetime (ascending = earliest first = most senior)
	usort( $apostles_with_dates, function( $a, $b ) {
		return $a['ordained_datetime'] <=> $b['ordained_datetime'];
	});
	
	// Extract just the IDs in sorted order
	$apostles = array_column( $apostles_with_dates, 'id' );
	
	// Cache for 1 week
	set_transient( $transient_key, $apostles, WEEK_IN_SECONDS );
	
	return $apostles;
}

/**
 * Get a leader's seniority position (1 = most senior)
 *
 * @param int $leader_id The church leader post ID.
 * @param bool $include_deceased Whether to include deceased in ranking.
 * @return int|null Position number, or null if not an apostle.
 */
function wasmo_get_leader_seniority( $leader_id, $include_deceased = false ) {
	$seniority_list = wasmo_get_apostle_seniority_list( $include_deceased );
	$position = array_search( $leader_id, $seniority_list );
	
	if ( false === $position ) {
		return null;
	}
	
	return $position + 1; // Convert 0-indexed to 1-indexed
}

// ============================================
// SERVED WITH (computed from date overlap)
// ============================================

/**
 * Get prophets that an apostle served under
 *
 * @param int $apostle_id The apostle's post ID.
 * @return array Array of prophet post IDs.
 */
function wasmo_get_served_with_prophets( $apostle_id ) {
	$transient_key = 'wasmo_served_with_' . $apostle_id;
	$cached = get_transient( $transient_key );
	
	if ( false !== $cached ) {
		return $cached;
	}

	$ordained_date = get_field( 'ordained_date', $apostle_id );
	if ( ! $ordained_date ) {
		return array();
	}

	$apostle_start = new DateTime( $ordained_date );
	$apostle_deathdate = get_field( 'deathdate', $apostle_id );
	$apostle_end = $apostle_deathdate ? new DateTime( $apostle_deathdate ) : new DateTime();

	// Get all prophets
	$president_term = get_term_by( 'slug', 'president', 'leader-role' );
	if ( ! $president_term ) {
		return array();
	}

	$presidents = get_posts( array(
		'post_type'      => 'church-leader',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'leader-role',
				'field'    => 'term_id',
				'terms'    => $president_term->term_id,
			),
		),
		'fields'         => 'ids',
	) );

	$served_with = array();

	foreach ( $presidents as $president_id ) {
		$president_date = get_field( 'became_president_date', $president_id );
		if ( ! $president_date ) {
			continue;
		}

		$president_start = new DateTime( $president_date );
		$president_deathdate = get_field( 'deathdate', $president_id );
		$president_end = $president_deathdate ? new DateTime( $president_deathdate ) : new DateTime();

		// Check for overlap: apostle_start < prophet_end AND apostle_end > prophet_start
		if ( $apostle_start < $president_end && $apostle_end > $president_start ) {
			$served_with[] = $president_id;
		}
	}

	// Sort by became_president_date
	usort( $served_with, function( $a, $b ) {
		$date_a = get_field( 'became_president_date', $a );
		$date_b = get_field( 'became_president_date', $b );
		return strcmp( $date_a, $date_b );
	} );

	// Cache for 1 week
	set_transient( $transient_key, $served_with, WEEK_IN_SECONDS );

	return $served_with;
}

/**
 * Get apostles who served under a specific prophet
 *
 * @param int $president_id The prophet's post ID.
 * @return array Array of apostle post IDs.
 */
function wasmo_get_apostles_who_served_under( $president_id ) {
	$transient_key = 'wasmo_apostles_under_' . $president_id;
	$cached = get_transient( $transient_key );
	
	if ( false !== $cached ) {
		return $cached;
	}

	$president_date = get_field( 'became_president_date', $president_id );
	if ( ! $president_date ) {
		return array();
	}

	$president_start = new DateTime( $president_date );
	$president_deathdate = get_field( 'deathdate', $president_id );
	$president_end = $president_deathdate ? new DateTime( $president_deathdate ) : new DateTime();

	// Get all apostles
	$all_apostles = wasmo_get_apostle_seniority_list( true );
	$served_under = array();

	foreach ( $all_apostles as $apostle_id ) {
		// Skip if this is the prophet themselves
		if ( $apostle_id === $president_id ) {
			continue;
		}

		$ordained_date = get_field( 'ordained_date', $apostle_id );
		if ( ! $ordained_date ) {
			continue;
		}

		$apostle_start = new DateTime( $ordained_date );
		$apostle_deathdate = get_field( 'deathdate', $apostle_id );
		$apostle_end = $apostle_deathdate ? new DateTime( $apostle_deathdate ) : new DateTime();

		// Check for overlap
		if ( $apostle_start < $president_end && $apostle_end > $president_start ) {
			$served_under[] = $apostle_id;
		}
	}

	// Cache for 1 week
	set_transient( $transient_key, $served_under, WEEK_IN_SECONDS );

	return $served_under;
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Get a church leader by their associated tag ID
 *
 * @param int $tag_id The post_tag term ID.
 * @return WP_Post|null The church leader post, or null if not found.
 */
function wasmo_get_leader_by_tag( $tag_id ) {
	if ( ! $tag_id ) {
		return null;
	}

	$leaders = get_posts( array(
		'post_type'      => 'church-leader',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'     => 'leader_tag',
				'value'   => $tag_id,
				'compare' => '=',
			),
		),
	) );

	return ! empty( $leaders ) ? $leaders[0] : null;
}

/**
 * Check if a leader is living (no death date)
 *
 * @param int $leader_id The church leader post ID.
 * @return bool True if living, false if deceased.
 */
function wasmo_is_leader_living( $leader_id ) {
	$deathdate = get_field( 'deathdate', $leader_id );
	return empty( $deathdate );
}

/**
 * Get leaders by role
 *
 * @param string $role_slug The leader-role taxonomy slug.
 * @param bool $living_only Whether to only return living leaders.
 * @return array Array of leader post objects.
 */
function wasmo_get_leaders_by_role( $role_slug, $living_only = true ) {
	$term = get_term_by( 'slug', $role_slug, 'leader-role' );
	if ( ! $term ) {
		return array();
	}

	$args = array(
		'post_type'      => 'church-leader',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'leader-role',
				'field'    => 'term_id',
				'terms'    => $term->term_id,
			),
		),
	);

	if ( $living_only ) {
		$args['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'     => 'deathdate',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => 'deathdate',
				'value'   => '',
				'compare' => '=',
			),
		);
	}

	return get_posts( $args );
}

/**
 * Get the current living prophet
 *
 * @return WP_Post|null The prophet post object, or null if not found.
 */
function wasmo_get_current_prophet() {
	$presidents = wasmo_get_leaders_by_role( 'president', true );
	return ! empty( $presidents ) ? $presidents[0] : null;
}

/**
 * Get the current first counselor in the first presidency
 *
 * @return WP_Post|null The prophet post object, or null if not found.
 */
function wasmo_get_current_first_counselor() {
	$first_counselor = wasmo_get_leaders_by_role( 'first-counselor', true );
	return ! empty( $first_counselor ) ? $first_counselor[0] : null;
}

/**
 * Get the current second counselor in the first presidency
 *
 * @return WP_Post|null The prophet post object, or null if not found.
 */
function wasmo_get_current_second_counselor() {
	$second_counselor = wasmo_get_leaders_by_role( 'second-counselor', true );
	return ! empty( $second_counselor ) ? $second_counselor[0] : null;
}

/**
 * Get the current First Presidency
 * 
 * Uses admin settings if configured, otherwise falls back to taxonomy-based detection.
 *
 * @return array Array with prophet and counselors: [president, first-counselor, second-counselor]
 */
function wasmo_get_current_first_presidency() {
	$transient_key = 'wasmo_first_presidency';
	$cached = get_transient( $transient_key );
	
	if ( false !== $cached ) {
		return $cached;
	}

	// Check for manual settings first
	$settings_president = get_option( 'wasmo_current_president', '' );
	$settings_first_counselor = get_option( 'wasmo_current_first_counselor', '' );
	$settings_second_counselor = get_option( 'wasmo_current_second_counselor', '' );

	// Use settings if available, otherwise fall back to taxonomy detection
	if ( $settings_president ) {
		$president_id = intval( $settings_president );
	} else {
		$president = wasmo_get_current_prophet();
		$president_id = $president ? $president->ID : null;
	}

	if ( $settings_first_counselor ) {
		$first_counselor_id = intval( $settings_first_counselor );
	} else {
		$first_counselor = wasmo_get_current_first_counselor();
		$first_counselor_id = $first_counselor ? $first_counselor->ID : null;
	}

	if ( $settings_second_counselor ) {
		$second_counselor_id = intval( $settings_second_counselor );
	} else {
		$second_counselor = wasmo_get_current_second_counselor();
		$second_counselor_id = $second_counselor ? $second_counselor->ID : null;
	}

	$result = array(
		'president'        => $president_id,
		'first-counselor'  => $first_counselor_id,
		'second-counselor' => $second_counselor_id,
	);

	// Cache for 1 week
	set_transient( $transient_key, $result, WEEK_IN_SECONDS );

	return $result;
}

/**
 * Get the current Quorum of the Twelve (excluding First Presidency members)
 *
 * @return array Array of apostle post IDs in seniority order.
 */
function wasmo_get_current_quorum_of_twelve() {
	$all_apostles = wasmo_get_apostle_seniority_list( false );
	$first_presidency = wasmo_get_current_first_presidency();
	
	$exclude = array(
		$first_presidency['president'],
		$first_presidency['first-counselor'],
		$first_presidency['second-counselor']
	);

	return array_values( array_diff( $all_apostles, $exclude ) );
}

/**
 * Check if a leader has a specific role
 *
 * @param int $leader_id The church leader post ID.
 * @param string $role_slug The role slug to check.
 * @return bool True if leader has the role.
 */
function wasmo_leader_has_role( $leader_id, $role_slug ) {
	return has_term( $role_slug, 'leader-role', $leader_id );
}

/**
 * Get related posts for a leader (via tag and ACF relationship)
 *
 * @param int $leader_id The church leader post ID.
 * @param int $limit Maximum number of posts to return.
 * @return array Array of post objects.
 */
function wasmo_get_leader_related_posts( $leader_id, $limit = 10 ) {
	$related_posts = array();
	$post_ids = array();

	// Get posts via ACF relationship (reverse lookup)
	$relationship_posts = get_posts( array(
		'post_type'      => 'post',
		'posts_per_page' => $limit,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'     => 'related_leaders',
				'value'   => '"' . $leader_id . '"',
				'compare' => 'LIKE',
			),
		),
	) );

	foreach ( $relationship_posts as $post ) {
		if ( ! in_array( $post->ID, $post_ids ) ) {
			$post_ids[] = $post->ID;
			$related_posts[] = $post;
		}
	}

	// Get posts via associated tag
	$leader_tag_id = get_field( 'leader_tag', $leader_id );
	if ( $leader_tag_id && count( $related_posts ) < $limit ) {
		$tag_posts = get_posts( array(
			'post_type'      => 'post',
			'posts_per_page' => $limit - count( $related_posts ),
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $leader_tag_id,
				),
			),
			'post__not_in'   => $post_ids,
		) );

		$related_posts = array_merge( $related_posts, $tag_posts );
	}

	return array_slice( $related_posts, 0, $limit );
}

/**
 * Get related media/images for a leader (via tag and ACF relationship)
 *
 * @param int $leader_id The church leader post ID.
 * @param int $limit Maximum number of media items to return.
 * @return array Array of attachment post objects.
 */
function wasmo_get_leader_related_media( $leader_id, $limit = 20 ) {
	$related_media = array();
	$media_ids = array();

	// Get media via ACF relationship (reverse lookup)
	$relationship_media = get_posts( array(
		'post_type'      => 'attachment',
		'posts_per_page' => $limit,
		'post_status'    => 'inherit',
		'meta_query'     => array(
			array(
				'key'     => 'related_leaders',
				'value'   => '"' . $leader_id . '"',
				'compare' => 'LIKE',
			),
		),
	) );

	foreach ( $relationship_media as $media ) {
		if ( ! in_array( $media->ID, $media_ids ) ) {
			$media_ids[] = $media->ID;
			$related_media[] = $media;
		}
	}

	// Get media via associated tag
	$leader_tag_id = get_field( 'leader_tag', $leader_id );
	if ( $leader_tag_id && count( $related_media ) < $limit ) {
		$tag_media = get_posts( array(
			'post_type'      => 'attachment',
			'posts_per_page' => $limit - count( $related_media ),
			'post_status'    => 'inherit',
			'tax_query'      => array(
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $leader_tag_id,
				),
			),
			'post__not_in'   => $media_ids,
		) );

		$related_media = array_merge( $related_media, $tag_media );
	}

	return array_slice( $related_media, 0, $limit );
}

/**
 * Get chart data for all apostles (for visualization)
 *
 * @return array Associative array of apostle data for charts.
 */
function wasmo_get_leaders_chart_data() {
	$transient_key = 'wasmo_leaders_chart_data';
	$cached = get_transient( $transient_key );
	
	if ( false !== $cached ) {
		return $cached;
	}

	$apostles = wasmo_get_apostle_seniority_list( true );
	$data = array( 'apostles' => array() );

	foreach ( $apostles as $apostle_id ) {
		$post = get_post( $apostle_id );
		$roles = wp_get_post_terms( $apostle_id, 'leader-role', array( 'fields' => 'slugs' ) );
		$first_presidency = wasmo_get_current_first_presidency();
		
		$is_first_presidency = false;
		if ( $apostle_id === $first_presidency['president']
			|| $apostle_id === $first_presidency['first-counselor']
			|| $apostle_id === $first_presidency['second-counselor']
		) {
			$is_first_presidency = true;
		}

		$thumbnail_id = get_post_thumbnail_id( $apostle_id );
		$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ) : '';

		// Get dates for service calculation
		$ordained_date = get_field( 'ordained_date', $apostle_id );
		$ordain_end = get_field( 'ordain_end', $apostle_id );
		$deathdate = get_field( 'deathdate', $apostle_id );
		$became_president_date = get_field( 'became_president_date', $apostle_id );
		$is_living = wasmo_is_leader_living( $apostle_id );
		
		// Calculate effective service end date for charts
		// Priority: ordain_end (if service ended early) > deathdate > null (still serving)
		$service_end = null;
		$service_ended_early = false;
		if ( $ordain_end ) {
			$service_end = $ordain_end;
			$service_ended_early = true;
		} elseif ( $deathdate ) {
			$service_end = $deathdate;
		}
		
		// Is currently serving (living and service hasn't ended)
		$is_currently_serving = $is_living && ! $service_ended_early;
		
		// Calculate precise president tenure (if applicable)
		$president_years = null;
		if ( $became_president_date ) {
			$start = new DateTime( $became_president_date );
			$end = $deathdate ? new DateTime( $deathdate ) : new DateTime();
			$diff = $start->diff( $end );
			$president_years = round( $diff->y + ( $diff->m / 12 ) + ( $diff->d / 365 ), 1 );
		}

		$data['apostles'][] = array(
			'id'                    => $apostle_id,
			'name'                  => $post->post_title,
			'url'                   => get_permalink( $apostle_id ),
			'birthdate'             => get_field( 'birthdate', $apostle_id ),
			'deathdate'             => $deathdate,
			'ordained_date'         => $ordained_date,
			'ordain_end'            => $ordain_end,
			'ordain_note'           => get_field( 'ordain_note', $apostle_id ),
			'service_end'           => $service_end,
			'service_ended_early'   => $service_ended_early,
			'became_president_date' => $became_president_date,
			'is_living'             => $is_living,
			'is_currently_serving'  => $is_currently_serving,
			'age'                   => wasmo_get_leader_age( $apostle_id ),
			'age_at_call'           => wasmo_get_leader_age_at_call( $apostle_id ),
			'years_served'          => wasmo_get_leader_years_served( $apostle_id ),
			'president_years'       => $president_years,
			'roles'                 => $roles,
			'is_first_presidency'   => $is_first_presidency,
			'is_president'            => in_array( 'president', $roles ),
			'served_under'          => wasmo_get_served_with_prophets( $apostle_id ),
			'thumbnail'             => $thumbnail_url,
		);
	}

	// Cache for 1 day
	set_transient( $transient_key, $data, DAY_IN_SECONDS );

	return $data;
}

/**
 * Format a date for display
 *
 * @param string $date Date string in Y-m-d format.
 * @param string $format PHP date format string.
 * @return string Formatted date or empty string.
 */
function wasmo_format_leader_date( $date, $format = 'F j, Y', $show_time_if_set = false ) {
	if ( empty( $date ) ) {
		return '';
	}
	
	// Try datetime format first (Y-m-d H:i:s)
	$datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $date );
	
	// Fall back to date-only format (Y-m-d)
	if ( ! $datetime ) {
		$datetime = DateTime::createFromFormat( 'Y-m-d', $date );
	}
	
	if ( ! $datetime ) {
		return '';
	}
	
	// If showing time and it's not midnight, append time to format
	if ( $show_time_if_set ) {
		$time = $datetime->format( 'H:i:s' );
		if ( $time !== '00:00:00' ) {
			$format .= ' \a\t g:i a';
		}
	}
	
	return $datetime->format( $format );
}

/**
 * Get leader's full display name
 *
 * @param int $leader_id The church leader post ID.
 * @return string Full name with middle initial if available.
 */
function wasmo_get_leader_display_name( $leader_id ) {
	$first = get_field( 'first_name', $leader_id );
	$middle = get_field( 'middle_name', $leader_id );
	$last = get_field( 'last_name', $leader_id );

	$name_parts = array();
	if ( $first ) {
		$name_parts[] = $first;
	}
	if ( $middle ) {
		// Use initial if middle name is longer than 2 characters
		$name_parts[] = strlen( $middle ) > 2 ? substr( $middle, 0, 1 ) . '.' : $middle;
	}
	if ( $last ) {
		$name_parts[] = $last;
	}

	return implode( ' ', $name_parts ) ?: get_the_title( $leader_id );
}

/**
 * Get leader's life span string (e.g., "1801-1877" or "1940-present")
 *
 * @param int $leader_id The church leader post ID.
 * @return string Life span string.
 */
function wasmo_get_leader_lifespan( $leader_id ) {
	$birthdate = get_field( 'birthdate', $leader_id );
	$deathdate = get_field( 'deathdate', $leader_id );

	if ( ! $birthdate ) {
		return '';
	}

	$birth_year = date( 'Y', strtotime( $birthdate ) );
	$age = wasmo_get_leader_age( $leader_id );
	
	if ( $deathdate ) {
		$death_year = date( 'Y', strtotime( $deathdate ) );
		return $birth_year . '–' . $death_year . ' (' . $age . ' years)';
	}

	return $birth_year . ' (' . $age . ' years)';
}

/**
 * Get leader's service date string (e.g., "Since 1830 (20 years)" or "1830–1877 (47 years)")
 *
 * Delegates to role-specific functions:
 * - For prophets: uses wasmo_get_president_service()
 * - For apostles: uses wasmo_get_apostle_service()
 *
 * @param int $leader_id The church leader post ID.
 * @return string Service date string.
 */
function wasmo_get_leader_service_date( $leader_id ) {
	// Check if prophet first (they may have both prophet and apostle roles)
	if ( wasmo_leader_has_role( $leader_id, 'president' ) ) {
		return wasmo_get_president_service( $leader_id );
	}
	
	// Check if apostle
	if ( wasmo_leader_has_role( $leader_id, 'apostle' ) ) {
		return wasmo_get_apostle_service( $leader_id );
	}
	
	return '';
}

/**
 * Get president's service data (time served as church president)
 * 
 * All presidents have served until death, so we use became_president_date
 * to deathdate. If no deathdate, they are the current president.
 *
 * @param int $leader_id The church leader post ID.
 * @return string President service date string.
 */
function wasmo_get_president_service( $leader_id ) {
	$pres_date = get_field( 'became_president_date', $leader_id );
	
	if ( ! $pres_date ) {
		return '';
	}
	
	$pres_year = date( 'Y', strtotime( $pres_date ) );
	$death_date = get_field( 'deathdate', $leader_id );
	$now_year = date( 'Y' );
	
	if ( $death_date ) {
		$death_year = date( 'Y', strtotime( $death_date ) );
		$service_len = $death_year - $pres_year;
		return 'President from ' . $pres_year . ' to ' . $death_year . ' (' . $service_len . ' years)';
	} else {
		// Current president (no death date)
		$service_len = $now_year - $pres_year;
		return 'President since ' . $pres_year . ' (' . $service_len . ' years)';
	}
}

/**
 * Get apostle's service data whether they are alive or deceased
 * 
 * Uses ordained_date to ordain_end (if service ended early via excommunication/removal),
 * otherwise deathdate, or current date if still living and serving.
 *
 * @param int $leader_id The church leader post ID.
 * @return string Apostle service date string.
 */
function wasmo_get_apostle_service( $leader_id ) {
	$ordained_date = get_field( 'ordained_date', $leader_id );
	
	if ( ! $ordained_date ) {
		return '';
	}
	
	$ordained_year = date( 'Y', strtotime( $ordained_date ) );
	$ordain_end = get_field( 'ordain_end', $leader_id );
	$death_date = get_field( 'deathdate', $leader_id );
	$now_year = date( 'Y' );
	
	// Service ended early (excommunication, resignation, removal)
	if ( $ordain_end ) {
		$end_year = date( 'Y', strtotime( $ordain_end ) );
		$service_len = $end_year - $ordained_year;
		return 'Apostle from ' . $ordained_year . ' to ' . $end_year . ' (' . $service_len . ' years)';
	}
	
	// Service ended at death
	if ( $death_date ) {
		$death_year = date( 'Y', strtotime( $death_date ) );
		$service_len = $death_year - $ordained_year;
		return 'Apostle from ' . $ordained_year . ' to ' . $death_year . ' (' . $service_len . ' years)';
	}
	
	// Living apostle still serving
	$service_len = $now_year - $ordained_year;
	return 'Apostle since ' . $ordained_year . ' (' . $service_len . ' years)';
}

// ============================================
// DISPLAY / RENDERING
// ============================================

/**
 * Render a leader card for archive and listing pages
 *
 * @param int    $leader_id         The church leader post ID.
 * @param string $size              Card size: 'small', 'medium', or 'large'.
 * @param bool   $show_age_dates    Whether to show lifespan dates.
 * @param bool   $show_service_dates Whether to show service dates.
 * @param bool   $show_role         Whether to show taxonomy roles.
 * @param string $role_override     Custom role text to display instead of taxonomy.
 */
function wasmo_render_leader_card(
	$leader_id,
	$size = 'medium',
	$show_age_dates = true,
	$show_service_dates = true,
	$show_role = false,
	$role_override = ''
) {
	$leader = get_post( $leader_id );
	$thumbnail = get_the_post_thumbnail_url( $leader_id, 'medium' );
	$roles = wp_get_post_terms( $leader_id, 'leader-role', array( 'fields' => 'names' ) );
	$is_living = wasmo_is_leader_living( $leader_id );
	$fp = wasmo_get_current_first_presidency();
	$is_pr = $leader_id === $fp['president'];
	$is_fc = $leader_id === $fp['first-counselor'];
	$is_sc = $leader_id === $fp['second-counselor'];
	$is_fp = $is_pr || $is_fc || $is_sc;
	?>
	<a
		href="<?php echo get_permalink( $leader_id ); ?>" 
		class="
			leader-card leader-card-<?php echo esc_attr( $size ); ?>
			<?php echo $is_living ? 'leader-living' : 'leader-deceased'; ?>
			<?php echo $is_fp ? 'leader-fp' : ''; ?>
			<?php echo $is_pr ? 'leader-pr' : ''; ?>
			<?php echo $is_fc ? 'leader-fc' : ''; ?>
			<?php echo $is_sc ? 'leader-sc' : ''; ?>
		"
		title="<?php echo esc_html( $leader->post_title ); ?>"
	>
		<div class="leader-card-image-wrapper">
			<?php if ( $thumbnail ) : ?>
				<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $leader->post_title ); ?>" class="leader-card-image">
			<?php else : ?>
				<div class="leader-card-placeholder">
					<span><?php echo esc_html( substr( $leader->post_title, 0, 1 ) ); ?></span>
				</div>
			<?php endif; ?>
		</div>
		<div class="leader-card-info">
			<span class="leader-card-name"><?php echo esc_html( $leader->post_title ); ?></span>
			<?php if ( $show_age_dates ) : ?>
				<span class="leader-card-dates"><?php echo esc_html( wasmo_get_leader_lifespan( $leader_id ) ); ?></span>
			<?php endif; ?>
			<?php if ( $show_role && ! empty( $roles ) ) : ?>
				<span class="leader-card-role"><?php echo esc_html( implode( ', ', $roles ) ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $role_override ) && $role_override !== '' ) : ?>
				<span class="leader-card-role"><?php echo esc_html( $role_override ); ?></span>
			<?php endif; ?>
			<?php if ( $show_service_dates ) : ?>
				<span class="leader-card-dates"><?php echo esc_html( wasmo_get_leader_service_date( $leader_id ) ); ?></span>
			<?php endif; ?>
		</div>
	</a>
	<?php
}

// ============================================
// CACHE MANAGEMENT
// ============================================

/**
 * Clear all church leader related transients
 * 
 * Called when a leader is saved to ensure fresh data.
 *
 * @param int $post_id The post ID being saved.
 */
function wasmo_clear_leader_transients( $post_id ) {
	// Only run for church-leader post type
	if ( get_post_type( $post_id ) !== 'church-leader' ) {
		return;
	}
	
	// Clear seniority lists
	delete_transient( 'wasmo_apostle_seniority' );
	delete_transient( 'wasmo_apostle_seniority_all' );
	
	// Clear chart data
	delete_transient( 'wasmo_leaders_chart_data' );
	
	// Clear first presidency
	delete_transient( 'wasmo_first_presidency' );
	
	// Clear individual leader served_with transients
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wasmo_served_with_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wasmo_served_with_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wasmo_apostles_under_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wasmo_apostles_under_%'" );
}
add_action( 'save_post', 'wasmo_clear_leader_transients' );
add_action( 'acf/save_post', 'wasmo_clear_leader_transients', 20 );
