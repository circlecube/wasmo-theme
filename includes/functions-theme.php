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
		wp_get_theme()->get('Version')
	);
	
	wp_enqueue_script( 
		'wasmo-script', 
		get_stylesheet_directory_uri() . '/js/script.js', 
		null, 
		wp_get_theme()->get('Version'),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'wasmo_enqueue' );

/**
 * Add google fonts
 */
function wasmo_add_google_fonts() {
	wp_enqueue_style( 
		'wasmo-google-fonts', 
		'https://fonts.googleapis.com/css?family=Crimson+Text:400,700|Open+Sans:400,700&display=swap', 
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
	if ($args->theme_location == 'utility') {
		$userid = get_current_user_id();
		
		$login = '<li class="login"><a href="' . home_url('/login/') . '" class="register">' . wasmo_get_icon_svg( 'join', 24 ) . __(" Join", 'wasmo') . '</a></li>';
		$login .= '<li class="login"><a href="' . home_url('/login/') . '" class="nav-login">' .  wasmo_get_icon_svg( 'login', 24 ) . __(" Login", 'wasmo') . '</a></li>';

		$logout =  '<li class="logout"><a href="' . wp_logout_url() . '">' . __("Log Out", 'wasmo') . '</a></li>';
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
			$items = $profile . $edit . $post . $logout;
		} else {
			$items = $login;
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_items', 'wasmo_loginout_menu_link', 10, 2 );