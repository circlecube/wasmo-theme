<?php

/**
 * Login redirect page
 */
function wasmo_login_redirect_page() {
	if ( current_user_can( 'manage_options' ) ) {
		// admins go to admin dashboard
		return admin_url();
	}
	// contributors go to edit page
	return home_url('/edit/');
}
add_filter('login_redirect', 'wasmo_login_redirect_page');

/**
 * Logout redirect page
 */
function wasmo_logout_redirect_page() {
	return home_url('/profiles/');
}
add_filter('logout_redirect', 'wasmo_logout_redirect_page');

/**
 * Send welcome email to new user
 */
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

/**
 * Send admin email when profile is updated
 * 
 * @param int $user_id The user ID.
 * @param int $save_count The save count.
 */
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

/**
 * Get profile text
 * 
 * @param int $userid The user ID.
 * @return string The profile text.
 */
function wasmo_get_profile_text( $userid ) {
	$profile_text = '';
	ob_start();
	set_query_var( 'userid', $userid );
	get_template_part( 'template-parts/content/content', 'usertext' );
	$profile_text .= ob_get_clean();
	return $profile_text;
}

/**
 * Register add meta
 * 
 * @param int $user_id The user ID.
 */
function wasmo_register_add_meta($user_id) { 
	add_user_meta( $user_id, 'has_received_welcome', false );
}
add_action( 'user_register', 'wasmo_register_add_meta' );

/**
 * First user login
 * 
 * @param string $user_login The user login.
 * @param WP_User $user The user object.
 */
