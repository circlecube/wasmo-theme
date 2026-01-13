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

	/**
	 * Taxonomy: Leader Roles.
	 * Non-hierarchical taxonomy for church leader positions.
	 */
	$labels = array(
		'name'                       => __( 'Leader Roles', 'wasmo' ),
		'singular_name'              => __( 'Leader Role', 'wasmo' ),
		'menu_name'                  => __( 'Leader Roles', 'wasmo' ),
		'all_items'                  => __( 'All Leader Roles', 'wasmo' ),
		'edit_item'                  => __( 'Edit Leader Role', 'wasmo' ),
		'view_item'                  => __( 'View Leader Role', 'wasmo' ),
		'update_item'                => __( 'Update Leader Role', 'wasmo' ),
		'add_new_item'               => __( 'Add New Leader Role', 'wasmo' ),
		'new_item_name'              => __( 'New Leader Role Name', 'wasmo' ),
		'search_items'               => __( 'Search Leader Roles', 'wasmo' ),
		'popular_items'              => __( 'Popular Leader Roles', 'wasmo' ),
		'separate_items_with_commas' => __( 'Separate leader roles with commas', 'wasmo' ),
		'add_or_remove_items'        => __( 'Add or remove leader roles', 'wasmo' ),
		'choose_from_most_used'      => __( 'Choose from the most used leader roles', 'wasmo' ),
		'not_found'                  => __( 'No leader roles found', 'wasmo' ),
	);

	$args = array(
		'label'                 => __( 'Leader Roles', 'wasmo' ),
		'labels'                => $labels,
		'public'                => true,
		'publicly_queryable'    => true,
		'hierarchical'          => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'leader-role', 'with_front' => true ),
		'show_admin_column'     => true,
		'show_in_rest'          => true,
		'rest_base'             => 'leader-role',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
		'show_in_quick_edit'    => true,
	);
	register_taxonomy( 'leader-role', array( 'church-leader' ), $args );
}
add_action( 'init', 'wasmo_cptui_register_my_taxes' );

/**
 * Register Church Leader Custom Post Type
 */
function wasmo_register_church_leader_cpt() {
	$labels = array(
		'name'                  => __( 'Church Leaders', 'wasmo' ),
		'singular_name'         => __( 'Church Leader', 'wasmo' ),
		'menu_name'             => __( 'Church Leaders', 'wasmo' ),
		'name_admin_bar'        => __( 'Church Leader', 'wasmo' ),
		'add_new'               => __( 'Add New', 'wasmo' ),
		'add_new_item'          => __( 'Add New Leader', 'wasmo' ),
		'new_item'              => __( 'New Church Leader', 'wasmo' ),
		'edit_item'             => __( 'Edit Church Leader', 'wasmo' ),
		'view_item'             => __( 'View Church Leader', 'wasmo' ),
		'all_items'             => __( 'All Church Leaders', 'wasmo' ),
		'search_items'          => __( 'Search Church Leaders', 'wasmo' ),
		'parent_item_colon'     => __( 'Parent Church Leaders:', 'wasmo' ),
		'not_found'             => __( 'No church leaders found.', 'wasmo' ),
		'not_found_in_trash'    => __( 'No church leaders found in Trash.', 'wasmo' ),
		'featured_image'        => __( 'Leader Portrait', 'wasmo' ),
		'set_featured_image'    => __( 'Set leader portrait', 'wasmo' ),
		'remove_featured_image' => __( 'Remove leader portrait', 'wasmo' ),
		'use_featured_image'    => __( 'Use as leader portrait', 'wasmo' ),
		'archives'              => __( 'Church Leader Archives', 'wasmo' ),
		'insert_into_item'      => __( 'Insert into church leader', 'wasmo' ),
		'uploaded_to_this_item' => __( 'Uploaded to this church leader', 'wasmo' ),
		'filter_items_list'     => __( 'Filter church leaders list', 'wasmo' ),
		'items_list_navigation' => __( 'Church leaders list navigation', 'wasmo' ),
		'items_list'            => __( 'Church leaders list', 'wasmo' ),
	);

	$args = array(
		'labels'              => $labels,
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'query_var'           => true,
		'rewrite'             => array( 'slug' => 'church-leader', 'with_front' => true ),
		'capability_type'     => 'post',
		'has_archive'         => true,
		'hierarchical'        => false,
		'menu_position'       => 5,
		'menu_icon'           => 'dashicons-groups',
		'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
		'show_in_rest'        => true,
		'rest_base'           => 'church-leaders',
		'rest_controller_class' => 'WP_REST_Posts_Controller',
		'taxonomies'          => array( 'leader-role', 'post_tag' ),
	);

	register_post_type( 'church-leader', $args );
}
add_action( 'init', 'wasmo_register_church_leader_cpt' );

