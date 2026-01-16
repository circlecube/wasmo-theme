<?php
/**
 * Saints Helper Functions
 * 
 * Functions for computed data, seniority calculations, and relationship logic
 * for the saint custom post type.
 *
 * @package wasmo
 */

// ============================================
// DATE NORMALIZATION & FORMATTING
// ============================================

/**
 * Normalize a partial date to a full date and detect if it's approximate.
 * 
 * - Year only (e.g., "1844") -> "1844-01-01" (approximate)
 * - Year-month (e.g., "1844-06") -> "1844-06-01" (approximate)
 * - Full date (e.g., "1844-06-15") -> "1844-06-15" (exact)
 *
 * @param string $date_string The date string to normalize.
 * @return array ['date' => string|null, 'approximate' => bool]
 */
function wasmo_normalize_date( $date_string ) {
	if ( empty( $date_string ) ) {
		return array( 'date' => null, 'approximate' => false );
	}

	$date_string = trim( $date_string );
	
	// Handle various date formats
	// Check for "circa" or "c." prefix
	$is_circa = false;
	if ( preg_match( '/^c\.?\s*/i', $date_string ) ) {
		$is_circa = true;
		$date_string = preg_replace( '/^c\.?\s*/i', '', $date_string );
	}
	if ( preg_match( '/^circa\s*/i', $date_string ) ) {
		$is_circa = true;
		$date_string = preg_replace( '/^circa\s*/i', '', $date_string );
	}
	
	// Try to parse the date
	$approximate = $is_circa;
	$normalized_date = null;
	
	// Pattern: Year only (1844)
	if ( preg_match( '/^(\d{4})$/', $date_string, $matches ) ) {
		$normalized_date = $matches[1] . '-01-01';
		$approximate = true;
	}
	// Pattern: YYYYMMDD format without dashes (18440615)
	elseif ( preg_match( '/^(\d{4})(\d{2})(\d{2})$/', $date_string, $matches ) ) {
		$normalized_date = sprintf( '%04d-%02d-%02d', $matches[1], $matches[2], $matches[3] );
		// Keep approximate flag from circa detection
	}
	// Pattern: Year-Month (1844-06 or 1844/06)
	elseif ( preg_match( '/^(\d{4})[-\/](\d{1,2})$/', $date_string, $matches ) ) {
		$normalized_date = sprintf( '%04d-%02d-01', $matches[1], $matches[2] );
		$approximate = true;
	}
	// Pattern: Month Year (Jun 1844 or June 1844)
	elseif ( preg_match( '/^([A-Za-z]+)\s+(\d{4})$/', $date_string, $matches ) ) {
		$month = date( 'm', strtotime( $matches[1] . ' 1, 2000' ) );
		if ( $month ) {
			$normalized_date = sprintf( '%04d-%02d-01', $matches[2], $month );
			$approximate = true;
		}
	}
	// Pattern: Full date (1844-06-15 or various formats)
	else {
		$timestamp = strtotime( $date_string );
		if ( $timestamp !== false ) {
			$normalized_date = date( 'Y-m-d', $timestamp );
			// Keep approximate flag from circa detection
		}
	}
	
	return array(
		'date' => $normalized_date,
		'approximate' => $approximate,
	);
}

/**
 * Format a saint date for display, including approximate indicator.
 *
 * @param string $date The date string (Y-m-d format).
 * @param string $format The PHP date format (default: F j, Y).
 * @param bool   $show_time Whether to show time if available.
 * @param bool   $is_approximate Whether the date is approximate.
 * @return string Formatted date string.
 */
function wasmo_format_saint_date_with_approx( $date, $format = 'F j, Y', $show_time = false, $is_approximate = false ) {
	if ( empty( $date ) ) {
		return '';
	}

	// Check if we should show only year for approximate dates
	$prefix = '';
	if ( $is_approximate ) {
		$prefix = 'c. '; // "circa" abbreviation
	}

	// Parse and format the date
	$timestamp = strtotime( $date );
	if ( $timestamp === false ) {
		return $date;
	}

	// For approximate dates, only show year if day/month were defaulted
	if ( $is_approximate && $format === 'F j, Y' ) {
		// Check if it's Jan 1 (year-only approximation)
		if ( date( 'm-d', $timestamp ) === '01-01' ) {
			return $prefix . date( 'Y', $timestamp );
		}
		// Check if it's 1st of month (month-only approximation)
		if ( date( 'd', $timestamp ) === '01' ) {
			return $prefix . date( 'F Y', $timestamp );
		}
	}

	$formatted = date( $format, $timestamp );

	// Optionally append time for disambiguation
	if ( $show_time && strpos( $date, ' ' ) !== false ) {
		$time = date( 'g:i a', $timestamp );
		$formatted .= ' ' . $time;
	}

	return $prefix . $formatted;
}

// ============================================
// AGE & TENURE CALCULATIONS
// ============================================

/**
 * Get a saint's age (current if living, at death if deceased)
 *
 * @param int $saint_id The saint post ID.
 * @return int|null Age in years, or null if birthdate not set.
 */
function wasmo_get_saint_age( $saint_id ) {
	$birthdate = get_field( 'birthdate', $saint_id );
	if ( ! $birthdate ) {
		return null;
	}

	$birth = new DateTime( $birthdate );
	$deathdate = get_field( 'deathdate', $saint_id );
	
	if ( $deathdate ) {
		$end = new DateTime( $deathdate );
	} else {
		$end = new DateTime();
	}

	$diff = $birth->diff( $end );
	return $diff->y;
}

/**
 * Get a saint's years of service as an apostle
 *
 * Uses ordain_end if service ended early (excommunication/removal),
 * otherwise deathdate, or current date if still serving.
 *
 * @param int $saint_id The saint post ID.
 * @return int|null Years served, or null if ordained_date not set.
 */
