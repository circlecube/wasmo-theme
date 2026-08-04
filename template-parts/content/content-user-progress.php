<?php
/**
 * Template part for the "Complete Your Story" progress checklist
 * and the "Take Things Further" engagement box.
 * Shown only on a user's own profile and the edit page (logged-in only).
 *
 * Expects $userid to be available via set_query_var / load_template extraction.
 */

if ( ! $userid || ! is_user_logged_in() ) {
	return;
}

$user_data = get_userdata( $userid );
$username  = $user_data ? esc_html( $user_data->display_name ) : '';

// ── Completion checks ─────────────────────────────────────────────────────────

$completed = [];

$completed['hi']       = (bool) get_field( 'hi', 'user_' . $userid );
$completed['tagline']  = (bool) get_field( 'tagline', 'user_' . $userid );
$completed['photo']    = (bool) get_field( 'photo', 'user_' . $userid );
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
$completed['q1']       = $answered >= 1;
$completed['q2']       = $answered >= 2;
$completed['q3']       = $answered >= 3;
$completed['spectrum'] = ! empty( get_field( 'mormon_spectrum', 'user_' . $userid ) );
$completed['shelf']    = ! empty( get_field( 'my_shelf', 'user_' . $userid ) );

$total = count( $completed );
$done  = count( array_filter( $completed ) );
$pct   = $total > 0 ? (int) round( $done / $total * 100 ) : 0;

// ── Encouragement message ─────────────────────────────────────────────────────

if ( $pct >= 100 ) {
	$message = "Amazing work, {$username} — your story is complete! Thank you for sharing.";
} elseif ( $pct >= 70 ) {
	$message = "You're almost there, {$username}! Just a couple more details will make your story shine.";
} elseif ( $pct >= 40 ) {
	$message = "Great start, {$username}! Keep going — each section helps others connect with your experience.";
} else {
	$message = "Hi {$username}! Help others find your story by filling in a few more details below.";
}

// ── Item labels ───────────────────────────────────────────────────────────────

$items = [
	'hi'       => 'Introduction',
	'tagline'  => 'Tagline',
	'photo'    => 'Photo',
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
	<details open>
		<summary>
			<div class="story-progress-header">
				<span class="story-progress-title">Complete Your Story<?php echo $username ? ', ' . $username : ''; ?></span>
				<span class="story-progress-right">
					<span class="story-progress-count"><?php echo esc_html( $done . '/' . $total ); ?></span>
					<span class="story-progress-arrow" aria-hidden="true"></span>
				</span>
			</div>
			<div class="story-progress-bar-wrap" role="progressbar" aria-valuenow="<?php echo esc_attr( $pct ); ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php echo esc_attr( $pct . '% complete' ); ?>">
				<div class="story-progress-bar" style="width:<?php echo esc_attr( $pct ); ?>%"></div>
			</div>
		</summary>
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
	</details>
</aside>

<?php
// ── "Take Things Further" box — shown when story is nearly complete (8+/10) ──
if ( $done < 8 ) {
	return;
}

$further_items = [
	'share'    => [
		'label' => 'Share your story on social media',
		'link'  => '#story-share',
		'text'  => 'Jump to the share buttons',
	],
	'video'    => [
		'label' => 'Add a video to tell your story',
		'link'  => home_url( '/edit/' ),
		'text'  => 'Edit your profile',
	],
	'more-q'   => [
		'label' => 'Answer more questions — or suggest new ones',
		'link'  => home_url( '/questions/' ),
		'text'  => 'See all questions',
	],
	'post'     => [
		'label' => 'Submit a post or idea for a new article',
		'link'  => home_url( '/contact/' ),
		'text'  => 'Contact us',
	],
	'react'    => [
		'label' => 'Read other profiles and add reactions',
		'link'  => home_url( '/profiles/' ),
		'text'  => 'Browse stories',
	],
	'comment'  => [
		'label' => 'Comment on other profiles',
		'link'  => home_url( '/profiles/' ),
		'text'  => 'Browse stories',
	],
	'update'   => [
		'label' => 'Update your profile — each save moves you to the top of the list',
		'link'  => home_url( '/edit/' ),
		'text'  => 'Edit your profile',
	],
	'invite'   => [
		'label' => 'Invite someone else to share their story',
		'link'  => null,
		'text'  => null,
	],
	'feedback' => [
		'label' => 'Send a feature request or idea',
		'link'  => home_url( '/contact/' ),
		'text'  => 'Contact form',
	],
	'donate'   => [
		'label' => 'Consider a donation to help keep the site running',
		'link'  => home_url( '/donate/' ),
		'text'  => 'Donate',
	],
];
?>

<aside class="story-further" aria-label="Take things further">
	<details>
		<summary>
			<div class="story-progress-header">
				<span class="story-progress-title">Take Things Further<?php echo $username ? ', ' . $username : ''; ?></span>
				<span class="story-progress-right">
					<span class="story-progress-arrow" aria-hidden="true"></span>
				</span>
			</div>
		</summary>
		<p class="story-progress-message">Your story is in great shape — here are some ways to go even further and help grow the community. Check them off as you go!</p>
		<ul class="story-further-checklist">
			<?php foreach ( $further_items as $key => $item ) : ?>
			<li class="story-further-item" data-key="<?php echo esc_attr( $key ); ?>">
				<label class="story-further-label">
					<input class="story-further-cb" type="checkbox" name="<?php echo esc_attr( $key ); ?>">
					<span class="story-progress-check" aria-hidden="true"></span>
					<span class="story-further-item-label">
						<?php echo esc_html( $item['label'] ); ?>
						<?php if ( $item['link'] ) : ?>
						<a class="story-further-link" href="<?php echo esc_url( $item['link'] ); ?>"><?php echo esc_html( $item['text'] ); ?></a>
						<?php endif; ?>
					</span>
				</label>
			</li>
			<?php endforeach; ?>
		</ul>
	</details>
</aside>

<script>
(function () {
	var storageKey = 'wasmo-further-<?php echo (int) $userid; ?>';
	var saved = [];
	try { saved = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch (e) {}

	document.querySelectorAll('.story-further-cb').forEach(function (cb) {
		if (saved.indexOf(cb.name) !== -1) {
			cb.checked = true;
			cb.closest('.story-further-item').classList.add('is-done');
		}
		cb.addEventListener('change', function () {
			cb.closest('.story-further-item').classList.toggle('is-done', cb.checked);
			var checked = Array.from(
				document.querySelectorAll('.story-further-cb:checked')
			).map(function (el) { return el.name; });
			try { localStorage.setItem(storageKey, JSON.stringify(checked)); } catch (e) {}
		});
	});
})();
</script>
