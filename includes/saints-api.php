<?php
/**
 * FamilySearch REST API Endpoints
 * 
 * Provides REST API endpoints for syncing saint data from local fs-verify tool
 * to production WordPress. Uses Application Passwords for authentication.
 * 
 * @package Wasmo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST API routes
 */
function wasmo_register_fs_api_routes() {
	$namespace = 'wasmo/v1';
	
	// GET /saints - List saints with optional filters
	register_rest_route( $namespace, '/saints', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'wasmo_api_get_saints',
		'permission_callback' => 'wasmo_api_permission_check',
		'args'                => array(
			'per_page' => array(
				'default'           => 100,
				'sanitize_callback' => 'absint',
			),
			'page' => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'needs_verification' => array(
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'has_fs_id' => array(
				'default'           => null,
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'familysearch_id' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'likely_deceased' => array(
				'default'           => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
			'role' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	) );
	
	// GET /saints/{id} - Get single saint
	register_rest_route( $namespace, '/saints/(?P<id>\d+)', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'wasmo_api_get_saint',
		'permission_callback' => 'wasmo_api_permission_check',
		'args'                => array(
			'id' => array(
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		),
	) );
	
	// POST /saints/{id} - Update saint fields
	register_rest_route( $namespace, '/saints/(?P<id>\d+)', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'wasmo_api_update_saint',
		'permission_callback' => 'wasmo_api_permission_check',
		'args'                => array(
			'id' => array(
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		),
	) );
	
	// POST /saints/{id}/portrait - Upload portrait image
	register_rest_route( $namespace, '/saints/(?P<id>\d+)/portrait', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'wasmo_api_upload_portrait',
		'permission_callback' => 'wasmo_api_permission_check',
		'args'                => array(
			'id' => array(
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		),
	) );
	
	// POST /saints/{id}/verify - Mark saint as verified
	register_rest_route( $namespace, '/saints/(?P<id>\d+)/verify', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'wasmo_api_verify_saint',
		'permission_callback' => 'wasmo_api_permission_check',
		'args'                => array(
			'id' => array(
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		),
	) );
	
	// GET /saints/by-fs-id/{fs_id} - Get saint by FamilySearch ID
	register_rest_route( $namespace, '/saints/by-fs-id/(?P<fs_id>[A-Z0-9-]+)', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'wasmo_api_get_saint_by_fs_id',
		'permission_callback' => 'wasmo_api_permission_check',
		'args'                => array(
			'fs_id' => array(
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	) );
	
	// POST /saints/{id}/merge - Merge a duplicate saint into this one
	register_rest_route( $namespace, '/saints/(?P<id>\d+)/merge', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'wasmo_api_merge_saint',
		'permission_callback' => 'wasmo_api_permission_check',
		'args'                => array(
			'id' => array(
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		),
	) );
	
	// GET /saints/duplicates - List saints with duplicate FamilySearch IDs
	register_rest_route( $namespace, '/saints/duplicates', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'wasmo_api_get_duplicate_saints',
		'permission_callback' => 'wasmo_api_permission_check',
	) );
	
	// POST /saints/create - Create a new saint
	register_rest_route( $namespace, '/saints/create', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'wasmo_api_create_saint',
		'permission_callback' => 'wasmo_api_permission_check',
	) );
}
add_action( 'rest_api_init', 'wasmo_register_fs_api_routes' );

/**
 * Permission check for API endpoints
 * Requires authenticated user with manage_options capability
 */
function wasmo_api_permission_check( $request ) {
	// Check if user is authenticated (Application Passwords work with this)
	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'rest_not_logged_in',
			'You must be authenticated to access this endpoint.',
			array( 'status' => 401 )
		);
	}
	
	// Check capability
	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error(
			'rest_forbidden',
			'You do not have permission to access this endpoint.',
			array( 'status' => 403 )
		);
	}
	
	return true;
}

/**
 * Find a saint by FamilySearch ID
 * 
 * @param string $fs_id The FamilySearch ID to search for
 * @return int|null The saint post ID if found, null otherwise
 */
function wasmo_find_saint_by_fs_id( $fs_id ) {
	if ( empty( $fs_id ) ) {
		return null;
	}
	
	$args = array(
		'post_type'      => 'saint',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'     => 'familysearch_id',
				'value'   => sanitize_text_field( $fs_id ),
				'compare' => '=',
			),
		),
		'fields' => 'ids',
	);
	
	$saints = get_posts( $args );
	return ! empty( $saints ) ? $saints[0] : null;
}

/**
 * Find a saint by name and birth year (fallback when no FS ID match)
 * 
 * @param string $name The person's name
 * @param string|int $birth_year The birth year (can be full date or just year)
 * @return int|null The saint post ID if found, null otherwise
 */