function wasmo_get_saint_years_served( $saint_id ) {
	$ordained_date = get_field( 'ordained_date', $saint_id );
	if ( ! $ordained_date ) {
		return null;
	}

	$ordained = new DateTime( $ordained_date );
	$ordain_end = get_field( 'ordain_end', $saint_id );
	$deathdate = get_field( 'deathdate', $saint_id );
	
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
 * Get a saint's age when they were called as an apostle
 *
 * @param int $saint_id The saint post ID.
 * @return int|null Age at call, or null if dates not set.
 */
function wasmo_get_saint_age_at_call( $saint_id ) {
	$birthdate = get_field( 'birthdate', $saint_id );
	$ordained_date = get_field( 'ordained_date', $saint_id );
	
	if ( ! $birthdate || ! $ordained_date ) {
		return null;
	}

	$birth = new DateTime( $birthdate );
	$ordained = new DateTime( $ordained_date );

	$diff = $birth->diff( $ordained );
	return $diff->y;
}

/**
 * Get a president's years as church president
 *
 * @param int $saint_id The saint post ID.
 * @return int|null Years as president, or null if date not set.
 */
function wasmo_get_saint_years_as_president( $saint_id ) {
	$president_date = get_field( 'became_president_date', $saint_id );
	if ( ! $president_date ) {
		return null;
	}

	$start = new DateTime( $president_date );
	$deathdate = get_field( 'deathdate', $saint_id );
	
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

	$apostle_term = get_term_by( 'slug', 'apostle', 'saint-role' );
	if ( ! $apostle_term ) {
		return array();
	}

	$args = array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'saint-role',
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
 * Get a saint's seniority position (1 = most senior)
 *
 * @param int $saint_id The saint post ID.
 * @param bool $include_deceased Whether to include deceased in ranking.
 * @return int|null Position number, or null if not an apostle.
 */
function wasmo_get_saint_seniority( $saint_id, $include_deceased = false ) {
	$seniority_list = wasmo_get_apostle_seniority_list( $include_deceased );
	$position = array_search( $saint_id, $seniority_list );
	
	if ( false === $position ) {
		return null;
	}
	
	return $position + 1; // Convert 0-indexed to 1-indexed
}

// ============================================
// SERVED WITH (computed from date overlap)
// ============================================

/**
 * Get presidents that an apostle served under
 *
 * @param int $apostle_id The apostle's post ID.
 * @return array Array of president post IDs.
 */
function wasmo_get_served_with_presidents( $apostle_id ) {
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

	// Get all presidents
	$president_term = get_term_by( 'slug', 'president', 'saint-role' );
	if ( ! $president_term ) {
		return array();
	}

	$presidents = get_posts( array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'saint-role',
				'field'    => 'term_id',
				'terms'    => $president_term->term_id,
			),
		),
		'fields'         => 'ids',
		'exclude'        => array( $apostle_id ),
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

		// Check for overlap: apostle_start < president_end AND apostle_end > president_start
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
 * Get apostles who served under a specific president
 *
 * @param int $president_id The president's post ID.
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
		// Skip if this is the president themselves
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
 * Get a saint by their associated tag ID
 *
 * @param int $tag_id The post_tag term ID.
 * @return WP_Post|null The saint post, or null if not found.
 */
function wasmo_get_saint_by_tag( $tag_id ) {
	if ( ! $tag_id ) {
		return null;
	}

	$saints = get_posts( array(
		'post_type'      => 'saint',
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

	return ! empty( $saints ) ? $saints[0] : null;
}

/**
 * Check if a saint is living (no death date)
 *
 * @param int $saint_id The saint post ID.
 * @return bool True if living, false if deceased.
 */
function wasmo_is_saint_living( $saint_id ) {
	$deathdate = get_field( 'deathdate', $saint_id );
	return empty( $deathdate );
}

/**
 * Get saints by role
 *
 * @param string $role_slug The saint-role taxonomy slug.
 * @param bool $living_only Whether to only return living saints.
 * @return array Array of saint post objects.
 */
function wasmo_get_saints_by_role( $role_slug, $living_only = true ) {
	$term = get_term_by( 'slug', $role_slug, 'saint-role' );
	if ( ! $term ) {
		return array();
	}

	$args = array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'saint-role',
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
 * Get the current living president
 *
 * @return WP_Post|null The president post object, or null if not found.
 */
function wasmo_get_current_president() {
	$presidents = wasmo_get_saints_by_role( 'president', true );
	return ! empty( $presidents ) ? $presidents[0] : null;
}

/**
 * Get the current first counselor in the first presidency
 *
 * @return WP_Post|null The first counselor post object, or null if not found.
 */
function wasmo_get_current_first_counselor() {
	$first_counselor = wasmo_get_saints_by_role( 'first-counselor', true );
	return ! empty( $first_counselor ) ? $first_counselor[0] : null;
}

/**
 * Get the current second counselor in the first presidency
 *
 * @return WP_Post|null The second counselor post object, or null if not found.
 */
function wasmo_get_current_second_counselor() {
	$second_counselor = wasmo_get_saints_by_role( 'second-counselor', true );
	return ! empty( $second_counselor ) ? $second_counselor[0] : null;
}

/**
 * Get the current First Presidency
 * 
 * Uses admin settings if configured, otherwise falls back to taxonomy-based detection.
 *
 * @return array Array with president and counselors: [president, first-counselor, second-counselor]
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
		$president = wasmo_get_current_president();
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
 * Check if a saint has a specific role
 *
 * @param int $saint_id The saint post ID.
 * @param string $role_slug The role slug to check.
 * @return bool True if saint has the role.
 */
function wasmo_saint_has_role( $saint_id, $role_slug ) {
	return has_term( $role_slug, 'saint-role', $saint_id );
}

/**
 * Get related posts for a saint (via tag and ACF relationship)
 *
 * @param int $saint_id The saint post ID.
 * @param int $limit Maximum number of posts to return.
 * @return array Array of post objects.
 */
function wasmo_get_saint_related_posts( $saint_id, $limit = 10 ) {
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
				'value'   => '"' . $saint_id . '"',
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
	$saint_tag_id = get_field( 'leader_tag', $saint_id );
	if ( $saint_tag_id && count( $related_posts ) < $limit ) {
		$tag_posts = get_posts( array(
			'post_type'      => 'post',
			'posts_per_page' => $limit - count( $related_posts ),
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $saint_tag_id,
				),
			),
			'post__not_in'   => $post_ids,
		) );

		$related_posts = array_merge( $related_posts, $tag_posts );
	}

	return array_slice( $related_posts, 0, $limit );
}

/**
 * Get related media/images for a saint (via tag and ACF relationship)
 *
 * @param int $saint_id The saint post ID.
 * @param int $limit Maximum number of media items to return.
 * @return array Array of attachment post objects.
 */
function wasmo_get_saint_related_media( $saint_id, $limit = 20 ) {
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
				'value'   => '"' . $saint_id . '"',
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
	$saint_tag_id = get_field( 'leader_tag', $saint_id );
	if ( $saint_tag_id && count( $related_media ) < $limit ) {
		$tag_media = get_posts( array(
			'post_type'      => 'attachment',
			'posts_per_page' => $limit - count( $related_media ),
			'post_status'    => 'inherit',
			'tax_query'      => array(
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $saint_tag_id,
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
function wasmo_get_saints_chart_data() {
	$transient_key = 'wasmo_saints_chart_data';
	$cached = get_transient( $transient_key );
	
	if ( false !== $cached ) {
		return $cached;
	}

	$apostles = wasmo_get_apostle_seniority_list( true );
	$data = array( 'apostles' => array() );

	foreach ( $apostles as $apostle_id ) {
		$post = get_post( $apostle_id );
		$roles = wp_get_post_terms( $apostle_id, 'saint-role', array( 'fields' => 'slugs' ) );
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
		$is_living = wasmo_is_saint_living( $apostle_id );
		
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
			'age'                   => wasmo_get_saint_age( $apostle_id ),
			'age_at_call'           => wasmo_get_saint_age_at_call( $apostle_id ),
			'years_served'          => wasmo_get_saint_years_served( $apostle_id ),
			'president_years'       => $president_years,
			'roles'                 => $roles,
			'is_first_presidency'   => $is_first_presidency,
			'is_president'          => in_array( 'president', $roles ),
			'served_under'          => wasmo_get_served_with_presidents( $apostle_id ),
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
function wasmo_format_saint_date( $date, $format = 'F j, Y', $show_time_if_set = false ) {
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
 * Get saint's full display name
 *
 * @param int $saint_id The saint post ID.
 * @return string Full name with middle initial if available.
 */
function wasmo_get_saint_display_name( $saint_id ) {
	$first = get_field( 'first_name', $saint_id );
	$middle = get_field( 'middle_name', $saint_id );
	$last = get_field( 'last_name', $saint_id );

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

	return implode( ' ', $name_parts ) ?: get_the_title( $saint_id );
}

/**
 * Get saint's life span string (e.g., "1801-1877" or "1940-present")
 *
 * @param int $saint_id The saint post ID.
 * @return string Life span string.
 */
function wasmo_get_saint_lifespan( $saint_id ) {
	$birthdate = get_field( 'birthdate', $saint_id );
	$deathdate = get_field( 'deathdate', $saint_id );

	if ( ! $birthdate ) {
		return '';
	}

	$birth_year = date( 'Y', strtotime( $birthdate ) );
	$age = wasmo_get_saint_age( $saint_id );
	
	if ( $deathdate ) {
		$death_year = date( 'Y', strtotime( $deathdate ) );
		return $birth_year . '–' . $death_year . ' (' . $age . ' years)';
	}

	return $birth_year . ' (' . $age . ' years)';
}

/**
 * Get saint's service date string (e.g., "Since 1830 (20 years)" or "1830–1877 (47 years)")
 *
 * Delegates to role-specific functions:
 * - For presidents: uses wasmo_get_president_service()
 * - For apostles: uses wasmo_get_apostle_service()
 *
 * @param int $saint_id The saint post ID.
 * @return string Service date string.
 */
function wasmo_get_saint_service_date( $saint_id ) {
	// Check if president first (they may have both president and apostle roles)
	if ( wasmo_saint_has_role( $saint_id, 'president' ) ) {
		return wasmo_get_president_service( $saint_id );
	}
	
	// Check if apostle
	if ( wasmo_saint_has_role( $saint_id, 'apostle' ) ) {
		return wasmo_get_apostle_service( $saint_id );
	}
	
	return '';
}

/**
 * Get president's service data (time served as church president)
 * 
 * All presidents have served until death, so we use became_president_date
 * to deathdate. If no deathdate, they are the current president.
 *
 * @param int $saint_id The saint post ID.
 * @return string President service date string.
 */
function wasmo_get_president_service( $saint_id ) {
	$pres_date = get_field( 'became_president_date', $saint_id );
	
	if ( ! $pres_date ) {
		return '';
	}
	
	$pres_year = date( 'Y', strtotime( $pres_date ) );
	$death_date = get_field( 'deathdate', $saint_id );
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
 * @param int $saint_id The saint post ID.
 * @return string Apostle service date string.
 */
function wasmo_get_apostle_service( $saint_id ) {
	$ordained_date = get_field( 'ordained_date', $saint_id );
	
	if ( ! $ordained_date ) {
		return '';
	}
	
	$ordained_year = date( 'Y', strtotime( $ordained_date ) );
	$ordain_end = get_field( 'ordain_end', $saint_id );
	$death_date = get_field( 'deathdate', $saint_id );
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
 * Render a saint card for archive and listing pages
 *
 * @param int    $saint_id           The saint post ID.
 * @param string $size               Card size: 'small', 'medium', or 'large'.
 * @param bool   $show_age_dates     Whether to show lifespan dates.
 * @param bool   $show_service_dates Whether to show service dates.
 * @param bool   $show_role          Whether to show taxonomy roles.
 * @param string $role_override      Custom role text to display instead of taxonomy.
 */
function wasmo_render_saint_card(
	$saint_id,
	$size = 'medium',
	$show_age_dates = true,
	$show_service_dates = true,
	$show_role = false,
	$role_override = ''
) {
	$saint = get_post( $saint_id );
	$thumbnail = get_the_post_thumbnail_url( $saint_id, 'medium' );
	$roles = wp_get_post_terms( $saint_id, 'saint-role', array( 'fields' => 'names' ) );
	$role_slugs = wp_get_post_terms( $saint_id, 'saint-role', array( 'fields' => 'slugs' ) );
	$is_living = wasmo_is_saint_living( $saint_id );
	$fp    = wasmo_get_current_first_presidency();
	$is_current_pr = $saint_id === $fp['president'];
	$is_fc = $saint_id === $fp['first-counselor'];
	$is_sc = $saint_id === $fp['second-counselor'];
	$is_fp = $is_current_pr || $is_fc || $is_sc;
	
	// Check if saint has president role (current or past)
	$has_president_role = in_array( 'president', $role_slugs, true );
	?>
	<a
		href="<?php echo get_permalink( $saint_id ); ?>" 
		class="
			saint-card saint-card-<?php echo esc_attr( $size ); ?>
			<?php echo $is_living ? 'saint-living' : 'saint-deceased'; ?>
			<?php echo $is_fp ? 'saint-first-presidency' : ''; ?>
			<?php echo $has_president_role ? 'saint-president' : ''; ?>
			<?php echo $is_fc ? 'saint-first-counselor' : ''; ?>
			<?php echo $is_sc ? 'saint-second-counselor' : ''; ?>
		"
		title="<?php echo esc_html( $saint->post_title ); ?>"
	>
		<div class="saint-card-image-wrapper">
			<?php if ( $thumbnail ) : ?>
				<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $saint->post_title ); ?>" class="saint-card-image">
			<?php else : ?>
				<div class="saint-card-placeholder">
					<span><?php
						// get the first letter of each word in the title and join them together
						$title_letters = explode( ' ', $saint->post_title );
						$title_letters = array_map( function( $letter ) {
							return substr( $letter, 0, 1 );
						}, $title_letters );
						echo esc_html( implode( '', $title_letters ) );
					?></span>
				</div>
			<?php endif; ?>
		</div>
		<div class="saint-card-info">
			<span class="saint-card-name"><?php echo esc_html( $saint->post_title ); ?></span>
			<?php if ( $show_age_dates ) : ?>
				<span class="saint-card-dates"><?php echo esc_html( wasmo_get_saint_lifespan( $saint_id ) ); ?></span>
			<?php endif; ?>
			<?php if ( $show_role && ! empty( $roles ) ) : ?>
				<span class="saint-card-role"><?php echo esc_html( implode( ', ', $roles ) ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $role_override ) && $role_override !== '' ) : ?>
				<span class="saint-card-role"><?php echo esc_html( $role_override ); ?></span>
			<?php endif; ?>
			<?php if ( $show_service_dates ) : ?>
				<span class="saint-card-dates"><?php echo esc_html( wasmo_get_saint_service_date( $saint_id ) ); ?></span>
			<?php endif; ?>
		</div>
	</a>
	<?php
}

// ============================================
// CACHE MANAGEMENT
// ============================================

/**
 * Clear all saint related transients
 * 
 * Called when a saint is saved to ensure fresh data.
 *
 * @param int $post_id The post ID being saved.
 */
function wasmo_clear_saint_transients( $post_id ) {
	// Only run for saint post type
	if ( get_post_type( $post_id ) !== 'saint' ) {
		return;
	}
	
	// Clear seniority lists
	delete_transient( 'wasmo_apostle_seniority' );
	delete_transient( 'wasmo_apostle_seniority_all' );
	
	// Clear chart data
	delete_transient( 'wasmo_saints_chart_data' );
	
	// Clear first presidency
	delete_transient( 'wasmo_first_presidency' );
	
	// Clear archive page transients
	delete_transient( 'wasmo_archive_all_living' );
	delete_transient( 'wasmo_archive_past_presidents' );
	delete_transient( 'wasmo_archive_past_apostles' );
	delete_transient( 'wasmo_archive_past_other' );
	delete_transient( 'wasmo_archive_current_other' );
	delete_transient( 'wasmo_archive_wives' );
	delete_transient( 'wasmo_saint_roles' );
	
	// Clear individual saint served_with transients
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wasmo_served_with_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wasmo_served_with_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wasmo_apostles_under_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wasmo_apostles_under_%'" );
	
	// Clear role-specific archive transients
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wasmo_role_saints_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wasmo_role_saints_%'" );
}
add_action( 'save_post', 'wasmo_clear_saint_transients' );
add_action( 'acf/save_post', 'wasmo_clear_saint_transients', 20 );

/**
 * Clear saint role transients when a term is edited
 * 
 * @param int    $term_id  Term ID.
 * @param int    $tt_id    Term taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 */
function wasmo_clear_saint_role_transients( $term_id, $tt_id, $taxonomy ) {
	if ( 'saint-role' !== $taxonomy ) {
		return;
	}
	
	// Clear the saint roles list
	delete_transient( 'wasmo_saint_roles' );
	
	// Clear the specific role transient
	delete_transient( 'wasmo_role_saints_' . $term_id );
}
add_action( 'edited_term', 'wasmo_clear_saint_role_transients', 10, 3 );
add_action( 'created_term', 'wasmo_clear_saint_role_transients', 10, 3 );
add_action( 'delete_term', 'wasmo_clear_saint_role_transients', 10, 3 );

// ============================================
// CACHED ARCHIVE QUERIES
// ============================================

/**
 * Transient expiration time for archive queries (1 week)
 */
define( 'WASMO_ARCHIVE_TRANSIENT_EXPIRATION', WEEK_IN_SECONDS );

/**
 * Get all living saints (excluding wives and other) - cached
 *
 * @param array $exclude_ids Array of post IDs to exclude (e.g., First Presidency, Q12).
 * @return array Array of saint post IDs.
 */
function wasmo_get_cached_all_living( $exclude_ids = array() ) {
	$transient_key = 'wasmo_archive_all_living';
	$all_living = get_transient( $transient_key );
	
	if ( false === $all_living ) {
		$all_living = get_posts( array(
			'post_type'      => 'saint',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
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
			),
			'tax_query'      => array(
				array(
					'taxonomy' => 'saint-role',
					'field'    => 'slug',
					'terms'    => array( 'wife', 'other' ),
					'operator' => 'NOT IN',
				),
			),
			'fields'         => 'ids',
		) );
		
		set_transient( $transient_key, $all_living, WASMO_ARCHIVE_TRANSIENT_EXPIRATION );
	}
	
	// Filter out excluded IDs (done after cache to allow different exclusions)
	if ( ! empty( $exclude_ids ) ) {
		$all_living = array_diff( $all_living, $exclude_ids );
	}
	
	return $all_living;
}

/**
 * Get past presidents (deceased) - cached
 *
 * @return array Array of saint post IDs ordered by became_president_date.
 */
function wasmo_get_cached_past_presidents() {
	$transient_key = 'wasmo_archive_past_presidents';
	$past_presidents = get_transient( $transient_key );
	
	if ( false === $past_presidents ) {
		$past_presidents = get_posts( array(
			'post_type'      => 'saint',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'saint-role',
					'field'    => 'slug',
					'terms'    => 'president',
				),
			),
			'meta_query'     => array(
				array(
					'key'     => 'deathdate',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'deathdate',
					'value'   => '',
					'compare' => '!=',
				),
			),
			'meta_key'       => 'became_president_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'fields'         => 'ids',
		) );
		
		set_transient( $transient_key, $past_presidents, WASMO_ARCHIVE_TRANSIENT_EXPIRATION );
	}
	
	return $past_presidents;
}

/**
 * Get past apostles (deceased) - cached
 *
 * @return array Array of saint post IDs ordered by ordained_date DESC.
 */
function wasmo_get_cached_past_apostles() {
	$transient_key = 'wasmo_archive_past_apostles';
	$past_apostles = get_transient( $transient_key );
	
	if ( false === $past_apostles ) {
		$past_apostles = get_posts( array(
			'post_type'      => 'saint',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'saint-role',
					'field'    => 'slug',
					'terms'    => 'apostle',
				),
			),
			'meta_query'     => array(
				array(
					'key'     => 'deathdate',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'deathdate',
					'value'   => '',
					'compare' => '!=',
				),
			),
			'meta_key'       => 'ordained_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'fields'         => 'ids',
		) );
		
		set_transient( $transient_key, $past_apostles, WASMO_ARCHIVE_TRANSIENT_EXPIRATION );
	}
	
	return $past_apostles;
}

/**
 * Get past other leaders (deceased, not apostles/presidents/wives) - cached
 *
 * @return array Array of saint post IDs ordered by title.
 */
function wasmo_get_cached_past_other() {
	$transient_key = 'wasmo_archive_past_other';
	$past_other = get_transient( $transient_key );
	
	if ( false === $past_other ) {
		$past_other = get_posts( array(
			'post_type'      => 'saint',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'saint-role',
					'field'    => 'slug',
					'terms'    => array( 'apostle', 'president', 'wife' ),
					'operator' => 'NOT IN',
				),
			),
			'meta_query'     => array(
				array(
					'key'     => 'deathdate',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'deathdate',
					'value'   => '',
					'compare' => '!=',
				),
			),
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		) );
		
		set_transient( $transient_key, $past_other, WASMO_ARCHIVE_TRANSIENT_EXPIRATION );
	}
	
	return $past_other;
}

/**
 * Get current "other" leaders (with 'other' role) - cached
 *
 * @return array Array of saint post IDs ordered by title.
 */
function wasmo_get_cached_current_other() {
	$transient_key = 'wasmo_archive_current_other';
	$current_other = get_transient( $transient_key );
	
	if ( false === $current_other ) {
		$current_other = get_posts( array(
			'post_type'      => 'saint',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'saint-role',
					'field'    => 'slug',
					'terms'    => array( 'other' ),
					'operator' => 'IN',
				),
			),
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		) );
		
		set_transient( $transient_key, $current_other, WASMO_ARCHIVE_TRANSIENT_EXPIRATION );
	}
	
	return $current_other;
}

/**
 * Get all wives - cached
 *
 * @return array Array of saint post IDs ordered by title.
 */
function wasmo_get_cached_wives() {
	$transient_key = 'wasmo_archive_wives';
	$wives = get_transient( $transient_key );
	
	if ( false === $wives ) {
		$wives = get_posts( array(
			'post_type'      => 'saint',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'saint-role',
					'field'    => 'slug',
					'terms'    => 'wife',
				),
			),
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		) );
		
		set_transient( $transient_key, $wives, WASMO_ARCHIVE_TRANSIENT_EXPIRATION );
	}
	
	return $wives;
}

