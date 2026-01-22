<?php
/**
 * The template for displaying Saint archive
 *
 * Displays current church leadership followed by historical leaders,
 * inspired by churchofjesuschrist.org/learn/global-leadership-of-the-church
 *
 * @package wasmo
 */

get_header();

// Get current leadership (these are already cached in their respective functions)
$first_presidency = wasmo_get_current_first_presidency();
$quorum_of_twelve = wasmo_get_current_quorum_of_twelve();

// Build exclusion list for "other living leaders"
$fp_and_twelve = array_merge(
	array(
		$first_presidency['president'],
		$first_presidency['first-counselor'],
		$first_presidency['second-counselor']
	),
	$quorum_of_twelve
);
$fp_and_twelve = array_filter( $fp_and_twelve ); // Remove nulls

// Get other living general authorities (not in First Presidency or Quorum of Twelve) - cached
$other_living_leaders = wasmo_get_cached_all_living( $fp_and_twelve );

// Get past presidents (deceased, ordered by became_president_date) - cached
$past_presidents = wasmo_get_cached_past_presidents();

// Get past apostles (deceased) - cached
$past_apostles = wasmo_get_cached_past_apostles();

// Get other past/current leaders - cached
$past_other = wasmo_get_cached_past_other();
$current_other = wasmo_get_cached_current_other();

// Merge past_other and current_other but remove duplicates
$combined_other = array_unique( array_merge( $past_other, $current_other ) );

// Get wives - cached
$plural_wives = wasmo_get_cached_wives();

?>

