<?php

/**
 * Custom wp-login logo
 */
function wasmo_login_logo() { ?>
	<style type="text/css">
		.login.login-action-register #login {
			width: 90%;
			max-width: 520px;
		}
		.login.login-action-register #login .wp-login-logo a {
			background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/img/wasmormon-logo.png);
			height: 190px;
			width: 190px;
			background-size: 190px 190px;
			background-repeat: no-repeat;
			padding-bottom: 30px;
		}
		.login.login-action-register .acf-user-register-fields {
			padding: 2rem 0 1rem;
		}
		.login.login-action-register #reg_passmail {
			margin-top: 2rem;
		}

		.login.login-action-register label[for="user_login"]::after {
			content: "(letters, numbers, dash, period, or underscore)";
			font-size: 0.8rem;
			margin-left: 0.5rem;
		}
		.login.login-action-register .user-login-note {
			display: block;
			font-size: 0.8rem;
			margin-top: -0.5rem;
			margin-bottom: 0.5rem;
			color: #666;
		}
		.login.login-action-register label[for="user_email"]::after {
			content: "(You'll receive a confirmation email with a link to set your password.)";
			font-size: 0.8rem;
			margin-left: 0.5rem;
		}
	</style>
		<script>	
		document.addEventListener('DOMContentLoaded', function() {
			var usernameInput = document.querySelector('input[name="user_login"]');
			
			if (usernameInput) {
				// Create preview element
				var previewElement = document.createElement('span');
				previewElement.className = 'user-login-note';
				
				// Insert preview element after the username input
				usernameInput.parentNode.insertBefore(previewElement, usernameInput.nextSibling);
				
				function formatUsername( username ) {
					return username
						.replace(/[\s\.]+/g, '-')
						.replace(/[^a-zA-Z0-9\-_]/g, '');
				}
				// Function to update preview
				function updatePreview() {
					var username = formatUsername( usernameInput.value.toLowerCase() );
					if (username) {
 						previewElement.innerHTML = '✅ Your story will be published at <u>wasmormon.org/@' + username + '</u>';
						previewElement.style.display = 'block';
					} else {
						previewElement.style.display = 'none';
					}
					updateGreeting();
				}
				// Function to update greeting with username
				function updateGreeting() {
					var username = formatUsername( usernameInput.value );
					var greeting = document.querySelector('input[id="acf-field_66311d94efe37"]');
					if (username) {
						greeting.value = 'Hello, I\'m ' + username + '!';
					} 
				}
				
				// Add event listeners for real-time updates
				usernameInput.addEventListener('input', updatePreview);
				usernameInput.addEventListener('keyup', updatePreview);
				usernameInput.addEventListener('change', updatePreview);
				
				// Initial update
				updatePreview();
			}
		});
	</script>
<?php }
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