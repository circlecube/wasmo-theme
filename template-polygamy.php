<?php
/**
 * Template Name: Polygamy Statistics
 * 
 * Displays polygamist leaders with statistics about their marriages
 *
 * @package wasmo
 */

get_header();

/**
 * Get all polygamists (male saints with more than one wife)
 */
function wasmo_get_polygamists() {
	// Get all male saints who are husbands
	global $wpdb;
	
	// Find all unique spouse IDs from marriages
	$spouse_ids = $wpdb->get_col(
		"SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
		WHERE meta_key LIKE 'marriages_%_spouse' 
		AND meta_value != '' 
		AND meta_value NOT LIKE 'a:0:%'"
	);
	
	$polygamists = array();
	$processed = array();
	
	foreach ( $spouse_ids as $spouse_data ) {
		// Handle serialized and non-serialized values
		if ( is_serialized( $spouse_data ) ) {
			$unserialized = maybe_unserialize( $spouse_data );
			$spouse_id = is_array( $unserialized ) ? intval( $unserialized[0] ?? 0 ) : intval( $unserialized );
		} else {
			$spouse_id = intval( $spouse_data );
		}
		
		if ( ! $spouse_id || in_array( $spouse_id, $processed, true ) ) {
			continue;
		}
		$processed[] = $spouse_id;
		
		// Verify it's a valid saint
		$post = get_post( $spouse_id );
		if ( ! $post || $post->post_status !== 'publish' || $post->post_type !== 'saint' ) {
			continue;
		}
		
		// Only include men
		$gender = get_field( 'gender', $spouse_id );
		if ( $gender !== 'male' ) {
			continue;
		}
		
		// Get marriage data for this potential polygamist
		$marriages = wasmo_get_all_marriage_data( $spouse_id );
		
		if ( count( $marriages ) > 1 ) {
			$polygamists[] = $spouse_id;
		}
	}
	
	return $polygamists;
}

/**
 * Get comprehensive polygamy data for all polygamists
 */
