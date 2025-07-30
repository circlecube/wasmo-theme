<?php

/**
 * Custom wp-login logo
 */
function wasmo_login_logo() {
	// add theme script to login page for username tweaks
	wp_enqueue_script( 
		'wasmo-script', 
		get_stylesheet_directory_uri() . '/js/script.js', 
		null, 
		wp_get_theme()->get('Version'),
		true
	);

	// login page styles (inline since they only go here)
?>
	<style type="text/css">
		body.login #login {
			width: 90%;
			max-width: 520px;
		}
		body.login #login .wp-login-logo a {
			background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/img/wasmormon-logo.png);
			height: 190px;
			width: 190px;
			background-size: 190px 190px;
			background-repeat: no-repeat;
			margin-bottom: 50px;
		}
		body.login .acf-user-register-fields {
			padding: 2rem 0 1rem;
		}
		body.login #reg_passmail {
			margin-top: 2rem;
		}

		body.login label[for="user_login"]::after {
			content: "(letters, numbers, dash, period, or underscore)";
			font-size: 0.8rem;
			margin-left: 0.5rem;
		}
		body.login .user-login-note {
			display: block;
			font-size: 0.8rem;
			margin-top: -0.5rem;
			margin-bottom: 0.5rem;
			color: #666;
		}
		body.login label[for="user_email"]::after {
			content: "(You'll receive a confirmation email with a link to set your password.)";
			font-size: 0.8rem;
			margin-left: 0.5rem;
		}
	</style>
<?php 
}
add_action( 'login_enqueue_scripts', 'wasmo_login_logo' );

/**
 * Login logo url
 * 
 * @return string The login logo url.
 */
function wasmo_login_logo_url() {
	return home_url();
}
add_filter( 'login_headerurl', 'wasmo_login_logo_url' );

/**
 * Login logo url title
 * 
 * @return string The login logo url title.
 */
function wasmo_login_logo_url_title() {
	return 'wasmormon.org';
}
add_filter( 'login_headertext', 'wasmo_login_logo_url_title' );

/**
 * Capture user login and add it as timestamp in user meta data
 * 
 * @param string $user_login The user login.
 * @param WP_User $user The user object.
 */
function wasmo_user_lastlogin( $user_login, $user ) {
	update_user_meta( $user->ID, 'last_login', time() );
}
add_action( 'wp_login', 'wasmo_user_lastlogin', 10, 2 );