function wasmo_find_saint_by_name_birthdate( $name, $birth_year ) {
	if ( empty( $name ) || empty( $birth_year ) ) {
		return null;
	}
	
	// Extract year if full date given
	if ( preg_match( '/(\d{4})/', $birth_year, $matches ) ) {
		$birth_year = $matches[1];
	}
	
	// Search by title (name)
	$args = array(
		'post_type'      => 'saint',
		'posts_per_page' => 10, // Get a few candidates
		'post_status'    => 'publish',
		's'              => sanitize_text_field( $name ),
		'fields'         => 'ids',
	);
	
	$candidates = get_posts( $args );
	
	foreach ( $candidates as $saint_id ) {
		$saint_title = get_the_title( $saint_id );
		$saint_birthdate = get_field( 'birthdate', $saint_id );
		
		// Check if name matches closely
		$name_match = ( 
			strcasecmp( $saint_title, $name ) === 0 || 
			stripos( $saint_title, $name ) !== false ||
			stripos( $name, $saint_title ) !== false
		);
		
		// Check if birth year matches
		$year_match = false;
		if ( $saint_birthdate && preg_match( '/(\d{4})/', $saint_birthdate, $saint_year ) ) {
			$year_match = ( $saint_year[1] === $birth_year );
		}
		
		if ( $name_match && $year_match ) {
			return $saint_id;
		}
	}
	
	return null;
}

/**
 * Look up a saint by FS ID, or fallback to name+birthdate match
 * If found by name+birthdate and the saint lacks an FS ID, add it
 * 
 * @param string $fs_id FamilySearch ID
 * @param string $name Person's name
 * @param string|int $birth_year Birth year
 * @return int|null Saint post ID if found
 */
function wasmo_lookup_saint_for_relationship( $fs_id, $name = '', $birth_year = '' ) {
	// First try FS ID lookup
	if ( ! empty( $fs_id ) ) {
		$saint_id = wasmo_find_saint_by_fs_id( $fs_id );
		if ( $saint_id ) {
			return $saint_id;
		}
	}
	
	// Fallback to name + birthdate
	if ( ! empty( $name ) && ! empty( $birth_year ) ) {
		$saint_id = wasmo_find_saint_by_name_birthdate( $name, $birth_year );
		if ( $saint_id ) {
			// If saint found but doesn't have FS ID, add it
			if ( ! empty( $fs_id ) ) {
				$existing_fs_id = get_field( 'familysearch_id', $saint_id );
				if ( empty( $existing_fs_id ) ) {
					update_field( 'familysearch_id', sanitize_text_field( $fs_id ), $saint_id );
				}
			}
			return $saint_id;
		}
	}
	
	return null;
}

/**
 * GET /saints - List saints with optional filters
 */
function wasmo_api_get_saints( $request ) {
	$per_page = $request->get_param( 'per_page' );
	$page = $request->get_param( 'page' );
	$needs_verification = $request->get_param( 'needs_verification' );
	$has_fs_id = $request->get_param( 'has_fs_id' );
	$familysearch_id = $request->get_param( 'familysearch_id' );
	$role = $request->get_param( 'role' );
	
	$args = array(
		'post_type'      => 'saint',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	);
	
	$meta_query = array();
	
	// Filter by role (saint-role taxonomy)
	if ( ! empty( $role ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'saint-role',
				'field'    => 'slug',
				'terms'    => $role,
			),
		);
	}
	
	// Filter by FamilySearch ID
	if ( ! empty( $familysearch_id ) ) {
		$meta_query[] = array(
			'key'   => 'familysearch_id',
			'value' => $familysearch_id,
		);
	}
	
	// Filter: has FamilySearch ID
	if ( $has_fs_id === true ) {
		$meta_query[] = array(
			'key'     => 'familysearch_id',
			'value'   => '',
			'compare' => '!=',
		);
	} elseif ( $has_fs_id === false ) {
		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'key'     => 'familysearch_id',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'   => 'familysearch_id',
				'value' => '',
			),
		);
	}
	
	// Filter: needs verification (never verified or verified > 30 days ago)
	if ( $needs_verification ) {
		$thirty_days_ago = date( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'key'     => 'familysearch_verified',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'   => 'familysearch_verified',
				'value' => '',
			),
			array(
				'key'     => 'familysearch_verified',
				'value'   => $thirty_days_ago,
				'compare' => '<',
				'type'    => 'DATETIME',
			),
		);
	}
	
	// Filter: likely deceased (has death date OR birthdate > 90 years ago)
	$likely_deceased = $request->get_param( 'likely_deceased' );
	if ( $likely_deceased ) {
		$cutoff_date = date( 'Y-m-d', strtotime( '-90 years' ) );
		$meta_query[] = array(
			'relation' => 'OR',
			// Has death date
			array(
				'key'     => 'deathdate',
				'value'   => '',
				'compare' => '!=',
			),
			// Or birthdate older than 90 years
			array(
				'key'     => 'birthdate',
				'value'   => $cutoff_date,
				'compare' => '<',
				'type'    => 'DATE',
			),
		);
	}
	
	if ( ! empty( $meta_query ) ) {
		$meta_query['relation'] = 'AND';
		$args['meta_query'] = $meta_query;
	}
	
	$query = new WP_Query( $args );
	$saints = array();
	
	foreach ( $query->posts as $post ) {
		$saints[] = wasmo_format_saint_for_api( $post->ID );
	}
	
	return new WP_REST_Response( array(
		'saints'      => $saints,
		'total'       => $query->found_posts,
		'total_pages' => $query->max_num_pages,
		'page'        => $page,
		'per_page'    => $per_page,
	), 200 );
}

