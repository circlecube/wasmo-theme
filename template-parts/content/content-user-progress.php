<?php
/**
 * Template part for the "Complete Your Story" progress checklist
 * and the "Take Things Further" engagement box.
 * Shown only on a user's own profile and the edit page (logged-in only).
 *
 * Expects $userid to be available via set_query_var / load_template extraction.
 * Also, only displays if a user has saved their profile more than 2 times.
 */

if ( ! $userid || ! is_user_logged_in() ) {
	return;
}

$profile_saves = get_user_meta( $userid, 'save_count', true );
if ( ! $profile_saves || $profile_saves < 2 ) {
	return;
}

$user_data = get_userdata( $userid );
$username  = $user_data ? esc_html( $user_data->display_name ) : '';

// ── Completion checks ─────────────────────────────────────────────────────────

$completed = [];

$completed['hi']       = (bool) get_field( 'hi', 'user_' . $userid );
$completed['tagline']  = (bool) get_field( 'tagline', 'user_' . $userid );
$completed['photo']    = (bool) get_field( 'photo', 'user_' . $userid );
$completed['why_left'] = (bool) get_field( 'why_i_left', 'user_' . $userid );
$completed['about_me'] = (bool) get_field( 'about_me', 'user_' . $userid );

$question_rows = get_field( 'questions', 'user_' . $userid );
$answered      = 0;
if ( is_array( $question_rows ) ) {
	foreach ( $question_rows as $row ) {
		if ( ! empty( $row['answer'] ) ) {
			++$answered;
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

// ── Item labels (ordered: foundation → story → community) ────────────────────

$items = [
	'hi'       => 'Introduction',
	'tagline'  => 'Tagline',
	'photo'    => 'Photo',
	'why_left' => 'Why I left',
	'about_me' => 'About me',
	'q1'       => 'Question answer (1 of 3)',
	'q2'       => 'Question answer (2 of 3)',
	'q3'       => 'Question answer (3 of 3)',
	'spectrum' => 'Mormon Spectrum label',
	'shelf'    => 'On my shelf label',
];

// ── Box state: open/closed/dismissed (server + localStorage in JS) ────────────

$box_states_raw = get_user_meta( $userid, 'wasmo_box_states', true );
$box_states     = [];
if ( $box_states_raw ) {
	$decoded = json_decode( $box_states_raw, true );
	if ( is_array( $decoded ) ) {
		$box_states = $decoded;
	}
}
$progress_open      = isset( $box_states['progress_open'] ) ? (bool) $box_states['progress_open'] : true;
$progress_dismissed = isset( $box_states['progress_dismissed'] ) ? (bool) $box_states['progress_dismissed'] : false;
$further_open       = isset( $box_states['further_open'] ) ? (bool) $box_states['further_open'] : false;
$further_dismissed  = isset( $box_states['further_dismissed'] ) ? (bool) $box_states['further_dismissed'] : false;

// Whether the further box is eligible to show
$show_further = ( $done >= 8 );

// ── Shared nonce for all box AJAX calls ───────────────────────────────────────

$further_nonce = wp_create_nonce( 'wasmo_further_nonce' );

// ── Load further data (needed for JS even when further box isn't shown) ───────

$saved_further = [];
$further_raw   = get_user_meta( $userid, 'wasmo_further_completed', true );
if ( $further_raw ) {
	$decoded = json_decode( $further_raw, true );
	if ( is_array( $decoded ) ) {
		$saved_further = $decoded;
	}
}

// Ordered low-effort → high-commitment
$further_items = [
	'share'    => [
		'label' => 'Share your story on social media',
		'link'  => '#story-share',
		'text'  => 'Jump to share buttons',
	],
	'update'   => [
		'label' => 'Update your profile — each save moves you to the top of the list',
		'link'  => home_url( '/edit/' ),
		'text'  => 'Edit your profile',
	],
	'video'    => [
		'label' => 'Add a video to tell your story',
		'link'  => home_url( '/edit/' ),
		'text'  => 'Edit your profile',
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
	'more-q'   => [
		'label' => 'Answer more questions — or suggest new ones',
		'link'  => home_url( '/questions/' ),
		'text'  => 'See all questions',
	],
	'invite'   => [
		'label' => 'Invite someone else to share their story',
		'link'  => null,
		'text'  => null,
	],
	'post'     => [
		'label' => 'Submit a post or idea for a new article',
		'link'  => home_url( '/contact/' ),
		'text'  => 'Contact us',
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

<?php // Progress box: always in DOM so JS restore can un-hide it after dismissal ?>
<aside class="story-progress" id="story-progress-box"<?php echo $progress_dismissed ? ' style="display:none"' : ''; ?>>
	<details<?php echo $progress_open ? ' open' : ''; ?>>
		<summary>
			<div class="story-progress-header">
				<span class="story-progress-title">Complete Your Story<?php echo $username ? ', ' . $username : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="story-progress-right">
					<span class="story-progress-count"><?php echo esc_html( $done . '/' . $total ); ?></span>
					<span class="story-progress-arrow" aria-hidden="true"></span>
					<button class="story-dismiss-btn" aria-label="Dismiss this box" data-box="progress">&#x2715;</button>
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
// Restore button: always in DOM; JS controls visibility via updateRestoreVisibility()
$any_dismissed = $progress_dismissed || ( $show_further && $further_dismissed );
?>
<button class="story-restore-btn" id="story-restore-btn" aria-label="Restore profile checklist"<?php echo ! $any_dismissed ? ' style="display:none"' : ''; ?>>
	<?php echo wasmo_get_icon_svg( 'checklist', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<span class="screen-reader-text">My Checklist</span>
</button>

<?php if ( $show_further ) : ?>
	<?php // Further box: always in DOM when eligible; hidden via inline style when dismissed ?>
<aside class="story-further" id="story-further-box"<?php echo $further_dismissed ? ' style="display:none"' : ''; ?>>
	<details<?php echo $further_open ? ' open' : ''; ?>>
		<summary>
			<div class="story-progress-header">
				<span class="story-progress-title">Take Things Further</span>
				<span class="story-progress-right">
					<span class="story-progress-arrow" aria-hidden="true"></span>
					<button class="story-dismiss-btn" aria-label="Dismiss this box" data-box="further">&#x2715;</button>
				</span>
			</div>
		</summary>
		<p class="story-progress-message">Your story is in great shape (you've saved it <?php echo esc_html( $profile_saves ); ?> times)! Keep going, but if you're done, here are some ways to go even further and help grow the community. Check them off as you go!</p>
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
<?php endif; // show_further ?>

<script>
(function () {
	var ajaxUrl  = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce    = '<?php echo esc_js( $further_nonce ); ?>';
	var uid      = <?php echo (int) $userid; ?>;
	var lsKey    = 'wasmo-further-' + uid;
	var stateKey = 'wasmo-box-states-' + uid;

	// ── Helpers ───────────────────────────────────────────────────────────────

	function ajax(action, data) {
		var fd = new FormData();
		fd.append('action', action);
		fd.append('nonce', nonce);
		Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
		fetch(ajaxUrl, { method: 'POST', body: fd }).catch(function () {});
	}

	var stateTimer = null;
	function saveBoxStates() {
		clearTimeout(stateTimer);
		stateTimer = setTimeout(function () {
			var s = getBoxStates();
			try { localStorage.setItem(stateKey, JSON.stringify(s)); } catch (e) {}
			ajax('wasmo_save_box_states', {
				progress_open:      s.progress_open      ? '1' : '0',
				further_open:       s.further_open       ? '1' : '0',
				progress_dismissed: s.progress_dismissed ? '1' : '0',
				further_dismissed:  s.further_dismissed  ? '1' : '0',
			});
		}, 600);
	}

	// ── Box state: open/closed/dismissed ──────────────────────────────────────

	// Seed from server (embedded in PHP), then layer localStorage on top
	var serverStates = 
	<?php
	echo wp_json_encode(
		[
			'progress_open'      => $progress_open,
			'further_open'       => $further_open,
			'progress_dismissed' => $progress_dismissed,
			'further_dismissed'  => $further_dismissed,
		]
	);
	?>
	;
	var localStates = {};
	try { localStates = JSON.parse(localStorage.getItem(stateKey) || '{}'); } catch (e) {}

	// localStorage wins for open/closed; server wins for dismissed (more permanent)
	var boxStates = {
		progress_open:      'progress_open'      in localStates ? localStates.progress_open      : serverStates.progress_open,
		further_open:       'further_open'       in localStates ? localStates.further_open       : serverStates.further_open,
		progress_dismissed: serverStates.progress_dismissed,
		further_dismissed:  serverStates.further_dismissed,
	};

	function getBoxStates() { return boxStates; }

	// Apply initial open/closed state (PHP already handles dismissed visibility)
	var progressDetails = document.querySelector('#story-progress-box details');
	var furtherDetails  = document.querySelector('#story-further-box details');

	if (progressDetails) {
		if (boxStates.progress_open) { progressDetails.open = true; }
		else { progressDetails.open = false; }

		progressDetails.addEventListener('toggle', function () {
			boxStates.progress_open = progressDetails.open;
			saveBoxStates();
		});
	}
	if (furtherDetails) {
		if (boxStates.further_open) { furtherDetails.open = true; }
		else { furtherDetails.open = false; }

		furtherDetails.addEventListener('toggle', function () {
			boxStates.further_open = furtherDetails.open;
			saveBoxStates();
		});
	}

	// ── Dismiss buttons ───────────────────────────────────────────────────────

	var restoreBtn = document.getElementById('story-restore-btn');

	function updateRestoreVisibility() {
		if (!restoreBtn) return;
		restoreBtn.style.display = (boxStates.progress_dismissed || boxStates.further_dismissed) ? '' : 'none';
	}

	document.querySelectorAll('.story-dismiss-btn').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var box = btn.getAttribute('data-box');
			if (box === 'progress') {
				boxStates.progress_dismissed = true;
				var el = document.getElementById('story-progress-box');
				if (el) el.style.display = 'none';
			} else if (box === 'further') {
				boxStates.further_dismissed = true;
				var el = document.getElementById('story-further-box');
				if (el) el.style.display = 'none';
			}
			updateRestoreVisibility();
			saveBoxStates();
		});
	});

	function restoreBoxes() {
		boxStates.progress_dismissed = false;
		boxStates.further_dismissed  = false;
		var pb = document.getElementById('story-progress-box');
		var fb = document.getElementById('story-further-box');
		if (pb) pb.style.display = '';
		if (fb) fb.style.display = '';
		updateRestoreVisibility();
		saveBoxStates();
	}

	// Restore button is always in DOM — wire it up and sync initial visibility
	if (restoreBtn) {
		restoreBtn.addEventListener('click', restoreBoxes);
	}
	updateRestoreVisibility();

	// ── Further checkboxes: restore from server + localStorage ────────────────

	var serverChecked = <?php echo wp_json_encode( $saved_further ); ?>;
	var localChecked  = [];
	try { localChecked = JSON.parse(localStorage.getItem(lsKey) || '[]'); } catch (e) {}

	var initialChecked = serverChecked.slice();
	localChecked.forEach(function (k) {
		if (initialChecked.indexOf(k) === -1) initialChecked.push(k);
	});
	try { localStorage.setItem(lsKey, JSON.stringify(initialChecked)); } catch (e) {}

	function saveFurther(checked) {
		try { localStorage.setItem(lsKey, JSON.stringify(checked)); } catch (e) {}
		ajax('wasmo_save_further', { items: JSON.stringify(checked) });
	}

	document.querySelectorAll('.story-further-cb').forEach(function (cb) {
		if (initialChecked.indexOf(cb.name) !== -1) {
			cb.checked = true;
			cb.closest('.story-further-item').classList.add('is-done');
		}
		cb.addEventListener('change', function () {
			var item = cb.closest('.story-further-item');
			item.classList.toggle('is-done', cb.checked);
			var checked = Array.from(
				document.querySelectorAll('.story-further-cb:checked')
			).map(function (el) { return el.name; });
			saveFurther(checked);
		});
	});

})();
</script>
