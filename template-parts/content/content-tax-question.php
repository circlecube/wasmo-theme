<?php
/**
 * Template part for displaying question taxonomy list
 */

?>

<!-- wp:heading {"level":2} -->
<h2 id="all-questions" class="has-regular-font-size">
	<?php wasmo_echo_icon_svg( 'question', 24 ); ?>
	Questions about the Mormon Church:
</h2>
<!-- /wp:heading -->

<ul class="questions">
	<li><a href="<?php echo esc_url( home_url( '/why-i-left/' ) ); ?>" class="question">Why I left?</a></li>
<?php
	// Answered Questions
	$question_terms = get_terms(
		[
			'taxonomy'   => 'question',
			'hide_empty' => false,
			'count'      => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
		]
	);
	// Array of WP_Term objects.
	foreach ( $question_terms as $question_term ) {
		$question_term_id = $question_term->term_id;

		// if has answers
		if ( 0 < $question_term->count ) {
			?>
		<li>
			<a 
				class="question question-<?php echo esc_attr( $question_term_id ); ?>"
				href="<?php echo esc_url( get_term_link( $question_term_id ) ); ?>"
			><?php echo esc_html( $question_term->name ); ?></a>
		</li>
		<?php } else { ?>
		<li><?php echo esc_html( $question_term->name ); ?></li>
			<?php
		}
	}
	?>
</ul>