<?php

/**
 * Register a custom menu page.
 */
function wasmo_register_admin_page(){
	add_menu_page( 
		'wasmo',
		'wasmormon',
		'manage_options',
		'wasmormon',
		'wasmo_menu_page',
		'dashicons-beer',
		1
	);
}
add_action( 'admin_menu', 'wasmo_register_admin_page' );

/**
 * Display a custom menu page
 */
function wasmo_menu_page(){
	// nothing here
	echo '<h1>wasmormon</h1>';
}

/**
 * Register Directory and Posts widgets
 */
function register_wasmo_widgets() {
	register_widget( 'wasmo\Directory_Widget' );
	register_widget( 'wasmo\Posts_Widget' );
}
add_action( 'widgets_init', 'register_wasmo_widgets' );

/**
 * Set up the sidebar
 */
function wasmo_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Sidebar', 'wasmo' ),
			'id'            => 'sidebar',
			'description'   => __( 'Add widgets here to appear in your sidebar.', 'wasmo' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'wasmo_widgets_init' );

/**
 * Set up the block pattern category
 * 
 * Defines theme specific block patterns in theme patterns folder
 */
function wasmo_register_pattern_categories() {
	register_block_pattern_category(
		'wasmormon',
		array( 'label' => __( 'wasmormon', 'wasmo' ) )
	);
}
add_action( 'init', 'wasmo_register_pattern_categories' );

/**
 * Hide admin menu items for non admin users
 */
function wasmo_remove_menu_items() {
	// IF NON ADMIN USER
	if ( !current_user_can( 'administrator' ) ) :
		remove_menu_page( 'index.php' ); // DASHBOARD
		//remove_menu_page( 'edit.php?post_type=custom_post_type' );
		//remove_submenu_page( 'edit.php?post_type=custom_post_type', 'post-new.php?post_type=custom_post_type' );
 		//remove_menu_page( 'edit.php' );
		remove_menu_page( 'upload.php' );
		remove_menu_page( 'edit-comments.php' );
		remove_menu_page( 'edit.php?post_type=page' );
		remove_menu_page( 'plugins.php' );
		remove_menu_page( 'themes.php' );
		remove_menu_page( 'users.php' );
		remove_menu_page( 'profile.php' );
		remove_menu_page( 'tools.php' );
		remove_menu_page( 'options-general.php' );
		remove_menu_page( 'jetpack' );
		remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=question' ); //questions taxonomy
		remove_submenu_page( 'index.php', 'my-sites.php' );
	endif;
}
add_action( 'admin_menu', 'wasmo_remove_menu_items', 1000 );

/**
 * Remove admin notices for non admin users
 */
function wasmo_hide_notices(){
	if ( !current_user_can('administrator') ) {
		remove_all_actions( 'admin_notices' );
	}
}	
add_action( 'admin_head', 'wasmo_hide_notices', 1 );

/**
 * Remove wpautop from term description
 */
remove_filter( 'term_description','wpautop' );

/**
 * Add tags for attachments
 */
function wasmo_add_tags_for_attachments() {
	register_taxonomy_for_object_type( 'post_tag', 'attachment' );
}
add_action( 'init' , 'wasmo_add_tags_for_attachments' );

/**
 * Modify the main query object
 * 
 * @param WP_Query $query The query object.
 * @return WP_Query The modified query object.
 */
function wasmo_media_in_main_query( $query ) {
	// Only run on frontend archive queries, not admin
	if ( is_admin() ) {
		return;
	}
	
	// only run on archive queries
	if ( $query->is_archive() && $query->is_main_query() ) {
		// Don't modify queries for custom post types - they should use their own post_type
		$queried_post_type = $query->get( 'post_type' );
		if ( ! empty( $queried_post_type ) && $queried_post_type !== 'post' ) {
			return;
		}
		
		// add attachment post types, media
		$query->set( 'post_type', array( 'post', 'attachment' ) );
		// add inherit post status since that is the default status of media
		$query->set( 'post_status', array( 'publish', 'inherit' ) );
	}
}
add_action( 'pre_get_posts', 'wasmo_media_in_main_query' );

/**
 * Update contributor capabilities
 */
function wasmo_update_contributor_capabilities() {
	// gets the contributor role
	$contributors = get_role( 'contributor' );
	$contributors->add_cap( 'read_private_pages' );
	$contributors->add_cap( 'read_private_posts' );
}
add_action( 'admin_init', 'wasmo_update_contributor_capabilities');

