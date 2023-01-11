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
if(!function_exists("filter_directory")) {
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
}}
if(!function_exists("filter_directory_for_tax")) {
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
}}


$transient_name = implode('-', array( 'wasmo_directory', $state, $context, $max_profiles, 'page_' . $paged ) );
$transient_exp = WEEK_IN_SECONDS;
// wasmo_delete_transients_with_prefix( 'wasmo_directory' );
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
		$tax_filtered_users = array_filter( $filtered_users, "filter_directory_for_tax" );
		$filtered_users = $tax_filtered_users;
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
		$registered = $user->user_registered;
		// echo $registered;
		$user_class = '';
		$diff = (int) abs( time() - strtotime($registered) );
		if ( $diff < WEEK_IN_SECONDS ) {
			$user_class .= ' user-week';
		} else if ( $diff < MONTH_IN_SECONDS ) {
			$user_class .= ' user-month';
		}

		$fresh_class = '';
		$last_save = intval( get_user_meta( $userid, 'last_save', true ) );
		$diff = (int) abs( time() - $last_save );
		if ( $diff < WEEK_IN_SECONDS ) {
			$fresh_class .= ' updated-week';
		} else if ( $diff < MONTH_IN_SECONDS ) {
			$fresh_class .= ' updated-month';
		} else {
			// $fresh_class .= ' updated-old';
		}
		
		$the_directory .= '<a title="' . $username . '" class="';
		$the_directory .= ' person person-' . $counter;
		$the_directory .= ' person-id-' . $userid . ' ' . $user_class . ' ' . $fresh_class;
		$the_directory .= '" href="' . get_author_posts_url( $userid ) . '">';
			$the_directory .= '<span class="directory-img">';
			$the_directory .= wasmo_get_user_image( $userid );
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
	if ( 'full' === $context && $total_users > $max_profiles ) {
		$big = 999999;
		$profiles_url = get_permalink( get_page_by_path( 'profiles' ) );
		$pl_args = array(
			'base'     => str_replace( $big, '%#%', esc_url( $profiles_url . 'page/' . $big . '/' ) ),
			'format'   => '',
			'total'    => ceil( $total_users / $max_profiles ),
			'current'  => max( 1, $paged ),
			'show_all' => true,
			'type'     => 'list',
			'prev_text'=> sprintf(
				'%s <span class="nav-prev-text">%s</span>',
				twentynineteen_get_icon_svg( 'chevron_left', 22 ),
				__( 'Newer profiles', 'twentynineteen' )
			),
			'next_text'=> sprintf(
				'<span class="nav-next-text">%s</span> %s',
				__( 'Older profiles', 'twentynineteen' ),
				twentynineteen_get_icon_svg( 'chevron_right', 22 )
			),
		);
		
		$the_directory .= '<div class="directory-pagination">' . paginate_links( $pl_args ) . '</div>';
	}
	$the_directory .= '</section>';
	
	if ( !current_user_can( 'administrator' ) ) { // only save transient if non admin user
		set_transient( $transient_name, $the_directory, $transient_exp );
	}
}
echo $the_directory;
if ( $context === 'full' ) {
	get_template_part( 'template-parts/content/content', 'taxonomies' );
}