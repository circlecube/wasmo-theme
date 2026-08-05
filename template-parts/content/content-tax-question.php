<?php
/**
 * Template part for displaying question taxonomy list
 */

?>

<!-- wp:heading {"level":2} -->
<h2 id="all-questions" class="has-regular-font-size">
	<?php echo wasmo_get_icon_svg( 'question', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	Questions about the Mormon Church:
</h2>
<!-- /wp:heading -->

<ul class="questions">
	<li><a href="<?php echo home_url( '/why-i-left/' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" class="question">Why I left?</a></li>
<?php
	// Answered Questions
	$terms = get_terms(
		[
			'taxonomy'   => 'question',
			'hide_empty' => false,
			'count'      => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
		]
	);
	// Array of WP_Term objects.
	foreach ( $terms as $term ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$termid = $term->term_id;

		// if has answers
		if ( 0 < $term->count ) {
			?>
		<li>
			<a 
				class="question question-<?php echo $termid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
				href="<?php echo get_term_link( $termid ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
			><?php echo $term->name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		</li>
		<?php } else { ?>
		<li><?php echo $term->name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
			<?php
		}
	}
	?>
</ul>