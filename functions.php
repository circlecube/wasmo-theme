<?php


// Enqueue styles - get parent theme styles first.
function my_theme_enqueue_styles() {

    $parent_style = 'parent-style'; // This is 'twentynineteen-style' for the Twenty Nineteen theme.

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );


// theme mods
// set_theme_mod( 'page_layout', 'one-column' );



// Allow front end acf form edits
// https://usersinsights.com/acf-user-profile/
function my_acf_user_form_func( $atts ) {
 
  $a = shortcode_atts( array(
    'field_group' => ''
  ), $atts );
 
  $uid = get_current_user_id();
  
  if ( ! empty ( $a['field_group'] ) && ! empty ( $uid ) ) {
    $options = array(
      'post_id' => 'user_'.$uid,
      'field_groups' => array( intval( $a['field_group'] ) ),
      'return' => add_query_arg( 'updated', 'true', get_permalink() )
    );
    
    ob_start();
    
    acf_form( $options );
    $form = ob_get_contents();
    
    ob_end_clean();
  }
  
    return $form;
}
 
add_shortcode( 'my_acf_user_form', 'my_acf_user_form_func' );


//adding AFC form head
function wasmo_add_acf_form_head(){
    global $post;
    
  if ( !empty($post) && has_shortcode( $post->post_content, 'my_acf_user_form' ) ) {
        acf_form_head();
    }
}
add_action( 'wp_head', 'wasmo_add_acf_form_head', 7 );


// hide admin bar for non admin users
add_action( 'set_current_user', 'wasmo_hide_admin_bar' );
function wasmo_hide_admin_bar() {
	if ( !current_user_can( 'edit_posts' ) ) {
		show_admin_bar( false );
	}
}

// function wasmo_member_template_redirect() {
//     global $wp_query;

//     if( 
// 		array_key_exists('author_name', $wp_query->query_vars) && 
// 		!empty($wp_query->query_vars['author_name'])
// 	) {
// 		global $member;
// 		$member = new WP_User( $wp_query->query_vars["author_name"] );
// 		if( $member ) {
// 			include( TEMPLATEPATH . "/member.php" );
// 			exit;
// 		}
// 	}
// }
// add_action( 'template_redirect', 'wasmo_member_template_redirect' );


?>