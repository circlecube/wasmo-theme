<?php
/**
 * Template part for displaying spectrum taxonomy list
 */

?>

<!-- wp:heading {"level":3} -->
<h3>
	<?php wasmo_echo_icon_svg( 'spectrum', 24 ); ?>
	Mormon Spectrum:
</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"tags"} -->
<ul class="tags">
<?php
		$spectrum_terms = get_terms(
			[
				'taxonomy'   => 'spectrum',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);
		foreach ( $spectrum_terms as $spectrum_term ) :
			?>
	<!-- wp:list-item -->
<li><a class="tag" data-id="<?php echo esc_attr( $spectrum_term->term_id ); ?>" href="<?php echo esc_url( get_term_link( $spectrum_term ) ); ?>"><?php echo esc_html( $spectrum_term->name ); ?></a></li>
<!-- /wp:list-item --><?php endforeach; ?></ul>
<!-- /wp:list -->