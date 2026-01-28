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

// ============================================
// SAINT POST TYPE SEO & STRUCTURED DATA
// ============================================

/**
 * Add structured data (Schema.org Person) for saint posts
 */
add_action( 'wp_head', function() {
	if ( ! is_singular( 'saint' ) ) {
		return;
	}
	
	$saint_id = get_the_ID();
	$name = get_the_title();
	$birthdate = get_field( 'birthdate', $saint_id );
	$deathdate = get_field( 'deathdate', $saint_id );
	$gender = get_field( 'gender', $saint_id ) ?: 'male';
	$familysearch_id = get_field( 'familysearch_id', $saint_id );
	$hometown = get_field( 'hometown', $saint_id );
	$bio = get_the_excerpt() ?: get_the_content();
	$bio = wp_trim_words( strip_shortcodes( $bio ), 50 );
	$portrait_url = get_the_post_thumbnail_url( $saint_id, 'large' ) ?: '';
	$url = get_permalink();
	
	// Get roles
	$roles = wp_get_post_terms( $saint_id, 'saint-role', array( 'fields' => 'names' ) );
	
	// Get polygamy information (only for men)
	$polygamy_stats = null;
	$polygamy_type = null;
	$is_polygamist = false;
	$number_of_wives = 0;
	if ( $gender === 'male' && function_exists( 'wasmo_get_polygamy_stats' ) && function_exists( 'wasmo_get_polygamy_type' ) ) {
		$polygamy_stats = wasmo_get_polygamy_stats( $saint_id );
		$polygamy_type = wasmo_get_polygamy_type( $saint_id );
		$is_polygamist = $polygamy_stats['was_polygamist'] ?? false;
		$number_of_wives = $polygamy_stats['number_of_marriages'] ?? 0;
	}
	
	// Build schema.org Person structured data
	$schema = array(
		'@context' => 'https://schema.org',
		'@type' => 'Person',
		'name' => $name,
		'url' => $url,
	);
	
	// Add description if available
	if ( $bio ) {
		$schema['description'] = $bio;
	}
	
	// Add image if available
	if ( $portrait_url ) {
		$schema['image'] = $portrait_url;
	}
	
	// Add birth date
	if ( $birthdate ) {
		$schema['birthDate'] = $birthdate;
	}
	
	// Add death date (if deceased)
	if ( $deathdate ) {
		$schema['deathDate'] = $deathdate;
	}
	
	// Add gender
	if ( $gender === 'female' ) {
		$schema['gender'] = 'https://schema.org/Female';
	} else {
		$schema['gender'] = 'https://schema.org/Male';
	}
	
	// Add job title/role (if has roles)
	if ( ! empty( $roles ) ) {
		$schema['jobTitle'] = implode( ', ', $roles );
		// For historic/celebrity figures, add additional type
		$schema['additionalType'] = 'https://schema.org/HistoricalFigure';
	}
	
	// Add FamilySearch ID as identifier
	if ( $familysearch_id ) {
		$schema['identifier'] = array(
			'@type' => 'PropertyValue',
			'name' => 'FamilySearch ID',
			'value' => $familysearch_id,
		);
	}
	
	// Add polygamy information (for men with multiple marriages)
	if ( $is_polygamist && $number_of_wives > 1 ) {
		$schema['knowsAbout'] = array( 'Polygamy' );
		
		// Add custom property for number of wives
		$schema['numberOfWives'] = $number_of_wives;
		
		// Add polygamy type if available
		if ( $polygamy_type && isset( $polygamy_type['type'] ) && $polygamy_type['type'] !== 'none' ) {
			$polygamy_type_label = $polygamy_type['type'] === 'celestial' 
				? 'Celestial Polygamist (sequential marriages)' 
				: 'Traditional Polygamist (simultaneous marriages)';
			$schema['polygamyType'] = $polygamy_type_label;
		}
		
		// Add number of children if available
		if ( isset( $polygamy_stats['number_of_children'] ) && $polygamy_stats['number_of_children'] > 0 ) {
			$schema['numberOfChildren'] = $polygamy_stats['number_of_children'];
		}
	}
	
	// Output JSON-LD
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}, 1 );

/**
 * Add Open Graph and Twitter Card meta tags for saint posts
 */
