<?php
/**
 * Template Name: Apostle Charts
 * 
 * Interactive data visualization for apostles throughout church history.
 * Inspired by https://threestory.com/apostles/
 *
 * @package wasmo
 */

get_header();

// Allow cache clearing via query param (admin only)
if ( isset( $_GET['clear_cache'] ) && current_user_can( 'manage_options' ) ) {
	delete_transient( 'wasmo_leaders_chart_data' );
	delete_transient( 'wasmo_apostle_seniority' );
	delete_transient( 'wasmo_apostle_seniority_all' );
	echo '<div class="notice notice-success" style="padding: 1rem; margin: 1rem; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;"><strong>Cache cleared!</strong> Refresh to see updated data.</div>';
}

// Get chart data
$chart_data = wasmo_get_saints_chart_data();
$first_presidency = wasmo_get_current_first_presidency();

// Separate current (living and still serving) and all apostles
$current_apostles = array_filter( $chart_data['apostles'], function( $a ) {
	return $a['is_currently_serving'];
} );

$all_apostles = $chart_data['apostles'];

// Filter for prophets only (for presidents chart)
$prophets = array_filter( $chart_data['apostles'], function( $a ) {
	return $a['is_president'];
} );

// Sort prophets by became_president_date (chronological)
usort( $prophets, function( $a, $b ) {
	$a_date = $a['became_president_date'] ? strtotime( $a['became_president_date'] ) : 0;
	$b_date = $b['became_president_date'] ? strtotime( $b['became_president_date'] ) : 0;
	return $a_date - $b_date;
} );

// Calculate max values for chart scaling
$max_age = 0;
$max_tenure = 0;
$max_age_at_call = 0;
$max_president_tenure = 0;

foreach ( $all_apostles as $apostle ) {
	if ( $apostle['age'] && $apostle['age'] > $max_age ) {
		$max_age = $apostle['age'];
	}
	if ( $apostle['years_served'] && $apostle['years_served'] > $max_tenure ) {
		$max_tenure = $apostle['years_served'];
	}
	if ( $apostle['age_at_call'] && $apostle['age_at_call'] > $max_age_at_call ) {
		$max_age_at_call = $apostle['age_at_call'];
	}
	// Track max president tenure from pre-calculated data
	if ( $apostle['president_years'] && $apostle['president_years'] > $max_president_tenure ) {
		$max_president_tenure = $apostle['president_years'];
	}
}
?>

