<?php
/**
 * Template part to load related posts for the sepecified taxonomy term.
 * 
 */

$tax    = get_query_var('tax');
$termid = get_query_var('termid');


$args = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'tax_query' => array(
        array(
            'taxonomy' => $tax,
            'field'    => 'term_id',
            'terms'    => $termid
        )
    )
);

$query = new WP_Query( $args );

if ( $query->have_posts() ) : ?>
    <h3>Blog Posts Related to <em><?php echo get_term( $termid )->name; ?></em>:</h3>
    <ul>
        <?php while ( $query->have_posts() ) : ?>
            <?php $query->the_post(); ?>
            <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
    </ul>
<?php endif; ?>