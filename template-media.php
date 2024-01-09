<?php
/**
 * Template Name: Media
 * 
 * The template for displaying all media
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0
 */

get_header();

?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main blog-main media-main">
            

			<header class="page-header">
                <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
			</header><!-- .page-header -->

            <?php
            // set up the query for media loop
            $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1; 

            $args = array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'paged'          => $paged, 
                'posts_per_page' => 50,
            );
            $media_query = new WP_Query( $args );

            if ( $media_query->have_posts() ) {

                // Load posts loop
                while ( $media_query->have_posts() ) {
                    $media_query->the_post();
                    $parent_post = get_post_parent();
                    if ( 
                        $parent_post && // only include attachments that are from posts
                        'publish' === $parent_post->post_status && // that are published
                        str_contains( get_post_mime_type(), 'image' ) // and that are image types (i.e. no pdf or video)
                    ) {
                        get_template_part( 'template-parts/content/content', 'loop' );
                    }
                }

                echo wasmo_pagination($paged, $media_query->max_num_pages);
                
            } else {

                // If no content, include the "No posts found" template.
                get_template_part( 'template-parts/content/content', 'none' );

            }
            ?>
        </main>
	</section><!-- .content-area -->

<?php
get_footer();