add_action( 'wp_head', function() {
	if ( ! is_singular( 'saint' ) ) {
		return;
	}
	
	$saint_id = get_the_ID();
	$name = get_the_title();
	$gender = get_field( 'gender', $saint_id ) ?: 'male';
	$bio = get_the_excerpt() ?: get_the_content();
	$bio = wp_trim_words( strip_shortcodes( $bio ), 30 );
	$portrait_url = get_the_post_thumbnail_url( $saint_id, 'large' ) ?: '';
	$url = get_permalink();
	
	// Get roles for description
	$roles = wp_get_post_terms( $saint_id, 'saint-role', array( 'fields' => 'names' ) );
	$role_text = ! empty( $roles ) ? implode( ', ', $roles ) : '';
	
	// Get polygamy information (only for men)
	$polygamy_info = '';
	if ( $gender === 'male' && function_exists( 'wasmo_get_polygamy_stats' ) && function_exists( 'wasmo_get_polygamy_type' ) ) {
		$polygamy_stats = wasmo_get_polygamy_stats( $saint_id );
		$polygamy_type = wasmo_get_polygamy_type( $saint_id );
		$is_polygamist = $polygamy_stats['was_polygamist'] ?? false;
		$number_of_wives = $polygamy_stats['number_of_marriages'] ?? 0;
		
		if ( $is_polygamist && $number_of_wives > 1 ) {
			$polygamy_type_label = '';
			if ( $polygamy_type && isset( $polygamy_type['type'] ) && $polygamy_type['type'] !== 'none' ) {
				$polygamy_type_label = $polygamy_type['type'] === 'celestial' 
					? ' (celestial polygamist)' 
					: ' (traditional polygamist)';
			}
			$polygamy_info = ' Married ' . $number_of_wives . ' wife' . ( $number_of_wives > 1 ? 's' : '' ) . $polygamy_type_label . '.';
		}
	}
	
	// Build description
	$description = $bio;
	if ( $role_text ) {
		$description = $name . ($description ? ' - ' . $description : '') . ' (' . $role_text . ')';
	}
	if ( ! $description ) {
		$description = $name . ' - Historical figure and Latter-day Saint leader';
	}
	
	// Append polygamy info if available
	if ( $polygamy_info ) {
		$description .= $polygamy_info;
	}
	
	$birthdate = get_field( 'birthdate', $saint_id );
	$deathdate = get_field( 'deathdate', $saint_id );
	$life_dates = '';
	if ( $birthdate ) {
		$birth_year = date( 'Y', strtotime( $birthdate ) );
		if ( $deathdate ) {
			$death_year = date( 'Y', strtotime( $deathdate ) );
			$life_dates = $birth_year . '–' . $death_year;
		} else {
			$life_dates = $birth_year . '–present';
		}
		if ( $life_dates ) {
			$description .= ' (' . $life_dates . ')';
		}
	}
	
	?>
	<!-- Open Graph / Facebook -->
	<meta property="og:type" content="profile" />
	<meta property="og:url" content="<?php echo esc_url( $url ); ?>" />
	<meta property="og:title" content="<?php echo esc_attr( $name ); ?><?php echo $life_dates ? ' (' . esc_attr( $life_dates ) . ')' : ''; ?>" />
	<meta property="og:description" content="<?php echo esc_attr( $description ); ?>" />
	<?php if ( $portrait_url ) : ?>
		<meta property="og:image" content="<?php echo esc_url( $portrait_url ); ?>" />
		<meta property="og:image:width" content="1200" />
		<meta property="og:image:height" content="630" />
	<?php endif; ?>
	<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
	<meta property="profile:first_name" content="<?php echo esc_attr( get_field( 'first_name', $saint_id ) ?: '' ); ?>" />
	<meta property="profile:last_name" content="<?php echo esc_attr( get_field( 'last_name', $saint_id ) ?: '' ); ?>" />
	<?php if ( $gender ) : ?>
		<meta property="profile:gender" content="<?php echo esc_attr( $gender ); ?>" />
	<?php endif; ?>
	
	<!-- Twitter Card -->
	<meta name="twitter:card" content="summary_large_image" />
	<meta name="twitter:title" content="<?php echo esc_attr( $name ); ?><?php echo $life_dates ? ' (' . esc_attr( $life_dates ) . ')' : ''; ?>" />
	<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>" />
	<?php if ( $portrait_url ) : ?>
		<meta name="twitter:image" content="<?php echo esc_url( $portrait_url ); ?>" />
	<?php endif; ?>
	
	<!-- Additional SEO Meta -->
	<meta name="description" content="<?php echo esc_attr( $description ); ?>" />
	<?php
}, 5 );

/**
 * Filter SEO title for saint posts
 */
