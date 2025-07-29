<?php

/**
 * Register custom taxonomies
 */
function wasmo_cptui_register_my_taxes() {

	/**
	 * Taxonomy: Questions.
	 */

	$labels = array(
		'name' => __( 'Questions', 'wasmo' ),
		'singular_name' => __( 'Question', 'wasmo' ),
	);

	$args = array(
		'label' => __( 'Questions', 'wasmo' ),
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'question', 'with_front' => true, ),
		'show_admin_column' => true,
		'show_in_rest' => true,
		'rest_base' => 'question',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
		'show_in_quick_edit' => true,
		'capabilities' =>
			array(
				'manage_terms'  => 'edit_posts',
				'edit_terms'    => 'edit_posts',
				'delete_terms'  => 'edit_posts',
				'assign_terms'  => 'edit_posts'
			)
	);
	register_taxonomy( 'question', array( 'post' ), $args );

	/**
	 * Taxonomy: Spectrum.
	 */

	$labels = array(
		'name' => __( 'Spectrum', 'wasmo' ),
		'singular_name' => __( 'Spectrum', 'wasmo' ),
	);

	$args = array(
		'label' => __( 'Spectrum', 'wasmo' ),
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => false,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'spectrum', 'with_front' => true, ),
		'show_admin_column' => false,
		'show_in_rest' => true,
		'rest_base' => 'spectrum',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
		'show_in_quick_edit' => false,
	);
	register_taxonomy( 'spectrum', array( 'post', 'user' ), $args );

	/**
	 * Taxonomy: Shelf Items.
	 */

	$labels = array(
		'name' => __( 'Shelf Items', 'wasmo' ),
		'singular_name' => __( 'Shelf Item', 'wasmo' ),
	);

	$args = array(
		'label' => __( 'Shelf Items', 'wasmo' ),
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => false,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'shelf', 'with_front' => true, ),
		'show_admin_column' => false,
		'show_in_rest' => true,
		'rest_base' => 'shelf',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
		'show_in_quick_edit' => false,
	);
	register_taxonomy( 'shelf', array( 'post', 'user' ), $args );
}
add_action( 'init', 'wasmo_cptui_register_my_taxes' );


/**
 * Add ACF options page
 */
if( function_exists('acf_add_options_page') ) {
	
	acf_add_options_page(
		array(
			'page_title'  => 'Settings',
			'menu_title'  => 'Settings',
			'menu_slug'   => 'wasmo-settings',
			'capability'  => 'manage_options',
			'parent_slug' => 'wasmormon',
			'redirect'    => false
		)
	);
}

/**
 * Get default display name value
 * 
 * @param string $value The value.
 * @param string $post_id The post ID.
 * @param array $field The field array.
 * @return string The default display name value.
 */
function wasmo_get_default_display_name_value($value, $post_id, $field) {
	if ( $value === NULL || $value === '' ) {
		$user_id = intval( substr( $post_id, 5 ) );
		$user_info = get_userdata( $user_id );
		$user_displayname = $user_info->display_name;
		
		$value = $user_displayname;
	}
	return $value;
}
add_filter('acf/load_value/name=display_name', 'wasmo_get_default_display_name_value', 20, 3);

/**
 * Get default profile id value
 * 
 * @param string $value The value.
 * @param string $post_id The post ID.
 * @param array $field The field array.
 * @return string The default profile id value.
 */
function wasmo_get_default_profile_id_value($value, $post_id, $field) {
	if ( $value === NULL || $value === '' ) {
		$user_id = intval( substr( $post_id, 5 ) );
		$user_info = get_userdata( $user_id );
		$user_nicename = $user_info->user_nicename;
		
		$value = $user_nicename;
	}
	return $value;
}
add_filter('acf/load_value/name=profile_id', 'wasmo_get_default_profile_id_value', 20, 3);

/**
 * Update user
 * 
 * @param int $post_id The post ID.
 */
function wasmo_update_user( $post_id ) {
	// only for users - skip for posts etc
	if ( strpos( $post_id, 'user_' ) !== 0 ) {
		return;
	}

	$user_id = intval( substr( $post_id, 5 ) );

	// update user_nicename and display_name from the equivalent acf fields

	$userDisplayname = sanitize_text_field( $_POST['acf']['field_5cb486045a336'] );
	$userSlug = sanitize_title( $_POST['acf']['field_5cb486165a337'] );
	// $user_displayname = sanitize_text_field( get_field( 'display_name', 'user_'. $user_id ) );
	// $user_slug = sanitize_title( get_field( 'profile_id', 'user_'. $user_id ) );
	update_user_meta( $user_id, 'nickname', $userSlug );
	$user_id = wp_update_user( 
		array(
			'ID'            => $user_id,
			'display_name'  => $userDisplayname,
			'user_nicename' => $userSlug,
		)
	);

	// Purge cloudflare super page cache 
	do_action( 'swcfpc_purge_cache' );
	
	// clear all directory transients
	wasmo_delete_transients_with_prefix( 'wasmo_directory-' );

	// update question counts if user includes any
	if( have_rows( 'questions', 'user_' . $user_id ) ){
		wasmo_update_user_question_count();
	}

	// increment save_count
	$save_count = get_user_meta( $user_id, 'save_count', true );
	if ('' === $save_count ) {
		$save_count = 0;
	}
	$save_count = intval($save_count) + 1;
	update_user_meta( $user_id, 'save_count', $save_count );
	
	// Add event to simple history logs
	apply_filters(
		'simple_history_log',
		'Updated profile for {displayname}({nicename}) (edit #{savecount}) {link}',
		[
			'nicename' => $userSlug,
			'displayname' => $userDisplayname,
			'savecount' => $save_count,
			'link' => get_author_posts_url( $user_id ),
		],
		'info'
	);


	// only if not edited by an admin
	if ( !current_user_can( 'administrator' ) ) {

		// update last_save timestamp for this user
		update_user_meta( $user_id, 'last_save', time() );

		// email notification to admin
		wasmo_send_admin_email__profile_update( $user_id, $save_count );
	}

	// redirect to view the profile on save
	wp_safe_redirect( get_author_posts_url( $user_id, $userSlug ), 301);
	exit();
}
add_action( 'acf/save_post', 'wasmo_update_user', 10 );

