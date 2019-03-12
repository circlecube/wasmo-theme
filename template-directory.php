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

				get_template_part( 'template-parts/content/content', 'page' );

			endwhile; // End of the loop.
			?>

			<?php
			// Directory

			/* Start the Loop */
			$args = array(
				'role'         => '',
				'role__in'     => array(),
				'role__not_in' => array(),
				'meta_key'     => '',
				'meta_value'   => '',
				'meta_compare' => '',
				'meta_query'   => array(),
				'date_query'   => array(),        
				'include'      => array(),
				'exclude'      => array(),
				'orderby'      => 'login',
				'order'        => 'ASC',
				'offset'       => '',
				'search'       => '',
				'number'       => '',
				'count_total'  => false,
				'fields'       => 'all',
				'who'          => '',
			); 
			$users = get_users( $args );

			?>
			<article class="entry">
				<div class="entry-content directory">

				<?php
				// Array of WP_User objects.
				foreach ( $users as $user ) { 
					$userid = $user->ID;

					// only add to directory if user includes themself
					// true = public
					// private = only to a logged in user
					if ( 'true' === get_field( 'in_directory', 'user_' . $userid ) ||
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
			</article>
		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
