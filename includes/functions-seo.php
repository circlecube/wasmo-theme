<?php

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
 * 
 * @param array $entries The entries array.
 * @return array The modified entries array.
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
 * 
 * @param string $description The description.
 * @return string The modified description.
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
 * 
 * @param string $description The description.
 * @return string The modified description.
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
 * Filter to update user profile page title for seo
 * 
 * @param string $title The title.
 * @return string The modified title.
 */
function wasmo_filter_profile_wpseo_title( $title ) {
	if( is_author() ) {
		$curauth = ( get_query_var( 'author_name' ) ) ? get_user_by( 'slug', get_query_var( 'author_name' ) ) : get_userdata( get_query_var( 'author' ) );
		$userid = $curauth->ID;
		$title = esc_html( get_field( 'hi', 'user_' . $userid ) ) . ' - ';
		$title .= 'Learn why I\'m no longer mormon at wasmormon.org';
	}
	return $title;
}
add_filter( 'wpseo_title', 'wasmo_filter_profile_wpseo_title' );

/**
 * Filter to update user profile page description for seo
 * 
 * @param string $metadesc The metadesc.
 * @return string The modified metadesc.
 */
function wasmo_user_profile_wpseo_metadesc( $metadesc ) {
	if( is_author() ) {
		$curauth = ( get_query_var( 'author_name' ) ) ? get_user_by( 'slug', get_query_var( 'author_name' ) ) : get_userdata( get_query_var( 'author' ) );
		$userid = $curauth->ID;
		$metadesc = esc_html( get_field( 'tagline', 'user_' . $userid ) );
	}
	return $metadesc;
}
add_filter( 'wpseo_metadesc', 'wasmo_user_profile_wpseo_metadesc' );

/**
 * Filter to update profile page open graph image to user profile image if there is one
 * 
 * @param string $image The image.
 * @return string The modified image.
 */
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
add_filter( 'wpseo_opengraph_image', 'wasmo_user_profile_set_og_image' );
add_filter( 'wpseo_twitter_image', 'wasmo_user_profile_set_og_image' );