/**
 * Add showall query var
 */
function wasmo_add_showall_query_var() { 
	global $wp; 
	$wp->add_query_var('context');
	$wp->add_query_var('max_profiles');
	$wp->add_query_var('lazy');
	$wp->add_query_var('showall');
}
add_action('init','wasmo_add_showall_query_var');

/**
 * Skip self pings
 * 
 * @param array $links The links array.
 * @return array The modified links array.
 */
function wasmo_skip_self_pings ( &$links ) {
	$home = get_option( 'home' );
	foreach ( $links as $l => $link ) {
		if ( 0 === strpos( $link, $home ) ) {
			unset( $links[ $l ] );
		}
	}
}
add_action( 'pre_ping', 'wasmo_skip_self_pings' );

/**
 * Directory shortcode
 * 
 * @param array $atts Shortcode attributes.
 * @return string Shortcode output.
 */
function wasmo_directory_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'max' => 12,
		'title' => ''
	), $atts, 'wasmo_directory' );
	$directory = '';
	if ( $atts['title'] !== '' ) {
		$directory .= '<h3>' . $atts['title'] . '</h3>';
	}
	ob_start();
	set_query_var( 'max_profiles', $atts['max'] );
	set_query_var( 'context', 'shortcode' );
	get_template_part( 'template-parts/content/content', 'directory' );
	$directory .= ob_get_clean();
	return $directory;
}
add_shortcode( 'wasmo_directory', 'wasmo_directory_shortcode' );

/**
 * Hide admin bar for non admin users
 */
function wasmo_hide_admin_bar() {
	if ( !current_user_can( 'publish_posts' ) ) {
		show_admin_bar( false );
	}
}
// add_action( 'set_current_user', 'wasmo_hide_admin_bar' );

/**
 * Hide admin bar for non admin users
 */
function remove_admin_bar() {
	if ( !current_user_can('administrator') && !is_admin() ) {
		show_admin_bar( false );
	}
}
// add_action('after_setup_theme', 'remove_admin_bar');

/**
 * Remove links/menus from the admin bar
 */
function wasmo_admin_bar_render() {
	global $wp_admin_bar;
	// hide stuff in admin bar for everyone
	$wp_admin_bar->remove_menu('aioseo-main');
	
	// alter user admin bar
	if ( !current_user_can('administrator') ) {
		$wp_admin_bar->remove_menu('search');
		$wp_admin_bar->remove_menu('wp-logo');
		$wp_admin_bar->remove_menu('comments');
		// $wp_admin_bar->remove_menu('my-account-with-avatar');
		// $wp_admin_bar->remove_menu('my-account');
		// $wp_admin_bar->remove_menu('get-shortlink');
		// $wp_admin_bar->remove_menu('appearance');
		// $wp_admin_bar->remove_menu('updates');
		// $wp_admin_bar->remove_menu('notes');
		// $wp_admin_bar->remove_menu('edit');
		
		//add menu items for user profile view and edit
		$wp_admin_bar->add_menu( array(
			'id'    => 'profile-edit',
			'parent' => null,
			'group'  => null,
			'title' => wasmo_get_icon_svg( 'edit', 14 ) . ' Edit Profile',
			'href'  => site_url('/edit/'),
			'meta' => [
				'title' => 'Edit Profile',
			]
		));
		
		$wp_admin_bar->add_menu( array(
			'id'    => 'profile-view',
			'parent' => null,
			'group'  => null,
			'title' => wasmo_get_icon_svg( 'person', 14 ) . ' View Profile',
			'href'  => get_author_posts_url( get_current_user_id() ),
			'meta' => [
				'title' => 'View Profile',
			]
		));
		
		if ( !is_admin() ) {
			// $wp_admin_bar->remove_menu('site-name');
		}
	}
}
add_action( 'wp_before_admin_bar_render', 'wasmo_admin_bar_render' );

/**
 * Only show users own posts
 * 
 * @param WP_Query $query The query object.
 * @return WP_Query The modified query object.
 */