function wasmo_first_user_login( $user_login, $user ) {
	$user_id = $user->ID;
	$has_received_welcome = get_user_meta( $user_id, 'has_received_welcome', true );
	if ( '' === $has_received_welcome || ! $has_received_welcome ) {
		wasmo_send_user_email__welcome( $user_id );
		update_user_meta( $user_id, 'has_received_welcome', true );
	}
}
add_action('wp_login', 'wasmo_first_user_login', 10, 2);


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
	
	if ( $post->post_type !== 'post' ) { // bail if not a blog post
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
	$nl           = "\r\n";
	$nlnl         = $nl . $nl;
	$sitename     = get_bloginfo( 'name' );

	// Admin emails
	if ( // Notify Admin that Non-Admin has created a new post.
		'new' === $new_status &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'New post created by contributor', 'wasmo' ) . ': "' . $post->post_title . '"';
		$message  = __( 'A new post was started.', 'wasmo' ) . $nlnl;
		$message .= __( 'Author', 'wasmo' ) . ': ' . $user->user_login . " : " . $user->display_name . $nl;
		$message .= __( 'Profile', 'wasmo' ) . ': ' . get_author_posts_url( $user->ID ) . $nl;
		$message .= __( 'Title', 'wasmo' ) . ': ' . $post->post_title . $nl;
		$message .= __( 'Status', 'wasmo' ) . ': ' . $status . $nl;
		$message .= __( 'Last edited by', 'wasmo' ) . ': ' . $last_edit . $nl;
		$message .= __( 'Last edit date', 'wasmo' ) . ': ' . $post->post_modified . $nlnl;
		$message .= __( 'Edit the submission', 'wasmo' ) . ': ' . $edit_link . $nl;
		$message .= __( 'Preview the submission', 'wasmo' ) . ': ' . $preview_link;
		$result   = wp_mail( $admin_email, $subject, $message, $headers );
	} elseif ( // Notify Admin that Non-Admin has saved a draft post.
		'draft' === $new_status &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'Post saved as draft', 'wasmo' ) . ': "' . $post->post_title . '"';
		$message  = __( 'A post was saved again.', 'wasmo' ) . $nlnl;
		$message .= __( 'Author', 'wasmo' ) . ': ' . $user->user_login . " : " . $user->display_name . $nl;
		$message .= __( 'Profile', 'wasmo' ) . ': ' . get_author_posts_url( $user->ID ) . $nl;
		$message .= __( 'Title', 'wasmo' ) . ': ' . $post->post_title . $nl;
		$message .= __( 'Status', 'wasmo' ) . ': ' . $status . $nl;
		$message .= __( 'Last edited by', 'wasmo' ) . ': ' . $last_edit . $nl;
		$message .= __( 'Last edit date', 'wasmo' ) . ': ' . $post->post_modified . $nlnl;
		$message .= __( 'Edit the submission', 'wasmo' ) . ': ' . $edit_link . $nl;
		$message .= __( 'Preview the submission', 'wasmo' ) . ': ' . $preview_link;
		$result   = wp_mail( $admin_email, $subject, $message, $headers );
	} elseif ( // Notify Admin that Non-Admin has saved a draft post.
		'pending' === $new_status &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'Post submitted for review', 'wasmo' ) . ': "' . $post->post_title . '"';
		$message  = __( 'A post was submittd for review. It probably needs images and tags.', 'wasmo' ) . $nlnl;
		$message .= __( 'Author', 'wasmo' ) . ': ' . $user->user_login . " : " . $user->display_name . $nl;
		$message .= __( 'Profile', 'wasmo' ) . ': ' . get_author_posts_url( $user->ID ) . $nl;
		$message .= __( 'Title', 'wasmo' ) . ': ' . $post->post_title . $nl;
		$message .= __( 'Status', 'wasmo' ) . ': ' . $status . $nl;
		$message .= __( 'Last edited by', 'wasmo' ) . ': ' . $last_edit . $nl;
		$message .= __( 'Last edit date', 'wasmo' ) . ': ' . $post->post_modified . $nlnl;
		$message .= __( 'Edit/approve the submission', 'wasmo' ) . ': ' . $edit_link . $nl;
		$message .= __( 'Preview the submission', 'wasmo' ) . ': ' . $preview_link;
		$result   = wp_mail( $admin_email, $subject, $message, $headers );
	}
	
	// User emails
	if ( // Notify Non-admin that Admin has published their post.
		'publish' === $new_status &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'The post you submitted is now live!', 'wasmo' );
		$message  = '"' . $post->post_title . '" ' . __( 'is now published on wasmormon.org', 'wasmo' ) . "!" . $nlnl;
		$message .= $url . $nlnl;
		$message .= __( 'It is displayed as a link on your profile page', 'wasmo' ) . ': ' . get_author_posts_url( $user->ID ) . $nl;
		$message .= __( 'Have more to say? Start another post', 'wasmo' ) . ': ' . admin_url( 'post-new.php' ) . $nl;
		$message .= __( 'Reply to this email if you have any questions or suggestions.', 'wasmo' ) . $nl;
		$message .= __( 'Best,', 'wasmo' ) . $nl . $sitename . $nlnl;
		$result   = wp_mail( $user_email ? $user_email : $admin_email, $subject, $message, $headers );
	}
	elseif ( // Notify Non-admin that Admin has scheduled their post.
		'future' === $new_status &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'The post you submitted is now scheduled!', 'wasmo' );
		$message  = '"' . $post->post_title . '" ' . __( 'is now scheduled to be published on wasmormon.org', 'wasmo' ) . "!" . $nlnl;
		$message .= $url . $nlnl;
		$message .= __( 'Take a look and let us know if anything needs updating. Preview the post', 'wasmo' ) . ': ' . $preview_link . $nl;
		$message .= __( 'Date and time to be published', 'wasmo' ) . ': ' . $post->post_date . $nlnl;
		$message .= __( 'It will display as a link on your profile page', 'wasmo' ) . ': ' . get_author_posts_url( $user->ID ) . $nl;
		$message .= __( 'Have more to say? Start a new post', 'wasmo' ) . ': ' . admin_url( 'post-new.php' ) . $nl;
		$message .= __( 'Reply to this email if you have any questions or suggestions.', 'wasmo' ) . $nlnl;
		$message .= __( 'Best,', 'wasmo' ) . $nl . $sitename . $nlnl;
		$result   = wp_mail( $user_email ? $user_email : $admin_email, $subject, $message, $headers );
	}
	elseif ( // Notify non-admin that they submitted a post for review
		'pending' === $new_status &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'You submitted a post!', 'wasmo' );
		$message  = __( 'Thank you for submitting a post!', 'wasmo' ) . $nlnl;
		$message .= '"' . $post->post_title . '" ' . __( 'is now submitted to wasmormon.org', 'wasmo' ) . "!" . $nlnl;
		$message .= __( 'We\'ll create graphics, get it worked into the publishing schedule, and let you know when it is published. ', 'wasmo' );
		$message .= __( 'Once it is published, it will display on your profile! ', 'wasmo' ) . $nl;
		$message .= __( 'Have more to say? Start a new post', 'wasmo' ) . ': ' . admin_url( 'post-new.php' ) . $nlnl;
		$message .= __( 'Reply to this email if you have any questions or suggestions.', 'wasmo' ) . $nlnl;
		$message .= __( 'Best,', 'wasmo' ) . $nl . $sitename . $nlnl;
		$result   = wp_mail( $user_email ? $user_email : $admin_email, $subject, $message, $headers );
	}
	elseif ( // Notify non-admin that they created a post
		( 'new' === $new_status || 'draft' === $new_status ) &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'You created a post!', 'wasmo' );
		$message  = __( 'Thank you for creating a post! ', 'wasmo' );
		$message .= '"' . $post->post_title . '" ' . __( 'is now saved as a draft post on wasmormon.org', 'wasmo' ) . "!" . $nlnl;
		$message .= __( 'Once it is ready, submit the post for review. We\'ll help create graphics and get it worked into the publishing schedule. ', 'wasmo' );
		$message .= __( 'Once it is published, it will display on your profile! ', 'wasmo' ) . $nlnl;
		$message .= __( 'Edit the post', 'wasmo' ) . ': ' . $edit_link . $nl;
		$message .= __( 'Have more to say? Start a new post', 'wasmo' ) . ': ' . admin_url( 'post-new.php' ) . $nlnl;
		$message .= __( 'Reply to this email if you have any questions or suggestions.', 'wasmo' ) . $nlnl;
		$message .= __( 'Best,', 'wasmo' ) . $nl . $sitename . $nlnl;
		$result   = wp_mail( $user_email ? $user_email : $admin_email, $subject, $message, $headers );
	}
}
add_action( 'transition_post_status', 'wasmo_pending_submission_notifications_send_email', 10, 3 );

