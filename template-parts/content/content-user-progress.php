<?php
/**
 * Template part for the "Complete Your Story" progress checklist.
 * Shown only on a user's own profile and the edit page (logged-in only).
 *
 * Expects $userid to be available via set_query_var / load_template extraction.
 */

if ( ! $userid || ! is_user_logged_in() ) {
	return;
}

// Gather completion state for each of the 10 items
$completed = [];

$completed['hi']       = (bool) get_field( 'hi', 'user_' . $userid );
$completed['tagline']  = (bool) get_field( 'tagline', 'user_' . $userid );
$completed['media']    = (bool) get_field( 'photo', 'user_' . $userid )
                      || (bool) get_field( 'video', 'user_' . $userid );
$completed['about_me'] = (bool) get_field( 'about_me', 'user_' . $userid );
$completed['why_left'] = (bool) get_field( 'why_i_left', 'user_' . $userid );

$question_rows = get_field( 'questions', 'user_' . $userid );
$answered = 0;
if ( is_array( $question_rows ) ) {
	foreach ( $question_rows as $row ) {
		if ( ! empty( $row['answer'] ) ) {
			$answered++;
		}
	}
}
$completed['q1']      = $answered >= 1;
$completed['q2']      = $answered >= 2;
$completed['q3']      = $answered >= 3;
$completed['spectrum'] = ! empty( get_field( 'mormon_spectrum', 'user_' . $userid ) );
$completed['shelf']    = ! empty( get_field( 'my_shelf', 'user_' . $userid ) );

$total = count( $completed );
$done  = count( array_filter( $completed ) );
$pct   = $total > 0 ? round( $done / $total * 100 ) : 0;

if ( $pct === 100 ) {
	$message = 'Your story is complete! Thank you for sharing.';
} elseif ( $pct >= 80 ) {
	$message = 'Almost there — just a few more details to add.';
} elseif ( $pct >= 50 ) {
	$message = 'Great progress! Keep going to help others find your story.';
} elseif ( $pct >= 20 ) {
	$message = 'Good start — fill in more to make your story shine.';
} else {
	$message = 'Help others connect with your experience — complete your story.';
}

$items = [
	'hi'       => 'Introduction',
	'tagline'  => 'Tagline',
	'media'    => 'Photo or video',
	'about_me' => 'About me',
	'why_left' => 'Why I left',
	'q1'       => 'Question answer (1 of 3)',
	'q2'       => 'Question answer (2 of 3)',
	'q3'       => 'Question answer (3 of 3)',
	'spectrum' => 'Mormon Spectrum label',
	'shelf'    => 'On my shelf label',
];
?>

<aside class="story-progress" aria-label="Story completion progress">
	<div class="story-progress-header">
		<span class="story-progress-title">Complete Your Story</span>
		<span class="story-progress-count"><?php echo esc_html( $done . ' / ' . $total ); ?></span>
	</div>
	<div class="story-progress-bar-wrap" role="progressbar" aria-valuenow="<?php echo esc_attr( $pct ); ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php echo esc_attr( $pct . '% complete' ); ?>">
		<div class="story-progress-bar" style="width:<?php echo esc_attr( $pct ); ?>%"></div>
	</div>
	<p class="story-progress-message"><?php echo esc_html( $message ); ?></p>
	<ul class="story-progress-checklist">
		<?php foreach ( $items as $key => $label ) : ?>
		<li class="story-progress-item <?php echo $completed[ $key ] ? 'is-done' : 'is-todo'; ?>">
			<span class="story-progress-check" aria-hidden="true"></span>
			<span class="story-progress-item-label"><?php echo esc_html( $label ); ?></span>
		</li>
		<?php endforeach; ?>
	</ul>
	<?php if ( $pct < 100 ) : ?>
	<a class="story-progress-cta" href="<?php echo esc_url( home_url( '/edit/' ) ); ?>">Edit your story</a>
	<?php endif; ?>
</aside>