/**
 * Get all saint roles with counts - cached
 *
 * @return array Array of WP_Term objects.
 */
function wasmo_get_cached_saint_roles() {
	$transient_key = 'wasmo_saint_roles';
	$roles = get_transient( $transient_key );
	
	if ( false === $roles ) {
		$roles = get_terms( array(
			'taxonomy'   => 'saint-role',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );
		
		if ( is_wp_error( $roles ) ) {
			$roles = array();
		}
		
		set_transient( $transient_key, $roles, WASMO_ARCHIVE_TRANSIENT_EXPIRATION );
	}
	
	return $roles;
}

/**
 * Get saints by role (for taxonomy archive) - cached
 *
 * @param int $term_id The term ID for the saint-role.
 * @return array Array of WP_Post objects.
 */
function wasmo_get_cached_saints_by_role( $term_id ) {
	$transient_key = 'wasmo_role_saints_' . $term_id;
	$saints = get_transient( $transient_key );
	
	if ( false === $saints ) {
		$saints = get_posts( array(
			'post_type'      => 'saint',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'saint-role',
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		) );
		
		// Sort by ordained_date first, then birthdate as fallback
		usort( $saints, function( $a, $b ) {
			$a_ordained = get_field( 'ordained_date', $a->ID );
			$b_ordained = get_field( 'ordained_date', $b->ID );
			$a_birth = get_field( 'birthdate', $a->ID );
			$b_birth = get_field( 'birthdate', $b->ID );
			
			// If both have ordained dates, sort by that
			if ( $a_ordained && $b_ordained ) {
				return strtotime( $a_ordained ) - strtotime( $b_ordained );
			}
			
			// If only one has ordained date, that one comes first
			if ( $a_ordained && ! $b_ordained ) {
				return -1;
			}
			if ( ! $a_ordained && $b_ordained ) {
				return 1;
			}
			
			// Neither has ordained date, fall back to birthdate
			if ( $a_birth && $b_birth ) {
				return strtotime( $a_birth ) - strtotime( $b_birth );
			}
			
			// Handle cases where birthdate might be missing
			if ( $a_birth && ! $b_birth ) {
				return -1;
			}
			if ( ! $a_birth && $b_birth ) {
				return 1;
			}
			
			return 0;
		} );
		
		set_transient( $transient_key, $saints, WASMO_ARCHIVE_TRANSIENT_EXPIRATION );
	}
	
	return $saints;
}

// ============================================
// POLYGAMY / MARRIAGE COMPUTED STATS
// ============================================

/**
 * Get all marriages for a saint (direct from their own record)
 *
 * @param int $saint_id The saint post ID.
 * @return array Array of marriage data from ACF repeater.
 */
function wasmo_get_saint_marriages( $saint_id ) {
	$marriages = get_field( 'marriages', $saint_id );
	return is_array( $marriages ) ? $marriages : array();
}

/**
 * Get all marriage data for a saint, accounting for gender
 * 
 * In the new architecture:
 * - Women store marriages directly (their own repeater)
 * - Men get marriages from reverse lookup (their wives' repeaters)
 *
 * @param int  $saint_id The saint post ID.
 * @param bool $force_direct If true, always return direct marriages (for backward compat).
 * @return array Array of marriage data with spouse info.
 */
function wasmo_get_all_marriage_data( $saint_id, $force_direct = false ) {
	$gender = get_field( 'gender', $saint_id ) ?: 'male';
	
	// Women store their own marriages directly
	if ( $gender === 'female' || $force_direct ) {
		return wasmo_get_saint_marriages( $saint_id );
	}
	
	// Men: Look up from wives' records
	return wasmo_get_spouses_with_marriage_data( $saint_id );
}

/**
 * Get spouses along with their marriage data (for reverse lookup)
 * Used primarily for men to find their wives' marriage entries
 *
 * @param int $saint_id The saint post ID to find as a spouse.
 * @return array Array of marriage data from spouses' records.
 */
function wasmo_get_spouses_with_marriage_data( $saint_id ) {
	$spouse_ids = wasmo_get_reverse_marriages( $saint_id );
	$marriage_data = array();
	
	foreach ( $spouse_ids as $spouse_id ) {
		$spouse_marriages = get_field( 'marriages', $spouse_id );
		
		if ( ! is_array( $spouse_marriages ) ) {
			continue;
		}
		
		// Find marriage entries where this saint is the spouse
		foreach ( $spouse_marriages as $marriage ) {
			$married_to = $marriage['spouse'] ?? null;
			$married_to_id = is_array( $married_to ) ? ( $married_to[0] ?? null ) : $married_to;
			
			if ( intval( $married_to_id ) === intval( $saint_id ) ) {
				// Return marriage data with spouse pointing to the wife (who owns this record)
				$marriage_data[] = array(
					'spouse'                    => array( $spouse_id ),
					'spouse_id'                 => $spouse_id,
					'spouse_name'               => get_the_title( $spouse_id ),
					'marriage_date'             => $marriage['marriage_date'] ?? '',
					'marriage_date_approximate' => $marriage['marriage_date_approximate'] ?? 0,
					'divorce_date'              => $marriage['divorce_date'] ?? '',
					'marriage_notes'            => $marriage['marriage_notes'] ?? '',
					'children'                  => $marriage['children'] ?? array(),
					'_source'                   => 'reverse', // Marker for debugging
				);
			}
		}
	}
	
	// Sort by marriage date
	usort( $marriage_data, function( $a, $b ) {
		$date_a = $a['marriage_date'] ?? '';
		$date_b = $b['marriage_date'] ?? '';
		return strcmp( $date_a, $date_b );
	});
	
	return $marriage_data;
}

/**
 * Get the number of marriages/spouses for a saint
 *
 * @param int $saint_id The saint post ID.
 * @return int Number of marriages.
 */
function wasmo_get_number_of_marriages( $saint_id ) {
	$gender = get_field( 'gender', $saint_id ) ?: 'male';
	
	if ( $gender === 'female' ) {
		// Women: count from their own marriages field
		$marriages = wasmo_get_saint_marriages( $saint_id );
		return count( $marriages );
	}
	
	// Men: count from reverse lookup
	return count( wasmo_get_reverse_marriages( $saint_id ) );
}

/**
 * Get total children count across all marriages
 *
 * @param int $saint_id The saint post ID.
 * @return int Total number of children.
 */
function wasmo_get_children_count( $saint_id ) {
	$marriages = wasmo_get_all_marriage_data( $saint_id );
	$total = 0;
	
	foreach ( $marriages as $marriage ) {
		if ( ! empty( $marriage['children'] ) && is_array( $marriage['children'] ) ) {
			$total += count( $marriage['children'] );
		}
	}
	
	return $total;
}

/**
 * Check if a child name is a placeholder (e.g., [Child 1], [Child 2])
 *
 * @param string $child_name The child name to check.
 * @return bool True if the name is a placeholder.
 */
function wasmo_is_placeholder_child( $child_name ) {
	if ( empty( $child_name ) ) {
		return true;
	}
	// Match patterns like [Child 1], [Child 2], [Unknown Child], etc.
	return preg_match( '/^\[.+\]$/', trim( $child_name ) ) === 1;
}

/**
 * Get displayable children (excluding placeholders) from a marriage
 *
 * @param array $marriage The marriage array with children data.
 * @return array Array of children that should be displayed.
 */
function wasmo_get_displayable_children( $marriage ) {
	if ( empty( $marriage['children'] ) || ! is_array( $marriage['children'] ) ) {
		return array();
	}
	
	return array_filter( $marriage['children'], function( $child ) {
		$name = $child['child_name'] ?? '';
		return ! wasmo_is_placeholder_child( $name );
	});
}

/**
 * Find a saint by child name for auto-linking
 * Uses caching to avoid repeated database queries during page rendering.
 *
 * @param string $child_name The child's name to search for.
 * @return int|null Saint post ID if found, null otherwise.
 */
function wasmo_find_saint_by_child_name( $child_name ) {
	static $cache = array();
	
	if ( empty( $child_name ) || wasmo_is_placeholder_child( $child_name ) ) {
		return null;
	}
	
	// Clean up name - remove "(adopted)" or similar suffixes
	$search_name = preg_replace( '/\s*\([^)]+\)\s*$/', '', $child_name );
	$search_name = trim( $search_name );
	
	if ( empty( $search_name ) ) {
		return null;
	}
	
	// Check cache first
	$cache_key = strtolower( $search_name );
	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}
	
	// Try exact title match first
	$saints = get_posts( array(
		'post_type'      => 'saint',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'title'          => $search_name,
		'fields'         => 'ids',
	) );
	
	if ( ! empty( $saints ) ) {
		$cache[ $cache_key ] = $saints[0];
		return $saints[0];
	}
	
	// Try case-insensitive search with LIKE
	global $wpdb;
	$saint_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} 
		WHERE post_type = 'saint' 
		AND post_status = 'publish'
		AND LOWER(post_title) = LOWER(%s)
		LIMIT 1",
		$search_name
	) );
	
	$cache[ $cache_key ] = $saint_id ? intval( $saint_id ) : null;
	return $cache[ $cache_key ];
}