/**
 * Update spotlight post for user
 * 
 * @param int $post_id The post ID.
 */
function wasmo_update_spotlight( $post_id ) {
	// only if category for spotlight posts
	if ( !has_category( 'spotlight', $post_id ) ) {
		return;
	}

	// get spotlight focus user
	$user_id = get_field( 'spotlight_for', $post_id ); // acf set to return user id only

	// update user meta with spotlight post, if found
	if ( $user_id ) {
		update_user_meta( $user_id, 'spotlight_post', $post_id );
	}
	
}
add_action( 'acf/save_post', 'wasmo_update_spotlight', 10 );

/**
 * Delete user
 * 
 * @param int $user_id The user ID.
 */
function wasmo_delete_user( $user_id ) {
	// clear all directory transients
	wasmo_delete_transients_with_prefix( 'wasmo_directory-' );
}
add_action( 'delete_user', 'wasmo_delete_user' );


/**
 * Update user question count
 */
function wasmo_update_user_question_count(){
	global $wpdb;

	//get terms
	$tempterms = [];
	// $terms = get_terms( 'question' );
	$terms = get_terms([
		'taxonomy' => 'question',
		'hide_empty' => false,
		'number' => 0,
	]);
	// set count to 0 for each term - reset to count fresh
	foreach ( $terms as $term ) { 
		$termtaxid = $term->id;
		$tempterms[$termtaxid] = 0;
	}
	// reset terms to empty array
	// $terms = ['users'=>0];

	// get all users
	$users = get_users();
	// user loop
	foreach ( $users as $user ) { 
		$userid = $user->ID;
		// $tempterms['users']++;
		// only use public users - so we don't end up with blank question pages
		$in_directory = get_field( 'in_directory', 'user_' . $userid );
		if ( 
			'true' === $in_directory ||
			'website' === $in_directory
		) {
			// get questions for user
			if( have_rows( 'questions', 'user_' . $userid ) ) {
				
				// question loop
				while ( have_rows( 'questions', 'user_' . $userid ) ) {
					the_row();
					$termtaxid = get_sub_field( 'question', 'users_' . $userid );
					$term = get_term( $termtaxid, 'questions' );
					if ( array_key_exists( $termtaxid, $tempterms ) ) {
						$tempterms[$termtaxid]++; // increment term
					} else {
						$tempterms[$termtaxid] = 1;
					}
				}
			}
		}
	}
	// write new counts to db
	foreach ( $tempterms as $key => $value ) { 
		$termtaxid = $key;
		$termcount = $value;
		$wpdb->update( 
			$wpdb->term_taxonomy, 
			array( 'count' => $termcount ), 
			array( 'term_taxonomy_id' => $termtaxid ) 
		);
	}
}

/**
 * Delete all transients from the database whose keys have a specific prefix.
 *
 * @param string $prefix The prefix. Example: 'my_cool_transient_'.
 */
function wasmo_delete_transients_with_prefix( $prefix ) {
	foreach ( wasmo_get_transient_keys_with_prefix( $prefix ) as $key ) {
		delete_transient( $key );
	}
}

/**
 * Gets all transient keys in the database with a specific prefix.
 *
 * Note that this doesn't work for sites that use a persistent object
 * cache, since in that case, transients are stored in memory.
 *
 * @param  string $prefix Prefix to search for.
 * @return array          Transient keys with prefix, or empty array on error.
 */
function wasmo_get_transient_keys_with_prefix( $prefix ) {
	global $wpdb;

	$prefix = $wpdb->esc_like( '_transient_' . $prefix );
	$sql    = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s'";
	$keys   = $wpdb->get_results( $wpdb->prepare( $sql, $prefix . '%' ), ARRAY_A );

	if ( is_wp_error( $keys ) ) {
		return [];
	}

	return array_map( function( $key ) {
		// Remove '_transient_' from the option name.
		return substr( $key['option_name'], strlen( '_transient_' ) );
	}, $keys );
}