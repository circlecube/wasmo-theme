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
	return home_url( '/edit/' );
}
add_filter( 'login_redirect', 'wasmo_login_redirect_page' );

/**
 * Logout redirect page
 */
function wasmo_logout_redirect_page() {
	return home_url( '/profiles/' );
}
add_filter( 'logout_redirect', 'wasmo_logout_redirect_page' );

/**
 * Send welcome email to new user
 */
function wasmo_send_user_email__welcome( $user_id ) {
	$sitename  = get_bloginfo( 'name' );
	$sitemail  = get_bloginfo( 'admin_email' );
	$user_info = get_userdata( $user_id );
	if ( $user_info ) {
		$user_displayname     = $user_info->display_name;
		$welcome_mail_to      = $user_info->user_email;
		$welcome_headers      = 'From: ' . $sitemail;
		$welcome_mail_subject = 'Welcome to ' . $sitename;
		$welcome_mail_message = $user_displayname . ', 

Welcome to ' . $sitename . '! We\'re glad you\'ve joined. Visit the following links (also found in the site header when you\'re logged in).

	Edit your proflie: ' . home_url( '/edit/' ) . '
	View/share your profile: ' . get_author_posts_url( $user_id ) . ' (you can change this url in your profile settings)

	Contribute articles: ' . admin_url( 'new-post.php' ) . '

We are genuinely excited to meet you and read your story. Please, don\'t hesitate to reach out if you have any questions or suggestions to improve the site (you can reply to this email).

Best,
' . $sitename;
		// the send
		wp_mail( $welcome_mail_to, $welcome_mail_subject, $welcome_mail_message, $welcome_headers );
	}
}

/**
 * Static cache for profile text captured before ACF saves new values.
 * Called by wasmo_capture_pre_save_profile_text() (functions-acf.php) to store,
 * and by wasmo_send_admin_email__profile_update() to retrieve.
 *
 * @param int|null    $user_id User ID key; null returns the whole cache array.
 * @param string|null $text    Text to store; omit to retrieve only.
 * @return string Cached text for $user_id, or '' if not set.
 */
function wasmo_pre_save_profile_text( $user_id = null, $text = null ) {
	static $cache = array();
	if ( null !== $user_id && null !== $text ) {
		$cache[ $user_id ] = $text;
	}
	return ( null !== $user_id ) ? ( isset( $cache[ $user_id ] ) ? $cache[ $user_id ] : '' ) : $cache;
}

/**
 * Build a side-by-side HTML diff table from two plain-text strings.
 * Uses word-level LCS to identify changes: removed words are highlighted red in the
 * "Before" column, added words are highlighted green in the "After" column.
 *
 * @param string $old_text Original profile text.
 * @param string $new_text Updated profile text.
 * @return string HTML <table> with before/after columns.
 */