// https://github.com/wp-plugins/oa-social-login/blob/master/filters.txt
//This function will be called after Social Login has added a new user
function wasmo_oa_social_login_do_after_user_insert ($user_data, $identity) {
	// These are the fields from the WordPress database
	// print_r($user_data);
	// This is the full social network profile of this user
	// print_r($identity);

	// record last login
	wasmo_user_lastlogin($user_data->user_login, $user_data);
	// send welcome?
	wasmo_first_user_login($user_data->user_login, $user_data);
}
// add_action ('oa_social_login_action_after_user_insert', 'wasmo_oa_social_login_do_after_user_insert', 10, 2);

//This function will be called before Social Login logs the user in
function wasmo_oa_social_login_do_before_user_login ($user_data, $identity, $new_registration) {
	// record last login
	wasmo_user_lastlogin($user_data->user_login, $user_data);
	// send welcome?
	wasmo_first_user_login($user_data->user_login, $user_data);
}
// add_action ('oa_social_login_action_before_user_login', 'wasmo_oa_social_login_do_before_user_login', 10, 3);

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
// add_filter("lostpassword_url", function ($url, $redirect) {	
	
// 	$args = array( 'action' => 'lostpassword' );
	
// 	if ( !empty($redirect) )
// 		$args['redirect_to'] = $redirect;

// 	return add_query_arg( $args, site_url('wp-login.php') );
// }, 10, 2);

// fixes other password reset related urls
// add_filter( 'network_site_url', function($url, $path, $scheme) {
	
// 	if (stripos($url, "action=rp") !== false)
// 		// return site_url('wp-login.php?action=lostpassword', $scheme);
// 		return str_replace( 'circlecube.com', 'wasmormon.org', $url );
	  
// 	if (stripos($url, "action=lostpassword") !== false)
// 		return site_url('wp-login.php?action=lostpassword', $scheme);
  
// 	if (stripos($url, "action=resetpass") !== false)
// 		return site_url('wp-login.php?action=resetpass', $scheme);
  
// 	return $url;
// }, 10, 3 );

// fixes URLs in email that goes out.
// add_filter("retrieve_password_message", function ($message, $key) {
// 	$message = str_replace(get_site_url(1), get_site_url(), $message);
// 	$message = str_replace('circlecubes', 'wasmormon.org', $message);
	 
//   	return $message;
// }, 10, 2);

// fixes email title
// add_filter("retrieve_password_title", function($title) {
// 	return "[" . wp_specialchars_decode(get_option('blogname'), ENT_QUOTES) . "] Password Reset";
// });