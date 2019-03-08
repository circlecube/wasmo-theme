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
			$wasmos = get_users( $args );
			// Array of WP_User objects.
			foreach ( $wasmos as $wasmo ) {
				echo '<span>' . esc_html( $wasmo->display_name ) . '</span>';
			}
			?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