<section id="primary" class="content-area">
	<main id="main" class="site-main apostle-charts-page">

		<header class="page-header">
			<h1 class="page-title">Latter-day Apostles Data</h1>
			<p class="page-description">
				Interactive visualization of all apostles who have served in The Church of Jesus Christ of Latter-day Saints since 1835.
			</p>
		</header>

		<!-- View Toggle -->
		<div class="chart-controls">
			<div class="view-toggle">
				<button class="view-btn active" data-view="current">Current Apostles</button>
				<button class="view-btn" data-view="all">All Apostles</button>
				<button class="view-btn" data-view="presidents">Church Presidents</button>
				<button class="view-btn" data-view="polygamy">Polygamy Data</button>
			</div>
		</div>

		<!-- Current Apostles View -->
		<section id="current-view" class="chart-view active">
			<h2>Current Apostles</h2>
			<p class="view-description">
				The <?php echo count( $current_apostles ); ?> living apostles currently serving, ordered by seniority.
			</p>

			<div class="current-apostles-legend">
				<span class="legend-item legend-prophet">
					<span class="legend-dot"></span> President of the Church
				</span>
				<span class="legend-item legend-first-presidency">
					<span class="legend-dot"></span> First Presidency
				</span>
				<span class="legend-item legend-twelve">
					<span class="legend-dot"></span> Quorum of Twelve
				</span>
			</div>

			<div class="current-apostles-grid">
				<?php 
				$seniority = 1;
				foreach ( $current_apostles as $apostle ) : 
					$is_president = $apostle['id'] === $first_presidency['president'];
					$is_fp = $apostle['id'] === $first_presidency['first-counselor'] || $apostle['id'] === $first_presidency['second-counselor'];
					$class = 'apostle-twelve';
					if ( $is_president ) $class = 'apostle-prophet';
					elseif ( $is_fp ) $class = 'apostle-first-presidency';
				?>
					<a href="<?php echo esc_url( $apostle['url'] ); ?>" class="current-apostle-card <?php echo $class; ?>">
						<span class="seniority-badge"><?php echo $seniority; ?></span>
						<?php if ( $apostle['thumbnail'] ) : ?>
							<img src="<?php echo esc_url( $apostle['thumbnail'] ); ?>" alt="<?php echo esc_attr( $apostle['name'] ); ?>" class="apostle-photo">
						<?php else : ?>
							<div class="apostle-photo-placeholder">
								<?php echo esc_html( substr( $apostle['name'], 0, 1 ) ); ?>
							</div>
						<?php endif; ?>
						<div class="apostle-card-details">
							<span class="apostle-name"><?php echo esc_html( $apostle['name'] ); ?></span>
							<span class="apostle-stat">Age: <?php echo esc_html( $apostle['age'] ); ?></span>
							<span class="apostle-stat">Served: <?php echo esc_html( $apostle['years_served'] ); ?> years</span>
						</div>
					</a>
				<?php 
					$seniority++;
				endforeach; 
				?>
			</div>

			<!-- Timeline for current apostles -->
			<div class="current-timeline">
				<h3>When Each Was Called</h3>
				<div class="timeline-chart">
					<?php 
					$min_year = 9999;
					$max_year = date( 'Y' );
					foreach ( $current_apostles as $apostle ) {
						if ( $apostle['ordained_date'] ) {
							$year = (int) date( 'Y', strtotime( $apostle['ordained_date'] ) );
							if ( $year < $min_year ) $min_year = $year;
						}
					}
					$year_range = $max_year - $min_year;
					
					foreach ( $current_apostles as $apostle ) : 
						if ( ! $apostle['ordained_date'] ) continue;
						$ordained_year = (int) date( 'Y', strtotime( $apostle['ordained_date'] ) );
						$offset = ( ( $ordained_year - $min_year ) / $year_range ) * 100;
						$is_president = $apostle['id'] === $first_presidency['president'];
						$is_fp = $apostle['id'] === $first_presidency['first-counselor'] || $apostle['id'] === $first_presidency['second-counselor'];
						$class = 'bar-twelve';
						if ( $is_president ) $class = 'bar-prophet';
						elseif ( $is_fp ) $class = 'bar-first-presidency';
					?>
						<div class="timeline-bar-container">
							<span class="timeline-name"><?php echo esc_html( $apostle['name'] ); ?></span>
							<div class="timeline-bar-track">
								<div class="timeline-bar <?php echo $class; ?>" style="left: <?php echo $offset; ?>%;" title="<?php echo esc_attr( $apostle['name'] . ' - ' . $ordained_year ); ?>">
									<span class="timeline-year"><?php echo $ordained_year; ?></span>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
					<div class="timeline-axis">
						<span class="axis-start"><?php echo $min_year; ?></span>
						<span class="axis-end"><?php echo $max_year; ?></span>
					</div>
				</div>
			</div>
		</section>

		<!-- All Apostles View -->
		<section id="all-view" class="chart-view">
			<h2>All Apostles Since 1835</h2>
			<p class="view-description">
				<?php echo count( $all_apostles ); ?> apostles have served since the church was organized.
			</p>

			<div class="chart-options">
				<div class="option-group">
					<label for="metric-select">View by:</label>
					<select id="metric-select">
						<option value="tenure" selected>Length of Tenure</option>
						<option value="age">Age (at death or current)</option>
						<option value="age_at_call">Age When Called</option>
					</select>
				</div>
				<div class="option-group">
					<label for="sort-select">Sort:</label>
					<select id="sort-select">
						<option value="desc" selected>Descending (Value)</option>
						<option value="asc">Ascending (Value)</option>
						<option value="chrono">Chronological</option>
						<option value="alpha">Alphabetical</option>
					</select>
				</div>
			</div>

			<div class="bar-chart-container">
				<div id="apostle-bar-chart" class="apostle-bar-chart">
					<?php foreach ( $all_apostles as $apostle ) : 
						$tenure_pct = $apostle['years_served'] ? ( $apostle['years_served'] / $max_tenure ) * 100 : 0;
						$age_pct = $apostle['age'] ? ( $apostle['age'] / $max_age ) * 100 : 0;
						$age_call_pct = $apostle['age_at_call'] ? ( $apostle['age_at_call'] / $max_age_at_call ) * 100 : 0;
						
						// Determine bar class: active (currently serving), removed (service ended early), or historical (deceased)
						$bar_class = 'bar-historical';
						if ( $apostle['is_currently_serving'] ) {
							$bar_class = 'bar-active';
						} elseif ( $apostle['service_ended_early'] ) {
							$bar_class = 'bar-removed';
						}
						if ( $apostle['is_president'] ) $bar_class .= ' bar-prophet';
						
						// Calculate service dates (ordained to service_end)
						$ordained_year = $apostle['ordained_date'] ? date( 'Y', strtotime( $apostle['ordained_date'] ) ) : '?';
						$service_end_year = $apostle['is_currently_serving'] ? 'present' : ( $apostle['service_end'] ? date( 'Y', strtotime( $apostle['service_end'] ) ) : '?' );
					?>
						<a href="<?php echo esc_url( $apostle['url'] ); ?>" 
						   class="bar-row <?php echo $apostle['service_ended_early'] ? 'service-ended-early' : ''; ?>" 
						   data-tenure="<?php echo esc_attr( $apostle['years_served'] ); ?>"
						   data-age="<?php echo esc_attr( $apostle['age'] ); ?>"
						   data-age-at-call="<?php echo esc_attr( $apostle['age_at_call'] ); ?>"
						   data-tenure-pct="<?php echo esc_attr( $tenure_pct ); ?>"
						   data-age-pct="<?php echo esc_attr( $age_pct ); ?>"
						   data-age-call-pct="<?php echo esc_attr( $age_call_pct ); ?>"
						   data-ordained="<?php echo esc_attr( $apostle['ordained_date'] ); ?>"
						   data-name="<?php echo esc_attr( $apostle['name'] ); ?>"
						   title="<?php echo $apostle['service_ended_early'] ? esc_attr( $apostle['ordain_note'] ) : ''; ?>">
							<span class="bar-name"><?php echo esc_html( $apostle['name'] ); ?></span>
							<div class="bar-track">
								<div class="bar <?php echo esc_attr( $bar_class ); ?>" style="width: <?php echo $tenure_pct; ?>%;">
									<span class="bar-value"><?php echo esc_html( $apostle['years_served'] ); ?></span>
								</div>
							</div>
							<span class="bar-dates">
								<?php echo $ordained_year . '–' . $service_end_year; ?>
								<?php if ( $apostle['service_ended_early'] ) : ?>
									<span class="removed-indicator" title="<?php echo esc_attr( $apostle['ordain_note'] ); ?>">*</span>
								<?php endif; ?>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="chart-legend">
				<span class="legend-item">
					<span class="legend-bar bar-active"></span> Currently Serving
				</span>
				<span class="legend-item">
					<span class="legend-bar bar-historical"></span> Served Until Death
				</span>
				<span class="legend-item">
					<span class="legend-bar bar-removed"></span> Service Ended Early*
				</span>
				<span class="legend-item">
					<span class="legend-bar bar-prophet"></span> Served as President
				</span>
			</div>
			<p class="legend-note">* Service ended early due to excommunication, resignation, or removal from the Quorum.</p>
		</section>

		<!-- Church Presidents View -->
		<section id="presidents-view" class="chart-view">
			<h2>Church Presidents</h2>
			<p class="view-description">
				<?php echo count( $prophets ); ?> men have served as President of The Church of Jesus Christ of Latter-day Saints since 1830.
			</p>

			<div class="presidents-timeline">
				<h3>President Timeline</h3>
				<div class="presidents-chart">
					<?php 
					$president_number = 1;
					$earliest_year = 1830;
					$latest_year = (int) date( 'Y' );
					$total_years = $latest_year - $earliest_year;
					
					foreach ( $prophets as $prophet ) : 
						if ( ! $prophet['became_president_date'] ) continue;
						
						$start_year = (int) date( 'Y', strtotime( $prophet['became_president_date'] ) );
						$end_year = $prophet['deathdate'] 
							? (int) date( 'Y', strtotime( $prophet['deathdate'] ) ) 
							: (int) date( 'Y' );
						
						$left_pct = ( ( $start_year - $earliest_year ) / $total_years ) * 100;
						$width_pct = ( ( $end_year - $start_year ) / $total_years ) * 100;
						if ( $width_pct < 1 ) $width_pct = 1; // Minimum width
						
						$tenure_years = $prophet['president_years'];
						$is_current = ! $prophet['deathdate'];
					?>
						<div class="president-row">
							<span class="president-number"><?php echo $president_number; ?></span>
							<a href="<?php echo esc_url( $prophet['url'] ); ?>" class="president-name">
								<?php echo esc_html( $prophet['name'] ); ?>
							</a>
							<div class="president-bar-track">
								<div class="president-bar <?php echo $is_current ? 'president-current' : ''; ?>" 
									 style="left: <?php echo $left_pct; ?>%; width: <?php echo $width_pct; ?>%;"
									 title="<?php echo esc_attr( $prophet['name'] . ': ' . $start_year . '–' . ( $is_current ? 'present' : $end_year ) . ' (' . $tenure_years . ' years)' ); ?>">
								</div>
							</div>
							<span class="president-dates">
								<?php echo $start_year; ?>–<?php echo $is_current ? 'present' : $end_year; ?>
								<span class="president-tenure">(<?php echo $tenure_years; ?> yrs)</span>
							</span>
						</div>
					<?php 
						$president_number++;
					endforeach; 
					?>
					<div class="timeline-axis presidents-axis">
						<span class="axis-label" style="left: 0%;"><?php echo $earliest_year; ?></span>
						<span class="axis-label" style="left: 25%;"><?php echo $earliest_year + round( $total_years * 0.25 ); ?></span>
						<span class="axis-label" style="left: 50%;"><?php echo $earliest_year + round( $total_years * 0.5 ); ?></span>
						<span class="axis-label" style="left: 75%;"><?php echo $earliest_year + round( $total_years * 0.75 ); ?></span>
						<span class="axis-label" style="left: 100%;"><?php echo $latest_year; ?></span>
					</div>
				</div>
			</div>

			<!-- Presidents Bar Chart by Tenure Length -->
			<div class="presidents-tenure-chart">
				<h3>Tenure Length Comparison</h3>
				<div class="chart-options">
					<div class="option-group">
						<label for="president-sort-select">Sort by:</label>
						<select id="president-sort-select">
							<option value="tenure-desc" selected>Tenure (Longest First)</option>
							<option value="tenure-asc">Tenure (Shortest First)</option>
							<option value="chrono">Chronological</option>
							<option value="alpha">Alphabetical</option>
						</select>
					</div>
				</div>
				<div id="president-bar-chart" class="president-bars">
					<?php 
					foreach ( $prophets as $prophet ) : 
						if ( ! $prophet['became_president_date'] ) continue;
						
						$tenure_pct = $max_president_tenure > 0 
							? ( $prophet['president_years'] / $max_president_tenure ) * 100 
							: 0;
						$is_current = ! $prophet['deathdate'];
						$start_year = date( 'Y', strtotime( $prophet['became_president_date'] ) );
						$end_year = $prophet['deathdate'] 
							? date( 'Y', strtotime( $prophet['deathdate'] ) ) 
							: 'present';
					?>
						<a href="<?php echo esc_url( $prophet['url'] ); ?>" 
						   class="president-tenure-row"
						   data-tenure="<?php echo esc_attr( $prophet['president_years'] ); ?>"
						   data-start="<?php echo esc_attr( $prophet['became_president_date'] ); ?>"
						   data-name="<?php echo esc_attr( $prophet['name'] ); ?>">
							<span class="bar-name"><?php echo esc_html( $prophet['name'] ); ?></span>
							<div class="bar-track">
								<div class="bar <?php echo $is_current ? 'bar-active' : 'bar-prophet'; ?>" 
									 style="width: <?php echo $tenure_pct; ?>%;">
									<span class="bar-value"><?php echo $prophet['president_years']; ?></span>
								</div>
							</div>
							<span class="bar-dates"><?php echo $start_year; ?>–<?php echo $end_year; ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Presidents Stats -->
			<div class="presidents-stats">
				<h3>President Statistics</h3>
				<div class="stats-grid">
					<?php
					$total_tenure = 0;
					$ages_at_start = array();
					$ages_at_death = array();
					
					foreach ( $prophets as $prophet ) {
						$total_tenure += $prophet['president_years'];
						
						// Calculate age when became president
						if ( $prophet['birthdate'] && $prophet['became_president_date'] ) {
							$birth = new DateTime( $prophet['birthdate'] );
							$start = new DateTime( $prophet['became_president_date'] );
							$ages_at_start[] = $birth->diff( $start )->y;
						}
						
						// Calculate age at death
						if ( $prophet['age'] && $prophet['deathdate'] ) {
							$ages_at_death[] = $prophet['age'];
						}
					}
					
					$avg_tenure = count( $prophets ) > 0 ? round( $total_tenure / count( $prophets ), 1 ) : 0;
					$avg_age_start = count( $ages_at_start ) > 0 ? round( array_sum( $ages_at_start ) / count( $ages_at_start ), 1 ) : 0;
					$avg_age_death = count( $ages_at_death ) > 0 ? round( array_sum( $ages_at_death ) / count( $ages_at_death ), 1 ) : 0;
					
					// Find longest and shortest tenures
					$longest_prophet = null;
					$shortest_prophet = null;
					$longest_tenure = 0;
					$shortest_tenure = PHP_INT_MAX;
					
					foreach ( $prophets as $prophet ) {
						if ( $prophet['president_years'] > $longest_tenure ) {
							$longest_tenure = $prophet['president_years'];
							$longest_prophet = $prophet;
						}
						if ( $prophet['president_years'] > 0 && $prophet['president_years'] < $shortest_tenure ) {
							$shortest_tenure = $prophet['president_years'];
							$shortest_prophet = $prophet;
						}
					}
					?>
					<div class="stat-card">
						<span class="stat-value"><?php echo count( $prophets ); ?></span>
						<span class="stat-label">Total Presidents</span>
					</div>
					<div class="stat-card">
						<span class="stat-value"><?php echo $avg_tenure; ?></span>
						<span class="stat-label">Avg. Years as President</span>
					</div>
					<div class="stat-card">
						<span class="stat-value"><?php echo $avg_age_start; ?></span>
						<span class="stat-label">Avg. Age When Called</span>
					</div>
					<div class="stat-card">
						<span class="stat-value"><?php echo $avg_age_death; ?></span>
						<span class="stat-label">Avg. Age at Death</span>
					</div>
					<?php if ( $longest_prophet ) : ?>
					<div class="stat-card stat-wide">
						<span class="stat-value"><?php echo esc_html( $longest_prophet['name'] ); ?></span>
						<span class="stat-label">Longest Tenure (<?php echo $longest_tenure; ?> years)</span>
					</div>
					<?php endif; ?>
					<?php if ( $shortest_prophet ) : ?>
					<div class="stat-card stat-wide">
						<span class="stat-value"><?php echo esc_html( $shortest_prophet['name'] ); ?></span>
						<span class="stat-label">Shortest Tenure (<?php echo $shortest_tenure; ?> year<?php echo $shortest_tenure !== 1 ? 's' : ''; ?>)</span>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="chart-legend">
				<span class="legend-item">
					<span class="legend-bar bar-active"></span> Currently Serving
				</span>
				<span class="legend-item">
					<span class="legend-bar bar-prophet"></span> Past Presidents
				</span>
			</div>
		</section>

		<!-- Polygamy View -->
		<section id="polygamy-view" class="chart-view">
			<h2>Polygamy Statistics</h2>
			<p class="view-description">
				Analysis of plural marriages among early church leaders. Data sourced from FamilySearch and historical records.
			</p>

			<?php
			// Get all saints with marriages data
			$polygamist_saints = get_posts( array(
				'post_type'      => 'saint',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'key'     => 'gender',
						'value'   => 'male',
						'compare' => '=',
					),
				),
			) );

			// Build polygamy data
			$polygamists = array();
			$total_wives = 0;
			$total_teenage_brides = 0;
			$age_diffs = array();
			
			foreach ( $polygamist_saints as $saint ) {
				$stats = wasmo_get_polygamy_stats( $saint->ID );
				if ( $stats['number_of_marriages'] > 1 ) {
					$polygamists[] = array(
						'id'              => $saint->ID,
						'name'            => $saint->post_title,
						'url'             => get_permalink( $saint->ID ),
						'wives_count'     => $stats['number_of_marriages'],
						'children_count'  => $stats['number_of_children'],
						'teenage_brides'  => $stats['teenage_brides_count'],
						'largest_age_diff' => $stats['largest_age_diff'],
						'avg_age_diff'    => $stats['avg_age_diff'],
						'is_president'    => wasmo_saint_has_role( $saint->ID, 'president' ),
						'thumbnail'       => get_the_post_thumbnail_url( $saint->ID, 'thumbnail' ),
					);
					$total_wives += $stats['number_of_marriages'];
					$total_teenage_brides += $stats['teenage_brides_count'];
					if ( $stats['avg_age_diff'] > 0 ) {
						$age_diffs[] = $stats['avg_age_diff'];
					}
				}
			}

			// Sort by number of wives
			usort( $polygamists, function( $a, $b ) {
				return $b['wives_count'] - $a['wives_count'];
			});

			$max_wives = ! empty( $polygamists ) ? $polygamists[0]['wives_count'] : 0;
			$avg_wives = count( $polygamists ) > 0 ? round( $total_wives / count( $polygamists ), 1 ) : 0;
			$avg_age_diff = count( $age_diffs ) > 0 ? round( array_sum( $age_diffs ) / count( $age_diffs ), 1 ) : 0;
			?>

			<!-- Polygamy Stats -->
			<div class="polygamy-stats">
				<div class="stats-grid">
					<div class="stat-card">
						<span class="stat-value"><?php echo count( $polygamists ); ?></span>
						<span class="stat-label">Polygamist Leaders</span>
					</div>
					<div class="stat-card">
						<span class="stat-value"><?php echo $total_wives; ?></span>
						<span class="stat-label">Total Plural Wives</span>
					</div>
					<div class="stat-card">
						<span class="stat-value"><?php echo $avg_wives; ?></span>
						<span class="stat-label">Avg. Wives per Polygamist</span>
					</div>
					<div class="stat-card">
						<span class="stat-value"><?php echo $total_teenage_brides; ?></span>
						<span class="stat-label">Teenage Brides (&lt;18)</span>
					</div>
					<div class="stat-card">
						<span class="stat-value"><?php echo $avg_age_diff; ?></span>
						<span class="stat-label">Avg. Age Gap (years)</span>
					</div>
					<?php if ( ! empty( $polygamists ) ) : ?>
					<div class="stat-card stat-wide">
						<span class="stat-value"><?php echo esc_html( $polygamists[0]['name'] ); ?></span>
						<span class="stat-label">Most Wives (<?php echo $max_wives; ?>)</span>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Number of Wives Chart -->
			<div class="polygamy-chart">
				<h3>Number of Wives by Leader</h3>
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
				
				<div id="polygamy-bar-chart" class="apostle-bar-chart">
					<?php foreach ( $polygamists as $p ) : 
						$wives_pct = ( $p['wives_count'] / $max_wives ) * 100;
						$bar_class = $p['is_president'] ? 'bar-prophet' : 'bar-historical';
						if ( $p['teenage_brides'] > 0 ) $bar_class .= ' has-teenage';
					?>
						<a href="<?php echo esc_url( $p['url'] ); ?>" 
						   class="bar-row polygamy-bar-row" 
						   data-wives="<?php echo esc_attr( $p['wives_count'] ); ?>"
						   data-teenage="<?php echo esc_attr( $p['teenage_brides'] ); ?>"
						   data-age-diff="<?php echo esc_attr( $p['largest_age_diff'] ); ?>"
						   data-name="<?php echo esc_attr( $p['name'] ); ?>">
							<span class="bar-name">
								<?php if ( $p['thumbnail'] ) : ?>
									<img src="<?php echo esc_url( $p['thumbnail'] ); ?>" alt="" class="bar-thumb">
								<?php endif; ?>
								<?php echo esc_html( $p['name'] ); ?>
								<?php if ( $p['is_president'] ) : ?>
									<span class="president-badge" title="Church President">★</span>
								<?php endif; ?>
							</span>
							<div class="bar-track">
								<div class="bar <?php echo esc_attr( $bar_class ); ?>" style="width: <?php echo $wives_pct; ?>%;">
									<span class="bar-value"><?php echo esc_html( $p['wives_count'] ); ?></span>
								</div>
							</div>
							<span class="bar-dates">
								<?php if ( $p['teenage_brides'] > 0 ) : ?>
									<span class="teenage-count" title="Teenage brides"><?php echo $p['teenage_brides']; ?> teen<?php echo $p['teenage_brides'] > 1 ? 's' : ''; ?></span>
								<?php endif; ?>
								<?php if ( $p['largest_age_diff'] > 0 ) : ?>
									<span class="age-diff" title="Largest age gap"><?php echo $p['largest_age_diff']; ?>yr gap</span>
								<?php endif; ?>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Teenage Brides Section -->
			<?php 
			$leaders_with_teen_brides = array_filter( $polygamists, function( $p ) {
				return $p['teenage_brides'] > 0;
			});
			
			if ( ! empty( $leaders_with_teen_brides ) ) :
				usort( $leaders_with_teen_brides, function( $a, $b ) {
					return $b['teenage_brides'] - $a['teenage_brides'];
				});
			?>
			<div class="teenage-brides-section">
				<h3>Leaders with Teenage Brides</h3>
				<p class="section-description">
					<?php echo count( $leaders_with_teen_brides ); ?> leaders married women under age 18.
				</p>
				<div class="teenage-grid">
					<?php foreach ( array_slice( $leaders_with_teen_brides, 0, 12 ) as $leader ) : ?>
						<a href="<?php echo esc_url( $leader['url'] ); ?>" class="teenage-card">
							<?php if ( $leader['thumbnail'] ) : ?>
								<img src="<?php echo esc_url( $leader['thumbnail'] ); ?>" alt="" class="teenage-card-thumb">
							<?php endif; ?>
							<div class="teenage-card-info">
								<span class="teenage-card-name"><?php echo esc_html( $leader['name'] ); ?></span>
								<span class="teenage-card-stat">
									<strong><?php echo $leader['teenage_brides']; ?></strong> teenage bride<?php echo $leader['teenage_brides'] > 1 ? 's' : ''; ?>
								</span>
								<span class="teenage-card-stat">
									of <?php echo $leader['wives_count']; ?> total wives
								</span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

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

		<footer class="charts-footer">
			<p>
				Data sourced from church records, FamilySearch, and historical documents. 
				<a href="<?php echo get_post_type_archive_link( 'saint' ); ?>">View all saints →</a>
			</p>
		</footer>

	</main>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// View toggle
	const viewBtns = document.querySelectorAll('.view-btn');
	const views = document.querySelectorAll('.chart-view');

	viewBtns.forEach(function(btn) {
		btn.addEventListener('click', function() {
			const targetView = this.getAttribute('data-view');
			
			viewBtns.forEach(function(b) { b.classList.remove('active'); });
			this.classList.add('active');
			
			views.forEach(function(v) {
				v.classList.remove('active');
				if (v.id === targetView + '-view') {
					v.classList.add('active');
				}
			});
		});
	});

	// ========== APOSTLE CHART ==========
	const metricSelect = document.getElementById('metric-select');
	const sortSelect = document.getElementById('sort-select');
	const barRows = document.querySelectorAll('.bar-row');
	const barChart = document.getElementById('apostle-bar-chart');

	function updateApostleChart() {
		const metric = metricSelect.value;
		const sortOrder = sortSelect.value;

		// Update bar widths and values
		barRows.forEach(function(row) {
			const bar = row.querySelector('.bar');
			const valueSpan = row.querySelector('.bar-value');
			let pct, value;

			switch(metric) {
				case 'age':
					pct = row.getAttribute('data-age-pct');
					value = row.getAttribute('data-age');
					break;
				case 'age_at_call':
					pct = row.getAttribute('data-age-call-pct');
					value = row.getAttribute('data-age-at-call');
					break;
				default: // tenure
					pct = row.getAttribute('data-tenure-pct');
					value = row.getAttribute('data-tenure');
			}

			bar.style.width = pct + '%';
			valueSpan.textContent = value || '?';
		});

		// Sort the rows
		const rowsArray = Array.from(barRows);
		rowsArray.sort(function(a, b) {
			let aVal, bVal;
			
			// Handle special sort modes
			if (sortOrder === 'chrono') {
				aVal = a.getAttribute('data-ordained') || '';
				bVal = b.getAttribute('data-ordained') || '';
				return aVal.localeCompare(bVal);
			}
			if (sortOrder === 'alpha') {
				aVal = a.getAttribute('data-name') || '';
				bVal = b.getAttribute('data-name') || '';
				return aVal.localeCompare(bVal);
			}
			
			// Value-based sorting
			switch(metric) {
				case 'age':
					aVal = parseFloat(a.getAttribute('data-age')) || 0;
					bVal = parseFloat(b.getAttribute('data-age')) || 0;
					break;
				case 'age_at_call':
					aVal = parseFloat(a.getAttribute('data-age-at-call')) || 0;
					bVal = parseFloat(b.getAttribute('data-age-at-call')) || 0;
					break;
				default:
					aVal = parseFloat(a.getAttribute('data-tenure')) || 0;
					bVal = parseFloat(b.getAttribute('data-tenure')) || 0;
			}
			return sortOrder === 'desc' ? bVal - aVal : aVal - bVal;
		});

		// Re-append in new order
		rowsArray.forEach(function(row) {
			barChart.appendChild(row);
		});
	}

	metricSelect.addEventListener('change', updateApostleChart);
	sortSelect.addEventListener('change', updateApostleChart);

	// Initial sort
	updateApostleChart();

	// ========== PRESIDENT CHART ==========
	const presidentSortSelect = document.getElementById('president-sort-select');
	const presidentRows = document.querySelectorAll('.president-tenure-row');
	const presidentChart = document.getElementById('president-bar-chart');

	function updatePresidentChart() {
		const sortOrder = presidentSortSelect.value;

		const rowsArray = Array.from(presidentRows);
		rowsArray.sort(function(a, b) {
			let aVal, bVal;
			
			switch(sortOrder) {
				case 'chrono':
					aVal = a.getAttribute('data-start') || '';
					bVal = b.getAttribute('data-start') || '';
					return aVal.localeCompare(bVal);
				case 'alpha':
					aVal = a.getAttribute('data-name') || '';
					bVal = b.getAttribute('data-name') || '';
					return aVal.localeCompare(bVal);
				case 'tenure-asc':
					aVal = parseFloat(a.getAttribute('data-tenure')) || 0;
					bVal = parseFloat(b.getAttribute('data-tenure')) || 0;
					return aVal - bVal;
				default: // tenure-desc
					aVal = parseFloat(a.getAttribute('data-tenure')) || 0;
					bVal = parseFloat(b.getAttribute('data-tenure')) || 0;
					return bVal - aVal;
			}
		});

		// Re-append in new order
		rowsArray.forEach(function(row) {
			presidentChart.appendChild(row);
		});
	}

	presidentSortSelect.addEventListener('change', updatePresidentChart);

	// Initial sort for presidents (by tenure descending)
	updatePresidentChart();

	// ========== POLYGAMY CHART ==========
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

	if (polygamySortSelect) {
		polygamySortSelect.addEventListener('change', updatePolygamyChart);
	}
});
</script>

<?php
get_footer();
