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

			<?php set_query_val( 'userid', $userid ); ?>
			<?php get_template_part( 'partials/content', 'user' ); ?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