/**
 * GET /saints/{id} - Get single saint
 */
function wasmo_api_get_saint( $request ) {
	$saint_id = $request->get_param( 'id' );
	
	$post = get_post( $saint_id );
	if ( ! $post || $post->post_type !== 'saint' ) {
		return new WP_Error(
			'saint_not_found',
			'Saint not found.',
			array( 'status' => 404 )
		);
	}
	
	return new WP_REST_Response( wasmo_format_saint_for_api( $saint_id, true ), 200 );
}

/**
 * GET /saints/by-fs-id/{fs_id} - Get saint by FamilySearch ID
 */
function wasmo_api_get_saint_by_fs_id( $request ) {
	$fs_id = $request->get_param( 'fs_id' );
	
	$saints = get_posts( array(
		'post_type'      => 'saint',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'   => 'familysearch_id',
				'value' => $fs_id,
			),
		),
	) );
	
	if ( empty( $saints ) ) {
		return new WP_Error(
			'saint_not_found',
			'No saint found with FamilySearch ID: ' . $fs_id,
			array( 'status' => 404 )
		);
	}
	
	return new WP_REST_Response( wasmo_format_saint_for_api( $saints[0]->ID, true ), 200 );
}

/**
 * POST /saints/create - Create a new saint
 * 
 * Required fields: name
 * Optional fields: familysearch_id, birthdate, deathdate, gender
 */
function wasmo_api_create_saint( $request ) {
	$body = $request->get_json_params();
	
	if ( empty( $body['name'] ) ) {
		return new WP_Error(
			'missing_name',
			'Name is required to create a saint.',
			array( 'status' => 400 )
		);
	}
	
	// Check if saint with this FS ID already exists
	if ( ! empty( $body['familysearch_id'] ) ) {
		$existing = wasmo_find_saint_by_fs_id( $body['familysearch_id'] );
		if ( $existing ) {
			return new WP_REST_Response( array(
				'created'  => false,
				'existing' => true,
				'saint'    => wasmo_format_saint_for_api( $existing, true ),
			), 200 );
		}
	}
	
	// Also check by name + birthdate to avoid duplicates
	if ( ! empty( $body['birthdate'] ) ) {
		$existing = wasmo_find_saint_by_name_birthdate( $body['name'], $body['birthdate'] );
		if ( $existing ) {
			// If found by name+birthdate but missing FS ID, add it
			if ( ! empty( $body['familysearch_id'] ) ) {
				$existing_fs_id = get_field( 'familysearch_id', $existing );
				if ( empty( $existing_fs_id ) ) {
					update_field( 'familysearch_id', sanitize_text_field( $body['familysearch_id'] ), $existing );
				}
			}
			return new WP_REST_Response( array(
				'created'  => false,
				'existing' => true,
				'matched_by' => 'name_birthdate',
				'saint'    => wasmo_format_saint_for_api( $existing, true ),
			), 200 );
		}
	}
	
	// Create new saint post
	$post_data = array(
		'post_title'  => sanitize_text_field( $body['name'] ),
		'post_type'   => 'saint',
		'post_status' => 'publish',
	);
	
	$saint_id = wp_insert_post( $post_data );
	
	if ( is_wp_error( $saint_id ) ) {
		return new WP_Error(
			'create_failed',
			'Failed to create saint: ' . $saint_id->get_error_message(),
			array( 'status' => 500 )
		);
	}
	
	// Set ACF fields
	if ( ! empty( $body['familysearch_id'] ) ) {
		update_field( 'familysearch_id', sanitize_text_field( $body['familysearch_id'] ), $saint_id );
	}
	if ( ! empty( $body['birthdate'] ) ) {
		update_field( 'birthdate', sanitize_text_field( $body['birthdate'] ), $saint_id );
	}
	if ( ! empty( $body['deathdate'] ) ) {
		update_field( 'deathdate', sanitize_text_field( $body['deathdate'] ), $saint_id );
	}
	if ( ! empty( $body['gender'] ) ) {
		update_field( 'gender', sanitize_text_field( $body['gender'] ), $saint_id );
	}
	
	// Set roles if provided
	if ( ! empty( $body['roles'] ) && is_array( $body['roles'] ) ) {
		$term_ids = array();
		foreach ( $body['roles'] as $role_slug ) {
			$term = get_term_by( 'slug', sanitize_text_field( $role_slug ), 'saint-role' );
			if ( $term ) {
				$term_ids[] = $term->term_id;
			}
		}
		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $saint_id, $term_ids, 'saint-role' );
		}
	}
	
	return new WP_REST_Response( array(
		'created' => true,
		'saint'   => wasmo_format_saint_for_api( $saint_id, true ),
	), 201 );
}

