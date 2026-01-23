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

// Get current leadership (needed for exclusion list in "Other Living" block)
$first_presidency = wasmo_get_current_first_presidency();
$quorum_of_twelve = wasmo_get_current_quorum_of_twelve();

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
			<?php
			echo render_block( array(
				'blockName'    => 'wasmo/saints-leadership',
				'attrs'        => array(
					'leadershipGroup' => 'first-presidency',
					'showTitle'       => true,
					'showDescription' => false,
					'showBadges'      => true,
					'cardSize'        => 'large',
				),
				'innerContent' => array(),
			) );
			?>

			<!-- Quorum of the Twelve -->
			<?php
			echo render_block( array(
				'blockName'    => 'wasmo/saints-leadership',
				'attrs'        => array(
					'leadershipGroup' => 'quorum-of-twelve',
					'showTitle'       => true,
					'showDescription' => true,
					'showBadges'      => true,
					'cardSize'        => 'medium',
				),
				'innerContent' => array(),
			) );
			?>

			<!-- Other Living General Authorities -->
			<?php
			// Build exclusion list for the block
			$fp_and_twelve_ids = array_merge(
				array(
					$first_presidency['president'],
					$first_presidency['first-counselor'],
					$first_presidency['second-counselor']
				),
				$quorum_of_twelve
			);
			$fp_and_twelve_ids = array_filter( $fp_and_twelve_ids ); // Remove nulls
			
			echo render_block( array(
				'blockName'    => 'wasmo/saints-leadership',
				'attrs'        => array(
					'filterMode'      => 'custom',
					'roleFilter'      => array( 'wife' ),
					'roleFilterOperator' => 'NOT IN',
					'livingStatus'    => 'living',
					'excludeIds'      => array_values( $fp_and_twelve_ids ),
					'orderBy'         => 'title',
					'order'           => 'ASC',
					'gridColumns'     => 5,
					'layout'          => 'grid',
					'showTitle'       => true,
					'customTitle'     => 'Other General Authorities',
					'showDescription' => false,
					'showBadges'      => false,
					'cardSize'        => 'small',
					'showAgeDates'    => false,
					'showServiceDates' => true,
					'showRoleBadge'   => true,
				),
				'innerContent' => array(),
			) );
			?>

		</section>

		<!-- HISTORICAL LEADERS -->
		<section class="leaders-section leaders-historical" style="max-width:100%;">
			<h2 class="section-title">Historical Leaders</h2>

			<!-- Past Church Presidents -->
			<?php
			echo render_block( array(
				'blockName'    => 'wasmo/saints-leadership',
				'attrs'        => array(
					'leadershipGroup' => 'past-presidents',
					'showTitle'       => true,
					'showDescription' => true,
					'showBadges'      => true,
					'cardSize'        => 'medium',
				),
				'innerContent' => array(),
			) );
			?>

			<!-- Past Apostles -->
			<?php
			echo render_block( array(
				'blockName'    => 'wasmo/saints-leadership',
				'attrs'        => array(
					'filterMode'      => 'custom',
					'roleFilter'      => array( 'apostle' ),
					'roleFilterOperator' => 'IN',
					'livingStatus'    => 'deceased',
					'orderBy'         => 'meta_value',
					'orderByMetaKey' => 'ordained_date',
					'order'           => 'DESC',
					'gridColumns'     => 5,
					'layout'          => 'grid',
					'showTitle'       => true,
					'customTitle'     => 'Past Apostles',
					'showDescription' => true,
					'customDescription' => 'Ordered by ordination date',
					'showBadges'      => false,
					'cardSize'        => 'small',
					'showAgeDates'    => true,
					'showServiceDates' => true,
					'showRoleBadge'   => false,
				),
				'innerContent' => array(),
			) );
			?>

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

			<!-- Other Notable or Historical Figures -->
			<?php
			// Note: This combines past_other (deceased, not apostle/president/wife) and current_other (role "other")
			// The filter below uses role "other" which covers current_other. For a complete match,
			// we'd need a more complex filter, but role "other" should cover most cases.
			// The block will handle the query and caching.
				echo render_block( array(
					'blockName'    => 'wasmo/saints-leadership',
					'attrs'        => array(
						'filterMode'      => 'custom',
						'roleFilter'      => array( 'other' ),
						'roleFilterOperator' => 'IN',
						'livingStatus'    => 'all',
						'orderBy'         => 'title',
						'order'           => 'ASC',
						'gridColumns'     => 5,
						'layout'          => 'grid',
						'showTitle'       => true,
						'customTitle'     => 'Other Notable or Historical Figures',
						'showDescription' => false,
						'showBadges'      => false,
						'cardSize'        => 'small',
						'showAgeDates'    => true,
						'showServiceDates' => true,
					'showRoleBadge'   => true,
				),
				'innerContent' => array(),
			) );
			?>

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