/**
 * Get children counts (total and displayable) from a marriage
 *
 * @param array $marriage The marriage array with children data.
 * @return array Array with 'total' and 'displayable' counts.
 */
function wasmo_get_children_counts( $marriage ) {
	$children = $marriage['children'] ?? array();
	$total = is_array( $children ) ? count( $children ) : 0;
	$displayable = count( wasmo_get_displayable_children( $marriage ) );
	
	return array(
		'total'       => $total,
		'displayable' => $displayable,
		'placeholder' => $total - $displayable,
	);
}

/**
 * Get marriage order for a specific spouse
 *
 * @param int $saint_id The saint post ID.
 * @param int $spouse_id The spouse's saint post ID.
 * @return int|null Marriage order (1-indexed), or null if not found.
 */
function wasmo_get_marriage_order( $saint_id, $spouse_id ) {
	$marriages = wasmo_get_saint_marriages( $saint_id );
	
	// Sort by marriage_date
	usort( $marriages, function( $a, $b ) {
		$date_a = $a['marriage_date'] ?? '';
		$date_b = $b['marriage_date'] ?? '';
		return strcmp( $date_a, $date_b );
	});
	
	$order = 1;
	foreach ( $marriages as $marriage ) {
		$spouse = $marriage['spouse'] ?? null;
		// Handle both array and single ID formats
		$marriage_spouse_id = is_array( $spouse ) ? ( $spouse[0] ?? null ) : $spouse;
		
		if ( intval( $marriage_spouse_id ) === intval( $spouse_id ) ) {
			return $order;
		}
		$order++;
	}
	
	return null;
}