/**
 * POST /saints/{id} - Update saint fields
 */
function wasmo_api_update_saint( $request ) {
	$saint_id = $request->get_param( 'id' );
	
	$post = get_post( $saint_id );
	if ( ! $post || $post->post_type !== 'saint' ) {
		return new WP_Error(
			'saint_not_found',
			'Saint not found.',
			array( 'status' => 404 )
		);
	}
	
	$body = $request->get_json_params();
	$updated_fields = array();
	
	// Allowed text fields to update
	$allowed_text_fields = array(
		'familysearch_id',
		'birthdate',
		'deathdate',
		'gender',
		'familysearch_notes',
	);
	
	foreach ( $allowed_text_fields as $field ) {
		if ( isset( $body[ $field ] ) ) {
			$value = sanitize_text_field( $body[ $field ] );
			update_field( $field, $value, $saint_id );
			$updated_fields[ $field ] = $value;
		}
	}
	
	// Handle boolean approx fields
	$approx_fields = array( 'birthdate_approx', 'deathdate_approx' );
	foreach ( $approx_fields as $field ) {
		if ( isset( $body[ $field ] ) ) {
			$value = $body[ $field ] ? 1 : 0;
			update_field( $field, $value, $saint_id );
			$updated_fields[ $field ] = $value;
		}
	}
	
	// Handle marriages update (supports full replacement or index-based updates)
	if ( isset( $body['marriages'] ) && is_array( $body['marriages'] ) ) {
		$marriages = get_field( 'marriages', $saint_id ) ?: array();
		$marriages_updated = false;
		
		// Check if this is a full replacement (entries have spouse_name or spouse fields)
		// vs index-based update (entries have 'index' field)
		$is_full_replacement = ! empty( $body['marriages'] ) && 
			( isset( $body['marriages'][0]['spouse_name'] ) || 
			  isset( $body['marriages'][0]['spouse'] ) ||
			  isset( $body['marriages'][0]['spouse_is_saint'] ) ||
			  isset( $body['marriages'][0]['spouse_familysearch_id'] ) );
		
		if ( $is_full_replacement ) {
			// Full replacement mode - replace entire marriages array
			$new_marriages = array();
			
			foreach ( $body['marriages'] as $marriage_data ) {
				$marriage = array(
					'spouse_is_saint'         => ! empty( $marriage_data['spouse_is_saint'] ) ? 1 : 0,
					'spouse'                  => null,
					'spouse_name'             => '',
					'spouse_birthdate'        => '',
					'spouse_familysearch_id'  => '',
					'marriage_date'           => '',
					'marriage_date_approximate' => 0,
					'divorce_date'            => '',
					'marriage_notes'          => '',
					'children'                => array(),
				);
				
				// Store spouse name and FS ID first (needed for lookup)
				if ( ! empty( $marriage_data['spouse_name'] ) ) {
					$marriage['spouse_name'] = sanitize_text_field( $marriage_data['spouse_name'] );
				}
				
				if ( ! empty( $marriage_data['spouse_familysearch_id'] ) ) {
					$marriage['spouse_familysearch_id'] = sanitize_text_field( $marriage_data['spouse_familysearch_id'] );
				}
				
				if ( ! empty( $marriage_data['spouse_birthdate'] ) ) {
					$marriage['spouse_birthdate'] = sanitize_text_field( $marriage_data['spouse_birthdate'] );
				}
				
				// Handle spouse - if explicitly provided, use it
				// Otherwise, try reverse lookup by FS ID or name+birthdate
				if ( ! empty( $marriage_data['spouse'] ) ) {
					// Spouse saint ID explicitly provided
					$marriage['spouse_is_saint'] = 1;
					$marriage['spouse'] = array( absint( $marriage_data['spouse'] ) );
				} else {
					// Try reverse lookup to find matching saint
					$spouse_birth_year = ! empty( $marriage_data['spouse_birth_year'] ) 
						? $marriage_data['spouse_birth_year'] 
						: ( ! empty( $marriage_data['spouse_birthdate'] ) ? $marriage_data['spouse_birthdate'] : '' );
					
					$spouse_saint_id = wasmo_lookup_saint_for_relationship(
						$marriage['spouse_familysearch_id'],
						$marriage['spouse_name'],
						$spouse_birth_year
					);
					
					if ( $spouse_saint_id ) {
						$marriage['spouse_is_saint'] = 1;
						$marriage['spouse'] = array( $spouse_saint_id );
					}
				}
				
				if ( ! empty( $marriage_data['marriage_date'] ) ) {
					$marriage['marriage_date'] = sanitize_text_field( $marriage_data['marriage_date'] );
				}
				
				// Handle marriage_date_approximate - use actual boolean value
				if ( isset( $marriage_data['marriage_date_approximate'] ) ) {
					$marriage['marriage_date_approximate'] = $marriage_data['marriage_date_approximate'] ? 1 : 0;
				}
				
				if ( ! empty( $marriage_data['divorce_date'] ) ) {
					$marriage['divorce_date'] = sanitize_text_field( $marriage_data['divorce_date'] );
				}
				
				if ( ! empty( $marriage_data['marriage_notes'] ) ) {
					$marriage['marriage_notes'] = sanitize_textarea_field( $marriage_data['marriage_notes'] );
				}
				
				// Process children
				if ( ! empty( $marriage_data['children'] ) && is_array( $marriage_data['children'] ) ) {
					foreach ( $marriage_data['children'] as $child_data ) {
						$child = array(
							'child_name'           => sanitize_text_field( $child_data['child_name'] ?? '' ),
							'child_birthdate'      => sanitize_text_field( $child_data['child_birthdate'] ?? '' ),
							'child_link'           => null,
							'child_familysearch_id' => sanitize_text_field( $child_data['child_familysearch_id'] ?? '' ),
						);
						
						// Handle child link - if explicitly provided, use it
						// Otherwise, try reverse lookup by FS ID or name+birthdate
						if ( ! empty( $child_data['child_link'] ) ) {
							$child['child_link'] = array( absint( $child_data['child_link'] ) );
						} else {
							// Try reverse lookup to find matching saint
							$child_birth_year = ! empty( $child_data['birth_year'] ) 
								? $child_data['birth_year'] 
								: ( ! empty( $child['child_birthdate'] ) ? $child['child_birthdate'] : '' );
							
							$child_saint_id = wasmo_lookup_saint_for_relationship(
								$child['child_familysearch_id'],
								$child['child_name'],
								$child_birth_year
							);
							
							if ( $child_saint_id ) {
								$child['child_link'] = array( $child_saint_id );
							}
						}
						
						$marriage['children'][] = $child;
					}
				}
				
				$new_marriages[] = $marriage;
			}
			
			$marriages = $new_marriages;
			$marriages_updated = true;
			
		} else {
			// Index-based update mode (backward compatibility)
			foreach ( $body['marriages'] as $marriage_update ) {
				if ( isset( $marriage_update['index'] ) && isset( $marriages[ $marriage_update['index'] ] ) ) {
					$idx = $marriage_update['index'];
					
					// Update marriage date
					if ( isset( $marriage_update['marriage_date'] ) ) {
						$marriages[ $idx ]['marriage_date'] = sanitize_text_field( $marriage_update['marriage_date'] );
						$marriages_updated = true;
					}
					
					// Update spouse FS ID
					if ( isset( $marriage_update['spouse_familysearch_id'] ) ) {
						$marriages[ $idx ]['spouse_familysearch_id'] = sanitize_text_field( $marriage_update['spouse_familysearch_id'] );
						$marriages_updated = true;
					}
					
					// Update children FamilySearch IDs
					if ( isset( $marriage_update['children'] ) && is_array( $marriage_update['children'] ) ) {
						foreach ( $marriage_update['children'] as $child_update ) {
							if ( isset( $child_update['index'] ) && isset( $marriages[ $idx ]['children'][ $child_update['index'] ] ) ) {
								$child_idx = $child_update['index'];
								
								if ( isset( $child_update['child_familysearch_id'] ) ) {
									$marriages[ $idx ]['children'][ $child_idx ]['child_familysearch_id'] = 
										sanitize_text_field( $child_update['child_familysearch_id'] );
									$marriages_updated = true;
								}
								
								if ( isset( $child_update['child_link'] ) ) {
									$marriages[ $idx ]['children'][ $child_idx ]['child_link'] = 
										array( absint( $child_update['child_link'] ) );
									$marriages_updated = true;
								}
							}
						}
					}
				}
			}
		}
		
		if ( $marriages_updated ) {
			update_field( 'marriages', $marriages, $saint_id );
			$updated_fields['marriages'] = count( $marriages ) . ' marriages synced';
		}
	}
	
	return new WP_REST_Response( array(
		'success'        => true,
		'saint_id'       => $saint_id,
		'updated_fields' => $updated_fields,
		'saint'          => wasmo_format_saint_for_api( $saint_id ),
	), 200 );
}

