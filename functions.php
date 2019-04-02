<?php

// Enqueue styles - get parent theme styles first.
function wasmo_enqueue() {

    $parent_style = 'parent-style'; // This is 'twentynineteen-style' for the Twenty Nineteen theme.

    wp_enqueue_style( 
		$parent_style, 
		get_template_directory_uri() . '/style.css'
	);
    wp_enqueue_style( 
		'wasmo-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
	);
	
	wp_enqueue_script( 
		'wasmo-script', 
		get_stylesheet_directory_uri() . '/js/script.js', 
		array ( 'jquery' ), 
		wp_get_theme()->get('Version'),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'wasmo_enqueue' );


// theme mods
// set_theme_mod( 'page_layout', 'one-column' );



// hide admin bar for non admin users
add_action( 'set_current_user', 'wasmo_hide_admin_bar' );
function wasmo_hide_admin_bar() {
	if ( !current_user_can( 'publish_posts' ) ) {
		show_admin_bar( false );
	}
}


function cptui_register_my_taxes() {

	/**
	 * Taxonomy: Questions.
	 */

	$labels = array(
		"name" => __( "Questions", "wasmo" ),
		"singular_name" => __( "Question", "wasmo" ),
	);

	$args = array(
		"label" => __( "Questions", "wasmo" ),
		"labels" => $labels,
		"public" => true,
		"publicly_queryable" => true,
		"hierarchical" => false,
		"show_ui" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"query_var" => true,
		"rewrite" => array( 'slug' => 'question', 'with_front' => true, ),
		"show_admin_column" => true,
		"show_in_rest" => true,
		"rest_base" => "question",
		"rest_controller_class" => "WP_REST_Terms_Controller",
		"show_in_quick_edit" => true,
		"capabilities" =>
			array(
				'manage_terms'  => 'edit_posts',
				'edit_terms'    => 'edit_posts',
				'delete_terms'  => 'edit_posts',
				'assign_terms'  => 'edit_posts'
			)
		);
	register_taxonomy( "question", array( "post" ), $args );
}
add_action( 'init', 'cptui_register_my_taxes' );



function wasmo_widgets_init() {

	register_sidebar(
		array(
			'name'          => __( 'Sidebar', 'twentynineteen' ),
			'id'            => 'sidebar',
			'description'   => __( 'Add widgets here to appear in your sidebar.', 'twentynineteen' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);

}
add_action( 'widgets_init', 'wasmo_widgets_init' );

if ( ! function_exists( 'wasmo_setup' ) ) :
	function wasmo_setup() {
		register_nav_menus(
			array(
				'utility' => __( 'Utility Menu', 'twentynineteen' ),
			)
		);
	}
endif;

add_action( 'after_setup_theme', 'wasmo_setup' );

function wasmo_loginout_menu_link( $items, $args ) {
	if ($args->theme_location == 'utility') {
		$login = '<li><a href="' . home_url('/login/') . '" class="nav-login">' . __("Log In") . '</a></li>';
		$logout = '<li><a href="' . wp_logout_url() . '">' . __("Log Out") . '</a></li>';
		$profile = '<li><a href="' . get_author_posts_url( get_current_user_id() ) . '">View</a></li>';
		if ( is_user_logged_in() ) {
			$items = $profile . $items . $logout;
		} else {
			$items .= $login;
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_items', 'wasmo_loginout_menu_link', 10, 2 );


function wasmo_login_redirect_page() {
  return home_url('/directory/');
}
add_filter('login_redirect', 'wasmo_login_redirect_page');

function wasmo_logout_redirect_page() {
  return home_url('/directory/');
}
add_filter('logout_redirect', 'wasmo_logout_redirect_page');


function my_acf_init() {
	acf_update_setting('google_api_key', 'AIzaSyAF3HYVew1ZS_9i0mY1wymX1Hs885AJtIw');
}

add_action('acf/init', 'my_acf_init');


function wasmo_filter_product_wpseo_title($title) {
    if( is_author() ) {
		$curauth = (get_query_var('author_name')) ? get_user_by('slug', get_query_var('author_name')) : get_userdata(get_query_var('author'));
		$userid = $curauth->ID;
		$title = get_field( 'hi', 'user_' . $userid ) . ' at wasMormon.org';
		// $username = esc_html( $curauth->user_login );
		// $title = $username . ' at wasMormon.org';
    }
    return $title;
}
add_filter('wpseo_title', 'wasmo_filter_product_wpseo_title');


/**
 * Plugin Name: Multisite: Passwort Reset on Local Blog
 * Plugin URI:  https://gist.github.com/eteubert/293e07a49f56f300ddbb
 * Description: By default, WordPress Multisite uses the main blog for passwort resets. This plugin enables users to stay in their blog during the whole reset process.
 * Version:     1.0.0
 * Author:      Eric Teubert
 * Author URI:  http://ericteubert.de
 * License:     MIT
 */

// fixes "Lost Password?" URLs on login page
add_filter("lostpassword_url", function ($url, $redirect) {	
	
	$args = array( 'action' => 'lostpassword' );
	
	if ( !empty($redirect) )
		$args['redirect_to'] = $redirect;

	return add_query_arg( $args, site_url('wp-login.php') );
}, 10, 2);

// fixes other password reset related urls
add_filter( 'network_site_url', function($url, $path, $scheme) {
	
	if (stripos($url, "action=rp") !== false)
		// return site_url('wp-login.php?action=lostpassword', $scheme);
		return str_replace( 'circlecube.com', 'wasmormon.org', $url );
	  
	if (stripos($url, "action=lostpassword") !== false)
		return site_url('wp-login.php?action=lostpassword', $scheme);
  
	if (stripos($url, "action=resetpass") !== false)
		return site_url('wp-login.php?action=resetpass', $scheme);
  
	return $url;
}, 10, 3 );

// fixes URLs in email that goes out.
add_filter("retrieve_password_message", function ($message, $key) {
  	return str_replace(get_site_url(1), get_site_url(), $message);
}, 10, 2);

// fixes email title
add_filter("retrieve_password_title", function($title) {
	return "[" . wp_specialchars_decode(get_option('blogname'), ENT_QUOTES) . "] Password Reset";
});


// Capture user login and add it as timestamp in user meta data
function wasmo_user_lastlogin( $user_login, $user ) {
    update_user_meta( $user->ID, 'last_login', time() );
}
add_action( 'wp_login', 'wasmo_user_lastlogin', 10, 2 );
 
// Display last login time
function wasmo_get_lastlogin() { 
    $last_login = get_the_author_meta('last_login');
    $the_login_date = human_time_diff($last_login);
    return $the_login_date; 
}

// function wasmo_update_extra_profile_fields($user_id) {
// 	// clear directory transients
// 	delete_transient( 'directory-1' );
// 	delete_transient( 'directory-0' );
	
// 	// update last_save timestamp
// 	update_user_meta( $user->ID, 'last_save', time() );
// 	die('My filter is running');
// }
// add_action('personal_options_update', 'wasmo_update_extra_profile_fields'); // own profile
// add_action('edit_user_profile_update', 'update_extra_profile_fields'); // other profiles

function wasmo_update_user( $post_id ) {
	if ( strpos( $post_id, 'user_' ) !== 0 )
		return;
	
	// update last_save timestamp for this user
	$user_id = intval( substr( $post_id, 5 ) );
	update_user_meta( $user_id, 'last_save', time() );
	
	// clear directory transients
	delete_transient( 'directory-1' );
	delete_transient( 'directory-0' );

	// update question counts if user includes any
	if( have_rows( 'questions', 'user_' . $user_id ) ){
		wasmo_update_user_question_count();
	}

}
add_action( 'acf/save_post', 'wasmo_update_user', 5 );

function wasmo_update_user_question_count(){
	global $wpdb;

	//get terms
	$tempterms = [];
	$terms = get_terms( 'question' );
	$terms = get_terms([
		'taxonomy' => 'question',
		'hide_empty' => false,
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
		$tempterms['users']++;
		// only use public users - so we don't end up with blank question pages
		if ( 'true' === get_field( 'in_directory', 'user_' . $userid ) ) {
			// get questions for user
			if( have_rows( 'questions', 'user_' . $userid ) ) {
				
				// question loop
				while ( have_rows( 'questions', 'user_' . $userid ) ) {
					the_row();
					$termtaxid = get_sub_field( 'question', 'users_' . $userid );
					$term = get_term( $termtaxid, 'questions' );
					$tempterms[$termtaxid]++; // increment term
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

//override twentynineteen_entry_footer
function wasmo_entry_footer() {

	// Hide author, post date, category and tag text for pages.
	if ( 'post' === get_post_type() ) {

		// Posted by
		//twentynineteen_posted_by(); // hide author

		// Posted on
		twentynineteen_posted_on();

		/* translators: used between list items, there is a space after the comma. */
		$categories_list = get_the_category_list( __( ', ', 'twentynineteen' ) );
		if ( $categories_list ) {
			printf(
				/* translators: 1: SVG icon. 2: posted in label, only visible to screen readers. 3: list of categories. */
				'<span class="cat-links">%1$s<span class="screen-reader-text">%2$s</span>%3$s</span>',
				twentynineteen_get_icon_svg( 'archive', 16 ),
				__( 'Posted in', 'twentynineteen' ),
				$categories_list
			); // WPCS: XSS OK.
		}

		/* translators: used between list items, there is a space after the comma. */
		$tags_list = get_the_tag_list( '', __( ', ', 'twentynineteen' ) );
		if ( $tags_list ) {
			printf(
				/* translators: 1: SVG icon. 2: posted in label, only visible to screen readers. 3: list of tags. */
				'<span class="tags-links">%1$s<span class="screen-reader-text">%2$s </span>%3$s</span>',
				twentynineteen_get_icon_svg( 'tag', 16 ),
				__( 'Tags:', 'twentynineteen' ),
				$tags_list
			); // WPCS: XSS OK.
		}
	}

	// Comment count.
	if ( ! is_singular() ) {
		twentynineteen_comment_count();
	}

	// Edit post link.
	edit_post_link(
		sprintf(
			wp_kses(
				/* translators: %s: Name of current post. Only visible to screen readers. */
				__( 'Edit <span class="screen-reader-text">%s</span>', 'twentynineteen' ),
				array(
					'span' => array(
						'class' => array(),
					),
				)
			),
			get_the_title()
		),
		'<span class="edit-link">' . twentynineteen_get_icon_svg( 'edit', 16 ),
		'</span>'
	);
}