/**
 * Check if a saint was a teenage bride at a specific marriage
 *
 * @param int    $saint_id     The saint (bride) post ID.
 * @param string $marriage_date The marriage date (Y-m-d).
 * @return bool True if was under 18 at marriage.
 */
function wasmo_was_teenage_bride( $saint_id, $marriage_date ) {
	$birthdate = get_field( 'birthdate', $saint_id );
	
	if ( ! $birthdate || ! $marriage_date ) {
		return false;
	}
	
	$birth = new DateTime( $birthdate );
	$wedding = new DateTime( $marriage_date );
	$age = $birth->diff( $wedding )->y;
	
	return $age < 18;
}

/**
 * Get age at a specific date
 *
 * @param int    $saint_id The saint post ID.
 * @param string $date     The date to calculate age at (Y-m-d).
 * @return int|null Age in years, or null if birthdate not set.
 */
function wasmo_get_age_at_date( $saint_id, $date ) {
	$birthdate = get_field( 'birthdate', $saint_id );
	
	if ( ! $birthdate || ! $date ) {
		return null;
	}
	
	$birth = new DateTime( $birthdate );
	$target = new DateTime( $date );
	
	return $birth->diff( $target )->y;
}

/**
 * Get age difference between two saints at a specific date
 *
 * @param int    $saint1_id First saint post ID.
 * @param int    $saint2_id Second saint post ID.
 * @param string $date      Date to calculate at (Y-m-d).
 * @return int|null Age difference (saint1 - saint2), or null if dates not available.
 */
