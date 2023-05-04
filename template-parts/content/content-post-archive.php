<?php
/**
 * Template part for displaying all blog posts with data headings
 */

?>

<?php

// Query args
$spotlight_args = array(
    'category__in'   => 440, // exclude spotlight
    'nopaging'       => true,
    'order'          => 'DESC',
    'order_by'       => 'date',
    'posts_per_page' => -1,
    'post_type'      => 'post',
    // 'post_status'    => array( 'publish', 'pending', 'future' ),
);
// The Query
$spotlight_query = new WP_Query( $spotlight_args );

// The Loop
if ( $spotlight_query->have_posts() ) {
    ?>
    <h2 id="spotlights" class="has-regular-font-size">Spotlights:</h2>
    <div class="wp-block-query alignwide">
        <ul class="is-flex-container columns-3 wp-block-post-template is-layout-flow">

    <?php
        while ( $spotlight_query->have_posts() ) {
            $spotlight_query->the_post();
            ?>
            <li class="wp-block-post">
                <figure class="wp-block-post-featured-image">
                    <a 
                        href="<?php the_permalink(); ?>" 
                        title="<?php the_title(); ?>" 
                    >
                        <?php the_post_thumbnail( 'medium' ); ?>
                    </a>
                </figure>
            </li>
            <?php
        }
    ?>

        </ul>
    </div>
    <?php
    /* Restore original Post Data */
    wp_reset_postdata();
}
?>


<?php

// Query args
$blog_args = array(
    'category__not_in' => 440, // exclude spotlight
    'nopaging'         => true,
    'order'            => 'DESC',
    'order_by'         => 'date',
    'posts_per_page'   => -1,
    'post_type'        => 'post',
    // 'post_status'      => array( 'publish', 'pending', 'future' ),
);
// The Query
$blog_query = new WP_Query( $blog_args );

// The Loop
if ( $blog_query->have_posts() ) {
    $year = '';
    $month = '';
    ?>
    <h2 id="all-posts" class="has-regular-font-size">Post Archive:</h2>
    <div class="wp-block-query alignwide is-layout-flow">
        <ul class="blog-posts-archive">
    <?php
    while ( $blog_query->have_posts() ) {
        $blog_query->the_post();
        $this_month = get_the_date("F");
        $this_year = get_the_date("Y");
        if ( $this_month !== $month ) {
            $month = $this_month;
            echo '</ul>'; // close list
            if ( $this_year !== $year ) {
                $year = $this_year;
                echo '<h3>' . $year . '</h3>'; // year heading
            }
            echo '<h4>' . $month . '</h4>'; // month heading
            echo '<ul class="blog-posts-archive">'; // reopen list
        }
        echo '<li><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></li>';
    }
    ?>
        </ul>
    </div>
    <?php
    /* Restore original Post Data */
    wp_reset_postdata();
}
?>