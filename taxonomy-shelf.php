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
					<h1 class="entry-title">
						<?php echo wasmo_get_icon_svg( 'shelf', 36 ); ?>
						<?php echo wp_kses_post( $term->name ); ?>
					</h1>
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
					set_query_var( 'context', 'tax' );
					set_query_var( 'tax', 'shelf' );
					set_query_var( 'termid', $termid );
					get_template_part( 'template-parts/content/content', 'directory' );
				?>

				<footer class="entry-footer">
					<h3>
					<?php echo wasmo_get_icon_svg( 'shelf', 24, 'style="margin-top:-3px;margin-right:0;"' ); ?>
						Other Shelf Items:
					</h3>
					<ul class="tags">
					<?php
						$terms = get_terms([
							'taxonomy'   => 'shelf',
							'hide_empty' => false,
							'orderby'    => 'name',
							'order'      => 'ASC'
						]);
						foreach ( $terms as $term ) : 
							if ( $termid !== $term->term_id ) :
					?>
						<li><a class="tag" data-id="<?php echo esc_attr( $term->term_id) ?>" href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a></li>
					<?php 
							endif;
						 endforeach; 
					?>
					</ul>

					<?php
						// load related posts for this term
						get_template_part( 'template-parts/content/taxonomy', 'relatedposts' );
					?>
				</footer>

			</article>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();