function wasmo_get_age_difference( $saint1_id, $saint2_id, $date = null ) {
	$birth1 = get_field( 'birthdate', $saint1_id );
	$birth2 = get_field( 'birthdate', $saint2_id );
	
	if ( ! $birth1 || ! $birth2 ) {
		return null;
	}
	
	$datetime1 = new DateTime( $birth1 );
	$datetime2 = new DateTime( $birth2 );
	
	// Calculate difference in years
	$diff = $datetime2->diff( $datetime1 );
	$years = $diff->y;
	
	// Make negative if saint1 is younger
	if ( $diff->invert ) {
		$years = -$years;
	}
	
	return $years;
}

/**
 * Get comprehensive polygamy statistics for a saint
 *
 * @param int $saint_id The saint post ID.
 * @return array Array of computed statistics.
 */
function wasmo_get_polygamy_stats( $saint_id ) {
	// Use gender-aware marriage data retrieval
	$marriages = wasmo_get_all_marriage_data( $saint_id );
	$birthdate = get_field( 'birthdate', $saint_id );
	$gender = get_field( 'gender', $saint_id ) ?: 'male';
	
	$stats = array(
		'number_of_marriages' => count( $marriages ),
		'number_of_children'  => 0,
		'teenage_brides_count' => 0,
		'largest_age_diff'    => 0,
		'total_age_diff'      => 0,
		'avg_age_diff'        => 0,
		'age_first_marriage'  => null,
		'was_polygamist'      => count( $marriages ) > 1,
		'marriages_data'      => array(),
	);
	
	$age_diffs = array();
	$first_marriage_date = null;
	
	foreach ( $marriages as $marriage ) {
		$spouse_field = $marriage['spouse'] ?? null;
		$spouse_id = is_array( $spouse_field ) ? ( $spouse_field[0] ?? null ) : $spouse_field;
		$marriage_date = $marriage['marriage_date'] ?? null;
		$children = $marriage['children'] ?? array();
		
		// Count children
		if ( is_array( $children ) ) {
			$stats['number_of_children'] += count( $children );
		}
		
		// Track first marriage date
		if ( $marriage_date && ( ! $first_marriage_date || $marriage_date < $first_marriage_date ) ) {
			$first_marriage_date = $marriage_date;
		}
		
		// Calculate age difference and teenage bride status
		if ( $spouse_id && $marriage_date ) {
			$spouse_birthdate = get_field( 'birthdate', $spouse_id );
			$spouse_gender = get_field( 'gender', $spouse_id ) ?: 'female';
			
			// Determine who is the bride (female spouse)
			if ( $gender === 'male' && $spouse_gender === 'female' ) {
				// This saint is the husband, spouse is wife
				$age_diff = wasmo_get_age_difference( $saint_id, $spouse_id );
				if ( $age_diff !== null ) {
					$age_diffs[] = abs( $age_diff );
					if ( abs( $age_diff ) > $stats['largest_age_diff'] ) {
						$stats['largest_age_diff'] = abs( $age_diff );
					}
				}
				
				// Check if spouse was teenage bride
				if ( wasmo_was_teenage_bride( $spouse_id, $marriage_date ) ) {
					$stats['teenage_brides_count']++;
				}
			}
			
			// Build marriage data
			$stats['marriages_data'][] = array(
				'spouse_id'       => $spouse_id,
				'spouse_name'     => get_the_title( $spouse_id ),
				'marriage_date'   => $marriage_date,
				'divorce_date'    => $marriage['divorce_date'] ?? null,
				'children_count'  => is_array( $children ) ? count( $children ) : 0,
				'age_diff'        => wasmo_get_age_difference( $saint_id, $spouse_id ),
				'saint_age'       => wasmo_get_age_at_date( $saint_id, $marriage_date ),
				'spouse_age'      => wasmo_get_age_at_date( $spouse_id, $marriage_date ),
			);
		}
	}
	
	// Calculate average age difference
	if ( ! empty( $age_diffs ) ) {
		$stats['avg_age_diff'] = round( array_sum( $age_diffs ) / count( $age_diffs ), 1 );
	}
	
	// Calculate age at first marriage
	if ( $first_marriage_date && $birthdate ) {
		$stats['age_first_marriage'] = wasmo_get_age_at_date( $saint_id, $first_marriage_date );
	}
	
	return $stats;
}