function wasmo_build_side_by_side_diff_html( $old_text, $new_text ) {
	// Tokenize on whitespace boundaries, keeping whitespace as tokens so spacing is preserved.
	$old_tokens = preg_split( '/(\s+)/', trim( $old_text ), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
	$new_tokens = preg_split( '/(\s+)/', trim( $new_text ), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

	$m = count( $old_tokens );
	$n = count( $new_tokens );

	// LCS DP table — O(m*n); profile texts are short enough for this to be fast.
	$dp = array_fill( 0, $m + 1, array_fill( 0, $n + 1, 0 ) );
	for ( $i = 1; $i <= $m; $i++ ) {
		for ( $j = 1; $j <= $n; $j++ ) {
			$dp[ $i ][ $j ] = ( $old_tokens[ $i - 1 ] === $new_tokens[ $j - 1 ] )
				? $dp[ $i - 1 ][ $j - 1 ] + 1
				: max( $dp[ $i - 1 ][ $j ], $dp[ $i ][ $j - 1 ] );
		}
	}

	// Backtrack to produce the diff sequence (collected in reverse, then flipped).
	$diff = array();
	$i    = $m;
	$j    = $n;
	while ( $i > 0 || $j > 0 ) {
		if ( $i > 0 && $j > 0 && $old_tokens[ $i - 1 ] === $new_tokens[ $j - 1 ] ) {
			$diff[] = array( '=', $old_tokens[ $i - 1 ] );
			--$i;
			--$j;
		} elseif ( $j > 0 && ( 0 === $i || $dp[ $i ][ $j - 1 ] >= $dp[ $i - 1 ][ $j ] ) ) {
			$diff[] = array( '+', $new_tokens[ $j - 1 ] );
			--$j;
		} else {
			$diff[] = array( '-', $old_tokens[ $i - 1 ] );
			--$i;
		}
	}
	$diff = array_reverse( $diff );

	// Build the before (left) and after (right) column HTML from the same diff.
	$left  = '';
	$right = '';
	foreach ( $diff as $entry ) {
		$op      = $entry[0];
		$token   = $entry[1];
		$is_ws   = (bool) preg_match( '/^\s+$/', $token );
		$escaped = $is_ws ? str_replace( "\n", '<br>', esc_html( $token ) ) : esc_html( $token );

		if ( $is_ws ) {
			// Whitespace tokens: include on the side(s) where the adjacent content appears.
			if ( '=' === $op || '-' === $op ) {
				$left .= $escaped;
			}
			if ( '=' === $op || '+' === $op ) {
				$right .= $escaped;
			}
		} elseif ( '=' === $op ) {
			$left  .= $escaped;
			$right .= $escaped;
		} elseif ( '-' === $op ) {
			$left .= '<del style="background:#f8d7da;color:#721c24;">' . $escaped . '</del>';
		} else { // '+'
			$right .= '<ins style="background:#d4edda;color:#155724;text-decoration:none;font-weight:bold;">' . $escaped . '</ins>';
		}
	}

	return '<table style="width:100%;border-collapse:collapse;font-family:monospace;font-size:13px;line-height:1.6;">'
		. '<thead><tr>'
		. '<th style="background:#f8d7da;color:#721c24;padding:8px 12px;text-align:left;width:50%;border:1px solid #e0b4ba;">Before</th>'
		. '<th style="background:#d4edda;color:#155724;padding:8px 12px;text-align:left;width:50%;border:1px solid #b2d8bb;">After</th>'
		. '</tr></thead>'
		. '<tbody><tr>'
		. '<td style="background:#fff8f8;padding:12px;vertical-align:top;white-space:pre-wrap;border:1px solid #e0b4ba;">' . $left . '</td>'
		. '<td style="background:#f8fff8;padding:12px;vertical-align:top;white-space:pre-wrap;border:1px solid #b2d8bb;">' . $right . '</td>'
		. '</tr></tbody></table>';
}

/**
 * Send admin email when profile is updated
 *
 * @param int $user_id The user ID.
 * @param int $save_count The save count.
 */
function wasmo_send_admin_email__profile_update( $user_id, $save_count ) {
	$user_info      = get_userdata( $user_id );
	$user_nicename  = $user_info->user_nicename;
	$notify_mail_to = get_bloginfo( 'admin_email' );
	$sitename       = get_bloginfo( 'name' );
	$profile_url    = get_author_posts_url( $user_id );
	$headers        = array(
		'From: ' . $notify_mail_to,
		'Content-Type: text/html; charset=UTF-8',
	);

	if ( $user_info ) {
		$is_new = $save_count <= 1;

		$notify_mail_subject = $is_new
			? $sitename . ' New Profile Added: ' . $user_nicename
			: $sitename . ' Profile Update (#' . $save_count . '): ' . $user_nicename;

		$action_label = $is_new
			? 'New profile created'
			: 'Profile updated (edit #' . $save_count . ')';

		$old_text = wasmo_pre_save_profile_text( $user_id );
		$new_text = wasmo_get_profile_text( $user_id );

		if ( $is_new || '' === trim( $old_text ) ) {
			// First save — no prior text to diff against; show full profile.
			$body_html = '<div style="white-space:pre-wrap;font-family:monospace;font-size:13px;'
				. 'background:#f8f9fa;padding:16px;border-radius:4px;line-height:1.6;">'
				. nl2br( esc_html( trim( $new_text ) ) )
				. '</div>';
		} else {
			// Subsequent saves — show before/after diff.
			$legend    = '<p style="margin:0 0 8px;font-size:12px;color:#6c757d;">'
				. '<span style="background:#d4edda;color:#155724;padding:2px 6px;border-radius:3px;font-weight:bold;">green = added</span>'
				. '&nbsp;&nbsp;'
				. '<span style="background:#f8d7da;color:#721c24;padding:2px 6px;border-radius:3px;text-decoration:line-through;">red = removed</span>'
				. '</p>';
			$body_html = $legend . wasmo_build_side_by_side_diff_html( $old_text, $new_text );
		}

		$notify_mail_message = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
			. '<body style="font-family:sans-serif;max-width:900px;margin:0 auto;padding:16px;color:#212529;">'
			. '<h2 style="margin-bottom:4px;">' . esc_html( $action_label ) . '</h2>'
			. '<p style="margin-top:0;">'
			. '<strong>' . esc_html( $user_nicename ) . '</strong> &mdash; '
			. '<a href="' . esc_url( $profile_url ) . '">' . esc_html( $profile_url ) . '</a>'
			. '</p>'
			. $body_html
			. '<p style="margin-top:16px;font-size:12px;color:#6c757d;">'
			. '<a href="' . esc_url( $profile_url ) . '">' . esc_html( $profile_url ) . '</a>'
			. '</p>'
			. '</body></html>';

		wp_mail( $notify_mail_to, $notify_mail_subject, $notify_mail_message, $headers );
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
function wasmo_register_add_meta( $user_id ) {
	add_user_meta( $user_id, 'has_received_welcome', false );
}
add_action( 'user_register', 'wasmo_register_add_meta' );

/**
 * First user login
 *
 * @param string  $user_login The user login.
 * @param WP_User $user The user object.
 */
function wasmo_first_user_login( $user_login, $user ) {
	$user_id              = $user->ID;
	$has_received_welcome = get_user_meta( $user_id, 'has_received_welcome', true );
	if ( '' === $has_received_welcome || ! $has_received_welcome ) {
		wasmo_send_user_email__welcome( $user_id );
		update_user_meta( $user_id, 'has_received_welcome', true );
	}
}
add_action( 'wp_login', 'wasmo_first_user_login', 10, 2 );


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
	$headers      = 'From: ' . $admin_email;
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
		$message .= __( 'Author', 'wasmo' ) . ': ' . $user->user_login . ' : ' . $user->display_name . $nl;
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
		$message .= __( 'Author', 'wasmo' ) . ': ' . $user->user_login . ' : ' . $user->display_name . $nl;
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
		$message .= __( 'Author', 'wasmo' ) . ': ' . $user->user_login . ' : ' . $user->display_name . $nl;
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
		$message  = '"' . $post->post_title . '" ' . __( 'is now published on wasmormon.org', 'wasmo' ) . '!' . $nlnl;
		$message .= $url . $nlnl;
		$message .= __( 'It is displayed as a link on your profile page', 'wasmo' ) . ': ' . get_author_posts_url( $user->ID ) . $nl;
		$message .= __( 'Have more to say? Start another post', 'wasmo' ) . ': ' . admin_url( 'post-new.php' ) . $nl;
		$message .= __( 'Reply to this email if you have any questions or suggestions.', 'wasmo' ) . $nl;
		$message .= __( 'Best,', 'wasmo' ) . $nl . $sitename . $nlnl;
		$result   = wp_mail( $user_email ? $user_email : $admin_email, $subject, $message, $headers );
	} elseif ( // Notify Non-admin that Admin has scheduled their post.
		'future' === $new_status &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'The post you submitted is now scheduled!', 'wasmo' );
		$message  = '"' . $post->post_title . '" ' . __( 'is now scheduled to be published on wasmormon.org', 'wasmo' ) . '!' . $nlnl;
		$message .= $url . $nlnl;
		$message .= __( 'Take a look and let us know if anything needs updating. Preview the post', 'wasmo' ) . ': ' . $preview_link . $nl;
		$message .= __( 'Date and time to be published', 'wasmo' ) . ': ' . $post->post_date . $nlnl;
		$message .= __( 'It will display as a link on your profile page', 'wasmo' ) . ': ' . get_author_posts_url( $user->ID ) . $nl;
		$message .= __( 'Have more to say? Start a new post', 'wasmo' ) . ': ' . admin_url( 'post-new.php' ) . $nl;
		$message .= __( 'Reply to this email if you have any questions or suggestions.', 'wasmo' ) . $nlnl;
		$message .= __( 'Best,', 'wasmo' ) . $nl . $sitename . $nlnl;
		$result   = wp_mail( $user_email ? $user_email : $admin_email, $subject, $message, $headers );
	} elseif ( // Notify non-admin that they submitted a post for review
		'pending' === $new_status &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'You submitted a post!', 'wasmo' );
		$message  = __( 'Thank you for submitting a post!', 'wasmo' ) . $nlnl;
		$message .= '"' . $post->post_title . '" ' . __( 'is now submitted to wasmormon.org', 'wasmo' ) . '!' . $nlnl;
		$message .= __( 'We\'ll create graphics, get it worked into the publishing schedule, and let you know when it is published. ', 'wasmo' );
		$message .= __( 'Once it is published, it will display on your profile! ', 'wasmo' ) . $nl;
		$message .= __( 'Have more to say? Start a new post', 'wasmo' ) . ': ' . admin_url( 'post-new.php' ) . $nlnl;
		$message .= __( 'Reply to this email if you have any questions or suggestions.', 'wasmo' ) . $nlnl;
		$message .= __( 'Best,', 'wasmo' ) . $nl . $sitename . $nlnl;
		$result   = wp_mail( $user_email ? $user_email : $admin_email, $subject, $message, $headers );
	} elseif ( // Notify non-admin that they created a post
		( 'new' === $new_status || 'draft' === $new_status ) &&
		! user_can( $user, 'manage_options' )
	) {
		$subject  = __( 'You created a post!', 'wasmo' );
		$message  = __( 'Thank you for creating a post! ', 'wasmo' );
		$message .= '"' . $post->post_title . '" ' . __( 'is now saved as a draft post on wasmormon.org', 'wasmo' ) . '!' . $nlnl;
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
// This function will be called after Social Login has added a new user
function wasmo_oa_social_login_do_after_user_insert( $user_data ) {
	// These are the fields from the WordPress database
	// print_r($user_data);
	// This is the full social network profile of this user
	// print_r($identity);

	// record last login
	wasmo_user_lastlogin( $user_data->user_login, $user_data );
	// send welcome?
	wasmo_first_user_login( $user_data->user_login, $user_data );
}
// add_action ('oa_social_login_action_after_user_insert', 'wasmo_oa_social_login_do_after_user_insert', 10, 2);

// This function will be called before Social Login logs the user in
function wasmo_oa_social_login_do_before_user_login( $user_data ) {
	// record last login
	wasmo_user_lastlogin( $user_data->user_login, $user_data );
	// send welcome?
	wasmo_first_user_login( $user_data->user_login, $user_data );
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

//  $args = array( 'action' => 'lostpassword' );

//  if ( !empty($redirect) )
//      $args['redirect_to'] = $redirect;

//  return add_query_arg( $args, site_url('wp-login.php') );
// }, 10, 2);

// fixes other password reset related urls
// add_filter( 'network_site_url', function($url, $path, $scheme) {

//  if (stripos($url, "action=rp") !== false)
//      // return site_url('wp-login.php?action=lostpassword', $scheme);
//      return str_replace( 'circlecube.com', 'wasmormon.org', $url );

//  if (stripos($url, "action=lostpassword") !== false)
//      return site_url('wp-login.php?action=lostpassword', $scheme);

//  if (stripos($url, "action=resetpass") !== false)
//      return site_url('wp-login.php?action=resetpass', $scheme);

//  return $url;
// }, 10, 3 );

// fixes URLs in email that goes out.
// add_filter("retrieve_password_message", function ($message, $key) {
//  $message = str_replace(get_site_url(1), get_site_url(), $message);
//  $message = str_replace('circlecubes', 'wasmormon.org', $message);

//      return $message;
// }, 10, 2);

// fixes email title
// add_filter("retrieve_password_title", function($title) {
//  return "[" . wp_specialchars_decode(get_option('blogname'), ENT_QUOTES) . "] Password Reset";
// });