add_filter( 'wpseo_title', function( $title ) {
	if ( is_singular( 'saint' ) ) {
		$saint_id = get_the_ID();
		$name = get_the_title();
		$birthdate = get_field( 'birthdate', $saint_id );
		$deathdate = get_field( 'deathdate', $saint_id );
		
		$life_dates = '';
		if ( $birthdate ) {
			$birth_year = date( 'Y', strtotime( $birthdate ) );
			if ( $deathdate ) {
				$death_year = date( 'Y', strtotime( $deathdate ) );
				$life_dates = ' (' . $birth_year . '–' . $death_year . ')';
			} else {
				$life_dates = ' (' . $birth_year . '–present)';
			}
		}
		
		$roles = wp_get_post_terms( $saint_id, 'saint-role', array( 'fields' => 'names' ) );
		$role_text = ! empty( $roles ) ? ' - ' . implode( ', ', $roles ) : '';
		
		$title = esc_html( $name ) . $life_dates . $role_text . ' - ' . get_bloginfo( 'name' );
	}
	return $title;
} );

/**
 * Filter SEO description for saint posts
 */
add_filter( 'wpseo_metadesc', function( $metadesc ) {
	if ( is_singular( 'saint' ) ) {
		$saint_id = get_the_ID();
		$name = get_the_title();
		$gender = get_field( 'gender', $saint_id ) ?: 'male';
		$bio = get_the_excerpt() ?: get_the_content();
		$bio = wp_trim_words( strip_shortcodes( $bio ), 30 );
		
		$roles = wp_get_post_terms( $saint_id, 'saint-role', array( 'fields' => 'names' ) );
		$role_text = ! empty( $roles ) ? implode( ', ', $roles ) : '';
		
		$birthdate = get_field( 'birthdate', $saint_id );
		$deathdate = get_field( 'deathdate', $saint_id );
		$life_dates = '';
		if ( $birthdate ) {
			$birth_year = date( 'Y', strtotime( $birthdate ) );
			if ( $deathdate ) {
				$death_year = date( 'Y', strtotime( $deathdate ) );
				$life_dates = $birth_year . '–' . $death_year;
			} else {
				$life_dates = $birth_year . '–present';
			}
		}
		
		// Get polygamy information (only for men)
		$polygamy_info = '';
		if ( $gender === 'male' && function_exists( 'wasmo_get_polygamy_stats' ) && function_exists( 'wasmo_get_polygamy_type' ) ) {
			$polygamy_stats = wasmo_get_polygamy_stats( $saint_id );
			$polygamy_type = wasmo_get_polygamy_type( $saint_id );
			$is_polygamist = $polygamy_stats['was_polygamist'] ?? false;
			$number_of_wives = $polygamy_stats['number_of_marriages'] ?? 0;
			
			if ( $is_polygamist && $number_of_wives > 1 ) {
				$polygamy_type_label = '';
				if ( $polygamy_type && isset( $polygamy_type['type'] ) && $polygamy_type['type'] !== 'none' ) {
					$polygamy_type_label = $polygamy_type['type'] === 'celestial' 
						? ' (celestial polygamist)' 
						: ' (traditional polygamist)';
				}
				$polygamy_info = ' Married ' . $number_of_wives . ' wife' . ( $number_of_wives > 1 ? 's' : '' ) . $polygamy_type_label . '.';
			}
		}
		
		$metadesc = $name;
		if ( $life_dates ) {
			$metadesc .= ' (' . $life_dates . ')';
		}
		if ( $role_text ) {
			$metadesc .= ' - ' . $role_text;
		}
		if ( $polygamy_info ) {
			$metadesc .= $polygamy_info;
		}
		if ( $bio ) {
			$metadesc .= ' ' . $bio;
		}
	}
	return $metadesc;
} );

/**
 * Filter Open Graph image for saint posts
 */
add_filter( 'wpseo_opengraph_image', function( $image ) {
	if ( is_singular( 'saint' ) ) {
		$saint_id = get_the_ID();
		$portrait_url = get_the_post_thumbnail_url( $saint_id, 'large' );
		if ( $portrait_url ) {
			return $portrait_url;
		}
	}
	return $image;
} );

add_filter( 'wpseo_twitter_image', function( $image ) {
	if ( is_singular( 'saint' ) ) {
		$saint_id = get_the_ID();
		$portrait_url = get_the_post_thumbnail_url( $saint_id, 'large' );
		if ( $portrait_url ) {
			return $portrait_url;
		}
	}
	return $image;
} );