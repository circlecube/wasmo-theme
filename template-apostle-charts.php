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

			<!-- Charts for current apostles -->
			<?php
			// Calculate data for current apostle charts
			$min_year = 9999;
			$max_year = (int) date( 'Y' );
			$current_max_tenure = 0;
			$current_max_age_at_call = 0;
			$current_max_age = 0;
			
			foreach ( $current_apostles as $apostle ) {
				if ( $apostle['ordained_date'] ) {
					$year = (int) date( 'Y', strtotime( $apostle['ordained_date'] ) );
					if ( $year < $min_year ) $min_year = $year;
				}
				if ( $apostle['years_served'] > $current_max_tenure ) {
					$current_max_tenure = $apostle['years_served'];
				}
				if ( $apostle['age_at_call'] > $current_max_age_at_call ) {
					$current_max_age_at_call = $apostle['age_at_call'];
				}
				if ( $apostle['age'] > $current_max_age ) {
					$current_max_age = $apostle['age'];
				}
			}
			$year_range = max( 1, $max_year - $min_year );
			?>

			<!-- Chart Toggle for Current Apostles -->
			<div class="current-charts-container">
				<div class="chart-toggle-container">
					<div class="chart-toggle">
						<button class="chart-toggle-btn active" data-chart="called">When Called</button>
						<button class="chart-toggle-btn" data-chart="service">Service Duration</button>
						<button class="chart-toggle-btn" data-chart="age-called">Age at Call</button>
						<button class="chart-toggle-btn" data-chart="current-age">Current Age</button>
						<button class="chart-toggle-btn" data-chart="president-prob">President Probability</button>
					</div>
				</div>

				<!-- When Called Timeline -->
				<div class="current-chart-panel active" id="current-chart-called">
					<div class="current-timeline">
						<p class="chart-subtitle">Year each apostle was ordained to the Quorum of the Twelve</p>
						<div class="timeline-chart">
							<?php foreach ( $current_apostles as $apostle ) : 
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
				</div>

				<!-- Service Duration Chart -->
				<div class="current-chart-panel" id="current-chart-service">
					<div class="current-timeline">
						<p class="chart-subtitle">Years served as an apostle (sorted by seniority)</p>
						<div class="horizontal-bar-chart">
							<?php foreach ( $current_apostles as $apostle ) : 
								$tenure_pct = $current_max_tenure > 0 ? ( $apostle['years_served'] / $current_max_tenure ) * 100 : 0;
								$is_president = $apostle['id'] === $first_presidency['president'];
								$is_fp = $apostle['id'] === $first_presidency['first-counselor'] || $apostle['id'] === $first_presidency['second-counselor'];
								$class = 'bar-twelve';
								if ( $is_president ) $class = 'bar-prophet';
								elseif ( $is_fp ) $class = 'bar-first-presidency';
							?>
								<div class="horizontal-bar-row">
									<span class="timeline-name"><?php echo esc_html( $apostle['name'] ); ?></span>
									<div class="horizontal-bar-track">
										<div class="horizontal-bar <?php echo $class; ?>" style="width: <?php echo $tenure_pct; ?>%;">
											<span class="bar-value"><?php echo esc_html( $apostle['years_served'] ); ?> yrs</span>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- Age at Call Chart -->
				<div class="current-chart-panel" id="current-chart-age-called">
					<div class="current-timeline">
						<p class="chart-subtitle">How old each apostle was when ordained (sorted by seniority)</p>
						<div class="horizontal-bar-chart">
							<?php foreach ( $current_apostles as $apostle ) : 
								$age_pct = $current_max_age_at_call > 0 ? ( $apostle['age_at_call'] / $current_max_age_at_call ) * 100 : 0;
								$is_president = $apostle['id'] === $first_presidency['president'];
								$is_fp = $apostle['id'] === $first_presidency['first-counselor'] || $apostle['id'] === $first_presidency['second-counselor'];
								$class = 'bar-twelve';
								if ( $is_president ) $class = 'bar-prophet';
								elseif ( $is_fp ) $class = 'bar-first-presidency';
							?>
								<div class="horizontal-bar-row">
									<span class="timeline-name"><?php echo esc_html( $apostle['name'] ); ?></span>
									<div class="horizontal-bar-track">
										<div class="horizontal-bar <?php echo $class; ?>" style="width: <?php echo $age_pct; ?>%;">
											<span class="bar-value"><?php echo esc_html( $apostle['age_at_call'] ); ?></span>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- Current Age Chart -->
				<div class="current-chart-panel" id="current-chart-current-age">
					<div class="current-timeline">
						<p class="chart-subtitle">Current age of each apostle (sorted by seniority)</p>
						<div class="horizontal-bar-chart">
							<?php foreach ( $current_apostles as $apostle ) : 
								$age_pct = $current_max_age > 0 ? ( $apostle['age'] / $current_max_age ) * 100 : 0;
								$is_president = $apostle['id'] === $first_presidency['president'];
								$is_fp = $apostle['id'] === $first_presidency['first-counselor'] || $apostle['id'] === $first_presidency['second-counselor'];
								$class = 'bar-twelve';
								if ( $is_president ) $class = 'bar-prophet';
								elseif ( $is_fp ) $class = 'bar-first-presidency';
							?>
								<div class="horizontal-bar-row">
									<span class="timeline-name"><?php echo esc_html( $apostle['name'] ); ?></span>
									<div class="horizontal-bar-track">
										<div class="horizontal-bar <?php echo $class; ?>" style="width: <?php echo $age_pct; ?>%;">
											<span class="bar-value"><?php echo esc_html( $apostle['age'] ); ?></span>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- President Probability Chart -->
				<div class="current-chart-panel" id="current-chart-president-prob">
					<div class="current-timeline">
						<p class="chart-subtitle">Probability of each apostle becoming the President of the Church over time.</p>
						<?php 
						// Calculate president probabilities using simplified actuarial model
						// Based on methodology from Zelophehad's Daughters
						// https://zelophehadsdaughters.com/2023/10/17/church-president-probabilities-2023-update/
						$apostle_data = array();
						$seniority = 1;
						foreach ( $current_apostles as $apostle ) {
							$apostle_data[] = array(
								'name' => $apostle['name'],
								'age' => $apostle['age'],
								'seniority' => $seniority,
								'is_president' => $apostle['id'] === $first_presidency['president'],
								'is_fp' => $apostle['id'] === $first_presidency['first-counselor'] || $apostle['id'] === $first_presidency['second-counselor'],
							);
							$seniority++;
						}
						
						// Simplified mortality table (annual probability of death by age)
						// Based on SOA white-collar male annuitant tables
						if ( ! function_exists( 'wasmo_get_mortality_rate' ) ) {
							function wasmo_get_mortality_rate( $age ) {
								if ( $age < 60 ) return 0.005;
								if ( $age < 70 ) return 0.01 + ( $age - 60 ) * 0.002;
								if ( $age < 80 ) return 0.03 + ( $age - 70 ) * 0.005;
								if ( $age < 90 ) return 0.08 + ( $age - 80 ) * 0.015;
								if ( $age < 100 ) return 0.23 + ( $age - 90 ) * 0.03;
								return 0.5;
							}
						}
						
						$current_year = (int) date( 'Y' );
						$years_to_project = 30;
						
						// Calculate probability over time for each apostle
						$probability_data = array();
						foreach ( $apostle_data as $idx => $apostle ) {
							$yearly_probs = array();
							
							for ( $year = 0; $year <= $years_to_project; $year++ ) {
								if ( $idx === 0 ) {
									// Current president - calculate probability they're still alive/president
									$prob_alive = 1.0;
									for ( $y = 0; $y < $year; $y++ ) {
										$prob_alive *= ( 1 - wasmo_get_mortality_rate( $apostle['age'] + $y ) );
									}
									$yearly_probs[] = round( $prob_alive * 100, 1 );
								} else {
									// Calculate probability all seniors have died AND this person is alive
									$prob_seniors_dead = 1.0;
									for ( $s = 0; $s < $idx; $s++ ) {
										$senior_age = $apostle_data[$s]['age'];
										$prob_senior_alive = 1.0;
										for ( $y = 0; $y < $year; $y++ ) {
											$prob_senior_alive *= ( 1 - wasmo_get_mortality_rate( $senior_age + $y ) );
										}
										$prob_seniors_dead *= ( 1 - $prob_senior_alive );
									}
									
									// Probability this person survives to this year
									$prob_self_alive = 1.0;
									for ( $y = 0; $y < $year; $y++ ) {
										$prob_self_alive *= ( 1 - wasmo_get_mortality_rate( $apostle['age'] + $y ) );
									}
									
									$yearly_probs[] = round( $prob_seniors_dead * $prob_self_alive * 100, 1 );
								}
							}
							
							$probability_data[] = array(
								'name' => $apostle['name'],
								'data' => $yearly_probs,
								'is_president' => $apostle['is_president'],
								'is_fp' => $apostle['is_fp'],
							);
						}
						
						// Generate year labels
						$year_labels = array();
						for ( $y = 0; $y <= $years_to_project; $y++ ) {
							$year_labels[] = $current_year + $y;
						}
						
						// Distinct colors for each apostle - based on site color palette
						$chart_colors = array(
							'#f07c27', // site orange - president
							'#007099', // site blue-2
							'#d80c81', // site pink
							'#00aeeb', // site blue
							'#a3096a', // site pink-2
							'#004b66', // site blue-3
							'#6b0646', // site pink-3
							'#f4a261', // warm orange variant
							'#2a9d8f', // teal complement
							'#e76f51', // coral
							'#264653', // dark teal
							'#e9c46a', // gold
							'#457b9d', // steel blue
							'#bc6c25', // rust
							'#a8dadc', // light teal
						);
						?>
						
						<div class="probability-chart-wrapper" style="height: 450px; position: relative;">
							<canvas id="president-probability-chart"></canvas>
						</div>
						
						<div class="probability-legend">
							<?php foreach ( $probability_data as $idx => $apostle ) : 
								$color = $chart_colors[ $idx % count( $chart_colors ) ];
							?>
								<span class="prob-legend-item" data-index="<?php echo $idx; ?>">
									<span class="prob-legend-color" style="background: <?php echo $color; ?>;"></span>
									<?php echo esc_html( $apostle['name'] ); ?>
									<?php if ( $apostle['is_president'] ) : ?><small>(current)</small><?php endif; ?>
								</span>
							<?php endforeach; ?>
						</div>
						
						<div class="probability-methodology">
							<p><strong>How is this calculated?</strong></p>
							<p>
								The Church uses a strict seniority-based succession system: when the President dies, the senior-most apostle (by ordination date) automatically becomes the new President. This means predicting who will become President is essentially a question of who will outlive whom.
							</p>
							<p>
								For each apostle, we calculate the probability they'll become President in any given future year. This requires two things to happen: (1) all apostles senior to them must have passed away by that year, and (2) they themselves must still be alive. We use actuarial mortality tables—statistical data showing the probability of death at each age—to estimate these survival probabilities. The tables used here are based on Society of Actuaries data for white-collar male annuitants.
							</p>
							<p>
								The current President's line shows his probability of <em>remaining</em> President (i.e., still being alive). Other apostles start near 0% and rise as their seniors age, peak when they're most likely to hold the office, then decline as their own mortality increases.
							</p>
							<p>
								<em>Note: These are statistical estimates only. They assume no changes to the succession system and cannot account for individual health factors. The calculations update automatically as the apostle data changes. For more detailed analyses, see <a href="https://zelophehadsdaughters.com/2023/10/17/church-president-probabilities-2023-update/" target="_blank" rel="noopener">Zelophehad's Daughters</a> or <a href="https://prophetpredict.com/" target="_blank" rel="noopener">ProphetPredict.com</a>.</em>
							</p>
						</div>
						
						<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
						<script>
						(function() {
							const probabilityData = <?php echo json_encode( $probability_data ); ?>;
							const yearLabels = <?php echo json_encode( $year_labels ); ?>;
							const chartColors = <?php echo json_encode( $chart_colors ); ?>;
							
							let probChart = null;
							
							window.initProbabilityChart = function() {
								const canvas = document.getElementById('president-probability-chart');
								if (!canvas) return;
								
								if (probChart) {
									probChart.destroy();
								}
								
								const ctx = canvas.getContext('2d');
								
								const datasets = probabilityData.map((apostle, idx) => ({
									label: apostle.name,
									data: apostle.data,
									borderColor: chartColors[idx % chartColors.length],
									backgroundColor: chartColors[idx % chartColors.length] + '20',
									borderWidth: apostle.is_president ? 3 : 2,
									pointRadius: 0,
									pointHoverRadius: 5,
									tension: 0.3,
									fill: false,
								}));
								
								probChart = new Chart(ctx, {
									type: 'line',
									data: {
										labels: yearLabels,
										datasets: datasets
									},
									options: {
										responsive: true,
										maintainAspectRatio: false,
										interaction: {
											mode: 'index',
											intersect: false,
										},
										plugins: {
											legend: {
												display: false // We use custom legend
											},
											tooltip: {
												callbacks: {
													label: function(context) {
														return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
													}
												}
											}
										},
										scales: {
											x: {
												title: {
													display: true,
													text: 'Year',
													font: { family: "'Josefin Sans', sans-serif", weight: 500 }
												},
												ticks: {
													callback: function(value, index) {
														// Show every 5 years
														return index % 5 === 0 ? yearLabels[index] : '';
													}
												},
												grid: {
													color: 'rgba(0,0,0,0.05)'
												}
											},
											y: {
												beginAtZero: true,
												max: 100,
												title: {
													display: true,
													text: 'Probability (%)',
													font: { family: "'Josefin Sans', sans-serif", weight: 500 }
												},
												ticks: {
													callback: function(value) {
														return value + '%';
													}
												},
												grid: {
													color: 'rgba(0,0,0,0.05)'
												}
											}
										}
									}
								});
							};
							
							// Toggle legend items
							document.querySelectorAll('.prob-legend-item').forEach(item => {
								item.addEventListener('click', function() {
									const idx = parseInt(this.dataset.index);
									if (probChart) {
										const meta = probChart.getDatasetMeta(idx);
										meta.hidden = !meta.hidden;
										this.classList.toggle('legend-hidden');
										probChart.update();
									}
								});
							});
							
							// Initialize on load
							if (document.readyState === 'complete') {
								window.initProbabilityChart();
							} else {
								window.addEventListener('load', window.initProbabilityChart);
							}
						})();
						</script>
					</div>
				</div>
			</div>

			<!-- Chart Toggle Script for Current Apostles -->
			<script>
			(function() {
				const toggleBtns = document.querySelectorAll('.current-charts-container .chart-toggle-btn');
				const panels = document.querySelectorAll('.current-chart-panel');
				
				toggleBtns.forEach(function(btn) {
					btn.addEventListener('click', function() {
						const targetChart = this.getAttribute('data-chart');
						
						toggleBtns.forEach(function(b) { b.classList.remove('active'); });
						this.classList.add('active');
						
						panels.forEach(function(panel) {
							panel.classList.remove('active');
							if (panel.id === 'current-chart-' + targetChart) {
								panel.classList.add('active');
								// Reinitialize probability chart when shown (canvas needs to be visible)
								if (targetChart === 'president-prob' && typeof window.initProbabilityChart === 'function') {
									setTimeout(window.initProbabilityChart, 50);
								}
							}
						});
					});
				});
			})();
			</script>
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

		<footer class="charts-footer">
			<p>Data sourced from church records, FamilySearch, and other historical documents. If you see any errors or omissions, please let us know.</p>
			<div class="leader-navigation">
				<a href="<?php echo get_post_type_archive_link( 'saint' ); ?>" class="btn btn-secondary">
					All Saints →
				</a>
				<a href="<?php echo home_url( '/plural-wives-and-polygamy/' ); ?>" class="btn btn-secondary">
					Polygamy Stats →
				</a>
			</div>
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
});
</script>

<?php
get_footer();