/**
 * Get reverse marriages (where this saint is the spouse in another saint's marriage repeater)
 *
 * @param int $saint_id The saint post ID.
 * @return array Array of saint IDs who have this saint as a spouse.
 */
function wasmo_get_reverse_marriages( $saint_id ) {
	global $wpdb;
	
	$saint_id = intval( $saint_id );
	
	// Search for saints who have this saint_id in their marriages repeater (serialized format)
	$results = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
		WHERE meta_key LIKE 'marriages_%%_spouse' 
		AND meta_value LIKE %s",
		'%"' . $saint_id . '"%'
	) );
	
	// Also check for non-serialized values (plain ID)
	$results2 = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
		WHERE meta_key LIKE 'marriages_%%_spouse' 
		AND meta_value = %s",
		strval( $saint_id )
	) );
	
	// Also check for array-style storage a:1:{i:0;s:X:"ID";}
	$results3 = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
		WHERE meta_key LIKE 'marriages_%%_spouse' 
		AND meta_value LIKE %s",
		'%i:0;i:' . $saint_id . ';%'
	) );
	
	// Merge all results, cast to integers, and remove duplicates
	$all_results = array_merge( $results, $results2, $results3 );
	$all_results = array_map( 'intval', $all_results );
	$all_results = array_unique( $all_results );
	
	// Filter to only include valid, published saints (exclude deleted/trashed posts)
	$all_results = array_filter( $all_results, function( $id ) use ( $saint_id ) {
		// Exclude the saint themselves
		if ( $id === $saint_id ) {
			return false;
		}
		// Check if the post exists and is a valid saint
		$post = get_post( $id );
		if ( ! $post || $post->post_status === 'trash' || $post->post_type !== 'saint' ) {
			return false;
		}
		return true;
	} );
	
	return array_values( $all_results );
}

