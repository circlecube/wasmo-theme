<?php
/**
 * Template Name: Directory
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
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php if ( ! twentynineteen_can_show_post_thumbnail() ) : ?>
	<header class="entry-header">
		<?php get_template_part( 'template-parts/header/entry', 'header' ); ?>
	</header>
	<?php endif; ?>

	<?php
	// Directory
	/* Start the Loop */
	$args = array(
		'orderby'      => 'meta_value',
		'meta_key'     => 'last_save',
		'order'        => 'DESC',
		'fields'       => 'all',
	); 
	$users = get_users( $args );

	?>
	<section class="entry-content the-directory">
		<div class="directory">

		<?php
		// Array of WP_User objects.
		foreach ( $users as $user ) { 
			$userid = $user->ID;

			// only add to directory if user includes themself and has filled out the first two fields
			// true = public
			// private = only to a logged in user
			if ( get_field( 'hi', 'user_' . $userid ) && 
				get_field( 'tagline', 'user_' . $userid ) &&
				'true' === get_field( 'in_directory', 'user_' . $userid ) ||
				'private' === get_field( 'in_directory', 'user_' . $userid ) && is_user_logged_in() ) {
				$userimg = get_field( 'photo', 'user_' . $userid );
				$username = esc_html( $user->nickname );
			?>
			<a class="person person-<?php echo $userid; ?>" href="<?php echo get_author_posts_url( $userid ); ?>">
				<span class="directory-img"><?php 
					if ( $userimg ) {
						echo wp_get_attachment_image( $userimg, 'medium' );
					} else {
						echo '<img src="' . get_stylesheet_directory_uri() . '/img/default.svg">';
					}
				?></span>
				<span class="directory-name"><?php echo $username; ?></span>
			</a>
			<?php 
			}
		}
		?>
		</div>
	</section>

	<div class="entry-content">
		<?php
		the_content();

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . __( 'Pages:', 'twentynineteen' ),
				'after'  => '</div>',
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
	</section><!-- #primary -->

<?php
get_footer();