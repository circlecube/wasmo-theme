<?php
/**
 * Template Name: Profile
 * 
 * The template for displaying all profiles in a directory
 *
 * @subpackage wasmo
 * @since 1.0.0
 */

get_header();
?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">

			<?php
				global $current_user;
				$user = wp_get_current_user();
				$userid = $user->ID;
			?>

			<?php //set_query_val( 'userid', $userid ); ?>
			<?php //get_template_part( 'partials/content', 'user' ); ?>

<div class="name"><?php echo esc_html( $user->display_name ); ?></div>
<div class="user_photo"><?php 
$userimg = get_field( 'photo', 'user_' . $userid );
if ( $userimg ) {
	echo wp_get_attachment_image( $userimg, 'medium' );
}
?></div>
<div class="hi"><?php echo get_field( 'hi', 'user_' . $userid ); ?></div>
<div class="tagline"><?php echo get_field( 'tagline', 'user_' . $userid ); ?></div>
<h3>Why I left</h3>
<div class="why_i_left"><?php echo get_field( 'why_i_left', 'user_' . $userid ); ?></div>
<h3>Questions</h3>
<?php
//questions repeater
if( have_rows( 'questions', 'user_' . $userid ) ):

 	// loop through the rows of data
	while ( have_rows( 'questions', 'user_' . $userid ) ) : 
		the_row();

        echo '<h4 class="question">';
        echo get_sub_field( 'question', 'users_' . $userid );
		echo '</h4>';
		echo get_sub_field( 'answer', 'users_' . $userid );

    endwhile;

else :
    // no questions found
endif;
?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
