<?php
/**
 * The template for displaying archive of profiles for the shelf taxonomy
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0
 */

get_header();

$termid = get_queried_object()->term_id;
$term = get_term_by( 'id', $termid, 'shelf' );
?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">
			<article class="entry">
				<header class="entry-header">
					<h1 class="entry-title"><?php echo wp_kses_post( $term->name ); ?></h1>
                    <p class="entry-description"><?php echo wp_kses_post( $term->description ); ?></p>
                    <?php
                        // TODO:
                        // Add blog posts relevant to this shelf item.
                        // Add links relevant to the shelf item - these can be added to the term description.
                    ?>
					<hr />
					<h3>Profiles with <em><?php echo wp_kses_post( $term->name ); ?></em> on their "shelf":</h3>
				</header><!-- .page-header -->

				<?php 
					// use directory template with taxonomy context and pass in term
					set_query_var( 'context', 'taxonomy' );
					set_query_var( 'tax', 'shelf' );
					set_query_var( 'termid', $termid );
					set_query_var( 'max_profiles', '-1' );
					get_template_part( 'template-parts/content/content', 'directory' );
				?>

				<footer class="entry-footer">
				</footer>

			</article>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();