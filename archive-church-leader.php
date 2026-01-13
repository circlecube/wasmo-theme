<?php
/**
 * The template for displaying Church Leader archive
 *
 * Displays current church leadership followed by historical leaders,
 * inspired by churchofjesuschrist.org/learn/global-leadership-of-the-church
 *
 * @package wasmo
 */

get_header();

// Get current leadership
$first_presidency = wasmo_get_current_first_presidency();
$quorum_of_twelve = wasmo_get_current_quorum_of_twelve();

// Get other living general authorities (not in First Presidency or Quorum of Twelve)
$other_living_leaders = array();
$fp_and_twelve = array_merge(
	array(
		$first_presidency['president'],
		$first_presidency['first-counselor'],
		$first_presidency['second-counselor']
	),
	$quorum_of_twelve
);
$fp_and_twelve = array_filter( $fp_and_twelve ); // Remove nulls

$all_living = get_posts( array(
	'post_type'      => 'church-leader',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'meta_query'     => array(
		'relation' => 'OR',
		array(
			'key'     => 'deathdate',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => 'deathdate',
			'value'   => '',
			'compare' => '=',
		),
	),
	'post__not_in'   => $fp_and_twelve,
	'fields'         => 'ids',
) );
$other_living_leaders = $all_living;

// Get past presidents (deceased, ordered by became_president_date)
$past_presidents = get_posts( array(
	'post_type'      => 'church-leader',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'tax_query'      => array(
		array(
			'taxonomy' => 'leader-role',
			'field'    => 'slug',
			'terms'    => 'president',
		),
	),
	'meta_query'     => array(
		array(
			'key'     => 'deathdate',
			'compare' => 'EXISTS',
		),
		array(
			'key'     => 'deathdate',
			'value'   => '',
			'compare' => '!=',
		),
	),
	'meta_key'       => 'became_president_date',
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'fields'         => 'ids',
) );

// Get past apostles (deceased, not presidents)
$past_apostles = get_posts( array(
	'post_type'      => 'church-leader',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'tax_query'      => array(
		array(
			'taxonomy' => 'leader-role',
			'field'    => 'slug',
			'terms'    => 'apostle',
		),
		array(
			'taxonomy' => 'leader-role',
			'field'    => 'slug',
			'terms'    => 'president',
			'operator' => 'NOT IN',
		),
	),
	'meta_query'     => array(
		array(
			'key'     => 'deathdate',
			'compare' => 'EXISTS',
		),
		array(
			'key'     => 'deathdate',
			'value'   => '',
			'compare' => '!=',
		),
	),
	'meta_key'       => 'ordained_date',
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'fields'         => 'ids',
) );

// Get other past leaders (deceased, not apostles)
$past_other = get_posts( array(
	'post_type'      => 'church-leader',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'tax_query'      => array(
		array(
			'taxonomy' => 'leader-role',
			'field'    => 'slug',
			'terms'    => array( 'apostle', 'president' ),
			'operator' => 'NOT IN',
		),
	),
	'meta_query'     => array(
		array(
			'key'     => 'deathdate',
			'compare' => 'EXISTS',
		),
		array(
			'key'     => 'deathdate',
			'value'   => '',
			'compare' => '!=',
		),
	),
	'orderby'        => 'title',
	'order'          => 'ASC',
	'fields'         => 'ids',
) );

?>

<section id="primary" class="content-area">
	<main id="main" class="site-main church-leaders-archive">

		<header class="archive-header">
			<h1 class="archive-title">Church Leadership</h1>
			<p class="archive-description">
				Prophets, apostles, and other leaders of The Church of Jesus Christ of Latter-day Saints today and throughout its history.
			</p>
			<div class="archive-actions">
				<a href="<?php echo home_url( '/church-leader-charts/' ); ?>" class="btn btn-secondary">
					View Data Charts →
				</a>
			</div>
		</header>

		<!-- CURRENT CHURCH LEADERSHIP -->
		<section class="leaders-section leaders-current">
			<h2 class="section-title">Current Church Leadership</h2>

			<!-- First Presidency -->
			<?php if ( $first_presidency['president'] || $first_presidency['first-counselor'] || $first_presidency['second-counselor'] ) : ?>
				<h3 class="group-title">The First Presidency</h3>
				<div class="leadership-group first-presidency-group">
					<?php if ( $first_presidency['first-counselor'] ) : ?>
						<div class="counselor-card">
							<?php wasmo_render_leader_card( $first_presidency['first-counselor'], 'large', true, true, false, 'First Counselor' ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $first_presidency['president'] ) : ?>
						<div class="president-feature">
							<?php wasmo_render_leader_card( $first_presidency['president'], 'large', true, true, false, 'President of the Church' ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $first_presidency['second-counselor'] ) : ?>
						<div class="counselor-card">
							<?php wasmo_render_leader_card( $first_presidency['second-counselor'], 'large', true, true, false, 'Second Counselor' ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Quorum of the Twelve -->
			<?php if ( ! empty( $quorum_of_twelve ) ) : ?>
				<div class="leadership-group twelve-apostles-group">
					<h3 class="group-title">Quorum of the Twelve Apostles</h3>
					<p class="group-description">Listed in order of seniority</p>
					<div class="leaders-grid leaders-grid-4">
						<?php 
						$position = 1;
						foreach ( $quorum_of_twelve as $apostle_id ) : 
						?>
							<div class="apostle-card-wrapper">
								<span class="seniority-number"><?php echo $position; ?></span>
								<?php wasmo_render_leader_card( $apostle_id, 'medium', true, true, false ); ?>
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
						<?php foreach ( $other_living_leaders as $leader_id ) : ?>
							<?php wasmo_render_leader_card( $leader_id, 'small', false, true, true ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

		</section>

		<!-- HISTORICAL LEADERS -->
		<section class="leaders-section leaders-historical">
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
								<?php wasmo_render_leader_card( $president_id, 'medium', true, true, false ); ?>
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
							<?php wasmo_render_leader_card( $apostle_id, 'small', true, true, false ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Other Past Leaders -->
			<?php if ( ! empty( $past_other ) ) : ?>
				<div class="leadership-group past-other-group">
					<h3 class="group-title">Other Historical Leaders</h3>
					<div class="leaders-grid leaders-grid-5">
						<?php foreach ( $past_other as $leader_id ) : ?>
							<?php wasmo_render_leader_card( $leader_id, 'small', true, true, true ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

		</section>

		<footer class="archive-footer">
			<p class="leader-count">
				<?php
				$total = wp_count_posts( 'church-leader' );
				$published = $total->publish;
				printf( 
					_n( '%s church leader in database', '%s church leaders in database', $published, 'wasmo' ),
					number_format_i18n( $published )
				);
				?>
			</p>
		</footer>

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
