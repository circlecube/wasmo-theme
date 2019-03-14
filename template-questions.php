<?php
/**
 * Template Name: Questions
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

			/* Start the Loop */
			while ( have_posts() ) :
				the_post();

				get_template_part( 'template-parts/content/content', 'page' );

			endwhile; // End of the loop.
			?>

			<?php
			// Questions

			/* Start the Loop */
			$terms = get_terms([
				'taxonomy' => 'question',
				'hide_empty' => false,
			]);

			?>
			<article class="entry">
				<div class="entry-content">
					<ul class="questions">
				<?php
				// Array of WP_User objects.
				foreach ( $terms as $term ) { 
					$termid = $term->term_id;

					?>
					<li><a class="question question-<?php echo $termid; ?>" 
						href="<?php echo get_term_link( $termid ); ?>">
						<?php echo $term->name; ?>
					</a></li>
					<?php 
				}
				?>
				</ul>
				</div>
			</article>
		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();