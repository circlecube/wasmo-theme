<?php
/**
 * Template part for displaying shelf taxonomy list
 */

?>

<!-- wp:heading {"level":3} -->
<h3>
	<?php wasmo_echo_icon_svg( 'shelf', 24 ); ?>
	Mormon shelf issues:
</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"tags"} -->
<ul class="tags">
<?php
		$shelf_terms = get_terms(
			[
				'taxonomy'   => 'shelf',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);
		foreach ( $shelf_terms as $shelf_term ) :
			?>
	<!-- wp:list-item -->
<li><a class="tag" data-id="<?php echo esc_attr( $shelf_term->term_id ); ?>" href="<?php echo esc_url( get_term_link( $shelf_term ) ); ?>"><?php echo esc_html( $shelf_term->name ); ?></a></li>
<!-- /wp:list-item --><?php endforeach; ?></ul>
<!-- /wp:list -->