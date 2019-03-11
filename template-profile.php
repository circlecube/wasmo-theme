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

			<article class="entry">
				<div class="entry-content">
				<?php
					$user = wp_get_current_user();
					$userid = $user->ID;
				?>
				<?php set_query_var( 'userid', $userid ); ?>
				<?php get_template_part( 'partials/content', 'user' ); ?>
				
				</div>
			</article>
		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
