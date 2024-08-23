<?php
require __DIR__ . '/vendor/autoload.php';

require_once( get_stylesheet_directory() . '/includes/wasmo-directory-widget.php' );
require_once( get_stylesheet_directory() . '/includes/wasmo-posts-widget.php' );
require_once( get_stylesheet_directory() . '/includes/updates.php' );

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
		"show_in_nav_menus" => false,
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
		"show_in_nav_menus" => false,
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

function wasmo_setup() {
	register_nav_menus(
		array(
			'utility' => __( 'Utility Menu', 'wasmo' ),
		)
	);
	set_theme_mod( 'image_filter', 0 );
}
add_action( 'after_setup_theme', 'wasmo_setup' );

// add hard coded utility menu items 
function wasmo_loginout_menu_link( $items, $args ) {
	if ($args->theme_location == 'utility') {
		$userid = get_current_user_id();
		$login = '<li class="login"><a href="' . home_url('/login/') . '" class="register">' . wasmo_get_icon_svg( 'join', 24 ) . __(" Join", 'wasmo') . '</a></li>';
		$login .= '<li class="login"><a href="' . home_url('/login/') . '" class="nav-login">' .  wasmo_get_icon_svg( 'login', 24 ) . __(" Login", 'wasmo') . '</a></li>';
		// $logout =  '<li class="logout"><a href="' . wp_logout_url() . '">' . __("Log Out", 'wasmo') . '</a></li>';
		$profile = '<li class="view"><a title="View Profile" href="' . get_author_posts_url( $userid ) . '">' . wasmo_get_icon_svg( 'person', 24 ) . 'View</a></li>';
		$edit = '<li class="edit"><a title="Edit Profile" href="' . home_url('/edit/') . '">' . wasmo_get_icon_svg( 'edit', 24 ) . 'Edit</a></li>';
		$post = '';
		$writeposts = get_field( 'i_want_to_write_posts', 'user_'.$userid );
		if ( 
			!empty( $writeposts ) &&
			'No thanks' !== $writeposts
			) {
			$post = '<li class="post"><a title="Submit Post" href="' . home_url('/wp-admin/post-new.php') . '">' . wasmo_get_icon_svg( 'edit-page', 24 ) . 'Submit Post</a></li>';
		}
		if ( is_user_logged_in() ) {
			$items = $profile . $edit . $post;
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


	//only if not edited by an admin
	if ( !current_user_can( 'administrator' ) ) {

		// update last_save timestamp for this user
		update_user_meta( $user_id, 'last_save', time() );

		// email notification to admin
		wasmo_send_admin_email__profile_update( $user_id, $save_count );
		
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

	// redirect to view the profile on save
	wp_safe_redirect( get_author_posts_url( $user_id, $userSlug ), 301);
	exit();
}

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

add_action( 'acf/save_post', 'wasmo_update_user', 10 );
add_action( 'acf/save_post', 'wasmo_update_spotlight', 10 );

// when a user has been deleted
function wasmo_delete_user( $user_id ) {
	// clear all directory transients
	wasmo_delete_transients_with_prefix( 'wasmo_directory-' );
}
add_action( 'delete_user', 'wasmo_delete_user' );

function wasmo_send_user_email__welcome( $user_id ){
	$sitename = get_bloginfo( 'name' );
	$sitemail = get_bloginfo( 'admin_email' );
	$user_info = get_userdata( $user_id );
	if ( $user_info ) {
		$user_displayname = $user_info->display_name;
		$welcome_mail_to = $user_info->user_email;
		$welcome_headers = 'From: '. $sitemail;
		$welcome_mail_subject = 'Welcome to '.$sitename;
		$welcome_mail_message = $user_displayname . ', 

Welcome to ' . $sitename . '! We\'re glad you\'ve joined. Visit the following links (also found in the site header when you\'re logged in).

	Edit your proflie: ' . home_url('/edit/') . '
	View/share your profile: ' . get_author_posts_url( $user_id ) . ' (you can change this url in your profile settings)

	Contribute articles: ' . admin_url( 'new-post.php' ) . '

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
	if ( $user_info ) {
		$user_displayname = $user_info->display_name;
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
// add_action ('oa_social_login_action_after_user_insert', 'oa_social_login_do_after_user_insert', 10, 2);

//This function will be called before Social Login logs the user in
function oa_social_login_do_before_user_login ($user_data, $identity, $new_registration) {
	// record last login
	wasmo_user_lastlogin($user_data->user_login, $user_data);
	// send welcome?
	wasmo_first_user_login($user_data->user_login, $user_data);
}
// add_action ('oa_social_login_action_before_user_login', 'oa_social_login_do_before_user_login', 10, 3);

// changing default gutenberg image block alignment to "center"
// function wasmo_change_default_gutenberg_image_block_options (){
// 	$block_type = WP_Block_Type_Registry::get_instance()->get_registered( "core/image" );
// 	$block_type->attributes['align']['default'] = 'center';
// }
// add_action( 'init', 'wasmo_change_default_gutenberg_image_block_options');

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

function wasmo_posted_by() {
	printf(
		/* translators: 1: SVG icon. 2: Post author, only visible to screen readers. 3: Author link. */
		'<span class="byline">%1$s<span class="screen-reader-text">%2$s</span><span class="author vcard"><a class="url fn n" href="%3$s">%4$s</a></span></span>',
		twentynineteen_get_icon_svg( 'person', 16 ),
		/* translators: Hidden accessibility text. */
		__( 'Posted by', 'twentynineteen' ),
		esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
		esc_html( get_the_author() )
	);
}

//override twentynineteen_entry_footer
function wasmo_entry_footer() {

	// Hide author, post date, category and tag text for pages.
	if ( 'post' === get_post_type() ) {
		$author = get_post_field( 'post_author', get_the_ID() );
		$user = get_user_by('id', $author);
		
		// Profile link
		if (has_category( 'spotlight', get_the_ID() ) && get_field( 'spotlight_for', get_the_ID() ) ) {
			$user_id = get_field( 'spotlight_for', get_the_ID() );
			$user = get_user_by( 'id', $user_id );
			?>
			<p>This post spotlights a real user's profile, please <a href="<?php echo get_author_posts_url($user_id); ?>">view the full profile for <?php echo $user->display_name;?> here</a>.</p>
			<?php
		}

		// Posted by
		if ( !$user->has_cap( 'manage_options' ) ) {
			twentynineteen_posted_by(); // hide author if admin
		}

		// Posted on
		twentynineteen_posted_on();

		/* translators: used between list items, there is a space after the comma. */
		$categories_list = get_the_category_list( __( ', ', 'wasmo' ) );
		if ( $categories_list ) {
			printf(
				/* translators: 1: SVG icon. 2: posted in label, only visible to screen readers. 3: list of categories. */
				'<span class="cat-links">%1$s<span class="screen-reader-text">%2$s</span>%3$s</span>',
				wasmo_get_icon_svg( 'archive', 16 ),
				__( 'Posted in', 'wasmo' ),
				$categories_list
			); // WPCS: XSS OK.
		}

		/* translators: used between list items, there is a space after the comma. */
		$tags_list = get_the_tag_list( '', __( ', ', 'wasmo' ) );
		if ( $tags_list ) {
			printf(
				/* translators: 1: SVG icon. 2: posted in label, only visible to screen readers. 3: list of tags. */
				'<span class="tags-links">%1$s<span class="screen-reader-text">%2$s </span>%3$s</span>',
				wasmo_get_icon_svg( 'tag', 16 ),
				__( 'Tags:', 'wasmo' ),
				$tags_list
			); // WPCS: XSS OK.
		}

		// Related Shelf items
		$shelf_list = get_the_term_list( get_the_ID(), 'shelf', '', ', ', '' );
		if ( $shelf_list ) {
			printf(
				/* translators: 1: SVG icon. 2: posted in label, only visible to screen readers. 3: list of tags. */
				'<span class="tags-links shelf-links"><span title="%2$s">%1$s<span class="screen-reader-text">%2$s </span></span>%3$s</span>',
				wasmo_get_icon_svg( 'shelf', 18, 'style="margin-top:-3px;"' ),
				__( 'Shelf items', 'wasmo' ),
				$shelf_list
			); // WPCS: XSS OK.
		}
		
		// Related Spectrum 
		$spectrum_list = get_the_term_list( get_the_ID(), 'spectrum', '', ', ', '' );
		if ( $spectrum_list ) {
			printf(
				/* translators: 1: SVG icon. 2: posted in label, only visible to screen readers. 3: list of tags. */
				'<span class="tags-links spectrum-links"><span title="%2$s">%1$s<span class="screen-reader-text">%2$s </span></span>%3$s</span>',
				wasmo_get_icon_svg( 'spectrum', 16 ),
				__( 'Mormon Spectrum', 'wasmo' ),
				$spectrum_list
			); // WPCS: XSS OK.
		}

		// Related Questions
		$question_list = get_the_term_list( get_the_ID(), 'question', '', '<br>' . wasmo_get_icon_svg( 'question', 18, 'style="margin-top:-3px;"'), '' );
		if ( $question_list ) {
			printf(
				/* translators: 1: SVG icon. 2: posted in label, only visible to screen readers. 3: list of tags. */
				'<span class="tags-links question-links"><span title="%2$s">%1$s<span class="screen-reader-text">%2$s </span></span>%3$s</span>',
				wasmo_get_icon_svg( 'question', 18, 'style="margin-top:-3px;"'),
				__( 'Questions', 'wasmo' ),
				$question_list
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
				__( 'Edit <span class="screen-reader-text">%s</span>', 'wasmo' ),
				array(
					'span' => array(
						'class' => array(),
					),
				)
			),
			get_the_title()
		),
		'<span class="edit-link">' . wasmo_get_icon_svg( 'edit', 16 ),
		'</span>'
	);
}

/**
 * Pagination Helper Method
 * 
 * @param Number $paged     Page Number
 * @param Number $max_page  Max Page
 * @param Boolean $profile   Flag for profile nav, this updates the baseurl and format so they work for the custom pagination
 * @return String Pagination links
 */
function wasmo_pagination( $paged = '', $max_page = '', $profiles = false ) {
	$big = 999999999; // need an unlikely integer

	if( ! $paged ) {
		$paged = get_query_var('paged');
	}

	if( ! $max_page ) {
		global $wp_query;
		$max_page = isset( $wp_query->max_num_pages ) ? $wp_query->max_num_pages : 1;
	}

	if ( $max_page > 7 ) {
		$show_all = false;
	} else {
		$show_all = true;
	}
	$base_url = str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) );
	$format   = '?paged=%#%';

	if ( $profiles ) {
		$base_url = get_permalink( get_page_by_path( 'profiles' ) ) . 'page/%_%';
		$format   = '%#%';
	}
	$paginated_links = paginate_links( 
		array(
			'base'      => $base_url,
			'format'    => $format,
			'current'   => max( 1, $paged ),
			'total'     => $max_page,
			'mid_size'  => 1,
			'end_size'  => 1,
			'prev_text' => sprintf(
				'%s <span class="screen-reader-text">%s</span>',
				wasmo_get_icon_svg( 'chevron_left', 22 ),
				__( 'Newer', 'twentynineteen' )
			),
			'next_text' => sprintf(
				'<span class="screen-reader-text">%s</span> %s',
				__( 'Older', 'twentynineteen' ),
				wasmo_get_icon_svg( 'chevron_right', 22 )
			),
			'type'      => 'list',
			'show_all'  => $show_all,
		)
	);

	return '<div class="wasmo-pagination">' . $paginated_links . '</div>';
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
						wasmo_get_icon_svg( 'chevron_left', 22 ) . '<em>Older Post</em><span class="adjacent-post"><span class="adjacent-post-title">%title</span>' . $prev_post_img . '</span>'
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
						'<em>Newer Post</em> '.wasmo_get_icon_svg( 'chevron_right', 22 ).'<span class="adjacent-post"><span class="adjacent-post-title">%title</span>' . $next_post_img . '</span>'
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
}

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
 * Add callout to create a profile to the top of each post
 * Only when user is not logged in
 */
function wasmo_before_after($content) {
	// skip if
	if (
		is_user_logged_in() || // logged in or
		!is_single() || // not a single post or
		!is_main_query() || // not the main loop or
		is_embed() // is a post embed
	) {
		return $content;
	}

	// top
	if ( get_field( 'before_post_callout', 'option' ) ) {
		$top_callout = '<aside class="callout callout-top">';
		$top_callout .= get_field( 'before_post_callout', 'option' );
		$top_callout .= '<h5>Recent Profiles</h5>';
		ob_start();
		set_query_var( 'max_profiles', 4 );
		set_query_var( 'context', 'bumper' );
		get_template_part( 'template-parts/content/content', 'directory' );
		$top_callout .= ob_get_clean(); 
		$top_callout .= '</aside>';
	} else {
		ob_start();
		?>
		<aside class="callout callout-top">
			<h4>Thank you for visiting wasmormon.org!</h4>
			<p>This site is mainly a repository of mormon faith transition stories. Hearing others stories is therapeutic, check out the <a href="/profiles/">was mormon profiles</a>.</p>
			<p>Telling your own story is therapeutic too, consider joining the movement and <a class="register" href="/login/">tell your own story now</a>!</p>
		</aside>
		<?php 
		$top_callout = ob_get_clean();
	}

	// bottom
	if ( get_field( 'after_post_callout', 'option' ) ) {
		$bottom_callout = '<aside class="callout callout-bottom">' . get_field( 'after_post_callout', 'option' ) . '</aside>';
	} else {
		ob_start();
		?>
		<aside class="callout callout-bottom">
			<h4>Thank you for reading!</h4>
			<p>Don't forget to also check out the <a href="/profiles/">mormon faith transition stories</a>.</p>
			<div class="wp-block-button"><a class="wp-block-button__link" href="/login/">Tell Your Own Story</a></div>
		</aside>
		<?php 
		$bottom_callout = ob_get_clean();
	}
	
	$fullcontent = $top_callout . $content . $bottom_callout;

	return $fullcontent;
}
add_filter( 'the_content', 'wasmo_before_after' );

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
function auto_link_text( $text ) {
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
			'<a rel="nofollow ugc" target="_blank" href="%s">%s</a>', 
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
function startsWith( $string, $startString ) {
	$len = strlen($startString); 
	return (substr($string, 0, $len) === $startString); 
}

/**
 * Replace pseudo __HTML__ in text with elements
 *
 * @param  string $text Text to add hrs to
 * @return string Text with hrs added
 */
function auto_htmlize_text( $text ) {
	
	$patterns = array(
		'<p>__HR__</p>', // make hr
		'BLOCKQUOTE__', // open blockquote
		'__BLOCKQUOTE', // close blockquote
		'CITE__', // open cite
		'__CITE', // close cite
		'STRONG__', // open strong
		'__STRONG', // close strong
		'EM__', // open italics
		'__EM', // close italics
	);
	$replacements = array(
		'<hr class="wp-block-separator profile-hr" />',
		'<blockquote class="wp-block-quote profile-blockquote">',
		'</blockquote>',
		'<cite class="profile-cite">',
		'</cite>',
		'<strong class="profile-strong">',
		'</strong>',
		'<em class="profile-em">',
		'</em>',
	);
	return str_replace( $patterns, $replacements, $text );
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
			wp_redirect( wasmo_get_random_profile_url(), 307 );
			exit;
   }
}
add_action( 'pre_user_query', 'wasmo_random_user_query' );

function wasmo_get_random_profile_url() {
	$args = array(
		'orderby'     => 'rand',
		// 'numberposts' => 1
	);
	$users = get_users( $args );
	foreach ( $users as $user ) {
		// check that user has content and is public
		if (
			! get_field( 'hi', 'user_' . $user->ID ) ||
			'private' === get_user_meta( $user->ID, 'in_directory', true ) ||
			'false' === get_user_meta( $user->ID, 'in_directory', true )
		) {
			continue;
		}
		return get_author_posts_url( $user->ID );
	}
}

function wasmo_random_user_query( $class ) {
	if( 'rand' == $class->query_vars['orderby'] ) {
		$class->query_orderby = str_replace(
			'user_login',
			'RAND()',
			$class->query_orderby
		);
	}
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

// hide admin menu items for non admin users
function wasmo_remove_menu_items() {
	if ( !current_user_can( 'administrator' ) ) : // IF NON ADMIN USER
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

// hide admin bar for non admin users
function wasmo_hide_admin_bar() {
	if ( !current_user_can( 'publish_posts' ) ) {
		show_admin_bar( false );
	}
}
// add_action( 'set_current_user', 'wasmo_hide_admin_bar' );

// hide admin bar for non admin users
// function remove_admin_bar() {
// 	if ( !current_user_can('administrator') && !is_admin() ) {
// 		show_admin_bar( false );
// 	}
// }
// add_action('after_setup_theme', 'remove_admin_bar');

// remove links/menus from the admin bar
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

// only show users own posts
function posts_for_current_author($query) {
	global $pagenow;
 
	if( 'edit.php' != $pagenow || !$query->is_admin )
		return $query;
 
	if( !current_user_can( 'edit_others_posts' ) ) {
		global $user_ID;
		$query->set('author', $user_ID );
		add_filter('views_edit-post', 'wasmo_fix_post_counts');
	}
	return $query;
}
add_filter('pre_get_posts', 'posts_for_current_author');

// Fix post counts
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
 * Allow author pages to be indexed, unless unlisted
 */
add_filter( 'wp_robots', function( $robots ) {
	if ( is_author() ) {
		$curauth = ( get_query_var('author_name') ) ? get_user_by( 'slug', get_query_var( 'author_name' ) ) : get_userdata( get_query_var( 'author' ) );
		$userid = $curauth->ID;

		// check privacy setting, noindex if not public
		$in_directory = get_field( 'in_directory', 'user_' . $userid );
		if(
			!$in_directory ||
			'false' === $in_directory ||
			'private' === $in_directory
		) {
			$robots['noindex'] = true;
		} else {
			// otherwise, index
			$robots['noindex'] = false;
		}
	}
	return $robots;
});

/**
 * Filter authors in sitemap to include all who are public and have required fields
 */
add_filter('aioseo_sitemap_author_archives', function( $entries ) {
	$authors = get_users();
	$entries = [];
	foreach ( $authors as $author ) {
		// check that profile has required content
		// require both hi and tagline content, bail early if not present
		if (
			!get_field( 'hi', 'user_' . $author->ID ) ||
			!get_field( 'tagline', 'user_' . $author->ID )
		) {
			continue;
		}

		// check privacy setting, bail if not public
		$in_directory = get_field( 'in_directory', 'user_' . $author->ID );
		if(
			!$in_directory ||
			'false' === $in_directory ||
			'private' === $in_directory
		) {
			continue;
		}

		// get the last save time
		$last_save = intval( get_user_meta( $author->ID, 'last_save', true ) );

		// $user = get_userdata( $author->ID );
		// $registered = strtotime( $user->user_registered );
		$entries[] = [
			'loc'        => get_author_posts_url( $author->ID ),
			'lastmod'    => $last_save ? date('Y-m-d', $last_save ) : false,
			'changefreq' => aioseo()->sitemap->priority->frequency( 'author' ),
			'priority'   => aioseo()->sitemap->priority->priority( 'author' ),
		];
	}
	return $entries;
});

/**
 * Filter aioseo description for profile pages with acf custom fields
 */
add_filter( 'aioseo_description', function ( $description ) {
	if ( is_author() ) {
		$curauth = ( get_query_var('author_name') ) ? get_user_by( 'slug', get_query_var( 'author_name' ) ) : get_userdata( get_query_var( 'author' ) );
		$userid = $curauth->ID;
    	$description .= get_field( 'hi', 'user_' . $userid ) . '. ' . get_field( 'tagline', 'user_' . $userid );
	}
	return $description;
});

/**
 * Add og meta values for author pages
 */
add_action( 'wp_head', function () {
	if ( is_author() ) {
		$curauth = ( get_query_var('author_name') ) ? get_user_by( 'slug', get_query_var( 'author_name' ) ) : get_userdata( get_query_var( 'author' ) );
		$userid = $curauth->ID;
		?>
		<meta property="og:title" content="I'm <?php echo $curauth->display_name; ?> and I was a mormon. A wasmormon.org profile." />
		<meta property="og:description" content="<?php echo get_field( 'hi', 'user_' . $userid ); ?> <?php echo get_field( 'tagline', 'user_' . $userid ); ?> Read my story to learn why I left the mormon church." />
		<meta property="og:type" content="profile" />
		<meta property="og:site_name" content="wasmormon.org" />
		<meta property="og:url" content="<?php echo get_author_posts_url( $userid ); ?>" />
		<meta property="og:image" content="<?php echo wasmo_get_user_image_url( $userid ); ?>" />
		<?php
	}
});

/**
 * Get user image url
 * 
 * @param Number $userid the user's id
 * @return String url to image
 */
function wasmo_get_user_image_url( $userid ) {
	$userimg = get_field( 'photo', 'user_' . $userid );
	if ( $userimg ) {
		return wp_get_attachment_image_url( $userimg, 'medium' );
	} else {
		$user = get_userdata( $userid );
		$hash = md5( strtolower( trim( $user->user_email ) ) );
		$default_img = urlencode( 'https://raw.githubusercontent.com/circlecube/wasmo-theme/main/img/default.png' );
		$gravatar = $hash . '?s=300&d='.$default_img;
		return "https://www.gravatar.com/avatar/" . $gravatar;
	}
}

/**
 * Get user image
 * 
 * @param Number $userid The user's id.
 * @param Boolean $isItempropImage Flag to determine wether to include itemProp=image (for structured data) (default false).
 * @return String html for image tag
 */
function wasmo_get_user_image( $userid, $isItempropImage = false ) {
	$userimg = get_field( 'photo', 'user_' . $userid );
	$user = get_userdata( $userid );
	$alt = $user->display_name . ' profile image for wasmormon.org';

	if ( $userimg ) {
		return wp_get_attachment_image( $userimg, 'medium', false, array(
			'alt' => $alt,
			'itemProp' => $isItempropImage ? 'image' : '',
		) );
	} else {
		$img_url = wasmo_get_user_image_url( $userid );
		$atts = $isItempropImage ? 'itemProm="image"' : '';
		return '<img src="' . $img_url . '" alt="' . $alt . '" ' . $atts . '>';
	}
}

/**
 * Send out email depending on who updates the status of the post.
 * 
 * New post created by user, contributor receives a confirmation email
 * Post submitted by user, contributor receives a confirmation email
 * Submitted post is scheduled to be published, contributor receives a confiramtion email
 * Submitted post is published, contributor receives a confiramtion email
 * 
 * Post submitted by user, admin receives notice of submitted post
 *
 * @param String  $new_status New post status.
 * @param String  $old_status Old post status.
 * @param WP_Post $post Post object.
 */
function wasmo_pending_submission_notifications_send_email( $new_status, $old_status, $post ) {
	if ( $new_status === $old_status ) { // bail if status has not changed
		return;
	}

	$admin_email  = get_bloginfo( 'admin_email' );
	$headers      = 'From: '. $admin_email;
	$user         = get_userdata( $post->post_author );
	$user_email   = $user->user_email;
	$url          = get_permalink( $post->ID );
	$edit_link    = get_edit_post_link( $post->ID, '' );
	$preview_link = get_permalink( $post->ID ) . '&preview=true';
	$last_edit    = get_the_modified_author();
	$status       = get_post_status( $post->ID );
	$datetime     = get_post_datetime( $post->ID );

	if ( // Notify Admin that Non-Admin has written a post.
		( 'new' === $new_status || 'draft' === $new_status || 'pending' === $new_status ) &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'New post submission pending review', 'wasmo' ) . ': "' . $post->post_title . '"';
		$message  = __( 'A new submission is pending review.', 'wasmo' );
		$message .= "\r\n\r\n";
		$message .= __( 'Author', 'wasmo' ) . ': ' . $user->user_login . " : " . $user->display_name . "\r\n";
		$message .= __( 'Profile', 'wasmo' ) . ': ' . get_author_posts_url( $user->ID ) . "\r\n";
		$message .= __( 'Title', 'wasmo' ) . ': ' . $post->post_title . "\r\n";
		$message .= __( 'Status', 'wasmo' ) . ': ' . $status . "\r\n";
		$message .= __( 'Last edited by', 'wasmo' ) . ': ' . $last_edit . "\r\n";
		$message .= __( 'Last edit date', 'wasmo' ) . ': ' . $post->post_modified;
		$message .= "\r\n\r\n";
		$message .= __( 'Edit the submission', 'wasmo' ) . ': ' . $edit_link . "\r\n";
		$message .= __( 'Preview the submission', 'wasmo' ) . ': ' . $preview_link;
		$result   = wp_mail( $admin_email, $subject, $message, $headers );
	}
	
	if ( // Notify Non-admin that Admin has published their post.
		( 'pending' === $old_status || 'future' === $old_status || 'draft' === $old_status ) &&
		'publish' === $new_status &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'The post you submitted is now live!', 'wasmo' );
		$message  = '"' . $post->post_title . '" ' . __( 'is now published on wasmormon.org', 'wasmo' ) . "! \r\n\r\n";
		$message .= $url;
		$message .= "\r\n\r\n";
		$message .= __( 'It is displayed as a link on your profile page', 'wasmo' ) . ': ' . get_author_posts_url( $user->ID ) . "\r\n";
		$message .= __( 'Have more to say? Start another post', 'wasmo' ) . ': ' . admin_url( 'post-new.php' ) . "\r\n";
		$message .= __( 'Reply to this email if you have any questions or suggestions.', 'wasmo' ) . "\r\n";
		$message .= __( 'Best,', 'wasmo' ) . "\r\n" . $sitename . "\r\n\r\n";
		$result   = wp_mail( $user_email, $subject, $message, $headers );
	}
	elseif ( // Notify Non-admin that Admin has scheduled their post.
		( 'pending' === $old_status || 'draft' === $old_status ) &&
		'future' === $new_status &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'The post you submitted is now scheduled!', 'wasmo' );
		$message  = '"' . $post->post_title . '" ' . __( 'is now scheduled to be published on wasmormon.org', 'wasmo' ) . "! \r\n\r\n";
		$message .= $url;
		$message .= "\r\n\r\n";
		$message .= __( 'Take a look and let us know if anything needs updating. Preview the post', 'wasmo' ) . ': ' . $preview_link;
		$message .= __( 'It will display as a link on your profile page', 'wasmo' ) . ': ' . get_author_posts_url( $user->ID ) . "\r\n";
		$message .= __( 'Have more to say? Start a new post', 'wasmo' ) . ': ' . admin_url( 'post-new.php' ) . "\r\n";
		$message .= __( 'Reply to this email if you have any questions or suggestions.', 'wasmo' ) . "\r\n\r\n";
		$message .= __( 'Best,', 'wasmo' ) . "\r\n" . $sitename . "\r\n\r\n";
		$result   = wp_mail( $user_email, $subject, $message, $headers );
	}
	elseif ( // Notify non-admin that they submitted a post for review
		'pending' === $new_status && 'draft' === $new_status &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'You submitted a post!', 'wasmo' );
		$message  = __( 'Thank you for submitting a post!', 'wasmo' );
		$message .= "\r\n\r\n";
		$message .= '"' . $post->post_title . '" ' . __( 'is now submitted to wasmormon.org', 'wasmo' ) . "! \r\n\r\n";
		$message .= "\r\n\r\n";
		$message .= __( 'We\'ll create graphics, get it worked into the publishing schedule, and let you know when it is published. ', 'wasmo' );
		$message .= __( 'Once it is published, it will display on your profile! ', 'wasmo' ) . "\r\n";
		$message .= __( 'Have more to say? Start a new post', 'wasmo' ) . ': ' . admin_url( 'post-new.php' ) . "\r\n\r\n";
		$message .= __( 'Reply to this email if you have any questions or suggestions.', 'wasmo' ) . "\r\n\r\n";
		$message .= __( 'Best,', 'wasmo' ) . "\r\n" . $sitename . "\r\n\r\n";
		$result   = wp_mail( $user_email, $subject, $message, $headers );
	}
	elseif ( // Notify non-admin that they created a post
		( 'new' === $new_status || 'draft' === $new_status ) &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'You created a post!', 'wasmo' );
		$message  = __( 'Thank you for creating a post!', 'wasmo' );
		$message .= '"' . $post->post_title . '" ' . __( 'is now saved as a draft on wasmormon.org', 'wasmo' ) . "! \r\n\r\n";
		$message .= "\r\n\r\n";
		$message .= __( 'Once it is ready, submit the post for review. We\'ll help create graphics and get it worked into the publishing schedule. ', 'wasmo' );
		$message .= __( 'Once it is published, it will display on your profile! ', 'wasmo' ) . "\r\n\r\n";
		$message .= __( 'Edit the post', 'wasmo' ) . ': ' . $edit_link . "\r\n";
		$message .= __( 'Have more to say? Start a new post', 'wasmo' ) . ': ' . admin_url( 'post-new.php' ) . "\r\n\r\n";
		$message .= __( 'Reply to this email if you have any questions or suggestions.', 'wasmo' ) . "\r\n\r\n";
		$message .= __( 'Best,', 'wasmo' ) . "\r\n" . $sitename . "\r\n\r\n";
		$result   = wp_mail( $user_email, $subject, $message, $headers );
	}
}
add_action( 'transition_post_status', 'wasmo_pending_submission_notifications_send_email', 10, 3 );

// Remove admin notices for non admin users
function wasmo_hide_notices(){
	if ( !current_user_can('administrator') ) {
		remove_all_actions( 'admin_notices' );
	}
}	
add_action( 'admin_head', 'wasmo_hide_notices', 1 );


/**
 * Icon svg method for wasmo theme.
 * 
 * @param String $icon string value.
 * @param Number $size number pixel value.
 * @param String $styles a styles attribute for any custom styles, such as `style="margin-left:20px;"`.
 * @return String svg element.
 */
function wasmo_get_icon_svg( $icon, $size = 24, $styles = '' ) {
	// map taxonomies to an icon
	switch ($icon) {
		case 'shelf':
			$icon = 'flag';
			break;
		case 'spectrum':
			$icon = 'nametag';
			break;
		case 'question':
			$icon = 'help';
			break;
	}

	// collected from https://github.com/WordPress/dashicons/tree/master/sources/svg
	$arr = array(

		'warning' => /* warning - dashicon */ '
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 20 20">
	<path d="M10 2c4.42 0 8 3.58 8 8s-3.58 8-8 8-8-3.58-8-8 3.58-8 8-8zM11.13 11.38l0.35-6.46h-2.96l0.35 6.46h2.26zM11.040 14.74c0.24-0.23 0.37-0.55 0.37-0.96 0-0.42-0.12-0.74-0.36-0.97s-0.59-0.35-1.060-0.35-0.82 0.12-1.070 0.35-0.37 0.55-0.37 0.97c0 0.41 0.13 0.73 0.38 0.96 0.26 0.23 0.61 0.34 1.060 0.34s0.8-0.11 1.050-0.34z"/>
</svg>',

		'help'    => /* help dashicon */'
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 20 20">
	<path d="M17 10c0-3.87-3.14-7-7-7-3.87 0-7 3.13-7 7s3.13 7 7 7c3.86 0 7-3.13 7-7zM10.7 11.48h-1.56v-0.43c0-0.38 0.080-0.7 0.24-0.98s0.46-0.57 0.88-0.89c0.41-0.29 0.68-0.53 0.81-0.71 0.14-0.18 0.2-0.39 0.2-0.62 0-0.25-0.090-0.44-0.28-0.58-0.19-0.13-0.45-0.19-0.79-0.19-0.58 0-1.25 0.19-2 0.57l-0.64-1.28c0.87-0.49 1.8-0.74 2.77-0.74 0.81 0 1.45 0.2 1.92 0.58 0.48 0.39 0.71 0.91 0.71 1.55 0 0.43-0.090 0.8-0.29 1.11-0.19 0.32-0.57 0.67-1.11 1.060-0.38 0.28-0.61 0.49-0.71 0.63-0.1 0.15-0.15 0.34-0.15 0.57v0.35zM9.23 14.22c-0.18-0.17-0.27-0.42-0.27-0.73 0-0.33 0.080-0.58 0.26-0.75s0.43-0.25 0.77-0.25c0.32 0 0.57 0.090 0.75 0.26s0.27 0.42 0.27 0.74c0 0.3-0.090 0.55-0.27 0.72-0.18 0.18-0.43 0.27-0.75 0.27-0.33 0-0.58-0.090-0.76-0.26z"/>
</svg>',

		'flag'   => /* flag dashicon */ '
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 20 20">
	<path d="M5 18v-15h-2v15h2zM6 12v-8c3-1 7 1 11 0v8c-3 1.27-8-1-11 0z"/>
</svg>',

		'nametag' => /* dashicon nametag */ '
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" viewBox="0 0 20 20">
	<path d="M12 5v-3c0-0.55-0.45-1-1-1h-2c-0.55 0-1 0.45-1 1v3c0 0.55 0.45 1 1 1h2c0.55 0 1-0.45 1-1zM10 2c0.55 0 1 0.45 1 1s-0.45 1-1 1-1-0.45-1-1 0.45-1 1-1zM18 15v-8c0-1.1-0.9-2-2-2h-3v0.33c0 0.92-0.75 1.67-1.67 1.67h-2.66c-0.92 0-1.67-0.75-1.67-1.67v-0.33h-3c-1.1 0-2 0.9-2 2v8c0 1.1 0.9 2 2 2h12c1.1 0 2-0.9 2-2zM17 9v6h-14v-6h14zM9 11c0-0.55-0.22-1-0.5-1s-0.5 0.45-0.5 1 0.22 1 0.5 1 0.5-0.45 0.5-1zM12 11c0-0.55-0.22-1-0.5-1s-0.5 0.45-0.5 1 0.22 1 0.5 1 0.5-0.45 0.5-1zM6.040 12.21c0.92 0.48 2.34 0.79 3.96 0.79s3.040-0.31 3.96-0.79c-0.21 1-1.89 1.79-3.96 1.79s-3.75-0.79-3.96-1.79z"></path>
</svg>',

		'edit-page' => /* dashicon edit-page */ '
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" viewBox="0 0 20 20" style="enable-background:new 0 0 20 20;" xml:space="preserve">
   <path d="M4,5H2v13h10v-2H4V5z M17.9,3.4l-1.3-1.3C16.2,1.7,15.5,1.6,15,2l0,0l-1,1H5v12h9V9l4-4l0,0C18.4,4.5,18.3,3.8,17.9,3.4z M12.2,9.4l-2.5,0.9l0.9-2.5L15,3.4L16.6,5L12.2,9.4z"/>
</svg>',

		'location' => /* dashicon location */ '
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" viewBox="0 0 20 20">
<path d="M10 2c-3.31 0-6 2.69-6 6 0 2.020 1.17 3.71 2.53 4.89 0.43 0.37 1.18 0.96 1.85 1.83 0.74 0.97 1.41 2.010 1.62 2.71 0.21-0.7 0.88-1.74 1.62-2.71 0.67-0.87 1.42-1.46 1.85-1.83 1.36-1.18 2.53-2.87 2.53-4.89 0-3.31-2.69-6-6-6zM10 4.56c1.9 0 3.44 1.54 3.44 3.44s-1.54 3.44-3.44 3.44-3.44-1.54-3.44-3.44 1.54-3.44 3.44-3.44z"></path>
</svg>',

		'join'   => '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
	<path d="M0 0h24v24H0z" fill="none"/><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
</svg>',

		'login'  => '
<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" viewBox="0 0 24 24">
	<g>
		<rect fill="none" height="24" width="24"/>
	</g>
	<g>
		<path d="M11,7L9.6,8.4l2.6,2.6H2v2h10.2l-2.6,2.6L11,17l5-5L11,7z M20,19h-8v2h8c1.1,0,2-0.9,2-2V5c0-1.1-0.9-2-2-2h-8v2h8V19z"/>
	</g>
</svg>',

		'link'   => /* material-design – link */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M0 0h24v24H0z" fill="none"></path>
    <path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"></path>
</svg>',

		'watch'  => /* material-design – watch-later */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <defs>
        <path id="a" d="M0 0h24v24H0V0z"></path>
    </defs>
    <clipPath id="b">
        <use xlink:href="#a" overflow="visible"></use>
    </clipPath>
    <path clip-path="url(#b)" d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm4.2 14.2L11 13V7h1.5v5.2l4.5 2.7-.8 1.3z"></path>
</svg>',

		'archive' => /* material-design – folder */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"></path>
    <path d="M0 0h24v24H0z" fill="none"></path>
</svg>',

		'tag' => /* material-design – local_offer */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
	<path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"></path>
	<path d="M0 0h24v24H0z" fill="none"></path>
</svg>',

		'comment' => /* material-design – comment */ '
<svg viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <path d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18z"></path>
    <path d="M0 0h24v24H0z" fill="none"></path>
</svg>',

		'person' => /* material-design – person */ '
<svg viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path>
    <path d="M0 0h24v24H0z" fill="none"></path>
</svg>',

		'edit' => /* material-design – edit */ '
<svg viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"></path>
    <path d="M0 0h24v24H0z" fill="none"></path>
</svg>',

		'chevron_left' => /* material-design – chevron_left */ '
<svg viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"></path>
    <path d="M0 0h24v24H0z" fill="none"></path>
</svg>',

		'chevron_right' => /* material-design – chevron_right */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"></path>
    <path d="M0 0h24v24H0z" fill="none"></path>
</svg>',

		'check' => /* material-design – check */ '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M0 0h24v24H0z" fill="none"></path>
    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path>
</svg>',

	);

	if ( array_key_exists( $icon, $arr ) ) {
		$repl = sprintf( 
			'<svg class="svg-icon" width="%d" height="%d" aria-hidden="true" role="img" focusable="false" %s ',
			$size,
			$size,
			$styles
		);
		$svg  = preg_replace( '/^<svg /', $repl, trim( $arr[ $icon ] ) ); // Add extra attributes to SVG code.
		$svg  = preg_replace( "/([\n\t]+)/", ' ', $svg ); // Remove newlines & tabs.
		$svg  = preg_replace( '/>\s*</', '><', $svg );    // Remove whitespace between SVG tags.
		return $svg;
	}

	return null;
}

remove_filter('term_description','wpautop');

// add tags for attachments
function add_tags_for_attachments() {
    register_taxonomy_for_object_type( 'post_tag', 'attachment' );
}

add_action( 'init' , 'add_tags_for_attachments' );

// Modify the main query object
function wasmo_media_in_main_query( $query ) {
	if ( $query->is_archive() && $query->is_main_query() ) { // only run on archive queries
		$query->set( 'post_type', array( 'post', 'attachment' ) ); // add attachment post types, media
		$query->set( 'post_status', array( 'publish', 'inherit' ) ); // add inherit post status since that is the default status of media
	}
}
// Hook my above function to the pre_get_posts action
add_action( 'pre_get_posts', 'wasmo_media_in_main_query' );

/**
 * Update amazon links with associate tag
 */
function add_zon_tag($content, $tag = 'circubstu-20' ) {
	$all_links = wp_extract_urls( $content );
	$zon_links = array_filter( 
		$all_links,
		function ($link) {
		if ( str_contains( $link, 'amazon.' ) ||  str_contains( $link, 'amzn.to' ) ) {
			return true;
		}
		return false;
		}
	);
	foreach( $zon_links as $link ) {
		$content = str_replace( $link, add_query_arg('tag', $tag, $link), $content );
	}
	return $content;
}
add_filter( 'the_content', 'add_zon_tag' );

require_once( get_stylesheet_directory() . '/includes/spotlight-posts-admin-page.php' );
require_once( get_stylesheet_directory() . '/includes/contributor-users-admin-page.php' );
require_once( get_stylesheet_directory() . '/includes/contributor-posts-admin-page.php' );

function update_contributor_capabilities() {
	// gets the contributor role
	$contributors = get_role( 'contributor' );
	$contributors->add_cap( 'read_private_pages' );
	$contributors->add_cap( 'read_private_posts' );
}
add_action( 'admin_init', 'update_contributor_capabilities');