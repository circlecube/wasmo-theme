<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since Twenty Nineteen 1.0
 */

get_header();
$termid = get_queried_object()->tag_id;
$term = get_term_by( 'id', $termid, 'tags' );
?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main blog-main archive">

		<?php if ( have_posts() ) : ?>

			<header class="page-header">
				<?php the_archive_title( '<h1 class="page-title">', '</h1>' ); ?>
				
				<?php if ( get_query_var('paged') ) {
					echo '<span class="paged-page-number">(Page '. get_query_var('paged') .')</span>';
				} ?>
                <?php if ( tag_description() ) { ?>
                    <h2 class="entry-description has-regular-font-size"><?php echo tag_description(); ?></h2>
                <?php } ?>
			</header><!-- .page-header -->

			<?php
			// Start the Loop.
			while ( have_posts() ) :
				the_post();

				/*
				 * Include the Post-Format-specific template for the content.
				 * If you want to override this in a child theme, then include a file
				 * called content-___.php (where ___ is the Post Format name) and that
				 * will be used instead.
				 */
				get_template_part( 'template-parts/content/content', 'loop' );

				// End the loop.
			endwhile;

			// Previous/next page navigation.
			echo wasmo_pagination();

			// If no content, include the "No posts found" template.
		else :
			get_template_part( 'template-parts/content/content', 'none' );

		endif;
		?>
			<footer class="entry-footer">
				<h3>
					<?php echo wasmo_get_icon_svg( 'tag', 24, 'style="margin-top:-3px;margin-right:0;"' ); ?>
					Tags:
				</h3>
				<ul class="tags">
				<?php
					$terms = get_terms([
						'taxonomy'   => 'post_tag',
						'hide_empty' => true,
						'orderby'    => 'name',
						'order'      => 'ASC',
						'count'      => true,
					]);
					foreach ( $terms as $term ) : 
				?>
						<li>
							<a 
								class="tag" 
								href="<?php echo get_term_link( $term ); ?>"
								data-id="<?php echo esc_attr( $term->term_id ); ?>" 
								data-count="<?php echo esc_attr( $term->count ); ?>"
							>
								<?php echo $term->name; ?>
							</a>
						</li>
				<?php endforeach; ?>
				</ul>
			</footer>
		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
