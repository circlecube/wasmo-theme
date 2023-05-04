<?php
/**
 * Template Name: Post Archive
 * 
 * The template for displaying all posts and questions in lists
 *
 * @subpackage wasmo
 * @since 1.0.0
 */

get_header();
?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">

			<?php

			/* Start the Loop */
			while ( have_posts() ) :
				the_post();

				get_template_part( 'template-parts/content/content', 'page' );

			endwhile; // End of the loop.
			?>

			<section class="entry the-posts">
				<div class="entry-content">
					<?php get_template_part(
						'template-parts/content/content',
						'post-archive'
					); ?>
				</div>
			</section>

			<section class="entry the-questions">
				<div class="entry-content">
					<?php get_template_part(
						'template-parts/content/content',
						'tax-question'
					); ?>	
				</div>
			</section>
		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();