<section id="primary" class="content-area">
	<main id="main" class="site-main saints-archive entry">
		<div class="entry-content">
		<header class="archive-header" style="max-width:100%;">
			<h1 class="archive-title">Church Leadership</h1>
			<p class="archive-description">
				Prophets, apostles, and other leaders of The Church of Jesus Christ of Latter-day Saints today and throughout its history.
			</p>
			<div class="archive-actions">
				<a href="<?php echo home_url( '/saint-charts/' ); ?>" class="btn btn-secondary">
					View Data Charts →
				</a>
				<a href="<?php echo home_url( '/plural-wives-and-polygamy/' ); ?>" class="btn btn-secondary">
					View Polygamy Charts →
				</a>
			</div>
		</header>

		<!-- CURRENT CHURCH LEADERSHIP -->
		<section class="leaders-section leaders-current" style="max-width:100%;">
			<h2 class="section-title">Current Leadershipship</h2>

			<!-- First Presidency -->
			<?php if ( $first_presidency['president'] || $first_presidency['first-counselor'] || $first_presidency['second-counselor'] ) : ?>
				<h3 class="group-title">The First Presidency</h3>
				<div class="leadership-group first-presidency-group">
					<div class="leaders-grid leaders-grid-3">
					<?php if ( $first_presidency['first-counselor'] ) : ?>
						<div class="fp-card-wrapper">
							<span class="fp-badge fp-badge-counselor">First Counselor</span>
							<?php wasmo_render_saint_card( $first_presidency['first-counselor'], 'large', true, true, false ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $first_presidency['president'] ) : ?>
						<div class="fp-card-wrapper president-feature">
							<span class="fp-badge fp-badge-president">President</span>
							<?php wasmo_render_saint_card( $first_presidency['president'], 'large', true, true, false ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $first_presidency['second-counselor'] ) : ?>
						<div class="fp-card-wrapper">
							<span class="fp-badge fp-badge-counselor">Second Counselor</span>
							<?php wasmo_render_saint_card( $first_presidency['second-counselor'], 'large', true, true, false ); ?>
						</div>
					<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Quorum of the Twelve -->
			<?php if ( ! empty( $quorum_of_twelve ) ) : ?>
				<div class="leadership-group twelve-apostles-group" style="max-width:100%;">
					<h3 class="group-title">Quorum of the Twelve Apostles</h3>
					<p class="group-description">Listed in order of seniority</p>
					<div class="leaders-grid leaders-grid-6">
						<?php 
						$position = 1;
						foreach ( $quorum_of_twelve as $apostle_id ) : 
						?>
							<div class="apostle-card-wrapper">
								<span class="seniority-number"><?php echo $position; ?></span>
								<?php wasmo_render_saint_card( $apostle_id, 'medium', true, true, false ); ?>
							</div>
						<?php 
							$position++;
						endforeach; 
						?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Other Living General Authorities -->
			<?php if ( ! empty( $other_living_leaders ) ) : ?>
				<div class="leadership-group other-authorities-group">
					<h3 class="group-title">Other General Authorities</h3>
					<div class="leaders-grid leaders-grid-5">
						<?php foreach ( $other_living_leaders as $saint_id ) : ?>
							<?php wasmo_render_saint_card( $saint_id, 'small', false, true, true ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

		</section>

		<!-- HISTORICAL LEADERS -->
		<section class="leaders-section leaders-historical" style="max-width:100%;">
			<h2 class="section-title">Historical Leaders</h2>

			<!-- Past Church Presidents -->
			<?php if ( ! empty( $past_presidents ) ) : ?>
				<div class="leadership-group past-presidents-group">
					<h3 class="group-title">Past Church Presidents</h3>
					<p class="group-description">In chronological order by presidency</p>
					<div class="leaders-timeline">
						<?php 
						$count = 1;
						foreach ( $past_presidents as $president_id ) : 
							$president_date = get_field( 'became_president_date', $president_id );
							$start_year = $president_date ? date( 'Y', strtotime( $president_date ) ) : '';
							$deathdate = get_field( 'deathdate', $president_id );
							$end_year = $deathdate ? date( 'Y', strtotime( $deathdate ) ) : '';
						?>
							<div class="timeline-item">
								<span class="timeline-number"><?php echo $count; ?></span>
								<?php wasmo_render_saint_card( $president_id, 'medium', true, true, false ); ?>
							</div>
						<?php 
							$count++;
						endforeach; 
						?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Past Apostles -->
			<?php if ( ! empty( $past_apostles ) ) : ?>
				<div class="leadership-group past-apostles-group">
					<h3 class="group-title">Past Apostles</h3>
					<p class="group-description">Ordered by ordination date</p>
					<div class="leaders-grid leaders-grid-5">
						<?php foreach ( $past_apostles as $apostle_id ) : ?>
							<?php wasmo_render_saint_card( $apostle_id, 'small', true, true, false ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Plural Wives -->
			<?php /*if ( ! empty( $plural_wives ) ) : ?>
				<div class="leadership-group plural-wives-group">
					<h3 class="group-title">Plural Wives</h3>
					<p class="group-description">Women who entered plural marriages with church leaders</p>
					<div class="leaders-grid leaders-grid-5">
						<?php foreach ( $plural_wives as $saint_id ) : ?>
							<?php wasmo_render_saint_card( $saint_id, 'small', true, false, false ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; */ ?>

			<!-- Other Past Leaders -->
			<?php if ( ! empty( $combined_other ) ) : ?>
				<div class="leadership-group past-other-group">
					<h3 class="group-title">Other Notable or Historical Figures</h3>
					<div class="leaders-grid leaders-grid-5">
						<?php foreach ( $combined_other as $saint_id ) : ?>
							<?php wasmo_render_saint_card( $saint_id, 'small', true, true, true ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

		</section>

		<footer class="archive-footer content-full-width" style="border:0;">
			<h3>
				<?php echo wasmo_get_icon_svg( 'saint', 24 ); ?>
				All Leadership Roles:
			</h3>
			<ul class="tags role-tags">
				<?php
				// Use cached saint roles
				$all_roles = wasmo_get_cached_saint_roles();
				
				foreach ( $all_roles as $role ) : 
				?>
					<li>
						<a class="tag saint-role-tag" 
						   href="<?php echo esc_url( get_term_link( $role ) ); ?>">
							<?php echo esc_html( $role->name ); ?>
							<span class="role-count">(<?php echo esc_html( $role->count ); ?>)</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</footer>
		</div>
	</main>
</section>

<script>
// Simple collapsible functionality
document.addEventListener('DOMContentLoaded', function() {
	const triggers = document.querySelectorAll('.collapsible-trigger');
	
	triggers.forEach(function(trigger) {
		trigger.addEventListener('click', function() {
			const parent = this.closest('.collapsible');
			const content = parent.querySelector('.collapsible-content');
			const icon = this.querySelector('.collapse-icon');
			const isCollapsed = parent.getAttribute('data-collapsed') === 'true';
			
			if (isCollapsed) {
				content.style.display = 'block';
				parent.setAttribute('data-collapsed', 'false');
				icon.textContent = '▲';
			} else {
				content.style.display = 'none';
				parent.setAttribute('data-collapsed', 'true');
				icon.textContent = '▼';
			}
		});
	});

	// Initialize collapsed state
	document.querySelectorAll('.collapsible[data-collapsed="true"] .collapsible-content').forEach(function(content) {
		content.style.display = 'none';
	});
});
</script>

<?php
get_footer();