function wasmo_get_all_polygamy_data() {
	$polygamists = wasmo_get_polygamists();
	$all_data = array();
	$aggregate = array(
		'total_polygamists'      => 0,
		'total_wives'            => 0,
		'total_children'         => 0,
		'total_teenage_brides'   => 0,
		'all_age_diffs'          => array(),
		'all_teenage_brides'     => array(),
		'large_age_diff_count'   => 0,
		'role_counts'            => array(),
		'celestial_count'        => 0,
		'simultaneous_count'     => 0,
	);
	
	foreach ( $polygamists as $saint_id ) {
		$stats = wasmo_get_polygamy_stats( $saint_id );
		$polygamy_type = wasmo_get_polygamy_type( $saint_id );
		$roles = wp_get_post_terms( $saint_id, 'saint-role', array( 'fields' => 'names' ) );
		$role_slugs = wp_get_post_terms( $saint_id, 'saint-role', array( 'fields' => 'slugs' ) );
		
		// Filter out 'wife' role for men
		$roles = array_filter( $roles, function( $role ) {
			return strtolower( $role ) !== 'wife';
		} );
		
		$data = array(
			'id'                  => $saint_id,
			'name'                => get_the_title( $saint_id ),
			'roles'               => $roles,
			'role_slugs'          => $role_slugs,
			'num_wives'           => $stats['number_of_marriages'],
			'num_children'        => $stats['number_of_children'],
			'num_teenage_brides'  => $stats['teenage_brides_count'],
			'avg_age_diff'        => $stats['avg_age_diff'],
			'largest_age_diff'    => $stats['largest_age_diff'],
			'marriages_data'      => $stats['marriages_data'],
			'lifespan'            => wasmo_get_saint_lifespan( $saint_id ),
			'polygamy_type'       => $polygamy_type['type'],
			'is_living'           => wasmo_is_saint_living( $saint_id ),
		);
		
		$all_data[] = $data;
		
		// Aggregate stats
		$aggregate['total_polygamists']++;
		$aggregate['total_wives'] += $stats['number_of_marriages'];
		$aggregate['total_children'] += $stats['number_of_children'];
		$aggregate['total_teenage_brides'] += $stats['teenage_brides_count'];
		
		// Track polygamy type
		if ( $polygamy_type['type'] === 'celestial' ) {
			$aggregate['celestial_count']++;
		} else {
			$aggregate['simultaneous_count']++;
		}
		
		// Track age differences
		foreach ( $stats['marriages_data'] as $marriage ) {
			if ( isset( $marriage['age_diff'] ) && $marriage['age_diff'] !== null ) {
				$aggregate['all_age_diffs'][] = abs( $marriage['age_diff'] );
				if ( abs( $marriage['age_diff'] ) >= 20 ) {
					$aggregate['large_age_diff_count']++;
				}
			}
			
			// Track teenage brides
			if ( isset( $marriage['spouse_age'] ) && $marriage['spouse_age'] !== null && $marriage['spouse_age'] < 18 ) {
				$aggregate['all_teenage_brides'][] = array(
					'bride_id'      => $marriage['spouse_id'],
					'bride_name'    => $marriage['spouse_name'],
					'bride_age'     => $marriage['spouse_age'],
					'husband_id'    => $saint_id,
					'husband_name'  => get_the_title( $saint_id ),
					'husband_age'   => $marriage['saint_age'],
					'marriage_date' => $marriage['marriage_date'],
				);
			}
		}
		
		// Track roles
		foreach ( $roles as $role ) {
			if ( ! isset( $aggregate['role_counts'][ $role ] ) ) {
				$aggregate['role_counts'][ $role ] = 0;
			}
			$aggregate['role_counts'][ $role ]++;
		}
	}
	
	// Sort by number of wives (descending)
	usort( $all_data, function( $a, $b ) {
		return $b['num_wives'] - $a['num_wives'];
	} );
	
	// Sort teenage brides by age (youngest first)
	usort( $aggregate['all_teenage_brides'], function( $a, $b ) {
		return ( $a['bride_age'] ?? 99 ) - ( $b['bride_age'] ?? 99 );
	} );
	
	return array(
		'polygamists' => $all_data,
		'aggregate'   => $aggregate,
	);
}

// Get all the data
$polygamy_data = wasmo_get_all_polygamy_data();
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
?>

<section id="primary" class="content-area">
	<main id="main" class="site-main polygamy-page entry">
		<div class="entry-content">
			
			<header class="page-header content-full-width">
				<h1 class="page-title no-line">Polygamy in LDS Leadership</h1>
				<p class="page-description">
					Statistics and data about plural marriage among leaders of The Church of Jesus Christ of Latter-day Saints.
					This page presents historical and contemporary data without editorial commentary.
				</p>
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

			<!-- TEENAGE BRIDES LIST -->
			<?php if ( ! empty( $aggregate['all_teenage_brides'] ) ) : ?>
			<section class="teenage-brides-section content-full-width">
				<h2>Teenage Brides</h2>
				<p class="section-description">
					Women who married church leaders before reaching age 18. 
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
							<?php foreach ( $aggregate['all_teenage_brides'] as $bride ) : 
								$age_gap = ( $bride['husband_age'] ?? 0 ) - ( $bride['bride_age'] ?? 0 );
								$year = $bride['marriage_date'] ? date( 'Y', strtotime( $bride['marriage_date'] ) ) : '—';
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
									<td class="<?php echo $bride['bride_age'] < 16 ? 'highlight-danger' : 'highlight-warning'; ?>">
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
								<?php if ( in_array( 'president', $p['role_slugs'] ?? array() ) ) : ?>
									<span class="president-badge" title="Church President">★</span>
								<?php endif; ?>
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
				<p>
					<a href="<?php echo home_url( '/saints/' ); ?>" class="btn btn-secondary">← Back to Church Leadership</a>
				</p>
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
