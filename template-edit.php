<?php
/**
 * Template Name: Edit Profile
 *
 * This is the template that allows a contributor to edit their profile. 
 * Redirect user if not logged in.
 *
 * @subpackage wasmo
 * @since 1.0.0
 */

//if not logged in, reditect to login page
if ( !is_user_logged_in() ) {
	//reditect to login page
	wp_safe_redirect( home_url( '/login/' ) );
	exit;
}
acf_form_head();
get_header(); ?>

<div class="wrap">
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<?php
			while ( have_posts() ) :
				the_post();

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php if ( ! twentynineteen_can_show_post_thumbnail() ) : ?>
	<header class="entry-header">
		<?php get_template_part( 'template-parts/header/entry', 'header' ); ?>
	</header>
	<?php endif; ?>

	<div class="entry-content">
		<?php 
			the_content();
			acf_form(
				array(
					'post_id' => 'user_' . get_current_user_id(),
					'field_groups' => array( 4 ),
					'return' => get_author_posts_url( get_current_user_id() )
				)
			);
			?>
	</div><!-- .entry-content -->

	<?php if ( get_edit_post_link() ) : ?>
		<footer class="entry-footer">
			<?php
			edit_post_link(
				sprintf(
					wp_kses(
						/* translators: %s: Name of current post. Only visible to screen readers */
						__( 'Edit <span class="screen-reader-text">%s</span>', 'twentynineteen' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					),
					get_the_title()
				),
				'<span class="edit-link">',
				'</span>'
			);
			?>
		</footer><!-- .entry-footer -->
	<?php endif; ?>
</article><!-- #post-<?php the_ID(); ?> -->

			<?php
			endwhile; // End of the loop.
			?>

		</main><!-- #main -->
	</div><!-- #primary -->
</div><!-- .wrap -->

<?php
get_footer();