/**
 * Check if a saint was a polygamist (had multiple spouses)
 *
 * @param int $saint_id The saint post ID.
 * @return bool True if polygamist.
 */
function wasmo_was_polygamist( $saint_id ) {
	return wasmo_get_number_of_marriages( $saint_id ) > 1;
}

// ============================================
// BACKWARDS COMPATIBILITY ALIASES
// ============================================

// Provide aliases for old function names during transition
function wasmo_get_leader_age( $id ) { return wasmo_get_saint_age( $id ); }
function wasmo_get_leader_years_served( $id ) { return wasmo_get_saint_years_served( $id ); }
function wasmo_get_leader_age_at_call( $id ) { return wasmo_get_saint_age_at_call( $id ); }
function wasmo_get_leader_years_as_president( $id ) { return wasmo_get_saint_years_as_president( $id ); }
function wasmo_get_leader_seniority( $id, $include = false ) { return wasmo_get_saint_seniority( $id, $include ); }
function wasmo_get_served_with_prophets( $id ) { return wasmo_get_served_with_presidents( $id ); }
function wasmo_get_leader_by_tag( $id ) { return wasmo_get_saint_by_tag( $id ); }
function wasmo_is_leader_living( $id ) { return wasmo_is_saint_living( $id ); }
function wasmo_get_leaders_by_role( $role, $living = true ) { return wasmo_get_saints_by_role( $role, $living ); }
function wasmo_get_current_prophet() { return wasmo_get_current_president(); }
function wasmo_leader_has_role( $id, $role ) { return wasmo_saint_has_role( $id, $role ); }
function wasmo_get_leader_related_posts( $id, $limit = 10 ) { return wasmo_get_saint_related_posts( $id, $limit ); }
function wasmo_get_leader_related_media( $id, $limit = 20 ) { return wasmo_get_saint_related_media( $id, $limit ); }
function wasmo_get_leaders_chart_data() { return wasmo_get_saints_chart_data(); }
function wasmo_format_leader_date( $date, $format = 'F j, Y', $show_time = false ) { return wasmo_format_saint_date( $date, $format, $show_time ); }
function wasmo_get_leader_display_name( $id ) { return wasmo_get_saint_display_name( $id ); }
function wasmo_get_leader_lifespan( $id ) { return wasmo_get_saint_lifespan( $id ); }
function wasmo_get_leader_service_date( $id ) { return wasmo_get_saint_service_date( $id ); }
function wasmo_render_leader_card( $id, $size = 'medium', $dates = true, $service = true, $role = false, $override = '' ) { 
	return wasmo_render_saint_card( $id, $size, $dates, $service, $role, $override ); 
}
function wasmo_clear_leader_transients( $id ) { return wasmo_clear_saint_transients( $id ); }
