<?php

require_once( get_stylesheet_directory() . '/includes/wasmo-directory-widget.php' );
require_once( get_stylesheet_directory() . '/includes/wasmo-posts-widget.php' );

// register Foo_Widget widget
function register_wasmo_widgets() {
	register_widget( 'wasmo\Directory_Widget' );
	register_widget( 'wasmo\Posts_Widget' );
}
add_action( 'widgets_init', 'register_wasmo_widgets' );

// Enqueue styles - get parent theme styles first.
function wasmo_enqueue() {

	$parent_style = 'parent-style'; // This is 'twentynineteen-style' for the Twenty Nineteen theme.

	wp_enqueue_style( 
		$parent_style, 
		get_stylesheet_directory_uri() . '/twentynineteen.css',
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

function wasmo_add_google_fonts() {
	wp_enqueue_style( 
		'wasmo-google-fonts', 
		'https://fonts.googleapis.com/css?family=Crimson+Text:400,700|Open+Sans:400,700&display=swap', 
		false
	); 
}
 
add_action( 'wp_enqueue_scripts', 'wasmo_add_google_fonts' );

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

	/**
	 * Taxonomy: Spectrum.
	 */

	$labels = array(
		"name" => __( "Spectrum", "wasmo" ),
		"singular_name" => __( "Spectrum", "wasmo" ),
	);

	$args = array(
		"label" => __( "Spectrum", "wasmo" ),
		"labels" => $labels,
		"public" => true,
		"publicly_queryable" => true,
		"hierarchical" => false,
		"show_ui" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"query_var" => true,
		"rewrite" => array( 'slug' => 'spectrum', 'with_front' => true, ),
		"show_admin_column" => false,
		"show_in_rest" => true,
		"rest_base" => "spectrum",
		"rest_controller_class" => "WP_REST_Terms_Controller",
		"show_in_quick_edit" => false,
		);
	register_taxonomy( "spectrum", array( "post", "user" ), $args );

	/**
	 * Taxonomy: Shelf Items.
	 */

	$labels = array(
		"name" => __( "Shelf Items", "wasmo" ),
		"singular_name" => __( "Shelf Item", "wasmo" ),
	);

	$args = array(
		"label" => __( "Shelf Items", "wasmo" ),
		"labels" => $labels,
		"public" => true,
		"publicly_queryable" => true,
		"hierarchical" => false,
		"show_ui" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"query_var" => true,
		"rewrite" => array( 'slug' => 'shelf', 'with_front' => true, ),
		"show_admin_column" => false,
		"show_in_rest" => true,
		"rest_base" => "shelf",
		"rest_controller_class" => "WP_REST_Terms_Controller",
		"show_in_quick_edit" => false,
		);
	register_taxonomy( "shelf", array( "post", "user" ), $args );
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

function wasmo_setup() {
	register_nav_menus(
		array(
			'utility' => __( 'Utility Menu', 'twentynineteen' ),
		)
	);
	set_theme_mod( 'image_filter', 0 );
}
add_action( 'after_setup_theme', 'wasmo_setup' );

// add hard coded utility menu items 
function wasmo_loginout_menu_link( $items, $args ) {
	if ($args->theme_location == 'utility') {
		$edit_svg = twentynineteen_get_icon_svg( 'edit', 24 );
		$user_svg = twentynineteen_get_icon_svg( 'person', 24 );
		$join_svg = '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
		$login_svg = '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24" viewBox="0 0 24 24" width="24"><g><rect fill="none" height="24" width="24"/></g><g><path d="M11,7L9.6,8.4l2.6,2.6H2v2h10.2l-2.6,2.6L11,17l5-5L11,7z M20,19h-8v2h8c1.1,0,2-0.9,2-2V5c0-1.1-0.9-2-2-2h-8v2h8V19z"/></g></svg>';
		$login =   '<li class="login"><a href="' . home_url('/login/') . '" class="register">' . $join_svg . __(" Join") . '</a></li>';
		$login .=   '<li class="login"><a href="' . home_url('/login/') . '" class="nav-login">' . $login_svg . __(" Login") . '</a></li>';
		// $logout =  '<li class="logout"><a href="' . wp_logout_url() . '">' . __("Log Out") . '</a></li>';
		$profile = '<li class="view"><a href="' . get_author_posts_url( get_current_user_id() ) . '">' . $user_svg . 'View</a></li>';
		$edit =    '<li class="edit"><a href="' . home_url('/edit/') . '">' . $edit_svg . 'Edit</a></li>';
		if ( is_user_logged_in() ) {
			$items = $profile . $edit;
		} else {
			$items = $login;
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_items', 'wasmo_loginout_menu_link', 10, 2 );


function wasmo_login_redirect_page() {
  return home_url('/edit/');
}
add_filter('login_redirect', 'wasmo_login_redirect_page');

function wasmo_logout_redirect_page() {
  return home_url('/profiles/');
}
add_filter('logout_redirect', 'wasmo_logout_redirect_page');


// function my_acf_init() {
	
// }

// add_action('acf/init', 'my_acf_init');

/**
 * Plugin Name: Multisite: Password Reset on Local Blog
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
	$message = str_replace(get_site_url(1), get_site_url(), $message);
	$message = str_replace('circlecubes', 'wasmormon.org', $message);
	 
  	return $message;
}, 10, 2);

// fixes email title
add_filter("retrieve_password_title", function($title) {
	return "[" . wp_specialchars_decode(get_option('blogname'), ENT_QUOTES) . "] Password Reset";
});


/*

Custom user hook summary

Register
-add custom has_received_welcome and set to false

Login
-update Last login field
-check if has_received_welcome
-send welcome? 
-update has_received_welcome

Profile Save/Update
-update user nicename/displayname from acf fields
-resave values back to user acf fields
-clear directory transients
-send belated welcome email if is_admin and not yet received welcome
-update question counts if user includes any
-update last_save timestamp for this user
-increment save_count
-admin notify email
-redirect user to their own profile

*/

// custom wp-login logo
function wasmo_login_logo() { ?>
	<style type="text/css">
		#login h1 a, .login h1 a {
			background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/img/wasmormon-logo.png);
			height: 190px;
			width: 190px;
			background-size: 190px 190px;
			background-repeat: no-repeat;
			padding-bottom: 30px;
		}
	</style>
<?php }
add_action( 'login_enqueue_scripts', 'wasmo_login_logo' );

function wasmo_login_logo_url() {
	return home_url();
}
add_filter( 'login_headerurl', 'wasmo_login_logo_url' );

function wasmo_login_logo_url_title() {
	return 'wasmormon.org';
}
add_filter( 'login_headertext', 'wasmo_login_logo_url_title' );

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

function get_default_display_name_value($value, $post_id, $field) {
	if ( $value === NULL || $value === '' ) {
		$user_id = intval( substr( $post_id, 5 ) );
		$user_info = get_userdata( $user_id );
		$user_displayname = $user_info->display_name;
		
		$value = $user_displayname;
	}
	return $value;
}
add_filter('acf/load_value/name=display_name', 'get_default_display_name_value', 20, 3);

function get_default_profile_id_value($value, $post_id, $field) {
	if ( $value === NULL || $value === '' ) {
		$user_id = intval( substr( $post_id, 5 ) );
		$user_info = get_userdata( $user_id );
		$user_nicename = $user_info->user_nicename;
		
		$value = $user_nicename;
	}
	return $value;
}
add_filter('acf/load_value/name=profile_id', 'get_default_profile_id_value', 20, 3);

function wasmo_update_user( $post_id ) {
	// only for users - skip for posts etc
	if ( strpos( $post_id, 'user_' ) !== 0 ) {
		return;
	}

	$user_id = intval( substr( $post_id, 5 ) );

	// update user nicename/displayname from acf fields
	wp_update_user( array( 
		'ID' => $user_id, 
		'user_nicename' => sanitize_title( get_field( 'profile_id', 'user_'. $user_id ) ),
		'display_name' => sanitize_text_field( get_field( 'display_name', 'user_'. $user_id ) )
	) );
	// resave values back ot user acf fields
	$user_info = get_userdata( $user_id );
	$user_loginname = $user_info->user_login;
	$user_displayname = $user_info->display_name;
	$user_nicename = $user_info->user_nicename;
	update_field( 'display_name', $user_displayname, 'user_' . $user_id );
	update_field( 'profile_id', $user_nicename, 'user_' . $user_id );

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
			'nicename' => $user_nicename,
			'displayname' => $user_displayname,
			'savecount' => $save_count,
			'link' => get_author_posts_url( $user_id ),
		],
		'info'
	);

	//only if not edited by an admin
	if ( !current_user_can( 'administrator' ) ) {
		// notify email
		wasmo_send_admin_email__profile_update( $user_id, $save_count );
		// update last_save timestamp for this user
		update_user_meta( $user_id, 'last_save', time() );
		/*
		// if admin - check if welcome email has been sent.
		$has_received_welcome = get_user_meta( $user_id, 'has_received_welcome', true );
		if ( '' === $has_received_welcome ) {
			// if not - send a belated welcome email
			wasmo_send_user_email__belated_welcome( $user_id );
			update_user_meta( $user_id, 'has_received_welcome', true );
		}
		*/
	}

	wp_redirect( get_author_posts_url( $user_id ), 301);

	exit;
}
add_action( 'acf/save_post', 'wasmo_update_user', 10 );
add_action( 'acf/save_post', 'wasmo_update_spotlight', 10 );


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

function wasmo_send_user_email__welcome( $user_id ){
	$sitename = get_bloginfo( 'name' );
	$sitemail = get_bloginfo( 'admin_email' );
	$user_info = get_userdata( $user_id );
	// $user_loginname = $user_info->user_login;
	if ( $user_info ) {
		$user_displayname = $user_info->display_name;
		// $user_nicename = $user_info->user_nicename;
		$welcome_mail_to = $user_info->user_email;
		$welcome_headers = 'From: '. $sitemail;
		$welcome_mail_subject = 'Welcome to '.$sitename;
		$welcome_mail_message = $user_displayname . ', 

Welcome to ' . $sitename . '! We\'re glad you\'ve joined. Visit the following links (also found in the site header when you\'re logged in).

	Edit your proflie: ' . home_url('/edit/') . '
	View/share your profile: ' . get_author_posts_url( $user_id ) . ' (you can change this on your profile)

We are genuinely excited to meet you and read your story. Please, don\'t hesitate to reach out if you have any questions or suggestions to improve the site (you can reply to this email).

Best,
'. $sitename;
		// the send
		wp_mail( $welcome_mail_to, $welcome_mail_subject, $welcome_mail_message, $welcome_headers );
	}
}

function wasmo_send_user_email__belated_welcome( $user_id ){
	$sitename = get_bloginfo( 'name' );
	$sitemail = get_bloginfo( 'admin_email' );
	$user_info = get_userdata( $user_id );
	// $user_loginname = $user_info->user_login;
	if ( $user_info ) {
		$user_displayname = $user_info->display_name;
		// $user_nicename = $user_info->user_nicename;
		$welcome_mail_to = $user_info->user_email;
		$welcome_headers = 'From: '. $sitemail;
		$welcome_mail_subject = 'A belated welcome to '.$sitename;
		$welcome_mail_message = $user_displayname . ', 

Thank you for joining ' . $sitename . '! We want to personally welcome you to the site (sorry we\'re a little late). We hope you have appreciated seeing all the faith transition stories. We hope you will contribute your own profile too.

	Edit your proflie: ' . home_url('/edit/') . '
	View/share your own profile: ' . get_author_posts_url( $user_id ) . '

We are genuinely excited to meet you and read your story. Remember, you can return to the site anytime to update your story, so there is no need to write the whole thing in one go. Start with a basic couple sentences if you wish. Also, take note that there is a setting to control the visibility of your profile - you can select if you want your profile to be displayed publicly, only to other site members or even to not display it anywhere. 

Thank you again for being a part of our mission to share honest faith transitions. Please, don\'t hesitate to reach out if you have any questions or suggestions to improve the site.

Best,
'. $sitename . '

P.S. We have a few thoughts published on the site as well as a facebook page if you want to follow along.';
		// the send
		wp_mail( $welcome_mail_to, $welcome_mail_subject, $welcome_mail_message, $welcome_headers );
	}
}

function wasmo_send_admin_email__profile_update( $user_id, $save_count ){
	$user_info = get_userdata( $user_id );
	$user_nicename = $user_info->user_nicename;
	$notify_mail_to = get_bloginfo( 'admin_email' );
	$sitename = get_bloginfo( 'name' );
	$headers = 'From: '. $notify_mail_to;
	if ( $user_info ) {
		$notify_mail_message = '';
		if ( $save_count <= 1 ) {
			$notify_mail_subject = $sitename . ' New Profile Added: ' . $user_nicename;
			$notify_mail_message .= 'New profile created ';
		}
		if ( $save_count > 1 ) {
			$notify_mail_subject = $sitename . ' Profile Update (#' . $save_count . '): ' . $user_nicename;
			$notify_mail_message .= 'Profile updated ';
		}
		$notify_mail_message .= 'by ' . $user_nicename .': ' . get_author_posts_url( $user_id );
		// profile content
		ob_start();
		set_query_var( 'userid', $user_id );
		get_template_part( 'template-parts/content/content', 'usertext' );
		$notify_mail_message .= ob_get_clean();
		$notify_mail_message .= get_author_posts_url( $user_id );

		// send mail
		wp_mail( $notify_mail_to, $notify_mail_subject,  $notify_mail_message , $headers );
	}
}

function wasmo_get_profile_text( $userid ) {
	$profile_text = '';
	ob_start();
	set_query_var( 'userid', $userid );
	get_template_part( 'template-parts/content/content', 'usertext' );
	$profile_text .= ob_get_clean();
	return $profile_text;
}

function wasmo_register_add_meta($user_id) { 
	add_user_meta( $user_id, 'has_received_welcome', false );
}
add_action( 'user_register', 'wasmo_register_add_meta' );

function wasmo_first_user_login( $user_login, $user ) {
	$user_id = $user->ID;
	$has_received_welcome = get_user_meta( $user_id, 'has_received_welcome', true );
	if ( '' === $has_received_welcome || ! $has_received_welcome ) {
		wasmo_send_user_email__welcome( $user_id );
		update_user_meta( $user_id, 'has_received_welcome', true );
	}
}
add_action('wp_login', 'wasmo_first_user_login', 10, 2);


// https://github.com/wp-plugins/oa-social-login/blob/master/filters.txt
//This function will be called after Social Login has added a new user
function oa_social_login_do_after_user_insert ($user_data, $identity) {
	// These are the fields from the WordPress database
	// print_r($user_data);
	// This is the full social network profile of this user
	// print_r($identity);

	// record last login
	wasmo_user_lastlogin($user_data->user_login, $user_data);
	// send welcome?
	wasmo_first_user_login($user_data->user_login, $user_data);
}
add_action ('oa_social_login_action_after_user_insert', 'oa_social_login_do_after_user_insert', 10, 2);

//This function will be called before Social Login logs the user in
function oa_social_login_do_before_user_login ($user_data, $identity, $new_registration) {
	// record last login
	wasmo_user_lastlogin($user_data->user_login, $user_data);
	// send welcome?
	wasmo_first_user_login($user_data->user_login, $user_data);
}

add_action ('oa_social_login_action_before_user_login', 'oa_social_login_do_before_user_login', 10, 3);


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

		wasmo_post_navi();

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

function wasmo_post_navi() {
	if( !is_singular('post') ) {
    	return;
	}
	
	$prev_post = get_previous_post();
	$next_post = get_next_post();

	?>
	<h4 class="post-pagination">
		<span class="post-pagination-link post-pagination-prev">
			<?php
				if ( $prev_post ) {
					$prev_post_img = get_the_post_thumbnail( 
						$prev_post->ID, 
						'medium', 
						array('class' => 'pagination-prev')
					); 
					previous_post_link(
						'%link',
						twentynineteen_get_icon_svg( 'chevron_left', 22 ) . '<em>Previous Post</em><br>%title' . $prev_post_img
					);
				}
			?>
		</span>
		<span class="post-pagination-link post-pagination-next">
			<?php
				if ( $next_post ) {
					$next_post_img = get_the_post_thumbnail( 
						$next_post->ID, 
						'medium', 
						array('class' => 'pagination-next')
					);
					next_post_link(
						'%link',
						'<em>Next Post</em> '.twentynineteen_get_icon_svg( 'chevron_right', 22 ).'<br>%title' . $next_post_img
					);
				}
			?>
		</span>
	</h4>
	<?php
}

function wasmo_excerpt_link() {
	return '<a class="more-link button button-small" href="' . get_permalink() . '">Read more</a>';
}
add_filter( 'excerpt_more', 'wasmo_excerpt_link' );


add_shortcode( 'wasmo_directory', 'wasmo_directory_shortcode' );
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

/**
 * Add ACF options page
 */
if( function_exists('acf_add_options_page') ) {
	
	acf_add_options_page(array(
		'page_title' 	=> 'wasmo Settings',
		'menu_title'	=> 'wasmo Settings',
		'menu_slug' 	=> 'wasmo-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> false
	));
}

/**
 * Add callout to create a profile to the top of each post
 * Only when user is not logged in
 */
function wasmo_before_after($content) {
	if ( is_user_logged_in() || ! is_single() ) {
		return $content;
	}

	// top
	if ( get_field( 'before_post_callout', 'option' ) ) {
		$top_callout = '<div class="callout callout-top">';
		$top_callout .= get_field( 'before_post_callout', 'option' );
		$top_callout .= '<h5>Recent Profiles</h5>';
		ob_start();
		set_query_var( 'max_profiles', 4 );
		set_query_var( 'context', 'bumper' );
		get_template_part( 'template-parts/content/content', 'directory' );
		$top_callout .= ob_get_clean(); 
		$top_callout .= '</div>';
	} else {
		ob_start();
		?>
		<div class="callout callout-top">
			<h4>Thank you for visiting wasmormon.org!</h4>
			<p>This site is mainly a repository of mormon faith transition stories. Hearing others stories is therapeutic, check out the <a href="/profiles/">was mormon profiles</a>.</p>
			<p>Telling your own story is therapeutic too, consider joining the movement and <a class="register" href="/login/">tell your own story now</a>!</p>
		</div>
		<?php 
		$top_callout = ob_get_clean();
	}


	// bottom
	if ( get_field( 'after_post_callout', 'option' ) ) {
		$bottom_callout = '<div class="callout callout-bottom">' . get_field( 'after_post_callout', 'option' ) . '</div>';
	} else {
		ob_start();
		?>
		<div class="callout callout-bottom">
			<h4>Thank you for reading!</h4>
			<p>Don't forget to also check out the <a href="/profiles/">mormon faith transition stories</a>.</p>
			<div class="wp-block-button"><a class="wp-block-button__link" href="/login/">Tell Your Own Story</a></div>
		</div>
		<?php 
		$bottom_callout = ob_get_clean();
	}
	
	$fullcontent = $top_callout . $content . $bottom_callout;


	return $fullcontent;
}
add_filter('the_content', 'wasmo_before_after');


add_filter( 'wpseo_title', 'wasmo_filter_profile_wpseo_title' );
add_filter( 'wpseo_metadesc', 'wasmo_user_profile_wpseo_metadesc' );
add_filter( 'wpseo_opengraph_image', 'wasmo_user_profile_set_og_image' );
add_filter( 'wpseo_twitter_image', 'wasmo_user_profile_set_og_image' );

// filter to update user profile page title for seo
function wasmo_filter_profile_wpseo_title( $title ) {
	if( is_author() ) {
		$curauth = ( get_query_var( 'author_name' ) ) ? get_user_by( 'slug', get_query_var( 'author_name' ) ) : get_userdata( get_query_var( 'author' ) );
		$userid = $curauth->ID;
		$title = esc_html( get_field( 'hi', 'user_' . $userid ) ) . ' - ';
		$title .= 'Learn why I\'m no longer mormon at wasmormon.org';
	}
	return $title;
}

// filter to update user profile page description for seo
function wasmo_user_profile_wpseo_metadesc( $metadesc ) {
	if( is_author() ) {
		$curauth = ( get_query_var( 'author_name' ) ) ? get_user_by( 'slug', get_query_var( 'author_name' ) ) : get_userdata( get_query_var( 'author' ) );
		$userid = $curauth->ID;
		$metadesc = esc_html( get_field( 'tagline', 'user_' . $userid ) );
	}
	return $metadesc;
}

// Filter to update profile page open graph image to user profile image if there is one
function wasmo_user_profile_set_og_image( $image ) {
	// if author page
	if ( is_author() ) {
		$curauth = ( get_query_var( 'author_name' ) ) ? get_user_by( 'slug', get_query_var( 'author_name' ) ) : get_userdata( get_query_var( 'author' ) );
		$userid = $curauth->ID;
		// if has author image
		$userimg = get_field( 'photo', 'user_' . $userid );
		if ( $userimg ) {
			$image = wp_get_attachment_image_url( $userimg, 'large' );
		}
	}
	return $image;
}

	/**
	 * Replace links in text with html links
	 *
	 * @param  string $text Text to add links to
	 * @return string Text with links added
	 */
	function auto_link_text( $text )
	{
		$pattern = "#\b((?:https?:(?:/{1,3}|[a-z0-9%])|[a-z0-9.\-]+[.](?:com|net|org|edu|gov|mil|aero|asia|biz|cat|coop|info|int|jobs|mobi|museum|name|post|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|Ja|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)/)(?:[^\s()<>{}\[\]]+|\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\))+(?:\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\)|[^\s`!()\[\]{};:'.,<>?«»“”‘’])|(?:(?<!@)[a-z0-9]+(?:[.\-][a-z0-9]+)*[.](?:com|net|org|edu|gov|mil|aero|asia|biz|cat|coop|info|int|jobs|mobi|museum|name|post|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|Ja|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)\b/?(?!@)))#";
		return preg_replace_callback( $pattern, function( $matches ) {
			$url = array_shift( $matches );

			// force http if no protocol included
			if ( !startsWith( $url, 'http' ) ) {
				$url = 'http://' . $url;
			}

			// make link text from url - removing protocol
			$text = parse_url( $url, PHP_URL_HOST ) . parse_url( $url, PHP_URL_PATH );
			
			// remove the www from the link text
			$text = preg_replace( "/^www./", "", $text );

			// remove any long trailing path from url
			$last = -( strlen( strrchr( $text, "/" ) ) ) + 1;
			if ( $last < 0 ) {
				$text = substr( $text, 0, $last ) . "&hellip;";
			}

			// update 
			return sprintf(
				'<a rel="nofollow" target="_blank" href="%s">%s</a>', 
				$url, 
				$text
			);
		}, $text );
	}

	/**
	 * Check strings for starting match
	 *
	 * @param  string $string String to check.
	 * @param  string $startString Startin string to match.
	 * @return boolean Wether string begins with startString. 
	 */
	function startsWith( $string, $startString ) 
	{ 
		$len = strlen($startString); 
		return (substr($string, 0, $len) === $startString); 
	}


// Some custom structure to apply to the signup form page `local-signup` via NSUR plugin
function wasmo_before_signup() {
	?>
	<div class="site-content entry">
		<div class="entry-content">
			<h1>Register</h1>
			<p>Choose a username (lowercase letters and numbers only) and enter your email address.</p>
	<?php
}

add_action( 'before_signup_form', 'wasmo_before_signup', 10 );

function wasmo_after_signup() {
	echo '</div></div>';
}

add_action( 'after_signup_form', 'wasmo_after_signup', 10 );

// Random profile
add_action('init','wasmo_random_add_rewrite');
function wasmo_random_add_rewrite() {
	global $wp;
	$wp->add_query_var('randomprofile');
	add_rewrite_rule('random/?$', '?randomprofile=1', 'top');
}

add_action('template_redirect','wasmo_random_profile_template');
function wasmo_random_profile_template() {
   if (get_query_var('randomprofile')) {
			$args = array(
				'orderby'     => 'rand',
				// 'numberposts' => 1
			);
			$users = get_users( $args );
			foreach ( $users as $user ) {
				// check that user has content and is public
				if (
					! get_field( 'hi', 'user_' . $user->ID ) ||
					'false' === get_user_meta( $user->ID, 'in_directory', true )
					// 'private' === get_user_meta( $userid, 'in_directory', true ) ||
				) {
					continue;
				}
				$link = get_author_posts_url( $user->ID );
			}
			wp_redirect( $link, 307 );
			exit;
   }
}
add_action( 'pre_user_query', 'wasmo_random_user_query' );

function wasmo_random_user_query( $class ) {
    if( 'rand' == $class->query_vars['orderby'] )
        $class->query_orderby = str_replace( 'user_login', 'RAND()', $class->query_orderby );

    return $class;
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

// set up the block pattern category
// define theme specific block patterns in theme patterns folder
function wasmo_register_pattern_categories() {
	register_block_pattern_category(
		'wasmormon',
		array( 'label' => __( 'wasmormon', 'wasmo' ) )
	);
}
add_action( 'init', 'wasmo_register_pattern_categories' );