/**
 * Create default leader role terms on theme activation
 */
function wasmo_create_default_leader_roles() {
	$default_roles = array(
		'president'           => 'President',
		'first-counselor'      => 'First Counselor',
		'second-counselor'    => 'Second Counselor',
		'apostle'             => 'Apostle',
		'seventy'             => 'Seventy',
		'presiding-bishopric' => 'Presiding Bishopric',
		'general-authority'   => 'General Authority',
		'other'               => 'Other',
	);

	foreach ( $default_roles as $slug => $name ) {
		if ( ! term_exists( $slug, 'leader-role' ) ) {
			wp_insert_term( $name, 'leader-role', array( 'slug' => $slug ) );
		}
	}
}
add_action( 'after_switch_theme', 'wasmo_create_default_leader_roles' );
add_action( 'init', 'wasmo_create_default_leader_roles', 20 );


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

	// force redirect to view the profile on save
	if ( is_user_logged_in() ) { // only if user is logged in
		wp_safe_redirect( get_author_posts_url( $user_id, $userSlug ), 301);
		exit;
	}
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

/**
 * Add leader role filter dropdown to Church Leaders admin list
 */
function wasmo_admin_leader_role_filter() {
	global $typenow;

	if ( 'church-leader' !== $typenow ) {
		return;
	}

	$taxonomy = 'leader-role';
	$selected = isset( $_GET[ $taxonomy ] ) ? sanitize_text_field( $_GET[ $taxonomy ] ) : '';
	
	$terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'orderby'    => 'name',
	) );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return;
	}

	echo '<select name="' . esc_attr( $taxonomy ) . '" id="' . esc_attr( $taxonomy ) . '">';
	echo '<option value="">' . esc_html__( 'All Roles', 'wasmo' ) . '</option>';
	
	foreach ( $terms as $term ) {
		$selected_attr = selected( $selected, $term->slug, false );
		echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected_attr . '>';
		echo esc_html( $term->name ) . ' (' . $term->count . ')';
		echo '</option>';
	}
	
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'wasmo_admin_leader_role_filter' );

/**
 * Add living/deceased filter dropdown to Church Leaders admin list
 */
function wasmo_admin_leader_status_filter() {
	global $typenow;

	if ( 'church-leader' !== $typenow ) {
		return;
	}

	$selected = isset( $_GET['leader_status'] ) ? sanitize_text_field( $_GET['leader_status'] ) : '';
	
	echo '<select name="leader_status" id="leader_status">';
	echo '<option value="">' . esc_html__( 'All (Living & Deceased)', 'wasmo' ) . '</option>';
	echo '<option value="living"' . selected( $selected, 'living', false ) . '>' . esc_html__( 'Living Only', 'wasmo' ) . '</option>';
	echo '<option value="deceased"' . selected( $selected, 'deceased', false ) . '>' . esc_html__( 'Deceased Only', 'wasmo' ) . '</option>';
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'wasmo_admin_leader_status_filter' );

/**
 * Filter Church Leaders query by living/deceased status
 */
