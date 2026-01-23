<?php
/**
 * Template Name: Polygamy Statistics
 * 
 * Displays polygamist leaders with statistics about their marriages
 *
 * @package wasmo
 */

get_header();

// Allow cache clearing via query param (admin only)
if ( isset( $_GET['clear_cache'] ) && current_user_can( 'manage_options' ) ) {
	delete_transient( 'wasmo_polygamists_list' );
	delete_transient( 'wasmo_polygamy_data' );
	echo '<div class="notice notice-success" style="padding: 1rem; margin: 1rem; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;"><strong>Polygamy cache cleared!</strong> Refresh to see updated data.</div>';
}

// Get all the data - now uses cached function from functions-saints.php
$polygamy_data = wasmo_get_cached_polygamy_data();
$polygamists = $polygamy_data['polygamists'];
$aggregate = $polygamy_data['aggregate'];

// Calculate aggregate statistics
$avg_wives = $aggregate['total_polygamists'] > 0 
	? round( $aggregate['total_wives'] / $aggregate['total_polygamists'], 1 ) 
	: 0;

$avg_children = $aggregate['total_polygamists'] > 0 
	? round( $aggregate['total_children'] / $aggregate['total_polygamists'], 1 ) 
	: 0;

$overall_avg_age_diff = ! empty( $aggregate['all_age_diffs'] ) 
	? round( array_sum( $aggregate['all_age_diffs'] ) / count( $aggregate['all_age_diffs'] ), 1 ) 
	: 0;

$largest_age_diff = ! empty( $aggregate['all_age_diffs'] ) 
	? max( $aggregate['all_age_diffs'] ) 
	: 0;

$pct_teenage_brides = $aggregate['total_wives'] > 0 
	? round( ( $aggregate['total_teenage_brides'] / $aggregate['total_wives'] ) * 100, 1 ) 
	: 0;

$pct_large_age_diff = $aggregate['total_wives'] > 0 
	? round( ( $aggregate['large_age_diff_count'] / $aggregate['total_wives'] ) * 100, 1 ) 
	: 0;

// Youngest bride age (teenage brides are already sorted youngest first)
$youngest_bride_age = ! empty( $aggregate['all_teenage_brides'] ) 
	? $aggregate['all_teenage_brides'][0]['bride_age'] 
	: null;

// Most wives by a single leader (polygamists already sorted by wives desc)
$most_wives_leader = ! empty( $polygamists ) ? $polygamists[0] : null;

// Count brides under 16
$brides_under_16 = 0;
foreach ( $aggregate['all_teenage_brides'] as $bride ) {
	if ( isset( $bride['bride_age'] ) && $bride['bride_age'] < 16 ) {
		$brides_under_16++;
	}
}

// Sort role counts
arsort( $aggregate['role_counts'] );

// Build expanded young brides list (under 20) for this page only
// This includes the standard teenage brides (<18) plus 18-19 year olds
$young_brides = array();
foreach ( $polygamists as $data ) {
	foreach ( $data['marriages_data'] as $marriage ) {
		if ( isset( $marriage['spouse_age'] ) && $marriage['spouse_age'] !== null && $marriage['spouse_age'] < 20 ) {
			$young_brides[] = array(
				'bride_id'      => $marriage['spouse_id'],
				'bride_name'    => $marriage['spouse_name'],
				'bride_age'     => $marriage['spouse_age'],
				'husband_id'    => $data['id'],
				'husband_name'  => $data['name'],
				'husband_age'   => $marriage['saint_age'],
				'marriage_date' => $marriage['marriage_date'],
			);
		}
	}
}
// Sort by age (youngest first)
usort( $young_brides, function( $a, $b ) {
	return ( $a['bride_age'] ?? 99 ) - ( $b['bride_age'] ?? 99 );
} );

// Count brides 18-19 for display
$brides_18_19 = 0;
foreach ( $young_brides as $bride ) {
	if ( isset( $bride['bride_age'] ) && $bride['bride_age'] >= 18 && $bride['bride_age'] < 20 ) {
		$brides_18_19++;
	}
}
?>