/**
 * POST /saints/{id}/portrait - Upload portrait image
 */
function wasmo_api_upload_portrait( $request ) {
	$saint_id = $request->get_param( 'id' );
	
	$post = get_post( $saint_id );
	if ( ! $post || $post->post_type !== 'saint' ) {
		return new WP_Error(
			'saint_not_found',
			'Saint not found.',
			array( 'status' => 404 )
		);
	}
	
	// Check for file upload
	$files = $request->get_file_params();
	
	if ( empty( $files['portrait'] ) ) {
		return new WP_Error(
			'no_file',
			'No portrait file provided. Use multipart/form-data with a "portrait" field.',
			array( 'status' => 400 )
		);
	}
	
	// Require media handling functions
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	
	// Generate filename
	$saint_slug = sanitize_title( get_the_title( $saint_id ) );
	$fs_id = get_field( 'familysearch_id', $saint_id );
	$filename = $saint_slug . '-familysearch-portrait';
	if ( $fs_id ) {
		$filename .= '-' . strtolower( $fs_id );
	}
	
	// Get original extension
	$ext = pathinfo( $files['portrait']['name'], PATHINFO_EXTENSION );
	if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ), true ) ) {
		$ext = 'jpg';
	}
	
	// Rename the uploaded file
	$files['portrait']['name'] = $filename . '.' . $ext;
	
	// Handle the upload
	$_FILES['portrait'] = $files['portrait'];
	$attachment_id = media_handle_upload( 'portrait', $saint_id );
	
	if ( is_wp_error( $attachment_id ) ) {
		return new WP_Error(
			'upload_failed',
			$attachment_id->get_error_message(),
			array( 'status' => 500 )
		);
	}
	
	// Set as featured image
	set_post_thumbnail( $saint_id, $attachment_id );
	
	// Add metadata
	update_post_meta( $attachment_id, '_wasmo_source', 'familysearch' );
	update_post_meta( $attachment_id, '_wasmo_synced', current_time( 'mysql' ) );
	
	return new WP_REST_Response( array(
		'success'       => true,
		'saint_id'      => $saint_id,
		'attachment_id' => $attachment_id,
		'url'           => wp_get_attachment_url( $attachment_id ),
		'thumbnail'     => wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
	), 200 );
}

