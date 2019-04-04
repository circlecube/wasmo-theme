<?php
// Directory

// define transient name - taxid + user state.
$transient_name = 'directory-' . is_user_logged_in();
$transient_exp = 24 * HOUR_IN_SECONDS;
// debug
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
	$users = get_users( $args );

	$the_directory .= '<section class="entry-content the-directory">';
	$the_directory .= '<div class="directory">';

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

			$userimg = get_field( 'photo', 'user_' . $userid );
			$username = esc_html( $user->nickname );

			$the_directory .= '<a class="person person-' . $userid . '" href="' . get_author_posts_url( $userid ) . '>">';
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
	}
	$the_directory .= '</div>';
	$the_directory .= '</section>';

	set_transient( $transient_name, $the_directory, $transient_exp );
}
echo $the_directory;