function wasmo_posts_for_current_author($query) {
	global $pagenow;
 
	if( 'edit.php' != $pagenow || !$query->is_admin )
		return $query;
	
	// Only apply to standard 'post' type, not custom post types like church-leader
	$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : 'post';
	if ( $post_type !== 'post' ) {
		return $query;
	}
 
	if( !current_user_can( 'edit_others_posts' ) ) {
		global $user_ID;
		$query->set('author', $user_ID );
		add_filter('views_edit-post', 'wasmo_fix_post_counts');
	}
	return $query;
}
add_filter('pre_get_posts', 'wasmo_posts_for_current_author');

/**
 * Fix post counts
 * 
 * @param array $views The views array.
 * @return array The modified views array.
 */
function wasmo_fix_post_counts($views) {
	global $current_user, $wp_query;
	unset($views['mine']);
	$types = array(
		array( 'status' =>  NULL ),
		array( 'status' => 'publish' ),
		array( 'status' => 'draft' ),
		array( 'status' => 'future' ),
		array( 'status' => 'pending' ),
		array( 'status' => 'trash' )
	);
	foreach( $types as $type ) {
		$query = array(
			'author'      => $current_user->ID,
			'post_type'   => 'post',
			'post_status' => $type['status']
		);
		$result = new WP_Query($query);
		if( $type['status'] == NULL ):
			$class = ($wp_query->query_vars['post_status'] == NULL) ? ' class="current"' : '';
			$views['all'] = sprintf(
				__('<a href="%s" '.$class.'>All <span class="count">(%d)</span></a>', 'wasmo'),
				admin_url('edit.php?post_type=post'),
				$result->found_posts
			);
		elseif( $type['status'] == 'publish' ):
			if ( $result->found_posts === 0 ) {
				unset($views['publish']);
			} else {
				$class = ($wp_query->query_vars['post_status'] == 'publish') ? ' class="current"' : '';
				$views['publish'] = sprintf(
					__('<a href="%s" '.$class.'>Published <span class="count">(%d)</span></a>', 'wasmo'),
					admin_url('edit.php?post_status=publish&post_type=post'),
					$result->found_posts
				);
			}
		elseif( $type['status'] == 'draft' ):
			if ( $result->found_posts === 0 ) {
				unset($views['draft']);
			} else {
				$class = ($wp_query->query_vars['post_status'] == 'draft') ? ' class="current"' : '';
				$views['draft'] = sprintf(
					__('<a href="%s" '.$class.'>Draft'. ((sizeof($result->posts) > 1) ? "s" : "") .' <span class="count">(%d)</span></a>', 'wasmo'),
					admin_url('edit.php?post_status=draft&post_type=post'),
					$result->found_posts
				);
			}
		elseif( $type['status'] == 'future' ):
			if ( $result->found_posts === 0 ) {
				unset($views['future']);
			} else {
				$class = ($wp_query->query_vars['post_status'] == 'future') ? ' class="future"' : '';
				$views['future'] = sprintf(
					__('<a href="%s" '.$class.'>Scheduled <span class="count">(%d)</span></a>', 'wasmo'),
					admin_url('edit.php?post_status=future&post_type=post'),
					$result->found_posts
				);
			}
		elseif( $type['status'] == 'pending' ):
			if ( $result->found_posts === 0 ) {
				unset($views['pending']);
			} else {
				$class = ($wp_query->query_vars['post_status'] == 'pending') ? ' class="current"' : '';
				$views['pending'] = sprintf(
					__('<a href="%s" '.$class.'>Pending <span class="count">(%d)</span></a>', 'wasmo'),
					admin_url('edit.php?post_status=pending&post_type=post'),
					$result->found_posts
				);
			}
		elseif( $type['status'] == 'trash' ):
			if ( $result->found_posts === 0 ) {
				unset($views['trash']);
			} else {
				$class = ($wp_query->query_vars['post_status'] == 'trash') ? ' class="current"' : '';
				$views['trash'] = sprintf(
					__('<a href="%s" '.$class.'>Trash <span class="count">(%d)</span></a>', 'wasmo'),
					admin_url('edit.php?post_status=trash&post_type=post'),
					$result->found_posts
				);
			}
		endif;
	}
	return $views;
}

/**
 * Changing default gutenberg image block alignment to "center"
 */
// function wasmo_change_default_gutenberg_image_block_options (){
// 	$block_type = WP_Block_Type_Registry::get_instance()->get_registered( "core/image" );
// 	$block_type->attributes['align']['default'] = 'center';
// }
// add_action( 'init', 'wasmo_change_default_gutenberg_image_block_options');
