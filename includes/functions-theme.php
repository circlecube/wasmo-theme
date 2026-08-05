<?php

/**
 * Enqueue styles - get parent theme styles first.
 */
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
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_script(
		'wasmo-script',
		get_stylesheet_directory_uri() . '/js/script.js',
		null,
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'wasmo_enqueue' );

/**
 * Add google fonts
 *
 * https://fonts.google.com/specimen/Crimson+Text
 * https://fonts.google.com/specimen/Open+Sans
 * https://fonts.google.com/specimen/Josefin+Sans
 */
function wasmo_add_google_fonts() {
	wp_enqueue_style(
		'wasmo-google-fonts',
		'https://fonts.googleapis.com/css?family=Josefin+Sans:ital,wght@0,100..700;1,100..700|Crimson+Text:400,700|Open+Sans:400,700&display=swap',
		false
	);
}
add_action( 'wp_enqueue_scripts', 'wasmo_add_google_fonts' );
/**
 * Setup theme
 */
function wasmo_setup() {
	register_nav_menus(
		array(
			'utility' => __( 'Utility Menu', 'wasmo' ),
		)
	);
	set_theme_mod( 'image_filter', 0 );
}
add_action( 'after_setup_theme', 'wasmo_setup' );

/**
 * Add hard coded utility menu items
 *
 * @param string $items The menu items.
 * @param object $args The menu arguments.
 * @return string The modified menu items.
 */
function wasmo_loginout_menu_link( $items, $args ) {
	if ( $args->theme_location == 'utility' ) {
		$userid = get_current_user_id();

		$login  = '<li class="login"><a href="' . home_url( '/login/' ) . '" class="register">' . wasmo_get_icon_svg( 'join', 24 ) . __( ' Join', 'wasmo' ) . '</a></li>';
		$login .= '<li class="login"><a href="' . home_url( '/login/' ) . '" class="nav-login">' . wasmo_get_icon_svg( 'login', 24 ) . __( ' Login', 'wasmo' ) . '</a></li>';

		$logout     = '<li class="logout"><a href="' . wp_logout_url() . '">' . __( 'Log Out', 'wasmo' ) . '</a></li>';
		$profile    = '<li class="view"><a title="View Profile" href="' . get_author_posts_url( $userid ) . '">' . wasmo_get_icon_svg( 'person', 24 ) . 'View</a></li>';
		$edit       = '<li class="edit"><a title="Edit Profile" href="' . home_url( '/edit/' ) . '">' . wasmo_get_icon_svg( 'edit', 24 ) . 'Edit</a></li>';
		$post       = '';
		$writeposts = get_field( 'i_want_to_write_posts', 'user_' . $userid );
		if ( ! empty( $writeposts ) &&
			'No thanks' !== $writeposts
			) {
			$post = '<li class="post"><a title="Submit Post" href="' . home_url( '/wp-admin/post-new.php' ) . '">' . wasmo_get_icon_svg( 'edit-page', 24 ) . 'Submit Post</a></li>';
		}
		if ( is_user_logged_in() ) {
			$items = $profile . $edit . $post . $logout;
		} else {
			$items = $login;
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_items', 'wasmo_loginout_menu_link', 10, 2 );

function wasmo_get_profile_count() {
	$cached = get_transient( 'wasmo_profile_count' );
	if ( $cached !== false ) {
		return (int) $cached;
	}
	$users = get_users( [ 'fields' => 'all' ] );
	$count = 0;
	foreach ( $users as $user ) {
		if ( ! get_field( 'hi', 'user_' . $user->ID ) ) {
			continue;
		}
		if ( ! get_field( 'tagline', 'user_' . $user->ID ) ) {
			continue;
		}
		$in_dir = get_field( 'in_directory', 'user_' . $user->ID );
		if ( 'false' === $in_dir || false === $in_dir ) {
			continue;
		}
		++$count;
	}
	set_transient( 'wasmo_profile_count', $count, DAY_IN_SECONDS );
	return $count;
}
function wasmo_profile_count_shortcode() {
	return number_format( wasmo_get_profile_count() );
}
add_shortcode( 'wasmo_profile_count', 'wasmo_profile_count_shortcode' );

// AJAX: save "Take Things Further" checked state to user meta
add_action( 'wp_ajax_wasmo_save_further', 'wasmo_ajax_save_further' );
function wasmo_ajax_save_further() {
	check_ajax_referer( 'wasmo_further_nonce', 'nonce' );
	$userid = get_current_user_id();
	if ( ! $userid ) {
		wp_send_json_error( 'not_logged_in' );
	}
	$allowed = [ 'share', 'update', 'video', 'react', 'comment', 'more-q', 'invite', 'post', 'feedback', 'donate' ];
	$raw     = isset( $_POST['items'] ) ? json_decode( stripslashes( wp_unslash( $_POST['items'] ) ), true ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$items   = is_array( $raw ) ? array_values( array_intersect( $raw, $allowed ) ) : [];
	update_user_meta( $userid, 'wasmo_further_completed', wp_json_encode( $items ) );
	wp_send_json_success();
}

// AJAX: save box open/closed/dismissed state to user meta
add_action( 'wp_ajax_wasmo_save_box_states', 'wasmo_ajax_save_box_states' );
function wasmo_ajax_save_box_states() {
	check_ajax_referer( 'wasmo_further_nonce', 'nonce' );
	$userid = get_current_user_id();
	if ( ! $userid ) {
		wp_send_json_error( 'not_logged_in' );
	}
	$states = [
		'progress_open'      => isset( $_POST['progress_open'] ) ? rest_sanitize_boolean( $_POST['progress_open'] ) : true,
		'further_open'       => isset( $_POST['further_open'] ) ? rest_sanitize_boolean( $_POST['further_open'] ) : false,
		'progress_dismissed' => isset( $_POST['progress_dismissed'] ) ? rest_sanitize_boolean( $_POST['progress_dismissed'] ) : false,
		'further_dismissed'  => isset( $_POST['further_dismissed'] ) ? rest_sanitize_boolean( $_POST['further_dismissed'] ) : false,
	];
	update_user_meta( $userid, 'wasmo_box_states', wp_json_encode( $states ) );
	wp_send_json_success();
}