/**
 * POST /saints/{id}/verify - Mark saint as verified
 */
function wasmo_api_verify_saint( $request ) {
	$saint_id = $request->get_param( 'id' );
	
	$post = get_post( $saint_id );
	if ( ! $post || $post->post_type !== 'saint' ) {
		return new WP_Error(
			'saint_not_found',
			'Saint not found.',
			array( 'status' => 404 )
		);
	}
	
	$body = $request->get_json_params();
	
	// Set verification timestamp
	$timestamp = isset( $body['timestamp'] ) ? sanitize_text_field( $body['timestamp'] ) : current_time( 'mysql' );
	update_field( 'familysearch_verified', $timestamp, $saint_id );
	
	// Optionally set notes
	if ( isset( $body['notes'] ) ) {
		update_field( 'familysearch_notes', sanitize_textarea_field( $body['notes'] ), $saint_id );
	}
	
	return new WP_REST_Response( array(
		'success'   => true,
		'saint_id'  => $saint_id,
		'verified'  => $timestamp,
		'saint'     => wasmo_format_saint_for_api( $saint_id ),
	), 200 );
}

/**
 * Format saint data for API response
 *
 * @param int  $saint_id   Saint post ID.
 * @param bool $include_marriages Whether to include full marriage data.
 * @return array Formatted saint data.
 */
