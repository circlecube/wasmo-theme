<?php
// Get vars from query
$context      = get_query_var( 'context' );
$max_profiles = get_query_var( 'max_profiles' );
$tax          = get_query_var('tax');
$termid       = get_query_var('termid');
$paged        = get_query_var('paged');
// Initialize the remaining vars
$offset = 0;
if ( empty( $context ) ) {
	$context      = 'full';
	$max_profiles = 48;
	$offset       = $paged ? ($paged - 1) * $max_profiles : 0;
}
if ( 'widget' === $context ) {
	$max_profiles = 9;
}
if ( empty( $max_profiles ) ) {
	$max_profiles = 48;
}

if ( $context === 'tax' ) {
	$context = 'tax-' . $tax . '_term-' . $termid;
}
// define transient name - taxid + user state.
if ( is_user_logged_in() ) {
	$state = 'private';
} else {
	$state = 'public';
}

// only add to directory if user includes themself and has filled out the first two fields
function filter_directory($user) {
	// global $context, $state;
	$context = get_query_var( 'context' );
	if ( empty( $context ) ) {
		$context = 'full';
	}

	if ( is_user_logged_in() ) {
		$state = 'private';
	} else {
		$state = 'public';
	}
	$userid = $user->ID;
	
	// require both hi and tagline content, bail early if not present
	if ( !get_field( 'hi', 'user_' . $userid ) || !get_field( 'tagline', 'user_' . $userid ) ) {
		return false;
	}

	// require image if not full directory, bail early if not present
	$userimg   = get_field( 'photo', 'user_' . $userid );
	$has_image = $userimg ? true : false;
	if ( 'full' !== $context && !$has_image ) {
		return false;
	}

	$in_directory = get_field( 'in_directory', 'user_' . $userid );
	// true = public
	// private = only to a logged in user
	// website = show on web but not on social
	// false = don't show anywhere

	// is privacy setting set to false
	if( 'false' === $in_directory ) {
		return false;
	}

	// is privacy setting set to private and user is logged in?
	if ( 'private' === $in_directory && 'private' !== $state ) {
		return false;
	}
	
	// not bailed yet?
	return true;
}
function filter_directory_for_tax($user){
	// global $tax, $termid;
	$tax = get_query_var('tax');
	$termid = get_query_var('termid');

	$userid = $user->ID;
	
	// skip if $context doesn't start with `taxonomy`
	// if ( strpos( $context, 'taxonomy' ) !== 0 ) {
	// 	return true;
	// }
	// echo $tax;
	// determine if user has term selected
	$userterms = null;
	if ( $tax === 'spectrum' ) {
		$userterms = get_field( 'mormon_spectrum', 'user_' . $userid );
	} else if ( $tax === 'shelf') {
		$userterms = get_field( 'my_shelf', 'user_' . $userid );
	} else {
		return false;
	}

	// bail early if no terms
	if ( empty( $userterms ) ) {
		return false;
	}

	// check each userterm for termid match
	foreach ( $userterms as $userterm ) {
		if ( $userterm->term_id === $termid ) {
			return true;
		}
	}

	// false if no match found
	return false;
}


$transient_name = implode('-', array( 'directory', $state, $context, $max_profiles, 'page_' . $paged ) );
$transient_exp = 7 * 24 * HOUR_IN_SECONDS; // one week

// delete_transient( 'directory-private-shortcode' );
// debug
// delete_transient( 'directory-private-full--1' );
// delete_transient( 'directory-public-full--1' );
// delete_transient( 'directory-private-widget-9' );
// delete_transient( 'directory-public-widget-9' );
// delete_transient( 'directory-private-shortcode-12' );
// delete_transient( 'directory-public-shortcode-12' );
if ( current_user_can('administrator') && WP_DEBUG ) {
	$transient_name = time();
}
//use transient to cache data
if ( false === ( $the_directory = get_transient( $transient_name ) ) ) {
	$the_directory = '';

	/* Start the Loop */
	$args = array(
		'orderby'  => 'meta_value',
		'meta_key' => 'last_save',
		'order'    => 'DESC',
		'fields'   => 'all'
	);

	// Array of WP_User objects.
	$users = get_users( $args );
	// filter out users we don't want
	$filtered_users = array_filter( $users, "filter_directory" );
	// maybe additional filter for taxonomy
	if ( !empty( $tax ) ) {
		$filtered_users = array_filter( $users , "filter_directory_for_tax" );
	}
	$total_users = count($filtered_users);
	$counter = 0;
	$the_directory .= '<section class="entry-content the-directory directory-' . $context . ' directory-' . $state . ' directory-' . $max_profiles . '">';
	$the_directory .= '<div class="directory directory-' . $context . '" data-offset="'.$offset.'" data-total="'.$total_users.'">';
	

	foreach ( $filtered_users as $user ) {

		$userid = $user->ID;
		$userimg = get_field( 'photo', 'user_' . $userid );
		$has_image = $userimg ? true : false;
		$counter++;
		if ( $offset >= $counter ) { // if offsetting, skip ahead
			continue;
		}
		$username = esc_html( $user->display_name );

		$the_directory .= '<a title="' . $username . '" class="person person-' . $counter . ' person-id-' . $userid . '" href="' . get_author_posts_url( $userid ) . '">';
			$the_directory .= '<span class="directory-img">';
				if ( $has_image ) {
					$the_directory .= wp_get_attachment_image( $userimg, 'medium' );
				} else {
					$hash = md5( strtolower( trim( $user->user_email ) ) );
					$default_img = urlencode( 'https://raw.githubusercontent.com/circlecube/wasmo-theme/main/img/default.png' );
					$gravatar = $hash . '?r=pg&size=300&default=' . $default_img;
					$the_directory .= '<img src="https://www.gravatar.com/avatar/' . $gravatar . '">';
				}
			$the_directory .= '</span>';
			$the_directory .= '<span class="directory-name">' . $username . '</span>';
		$the_directory .= '</a>';
		

		// check counter against limit
		if ( $max_profiles > 0 && $counter >= $max_profiles + $offset ) {
			break;
		}
	}
	if ( $total_users === 0) {
		$the_directory .= '<p>No profiles found here</p>';
	}
	$the_directory .= '</div>';
	if ( $total_users > $max_profiles ) {
		$pl_args = array(
			'base'     => add_query_arg('paged','%#%'),
			'format'   => '',
			'total'    => ceil($total_users / $max_profiles),
			'current'  => max(1, $paged),
			'show_all' => true,
			'type'     => 'list',
		);
		
		// for ".../page/n"
		if($GLOBALS['wp_rewrite']->using_permalinks()) {
			$pl_args['base'] = user_trailingslashit(trailingslashit(get_pagenum_link(1)).'page/%#%/', 'paged');
		}
		$the_directory .= '<div class="directory-pagination">' . paginate_links($pl_args) . '</div>';
	}
	$the_directory .= '</section>';
	
	if ( !current_user_can('administrator') ) { // only save transient if non admin user
		set_transient( $transient_name, $the_directory, $transient_exp );
	}
}
echo $the_directory;