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

			<div class="directory">
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
			// Array of WP_User objects.
			foreach ( $users as $user ) { 
				$userid = $user->ID;

				// only add to directory if user includes themself
				if ( get_field( 'in_directory', 'user_' . $userid ) ) {
				?>
				<div class="person person-<?php echo $userid; ?>">
					<span><?php 
						$userimg = get_field( 'photo', 'user_' . $userid );
						if ( $userimg ) {
							echo wp_get_attachment_image( $userimg, 'medium' );
						}
					?></span>
					<span><?php esc_html( $user->display_name ); ?></span>
				</div>
				<?php 
				}
			}
			?>
			</div>
		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
