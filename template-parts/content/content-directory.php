<?php
// Directory
$context = get_query_var( 'context' );
$max_profiles = get_query_var( 'max_profiles' );

if ( empty( $context ) ) {
	$context = 'full';
	$max_profiles = -1;
}
if ( 'widget' === $context ) {
	$max_profiles = 9;
}
if ( empty( $max_profiles ) ) {
	$max_profiles = 50;
}

// define transient name - taxid + user state.
if ( is_user_logged_in() ) {
	$state = 'private';
} else {
	$state = 'public';
}

$transient_name = implode('-', array( 'directory', $state, $context, $max_profiles ) );
$transient_exp = 7 * 24 * HOUR_IN_SECONDS; // one week

delete_transient( 'directory-private-shortcode' );
// debug
// delete_transient( 'directory-private-full--1' );
// delete_transient( 'directory-public-full--1' );
// delete_transient( 'directory-private-widget-9' );
// delete_transient( 'directory-public-widget-9' );
// delete_transient( 'directory-private-shortcode-12' );
// delete_transient( 'directory-public-shortcode-12' );
// if ( current_user_can('administrator') && WP_DEBUG ) {
// 	$transient_name = time();
// }
//use transient to cache data
if ( false === ( $the_directory = get_transient( $transient_name ) ) ) {
	$the_directory = '';

	/* Start the Loop */
	$args = array(
		'orderby'      => 'meta_value',
		'meta_key'     => 'last_save',
		'order'        => 'DESC',
		'fields'       => 'all',
	);
	// if ( 'widget' === $context ) {
		// $args['number'] = 10;
	// }
	$users = get_users( $args );

	$the_directory .= '<section class="entry-content the-directory directory-' . $context . ' directory-' . $state . ' directory-' . $max_profiles . '">';
	$the_directory .= '<div class="directory">';
	$counter = 0;
	// Array of WP_User objects.
	foreach ( $users as $user ) { 
		$userid = $user->ID;
		// only add to directory if user includes themself and has filled out the first two fields
		// true = public
		// private = only to a logged in user
		if ( get_field( 'hi', 'user_' . $userid ) && 
			get_field( 'tagline', 'user_' . $userid ) &&
			'true' === get_field( 'in_directory', 'user_' . $userid ) ||
			'private' === get_field( 'in_directory', 'user_' . $userid ) && is_user_logged_in() ) {
			
			$counter++;
			$userimg = get_field( 'photo', 'user_' . $userid );
			$username = esc_html( $user->display_name );

			$the_directory .= '<a class="person person-' . $counter . ' person-id-' . $userid . '" href="' . get_author_posts_url( $userid ) . '">';
				$the_directory .= '<span class="directory-img">';
					if ( $userimg ) {
						$the_directory .= wp_get_attachment_image( $userimg, 'medium' );
					} else {
						$the_directory .= '<img src="' . get_stylesheet_directory_uri() . '/img/default.svg">';
					}
				$the_directory .= '</span>';
				$the_directory .= '<span class="directory-name">' . $username . '</span>';
			$the_directory .= '</a>';
		}

		// check counter against limit
		if ( $max_profiles > 0 && $counter >= $max_profiles ) {
			break;
		}
	}
	$the_directory .= '</div>';
	$the_directory .= '</section>';

	set_transient( $transient_name, $the_directory, $transient_exp );
}
echo $the_directory;