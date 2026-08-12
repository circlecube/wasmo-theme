<?php
/**
 * Template part to load related posts for the sepecified taxonomy term.
 */

$related_tax      = get_query_var( 'tax' );
$related_term_id  = get_query_var( 'termid' );


$args = array(
	'post_type'   => 'post',
	'post_status' => 'publish',
	'tax_query'   => array(
		array(
			'taxonomy' => $related_tax,
			'field'    => 'term_id',
			'terms'    => $related_term_id,
		),
	),
);

$query = new WP_Query( $args );

if ( $query->have_posts() ) : ?>
	<h3><em><?php echo esc_html( get_term( $related_term_id )->name ); ?></em><br>
	Related Blog Posts:</h3>
	<ul>
		<?php while ( $query->have_posts() ) : ?>
			<?php $query->the_post(); ?>
			<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
		<?php endwhile; ?>
		<?php wp_reset_postdata(); ?>
	</ul>
<?php endif; ?>