<section id="primary" class="content-area">
	<main id="main" class="site-main polygamy-page entry">
		<div class="entry-content">
			
			<header class="page-header content-full-width">
				<h1 class="page-title no-line">Polygamy in LDS Leadership</h1>
				<p class="page-description">Statistics and data about plural marriage among leaders of The Church of Jesus Christ of Latter-day Saints from 1830 to present.</p>
				<p>Polygamy is the practice of marrying multiple spouses (opposed to monogamy where a person is married to only one spouse). When a man is married to more than one wife at the same time, it is technically called polygyny, though the church calls it plural marriage or polygamy. When a woman is married to more than one husband at the same time, it is called polyandry. When there a man has multiple wives who in turn have multiple husbands at the same time, it is called polyamory.</p>
			</header>

			<!-- AGGREGATE STATISTICS -->
			<section class="stats-overview content-full-width">
				<h2>Overview Statistics</h2>
				<div class="stats-grid">
					<div class="stat-card">
						<span class="stat-value"><?php echo number_format( $aggregate['total_polygamists'] ); ?></span>
						<span class="stat-label">Total Polygamist Leaders <br><small>(on the site so far)</small></span>
					</div>
					<div class="stat-card stat-card-blue">
						<span class="stat-value"><?php echo number_format( $aggregate['simultaneous_count'] ); ?></span>
						<span class="stat-label">Simultaneous Polygamists</span>
						<span class="stat-note">Multiple living wives at once</span>
					</div>
					<div class="stat-card stat-card-gray">
						<span class="stat-value"><?php echo number_format( $aggregate['celestial_count'] ); ?></span>
						<span class="stat-label">Celestial Polygamists</span>
						<span class="stat-note">Sequential marriages only</span>
					</div>
					<div class="stat-card">
						<span class="stat-value"><?php echo number_format( $aggregate['total_wives'] ); ?></span>
						<span class="stat-label">Total Plural Wives</span>
					</div>
					<div class="stat-card">
						<span class="stat-value"><?php echo $avg_wives; ?></span>
						<span class="stat-label">Avg Wives per Polygamist</span>
					</div>
					<?php if ( $most_wives_leader ) : ?>
					<div class="stat-card">
						<span class="stat-value"><?php echo $most_wives_leader['num_wives']; ?></span>
						<span class="stat-label">Most Wives (Single Leader)</span>
						<span class="stat-note"><?php echo esc_html( $most_wives_leader['name'] ); ?></span>
					</div>
					<?php endif; ?>
					<div class="stat-card">
						<span class="stat-value"><?php echo $overall_avg_age_diff; ?> yrs</span>
						<span class="stat-label">Avg Age Difference</span>
					</div>
					<div class="stat-card">
						<span class="stat-value"><?php echo $largest_age_diff; ?> yrs</span>
						<span class="stat-label">Largest Age Difference</span>
					</div>
					<div class="stat-card">
						<span class="stat-value"><?php echo $pct_large_age_diff; ?>%</span>
						<span class="stat-label">Marriages with 20+ yr Gap</span>
					</div>
					<div class="stat-card stat-card-highlight">
						<span class="stat-value"><?php echo number_format( $aggregate['total_teenage_brides'] ); ?></span>
						<span class="stat-label">Teenage Brides (&lt;18)</span>
					</div>
					<?php if ( $brides_under_16 > 0 ) : ?>
					<div class="stat-card stat-card-highlight">
						<span class="stat-value"><?php echo $brides_under_16; ?></span>
						<span class="stat-label">Brides Under 16</span>
					</div>
					<?php endif; ?>
					<div class="stat-card stat-card-highlight">
						<span class="stat-value"><?php echo $pct_teenage_brides; ?>%</span>
						<span class="stat-label">Percentage Teenage Brides</span>
					</div>
					<?php if ( $youngest_bride_age !== null ) : ?>
					<div class="stat-card stat-card-highlight">
						<span class="stat-value"><?php echo $youngest_bride_age; ?></span>
						<span class="stat-label">Youngest Bride Age</span>
					</div>
					<?php endif; ?>
					<div class="stat-card">
						<span class="stat-value"><?php echo number_format( $aggregate['total_children'] ); ?></span>
						<span class="stat-label">Total Children</span>
					</div>
				</div>
			</section>

			<!-- POLYGAMISTS BY ROLE -->
			<?php /* if ( ! empty( $aggregate['role_counts'] ) ) : ?>
			<section class="stats-by-role content-full-width">
				<h2>Polygamists by Church Role</h2>
				<div class="role-stats-grid">
					<?php foreach ( $aggregate['role_counts'] as $role => $count ) : ?>
						<div class="role-stat">
							<span class="role-name"><?php echo esc_html( $role ); ?></span>
							<span class="role-count"><?php echo $count; ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; */ ?>

			<!-- POLYGAMIST CARDS -->
			<section class="polygamists-cards content-full-width">
				<h2>Polygamist Leaders</h2>
				<p class="section-description">Click any card for full biographical details. Ordered by number of wives.</p>
				<div class="leaders-grid leaders-grid-5">
					<?php foreach ( $polygamists as $data ) : ?>
						<?php
						$content = '<span class="stat-badge">'.$data['num_wives'].' wives</span>';
						$content .= '<span class="stat-badge">'.$data['num_children'].' children</span>';
						if ( $data['num_teenage_brides'] > 0 ) :
							$content .= '<span class="stat-badge stat-badge-warning">'.$data['num_teenage_brides'].' teen';
							$content .= $data['num_teenage_brides'] > 1 ? 's' : '';
							$content .= '</span>';
						endif;
						?>
						<div class="polygamist-card-wrapper">
							<?php wasmo_render_saint_card( $data['id'], 'small', true, false, false, '', $content ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- SORTABLE TABLE -->
			<section class="polygamists-table-section content-full-width">
				<h2>Detailed Data Table</h2>
				<p class="section-description">Showing <?php echo count( $polygamists ); ?> leaders and some plural marriage data.</p>
				<div class="table-responsive">
					<table class="polygamists-table sortable-table" id="polygamists-table">
						<thead>
							<tr>
								<th data-sort="string">Name</th>
								<!-- <th data-sort="string">Role(s)</th> -->
								<!-- <th data-sort="string">Type</th> -->
								<th data-sort="int">Wives</th>
								<th data-sort="int">Teen Brides</th>
								<th data-sort="int">Children</th>
								<th data-sort="float">Avg Age Diff</th>
								<th data-sort="int">Max Age Diff</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $polygamists as $data ) : 
								$type_label = ( $data['polygamy_type'] === 'celestial' ) ? 'Celestial' : 'Simultaneous';
								$type_class = ( $data['polygamy_type'] === 'celestial' ) ? 'type-celestial' : 'type-simultaneous';
							?>
								<tr>
									<td>
										<a href="<?php echo get_permalink( $data['id'] ); ?>">
											<?php echo esc_html( $data['name'] ); ?>
										</a>
										<?php if ( $data['is_living'] ) : ?>
											<span class="living-badge" title="Living">●</span>
										<?php endif; ?>
									</td>
									<!-- <td><?php echo esc_html( implode( ', ', $data['roles'] ) ); ?></td> -->
									<!-- <td class="<?php echo esc_attr( $type_class ); ?>"><?php echo esc_html( $type_label ); ?></td> -->
									<td><?php echo $data['num_wives']; ?></td>
									<td class="<?php echo $data['num_teenage_brides'] > 0 ? 'highlight-warning' : ''; ?>">
										<?php echo $data['num_teenage_brides']; ?>
									</td>
									<td><?php echo $data['num_children']; ?></td>
									<td><?php echo $data['avg_age_diff'] ? $data['avg_age_diff'] . ' yrs' : '—'; ?></td>
									<td><?php echo $data['largest_age_diff'] ? $data['largest_age_diff'] . ' yrs' : '—'; ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>

			<!-- YOUNG BRIDES LIST (Under 20) -->
			<?php if ( ! empty( $young_brides ) ) : ?>
			<section class="teenage-brides-section content-full-width">
				<h2>Young Brides</h2>
				<p class="section-description">
					Women who married church leaders before age 20. 
					This includes <?php echo $aggregate['total_teenage_brides']; ?> teenage brides (under 18) 
					plus <?php echo $brides_18_19; ?> brides aged 18-19.
					Listed by age at marriage, youngest first.
				</p>
				<div class="table-responsive">
					<table class="teenage-brides-table sortable-table" id="teenage-brides-table">
						<thead>
							<tr>
								<th data-sort="string">Bride</th>
								<th data-sort="int">Age</th>
								<th data-sort="string">Husband</th>
								<th data-sort="int">His Age</th>
								<th data-sort="int">Age Gap</th>
								<th data-sort="string">Year</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $young_brides as $bride ) : 
								$age_gap = ( $bride['husband_age'] ?? 0 ) - ( $bride['bride_age'] ?? 0 );
								$year = $bride['marriage_date'] ? date( 'Y', strtotime( $bride['marriage_date'] ) ) : '—';
								// Determine highlight class based on age
								$age_class = '';
								if ( $bride['bride_age'] < 16 ) {
									$age_class = 'highlight-danger';
								} elseif ( $bride['bride_age'] < 18 ) {
									$age_class = 'highlight-warning';
								}
								// 18-19 year olds get no special highlight
							?>
								<tr>
									<td>
										<?php if ( $bride['bride_id'] ) : ?>
											<a href="<?php echo get_permalink( $bride['bride_id'] ); ?>">
												<?php echo esc_html( $bride['bride_name'] ); ?>
											</a>
										<?php else : ?>
											<?php echo esc_html( $bride['bride_name'] ); ?>
										<?php endif; ?>
									</td>
									<td class="<?php echo esc_attr( $age_class ); ?>">
										<?php echo $bride['bride_age'] ?? '—'; ?>
									</td>
									<td>
										<a href="<?php echo get_permalink( $bride['husband_id'] ); ?>">
											<?php echo esc_html( $bride['husband_name'] ); ?>
										</a>
									</td>
									<td><?php echo $bride['husband_age'] ?? '—'; ?></td>
									<td class="<?php echo $age_gap >= 20 ? 'highlight-warning' : ''; ?>">
										<?php echo $age_gap > 0 ? $age_gap . ' yrs' : '—'; ?>
									</td>
									<td><?php echo esc_html( $year ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>
			<?php endif; ?>

			<!-- VISUAL BAR CHART -->
			<section class="polygamy-chart-section content-full-width">
				<h2>Number of Wives by Leader</h2>
				<div class="chart-options">
					<div class="option-group">
						<label for="polygamy-sort">Sort:</label>
						<select id="polygamy-sort">
							<option value="wives-desc" selected>Most Wives</option>
							<option value="wives-asc">Fewest Wives</option>
							<option value="teenage-desc">Most Teenage Brides</option>
							<option value="age-diff-desc">Largest Age Gap</option>
							<option value="alpha">Alphabetical</option>
						</select>
					</div>
				</div>
				
				<?php 
				$max_wives = ! empty( $polygamists ) ? $polygamists[0]['num_wives'] : 1;
				?>
				
				<div id="polygamy-bar-chart" class="apostle-bar-chart">
					<?php foreach ( $polygamists as $p ) : 
						$wives_pct = ( $p['num_wives'] / $max_wives ) * 100;
						$bar_class = in_array( 'president', $p['role_slugs'] ?? array() ) ? 'bar-prophet' : 'bar-historical';
						if ( $p['num_teenage_brides'] > 0 ) $bar_class .= ' has-teenage';
						$thumbnail = get_the_post_thumbnail_url( $p['id'], 'thumbnail' );
					?>
						<a href="<?php echo esc_url( get_permalink( $p['id'] ) ); ?>" 
						   class="bar-row polygamy-bar-row" 
						   data-wives="<?php echo esc_attr( $p['num_wives'] ); ?>"
						   data-teenage="<?php echo esc_attr( $p['num_teenage_brides'] ); ?>"
						   data-age-diff="<?php echo esc_attr( $p['largest_age_diff'] ?? 0 ); ?>"
						   data-name="<?php echo esc_attr( $p['name'] ); ?>">
							<span class="bar-name">
								<?php if ( $thumbnail ) : ?>
									<img src="<?php echo esc_url( $thumbnail ); ?>" alt="" class="bar-thumb">
								<?php endif; ?>
								<?php echo esc_html( $p['name'] ); ?>
								<?php /* if ( in_array( 'president', $p['role_slugs'] ?? array() ) ) : ?>
									<span class="president-badge" title="Church President">★</span>
								<?php endif; */ ?>
							</span>
							<div class="bar-track">
								<div class="bar <?php echo esc_attr( $bar_class ); ?>" style="width: <?php echo $wives_pct; ?>%;">
									<span class="bar-value"><?php echo esc_html( $p['num_wives'] ); ?></span>
								</div>
							</div>
							<span class="bar-dates">
								<?php if ( $p['num_teenage_brides'] > 0 ) : ?>
									<span class="teenage-count" title="Teenage brides"><?php echo $p['num_teenage_brides']; ?> teen<?php echo $p['num_teenage_brides'] > 1 ? 's' : ''; ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $p['largest_age_diff'] ) && $p['largest_age_diff'] > 0 ) : ?>
									<span class="age-diff" title="Largest age gap"><?php echo $p['largest_age_diff']; ?>yr gap</span>
								<?php endif; ?>
							</span>
						</a>
					<?php endforeach; ?>
				</div>

				<div class="chart-legend">
					<span class="legend-item">
						<span class="legend-bar bar-prophet"></span> Church Presidents
					</span>
					<span class="legend-item">
						<span class="legend-bar bar-historical"></span> Other Leaders
					</span>
					<span class="legend-item">
						<span class="teenage-marker">●</span> Had Teenage Brides
					</span>
				</div>
			</section>

			<footer class="page-footer content-full-width">
				<p class="data-note">
					<strong>Data Note:</strong> Statistics are based on available historical records (mainly familysearch.org). 
					Some marriages may not be fully documented, and ages may be approximate.
					This data is presented for historical and educational purposes.
				</p>
				<div class="leader-navigation">
					<a href="<?php echo get_post_type_archive_link( 'saint' ); ?>" class="btn btn-secondary">
						All Saints →
					</a>
					<a href="<?php echo home_url( '/saint-charts/' ); ?>" class="btn btn-secondary">
						Saints Data →
					</a>
				</div>
			</footer>

		</div>
	</main>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Polygamy chart sorting
	const polygamySortSelect = document.getElementById('polygamy-sort');
	const polygamyRows = document.querySelectorAll('.polygamy-bar-row');
	const polygamyChart = document.getElementById('polygamy-bar-chart');

	function updatePolygamyChart() {
		if (!polygamySortSelect || !polygamyChart) return;
		
		const sortOrder = polygamySortSelect.value;
		const rowsArray = Array.from(polygamyRows);

		rowsArray.sort(function(a, b) {
			let aVal, bVal;
			
			switch(sortOrder) {
				case 'wives-asc':
					aVal = parseInt(a.getAttribute('data-wives')) || 0;
					bVal = parseInt(b.getAttribute('data-wives')) || 0;
					return aVal - bVal;
				case 'teenage-desc':
					aVal = parseInt(a.getAttribute('data-teenage')) || 0;
					bVal = parseInt(b.getAttribute('data-teenage')) || 0;
					return bVal - aVal;
				case 'age-diff-desc':
					aVal = parseInt(a.getAttribute('data-age-diff')) || 0;
					bVal = parseInt(b.getAttribute('data-age-diff')) || 0;
					return bVal - aVal;
				case 'alpha':
					aVal = a.getAttribute('data-name') || '';
					bVal = b.getAttribute('data-name') || '';
					return aVal.localeCompare(bVal);
				default: // wives-desc
					aVal = parseInt(a.getAttribute('data-wives')) || 0;
					bVal = parseInt(b.getAttribute('data-wives')) || 0;
					return bVal - aVal;
			}
		});

		// Re-append in new order
		rowsArray.forEach(function(row) {
			polygamyChart.appendChild(row);
		});
	}

	polygamySortSelect.addEventListener('change', updatePolygamyChart);
});
</script>

<?php
get_footer();