function wasmo_admin_filter_leaders_by_status( $query ) {
	global $pagenow, $typenow;

	if ( ! is_admin() || 'edit.php' !== $pagenow || 'church-leader' !== $typenow ) {
		return;
	}

	if ( ! $query->is_main_query() ) {
		return;
	}

	$status = isset( $_GET['leader_status'] ) ? sanitize_text_field( $_GET['leader_status'] ) : '';

	if ( empty( $status ) ) {
		return;
	}

	$meta_query = $query->get( 'meta_query' ) ?: array();

	if ( 'living' === $status ) {
		// Living = no death date
		$meta_query[] = array(
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
	} elseif ( 'deceased' === $status ) {
		// Deceased = has death date
		$meta_query[] = array(
			'key'     => 'deathdate',
			'value'   => '',
			'compare' => '!=',
		);
	}

	$query->set( 'meta_query', $meta_query );
}
add_action( 'pre_get_posts', 'wasmo_admin_filter_leaders_by_status' );

/**
 * Add custom columns to Church Leaders admin list
 */
function wasmo_admin_leader_columns( $columns ) {
	$new_columns = array();
	
	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		
		// Add custom columns after title
		if ( 'title' === $key ) {
			$new_columns['leader_roles'] = __( 'Roles', 'wasmo' );
			$new_columns['leader_dates'] = __( 'Life Dates', 'wasmo' );
			$new_columns['leader_ordained'] = __( 'Ordained', 'wasmo' );
		}
	}
	
	// Remove the default taxonomy column if it exists (we're adding our own)
	unset( $new_columns['taxonomy-leader-role'] );
	
	return $new_columns;
}
add_filter( 'manage_church-leader_posts_columns', 'wasmo_admin_leader_columns' );

/**
 * Display custom column content for Church Leaders
 */
function wasmo_admin_leader_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'leader_roles':
			$terms = wp_get_post_terms( $post_id, 'leader-role', array( 'fields' => 'names' ) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				echo esc_html( implode( ', ', $terms ) );
			} else {
				echo '—';
			}
			break;
			
		case 'leader_dates':
			$birthdate = get_field( 'birthdate', $post_id );
			$deathdate = get_field( 'deathdate', $post_id );
			
			if ( $birthdate ) {
				$birth_year = date( 'Y', strtotime( $birthdate ) );
				if ( $deathdate ) {
					$death_year = date( 'Y', strtotime( $deathdate ) );
					echo esc_html( $birth_year . '–' . $death_year );
				} else {
					echo esc_html( $birth_year . '–present' );
				}
			} else {
				echo '—';
			}
			break;
			
		case 'leader_ordained':
			$ordained = get_field( 'ordained_date', $post_id );
			if ( $ordained ) {
				echo esc_html( date( 'M j, Y', strtotime( $ordained ) ) );
			} else {
				echo '—';
			}
			break;
	}
}
add_action( 'manage_church-leader_posts_custom_column', 'wasmo_admin_leader_column_content', 10, 2 );

/**
 * Make custom columns sortable
 */
function wasmo_admin_leader_sortable_columns( $columns ) {
	$columns['leader_dates'] = 'birthdate';
	$columns['leader_ordained'] = 'ordained_date';
	return $columns;
}
add_filter( 'manage_edit-church-leader_sortable_columns', 'wasmo_admin_leader_sortable_columns' );

/**
 * Handle sorting by custom columns
 */
function wasmo_admin_leader_column_orderby( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$orderby = $query->get( 'orderby' );

	if ( 'birthdate' === $orderby ) {
		$query->set( 'meta_key', 'birthdate' );
		$query->set( 'orderby', 'meta_value' );
	} elseif ( 'ordained_date' === $orderby ) {
		$query->set( 'meta_key', 'ordained_date' );
		$query->set( 'orderby', 'meta_value' );
	}
}
add_action( 'pre_get_posts', 'wasmo_admin_leader_column_orderby' );