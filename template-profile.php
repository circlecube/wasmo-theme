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
echo '<span>' . esc_html( $user->display_name ) . '</span>';
echo '<span>' . get_field('hi', 'user_'.$user->ID) . '</span>';

			?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
