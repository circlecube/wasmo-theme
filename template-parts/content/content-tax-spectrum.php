<?php
/**
 * Template part for displaying spectrum taxonomy list
 */

?>

<!-- wp:heading {"level":3} -->
<h3>
	<?php echo wasmo_get_icon_svg( 'spectrum', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	Mormon Spectrum:
</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"tags"} -->
<ul class="tags">
<?php
		$terms = get_terms(
			[
				'taxonomy'   => 'spectrum',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);
		foreach ( $terms as $term ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			?>
	<!-- wp:list-item -->
<li><a class="tag" data-id="<?php echo esc_attr( $term->term_id ); ?>" href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></li>
<!-- /wp:list-item --><?php endforeach; ?></ul>
<!-- /wp:list -->