function wasmo_format_saint_for_api( $saint_id, $include_marriages = false ) {
	$data = array(
		'id'                   => $saint_id,
		'name'                 => get_the_title( $saint_id ),
		'familysearch_id'      => get_field( 'familysearch_id', $saint_id ) ?: null,
		'birthdate'            => get_field( 'birthdate', $saint_id ) ?: null,
		'birthdate_approx'     => (bool) get_field( 'birthdate_approx', $saint_id ),
		'deathdate'            => get_field( 'deathdate', $saint_id ) ?: null,
		'deathdate_approx'     => (bool) get_field( 'deathdate_approx', $saint_id ),
		'gender'               => get_field( 'gender', $saint_id ) ?: null,
		'familysearch_verified'=> get_field( 'familysearch_verified', $saint_id ) ?: null,
		'familysearch_notes'   => get_field( 'familysearch_notes', $saint_id ) ?: null,
		'has_portrait'         => has_post_thumbnail( $saint_id ),
		'portrait_url'         => get_the_post_thumbnail_url( $saint_id, 'medium' ) ?: null,
		'edit_url'             => get_edit_post_link( $saint_id, 'raw' ),
		'view_url'             => get_permalink( $saint_id ),
	);
	
	if ( $include_marriages ) {
		$data['marriages'] = array();
		$marriages = get_field( 'marriages', $saint_id ) ?: array();
		
		foreach ( $marriages as $idx => $marriage ) {
			$spouse_id = null;
			$spouse_name = '';
			$spouse_saint_fs_id = null; // FS ID from linked saint record
			$spouse_familysearch_id = $marriage['spouse_familysearch_id'] ?? null; // FS ID stored on marriage
			
			if ( ! empty( $marriage['spouse'] ) ) {
				$spouse_id = is_array( $marriage['spouse'] ) ? ( $marriage['spouse'][0] ?? null ) : $marriage['spouse'];
				if ( $spouse_id ) {
					$spouse_name = get_the_title( $spouse_id );
					$spouse_saint_fs_id = get_field( 'familysearch_id', $spouse_id );
				}
			} elseif ( ! empty( $marriage['spouse_name'] ) ) {
				$spouse_name = $marriage['spouse_name'];
			}
			
			$marriage_data = array(
				'index'               => $idx,
				'spouse_id'           => $spouse_id,
				'spouse_name'         => $spouse_name,
				'spouse_saint_fs_id'  => $spouse_saint_fs_id,
				'spouse_familysearch_id' => $spouse_familysearch_id,
				'marriage_date'       => $marriage['marriage_date'] ?? null,
				'marriage_date_approximate' => (bool) ( $marriage['marriage_date_approximate'] ?? false ),
				'children'            => array(),
			);
			
			if ( ! empty( $marriage['children'] ) ) {
				foreach ( $marriage['children'] as $child_idx => $child ) {
					$child_link_id = null;
					$child_link_name = null;
					$child_link_fs_id = null;
					
					if ( ! empty( $child['child_link'] ) ) {
						$child_link_id = is_array( $child['child_link'] ) ? ( $child['child_link'][0] ?? null ) : $child['child_link'];
						if ( $child_link_id ) {
							$child_link_name = get_the_title( $child_link_id );
							$child_link_fs_id = get_field( 'familysearch_id', $child_link_id );
						}
					}
					
					$marriage_data['children'][] = array(
						'index'               => $child_idx,
						'child_name'          => $child['child_name'] ?? null,
						'child_birthdate'     => $child['child_birthdate'] ?? null,
						'child_familysearch_id' => $child['child_familysearch_id'] ?? null,
						'child_link_id'       => $child_link_id,
						'child_link_name'     => $child_link_name,
						'child_link_fs_id'    => $child_link_fs_id,
					);
				}
			}
			
			$data['marriages'][] = $marriage_data;
		}
	}
	
	return $data;
}

/**
 * GET /saints/duplicates - Find saints with duplicate FamilySearch IDs
 */
function wasmo_api_get_duplicate_saints( $request ) {
	global $wpdb;
	
	// Find FamilySearch IDs that appear more than once
	$duplicates_query = $wpdb->prepare(
		"SELECT pm.meta_value as fs_id, COUNT(*) as count
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
		WHERE pm.meta_key = %s
		AND pm.meta_value != ''
		AND p.post_type = 'saint'
		AND p.post_status = 'publish'
		GROUP BY pm.meta_value
		HAVING COUNT(*) > 1",
		'familysearch_id'
	);
	
	$duplicate_fs_ids = $wpdb->get_results( $duplicates_query );
	
	if ( empty( $duplicate_fs_ids ) ) {
		return new WP_REST_Response( array(
			'duplicates' => array(),
			'total'      => 0,
		), 200 );
	}
	
	$result = array();
	
	foreach ( $duplicate_fs_ids as $dup ) {
		$saints = get_posts( array(
			'post_type'      => 'saint',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'   => 'familysearch_id',
					'value' => $dup->fs_id,
				),
			),
		) );
		
		$saint_data = array();
		foreach ( $saints as $saint ) {
			$saint_data[] = array(
				'id'           => $saint->ID,
				'name'         => $saint->post_title,
				'birthdate'    => get_field( 'birthdate', $saint->ID ),
				'deathdate'    => get_field( 'deathdate', $saint->ID ),
				'has_portrait' => has_post_thumbnail( $saint->ID ),
				'marriages_count' => count( get_field( 'marriages', $saint->ID ) ?: array() ),
				'edit_url'     => get_edit_post_link( $saint->ID, 'raw' ),
			);
		}
		
		$result[] = array(
			'familysearch_id' => $dup->fs_id,
			'count'           => intval( $dup->count ),
			'saints'          => $saint_data,
		);
	}
	
	return new WP_REST_Response( array(
		'duplicates' => $result,
		'total'      => count( $result ),
	), 200 );
}

