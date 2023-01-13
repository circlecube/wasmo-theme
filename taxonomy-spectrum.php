<?php
/**
 * The template for displaying archive of profiles for the spectrums taxonomy
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0
 */

get_header();

$termid = get_queried_object()->term_id;
$term = get_term_by( 'id', $termid, 'spectrum' );
?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">
			<article class="entry">
				<header class="entry-header">
					<h1 class="entry-title">
						<?php echo wasmo_get_icon_svg( 'spectrum', 36 ); ?>
						<?php echo wp_kses_post( $term->name ); ?>
					</h1>
                    <h2 class="entry-description has-regular-font-size"><?php echo wp_kses_post( $term->description ); ?></h2>
					<hr />
					<h3 class="has-small-font-size">Profiles self-identifying as <em><?php echo wp_kses_post( $term->name ); ?></em> on the mormon spectrum:</h3>
				</header><!-- .page-header -->

				<?php 
					// use directory template with taxonomy context and pass in term
					set_query_var( 'context', 'tax' );
					set_query_var( 'tax', 'spectrum' );
					set_query_var( 'termid', $termid );
					get_template_part( 'template-parts/content/content', 'directory' );
				?>

				<footer class="entry-footer">
					<h3>
						<?php echo wasmo_get_icon_svg( 'spectrum', 24, 'style="margin-top:-3px;margin-right:0;"' ); ?>
						Mormon Spectrum:
					</h3>
					<ul class="tags">
					<?php
						$terms = get_terms([
							'taxonomy'   => 'spectrum',
							'hide_empty' => false,
							'orderby'    => 'name',
							'order'      => 'ASC'
						]);
						foreach ( $terms as $term ) : 
							// if ( $termid !== $term->term_id ) :
					?>
						<li><a class="tag" data-id="<?php echo esc_attr( $term->term_id) ?>" href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a></li>
					<?php 
							// endif;
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