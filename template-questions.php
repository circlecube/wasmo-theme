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
			// Answered Questions
			$terms = get_terms([
				'taxonomy'   => 'question',
				'hide_empty' => false,
				'count'      => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
			]);

			?>
			<article class="entry the-questions">
				<div class="entry-content">
					<ul class="questions">
						<li><a href="<?php echo home_url( '/why-i-left/' ); ?>" class="question">Why I left?</a></li>
				<?php
				// Array of WP_Term objects.
				foreach ( $terms as $term ) { 
					$termid = $term->term_id;

					// if has answers
					if ( 0 < $term->count ) {
					?>
					<li>
						<a 
							class="question question-<?php echo $termid; ?>" 
							href="<?php echo get_term_link( $termid ); ?>"
						><?php echo $term->name; ?></a>
					</li>
					<?php
					} else {
					?>
					<li><?php echo $term->name; ?></li>
					<?php 
					}
				}
				?>
				</ul>
				</div>
			</article>
		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();