/**
 * POST /saints/{id}/merge - Merge a duplicate saint into this one
 * 
 * Merges another saint (merge_from_id) into the target saint (id):
 * - Updates all marriage/child relationships pointing to merge_from_id
 * - Preserves data from the target saint, fills in missing data from source
 * - Deletes the source saint after merge
 */
function wasmo_api_merge_saint( $request ) {
	$target_id = $request->get_param( 'id' );
	$body = $request->get_json_params();
	
	if ( empty( $body['merge_from_id'] ) ) {
		return new WP_Error(
			'missing_param',
			'merge_from_id is required',
			array( 'status' => 400 )
		);
	}
	
	$source_id = absint( $body['merge_from_id'] );
	
	// Verify both posts exist and are saints
	$target = get_post( $target_id );
	$source = get_post( $source_id );
	
	if ( ! $target || $target->post_type !== 'saint' ) {
		return new WP_Error( 'not_found', 'Target saint not found', array( 'status' => 404 ) );
	}
	
	if ( ! $source || $source->post_type !== 'saint' ) {
		return new WP_Error( 'not_found', 'Source saint not found', array( 'status' => 404 ) );
	}
	
	// Track what gets updated
	$updates = array(
		'relationships_updated' => 0,
		'fields_merged'         => array(),
	);
	
	// Step 1: Update all marriage/child relationships pointing to source saint
	global $wpdb;
	
	// Find all saints that reference the source saint in their marriages
	$all_saints = get_posts( array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	) );
	
	foreach ( $all_saints as $saint ) {
		$marriages = get_field( 'marriages', $saint->ID );
		if ( empty( $marriages ) ) continue;
		
		$updated = false;
		
		foreach ( $marriages as $idx => $marriage ) {
			// Check spouse relationship
			$spouse_id = is_array( $marriage['spouse'] ) ? ( $marriage['spouse'][0] ?? null ) : $marriage['spouse'];
			if ( $spouse_id == $source_id ) {
				$marriages[ $idx ]['spouse'] = array( $target_id );
				$updated = true;
				$updates['relationships_updated']++;
			}
			
			// Check children relationships
			if ( ! empty( $marriage['children'] ) ) {
				foreach ( $marriage['children'] as $child_idx => $child ) {
					$child_link = is_array( $child['child_link'] ) ? ( $child['child_link'][0] ?? null ) : $child['child_link'];
					if ( $child_link == $source_id ) {
						$marriages[ $idx ]['children'][ $child_idx ]['child_link'] = array( $target_id );
						$updated = true;
						$updates['relationships_updated']++;
					}
				}
			}
		}
		
		if ( $updated ) {
			update_field( 'marriages', $marriages, $saint->ID );
		}
	}
	
	// Step 2: Merge data from source to target (fill in missing fields)
	$fields_to_merge = array( 'birthdate', 'deathdate', 'gender', 'familysearch_notes' );
	
	foreach ( $fields_to_merge as $field ) {
		$target_value = get_field( $field, $target_id );
		$source_value = get_field( $field, $source_id );
		
		if ( empty( $target_value ) && ! empty( $source_value ) ) {
			update_field( $field, $source_value, $target_id );
			$updates['fields_merged'][] = $field;
		}
	}
	
	// Step 3: If target has no featured image but source does, copy it
	if ( ! has_post_thumbnail( $target_id ) && has_post_thumbnail( $source_id ) ) {
		$source_thumb_id = get_post_thumbnail_id( $source_id );
		set_post_thumbnail( $target_id, $source_thumb_id );
		$updates['fields_merged'][] = 'portrait';
	}
	
	// Step 4: Merge marriages if source has any that target doesn't
	$target_marriages = get_field( 'marriages', $target_id ) ?: array();
	$source_marriages = get_field( 'marriages', $source_id ) ?: array();
	
	if ( ! empty( $source_marriages ) && empty( $target_marriages ) ) {
		update_field( 'marriages', $source_marriages, $target_id );
		$updates['fields_merged'][] = 'marriages';
	}
	
	// Step 5: Delete the source saint
	$deleted = wp_delete_post( $source_id, true ); // true = force delete (skip trash)
	
	// Update verification notes
	$current_notes = get_field( 'familysearch_notes', $target_id ) ?: '';
	$merge_note = sprintf(
		'Merged duplicate saint "%s" (ID: %d) on %s',
		$source->post_title,
		$source_id,
		current_time( 'Y-m-d H:i' )
	);
	update_field( 'familysearch_notes', trim( $current_notes . "\n" . $merge_note ), $target_id );
	
	return new WP_REST_Response( array(
		'success'     => true,
		'target_id'   => $target_id,
		'source_id'   => $source_id,
		'deleted'     => $deleted !== false,
		'updates'     => $updates,
		'saint'       => wasmo_format_saint_for_api( $target_id ),
	), 200 );
}
