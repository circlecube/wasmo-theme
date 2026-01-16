<?php
/**
 * The template for displaying single Saint posts
 *
 * @package wasmo
 */

get_header();

while ( have_posts() ) :
	the_post();
	
	$saint_id = get_the_ID();
	$is_living = wasmo_is_saint_living( $saint_id );
	$is_apostle = wasmo_saint_has_role( $saint_id, 'apostle' );
	$is_president = wasmo_saint_has_role( $saint_id, 'president' );
	$is_wife = wasmo_saint_has_role( $saint_id, 'wife' );
	$gender = get_field( 'gender', $saint_id ) ?: 'male';
	
	// Get role terms for badges
	$roles = wp_get_post_terms( $saint_id, 'saint-role' );
	
	// Get related content
	$related_posts = wasmo_get_saint_related_posts( $saint_id, -1 );
	$related_media = wasmo_get_saint_related_media( $saint_id, 100 );
	
	// Get prophets this leader served under (if apostle)
	$served_under = array();
	if ( $is_apostle ) {
		$served_under = wasmo_get_served_with_presidents( $saint_id );
	}
	
	// Get apostles who served under this leader (if prophet)
	$apostles_under = array();
	if ( $is_president ) {
		$apostles_under = wasmo_get_apostles_who_served_under( $saint_id );
	}
	
	// Get marriages data (gender-aware: women store directly, men use reverse lookup)
	$marriages = wasmo_get_all_marriage_data( $saint_id );
	$polygamy_stats = wasmo_get_polygamy_stats( $saint_id );
?>

<?php
// Filter out saint-role-* classes from post_class to avoid archive card styling
$classes = get_post_class( 'saint-single' );
$classes = array_filter( $classes, function( $class ) {
	return strpos( $class, 'saint-role-' ) !== 0;
});
?>
<article id="post-<?php the_ID(); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<div class="entry-content">

	<div class="leader-content-wrapper" style="max-width:100%;">
		<div class="leader-main-content">
			<header class="leader-header">
				<div class="leader-header-content">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="leader-portrait">
							<?php the_post_thumbnail( 'medium_large' ); ?>
						</div>
					<?php endif; ?>
					
					<div class="leader-header-info">
						
						<h1 class="leader-name">
							<?php the_title(); ?>
						</h1>
						
						<p class="leader-lifespan">
							<?php echo esc_html( wasmo_get_saint_lifespan( $saint_id ) ); ?>
							<?php if ( $is_living ) : ?>
								<span class="leader-status-badge leader-status-living">Living</span>
							<?php endif; ?>
						</p>
						
						<?php 
						$hometown = get_field( 'hometown' );
						if ( $hometown ) : ?>
							<p class="leader-hometown">
								<span class="label">Hometown:</span> <?php echo esc_html( $hometown ); ?>
							</p>
						<?php endif; ?>
						
						<div class="saint-roles">
							<?php echo wasmo_get_icon_svg( 'saint', 30 ); ?>
							<ul class="tags">
							<?php foreach ( $roles as $role ) : ?>
								<li>
									<a href="<?php echo esc_url( get_term_link( $role ) ); ?>" class="tag saint-role-badge saint-role-<?php echo esc_attr( $role->slug ); ?>">
										<?php echo esc_html( $role->name ); ?>
									</a>
								</li>
							<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
			</header>

			<?php if ( get_the_content() ) : ?>
				<section class="leader-bio">
					<h2>Biography</h2>
					<div class="leader-bio-content">
						<?php the_content(); ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $served_under ) ) : ?>
				<section class="leader-served-under">
					<h2>Served Under</h2>
					<p class="section-description">Church presidents during this leader's apostolic service:</p>
					<div class="leaders-grid">
						<?php foreach ( $served_under as $prophet_id ) : 
							$prophet = get_post( $prophet_id );
							$prophet_thumbnail = get_the_post_thumbnail_url( $prophet_id, 'thumbnail' );
						?>
							<a href="<?php echo get_permalink( $prophet_id ); ?>" class="leader-card leader-card-small">
								<?php if ( $prophet_thumbnail ) : ?>
									<img src="<?php echo esc_url( $prophet_thumbnail ); ?>" alt="<?php echo esc_attr( $prophet->post_title ); ?>" class="leader-card-image">
								<?php else : ?>
									<div class="leader-card-placeholder"></div>
								<?php endif; ?>
								<span class="leader-card-name"><?php echo esc_html( $prophet->post_title ); ?></span>
								<span class="leader-card-dates"><?php echo esc_html( wasmo_get_saint_lifespan( $prophet_id ) ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $apostles_under ) ) : ?>
				<section class="leader-apostles-under">
					<h2>Apostles Who Served During This Presidency</h2>
					<div class="leaders-grid">
						<?php 
						$count = 0;
						foreach ( $apostles_under as $apostle_id ) : 
							if ( $count >= 15 ) break; // Limit display
							$apostle = get_post( $apostle_id );
							$apostle_thumbnail = get_the_post_thumbnail_url( $apostle_id, 'thumbnail' );
						?>
							<a href="<?php echo get_permalink( $apostle_id ); ?>" class="leader-card leader-card-small">
								<?php if ( $apostle_thumbnail ) : ?>
									<img src="<?php echo esc_url( $apostle_thumbnail ); ?>" alt="<?php echo esc_attr( $apostle->post_title ); ?>" class="leader-card-image">
								<?php else : ?>
									<div class="leader-card-placeholder"></div>
								<?php endif; ?>
								<span class="leader-card-name"><?php echo esc_html( $apostle->post_title ); ?></span>
							</a>
						<?php 
							$count++;
						endforeach; 
						?>
					</div>
					<?php if ( count( $apostles_under ) > 15 ) : ?>
						<p class="view-all-link">
							<a href="<?php echo get_post_type_archive_link( 'saint' ); ?>">View all church leaders →</a>
						</p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php 
			// Show marriages section (gender-aware: men show wives, women show husbands)
			if ( ! empty( $marriages ) ) : 
				$spouse_label = ( $gender === 'female' ) ? 'Husbands' : 'Wives';
				$is_showing_wives = ( $gender === 'male' );
				
				// Prepare chart data
				$chart_marriages = array();
				$order = 1;
				foreach ( $marriages as $marriage ) {
					// Check if spouse is a saint (default true for backwards compatibility)
					$spouse_is_saint = isset( $marriage['spouse_is_saint'] ) ? (bool) $marriage['spouse_is_saint'] : true;
					
					$spouse_field = $marriage['spouse'] ?? $marriage['spouse_id'] ?? null;
					$spouse_id = is_array( $spouse_field ) ? ( $spouse_field[0] ?? null ) : $spouse_field;
					$spouse_name_text = $marriage['spouse_name'] ?? null; // For non-saint spouses
					$marriage_date = $marriage['marriage_date'] ?? null;
					$marriage_date_approx = $marriage['marriage_date_approximate'] ?? false;
					$children = $marriage['children'] ?? array();
					
					// Get children counts (excluding placeholders for display)
					$children_counts = wasmo_get_children_counts( $marriage );
					
					// Handle saint spouse
					if ( $spouse_is_saint && $spouse_id && $marriage_date ) {
						$spouse_post = get_post( $spouse_id );
						$spouse_age = wasmo_get_age_at_date( $spouse_id, $marriage_date );
						$saint_age = wasmo_get_age_at_date( $saint_id, $marriage_date );
						$age_diff = $saint_age && $spouse_age ? abs( $saint_age - $spouse_age ) : 0;
						$marriage_year = date( 'Y', strtotime( $marriage_date ) );
						$spouse_birthdate = get_field( 'birthdate', $spouse_id );
						$spouse_birthdate_approx = get_field( 'birthdate_approximate', $spouse_id );
						
						// For men showing wives: wife's marital status is on her record
						// For women showing husbands: woman's own marital status
						if ( $is_showing_wives ) {
							$marital_status = get_field( 'marital_status_at_marriage', $spouse_id );
							$was_teenage = wasmo_was_teenage_bride( $spouse_id, $marriage_date );
						} else {
							$marital_status = get_field( 'marital_status_at_marriage', $saint_id );
							// For women, check if SHE was a teenage bride
							$was_teenage = wasmo_was_teenage_bride( $saint_id, $marriage_date );
						}
						
						$chart_marriages[] = array(
							'order'                     => $order,
							'spouse_is_saint'           => true,
							'spouse_id'                 => $spouse_id,
							'spouse_name'               => $spouse_post ? $spouse_post->post_title : 'Unknown',
							'spouse_url'                => get_permalink( $spouse_id ),
							'marriage_date'             => $marriage_date,
							'marriage_date_approximate' => $marriage_date_approx,
							'marriage_year'             => $marriage_year,
							'spouse_age'                => $spouse_age,
							'spouse_age_approximate'    => $spouse_birthdate_approx || $marriage_date_approx,
							'saint_age'                 => $saint_age,
							'age_diff'                  => $age_diff,
							'is_teenage'                => $was_teenage,
							'children_count'            => $children_counts['total'],
							'children_displayable'      => $children_counts['displayable'],
							'marital_status'            => $marital_status,
						);
					}
					// Handle non-saint spouse (text name only)
					elseif ( ! $spouse_is_saint && $spouse_name_text && $marriage_date ) {
						$saint_age = wasmo_get_age_at_date( $saint_id, $marriage_date );
						$marriage_year = date( 'Y', strtotime( $marriage_date ) );
						
						// Calculate spouse age from spouse_birthdate if available
						$spouse_birthdate = $marriage['spouse_birthdate'] ?? null;
						$spouse_age = null;
						$age_diff = null;
						if ( $spouse_birthdate && $marriage_date ) {
							$birth = new DateTime( $spouse_birthdate );
							$married = new DateTime( $marriage_date );
							$spouse_age = $birth->diff( $married )->y;
							if ( $saint_age && $spouse_age ) {
								$age_diff = abs( $saint_age - $spouse_age );
							}
						}
						
						// For women: her own marital status
						$marital_status = get_field( 'marital_status_at_marriage', $saint_id );
						$was_teenage = wasmo_was_teenage_bride( $saint_id, $marriage_date );
						
						$chart_marriages[] = array(
							'order'                     => $order,
							'spouse_is_saint'           => false,
							'spouse_id'                 => null,
							'spouse_name'               => $spouse_name_text,
							'spouse_url'                => null,
							'marriage_date'             => $marriage_date,
							'marriage_date_approximate' => $marriage_date_approx,
							'marriage_year'             => $marriage_year,
							'spouse_age'                => $spouse_age,
							'spouse_age_approximate'    => $marriage_date_approx, // Only approximate if marriage date is approximate
							'saint_age'                 => $saint_age,
							'age_diff'                  => $age_diff,
							'is_teenage'                => $was_teenage,
							'children_count'            => $children_counts['total'],
							'children_displayable'      => $children_counts['displayable'],
							'marital_status'            => $marital_status,
						);
					}
					$order++;
				}
				
				// Sort by marriage date
				usort( $chart_marriages, function( $a, $b ) {
					return strtotime( $a['marriage_date'] ) - strtotime( $b['marriage_date'] );
				});
				
				// Get saint's birth/death years for timeline context
				$saint_birthdate = get_field( 'birthdate', $saint_id );
				$saint_deathdate = get_field( 'deathdate', $saint_id );
				$saint_birth_year = $saint_birthdate ? (int) date( 'Y', strtotime( $saint_birthdate ) ) : null;
				$saint_death_year = $saint_deathdate ? (int) date( 'Y', strtotime( $saint_deathdate ) ) : (int) date( 'Y' );
			?>
				<section class="saint-marriages">
					<h2><?php echo esc_html( $spouse_label ); ?> (<?php echo count( $marriages ); ?>)</h2>
					<?php if ( $polygamy_stats['number_of_marriages'] > 1 ) : ?>
						<p class="section-description">
							<?php echo esc_html( get_the_title( $saint_id ) ); ?> was a polygamist with <?php echo esc_html( $polygamy_stats['number_of_marriages'] ); ?> marriages.
							<?php if ( $polygamy_stats['teenage_brides_count'] > 0 && $gender === 'male' ) : ?>
								<strong><?php echo esc_html( $polygamy_stats['teenage_brides_count'] ); ?></strong> of these wives were teenagers at the time of marriage.
							<?php endif; ?>
						</p>
					<?php endif; ?>

					<?php if ( count( $chart_marriages ) > 1 && $gender === 'male' ) : ?>
					<!-- Marriage Age Comparison Chart -->
					<div class="marriage-chart-section">
						<h3>Marriage Age Comparison</h3>
						<p class="chart-subtitle"><?php echo count( $chart_marriages ); ?> wives · Ages at marriage</p>
						
						<div class="chart-wrapper" style="height: <?php echo max( 300, count( $chart_marriages ) * 40 + 80 ); ?>px;">
							<canvas id="marriage-age-chart" aria-label="Age comparison chart for <?php echo esc_attr( get_the_title() ); ?>" role="img"></canvas>
						</div>
						
						<div class="chart-legend">
							<span class="legend-item"><span class="legend-color legend-her"></span> Her age</span>
							<span class="legend-item"><span class="legend-color legend-his"></span> His age</span>
							<span class="legend-item"><span class="legend-color legend-teen"></span> Teenage bride (&lt;18)</span>
						</div>
					</div>

					<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
					<script>
					(function() {
						// Defer chart initialization to prevent layout shift
						function initChart() {
							const canvas = document.getElementById('marriage-age-chart');
							if (!canvas) return;
							const ctx = canvas.getContext('2d');
							
							// Marriage data from PHP
							const marriageData = <?php echo json_encode( $chart_marriages ); ?>;
						
						const labels = marriageData.map(m => {
							const year = m.marriage_year;
							const name = m.spouse_name.split(' ').slice(0, 2).join(' '); // First two words of name
							return `${name} (${year})`;
						});
						
						const herAges = marriageData.map(m => parseInt(m.spouse_age) || 0);
						const hisAges = marriageData.map(m => parseInt(m.saint_age) || 0);
						const isTeenage = marriageData.map(m => m.is_teenage);
						
						// Color scheme
						const teenageColor = '#c2410c';       // Burnt orange for teenage
						const herAgeColor = '#0ea5e9';        // Sky blue for her age
						const hisAgeColor = '#64748b';        // Slate for his age
						const teenageBg = 'rgba(194, 65, 12, 0.85)';
						const herAgeBg = 'rgba(14, 165, 233, 0.85)';
						const hisAgeBg = 'rgba(100, 116, 139, 0.4)';
						
						new Chart(ctx, {
							type: 'bar',
							data: {
								labels: labels,
								datasets: [
									{
										label: 'Her Age at Marriage',
										data: herAges,
										backgroundColor: herAges.map((age, i) => isTeenage[i] ? teenageBg : herAgeBg),
										borderColor: herAges.map((age, i) => isTeenage[i] ? teenageColor : herAgeColor),
										borderWidth: 2,
										borderRadius: 4,
										barPercentage: 0.7,
									},
									{
										label: 'His Age at Marriage',
										data: hisAges,
										backgroundColor: hisAgeBg,
										borderColor: hisAgeColor,
										borderWidth: 1,
										borderRadius: 4,
										barPercentage: 0.7,
									}
								]
							},
							options: {
								indexAxis: 'y',
								responsive: true,
								maintainAspectRatio: false,
								onClick: (e, elements) => {
									if (elements.length > 0) {
										const idx = elements[0].index;
										const url = marriageData[idx].spouse_url;
										if (url) window.location.href = url;
									}
								},
								plugins: {
									legend: {
										display: false
									},
									tooltip: {
										callbacks: {
											afterBody: function(context) {
												const idx = context[0].dataIndex;
												const m = marriageData[idx];
												const lines = [];
												if (m.age_diff > 0) {
													lines.push(`Age difference: ${m.age_diff} years`);
												}
												if (m.marital_status && m.marital_status !== 'never_married') {
													const statusLabels = { 'widow': 'Widow', 'divorced': 'Divorced', 'separated': 'Separated' };
													lines.push(`Status at marriage: ${statusLabels[m.marital_status] || m.marital_status}`);
												}
												if (m.children_count > 0) {
													lines.push(`Children: ${m.children_count}`);
												}
												if (m.is_teenage) {
													lines.push('⚠ Teenage bride');
												}
												return lines;
											}
										}
									}
								},
								scales: {
									x: {
										beginAtZero: true,
										max: Math.max(...hisAges, ...herAges) + 5,
										title: {
											display: true,
											text: 'Age at Marriage',
											font: {
												family: "'Josefin Sans', sans-serif",
												weight: 500
											}
										},
										grid: {
											color: 'rgba(0,0,0,0.06)'
										}
									},
									y: {
										ticks: {
											font: {
												family: "'Josefin Sans', sans-serif",
												size: 11
											}
										},
										grid: {
											display: false
										}
									}
								}
							}
						});
						}
						
						// Initialize chart after DOM is ready and layout is stable
						if (document.readyState === 'complete') {
							// Small delay to ensure CSS has been applied
							requestAnimationFrame(() => requestAnimationFrame(initChart));
						} else {
							window.addEventListener('load', () => {
								requestAnimationFrame(() => requestAnimationFrame(initChart));
							});
						}
					})();
					</script>
					<?php endif; ?>

					<!-- Marriage Details Table -->
					<div class="marriages-table-wrapper">
						<table class="marriages-table">
							<thead>
								<tr>
									<th>#</th>
									<th>Name</th>
									<th>Marriage Date</th>
									<?php if ( $gender === 'female' ) : ?>
										<th>His Age</th>
										<th>Her Age</th>
									<?php else : ?>
										<th>Her Age</th>
										<th>His Age</th>
									<?php endif; ?>
									<th>Age Diff</th>
									<th>Children</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $chart_marriages as $m ) : ?>
								<tr class="<?php echo $m['is_teenage'] ? 'teenage-row' : ''; ?>">
									<td class="marriage-order-cell"><?php echo esc_html( $m['order'] ); ?></td>
									<td>
										<?php if ( ! empty( $m['spouse_is_saint'] ) && $m['spouse_url'] ) : ?>
											<a href="<?php echo esc_url( $m['spouse_url'] ); ?>">
												<?php echo esc_html( $m['spouse_name'] ); ?>
											</a>
										<?php else : ?>
											<?php echo esc_html( $m['spouse_name'] ); ?>
											<span class="non-saint-marker" title="Not in database">(non-member)</span>
										<?php endif; ?>
										<?php if ( $m['is_teenage'] ) : ?>
											<span class="teen-marker" title="Teenage bride">⚠</span>
										<?php endif; ?>
									</td>
									<td>
										<?php echo esc_html( wasmo_format_saint_date_with_approx( $m['marriage_date'], 'M j, Y', false, $m['marriage_date_approximate'] ) ); ?>
									</td>
									<td class="num <?php echo $m['is_teenage'] ? 'teenage-age' : ''; ?>" title="<?php echo $m['spouse_age_approximate'] ? 'Age is approximate' : ''; ?>">
										<?php if ( $m['spouse_age'] ) : ?>
											<?php echo $m['spouse_age_approximate'] ? 'c. ' : ''; ?><?php echo esc_html( $m['spouse_age'] ); ?>
										<?php else : ?>
											—
										<?php endif; ?>
									</td>
									<td class="num"><?php echo $m['saint_age'] ? esc_html( $m['saint_age'] ) : '—'; ?></td>
									<td class="num"><?php echo $m['age_diff'] > 0 ? esc_html( $m['age_diff'] ) : '—'; ?></td>
									<td class="num"><?php echo esc_html( $m['children_count'] ); ?></td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</section>
			<?php endif; ?>

			<?php 
			// Show children section
			if ( $polygamy_stats['number_of_children'] > 0 ) : 
			?>
				<section class="saint-children">
					<h2>Children (<?php echo esc_html( $polygamy_stats['number_of_children'] ); ?>)</h2>
					<div class="children-by-marriage">
						<?php 
						foreach ( $marriages as $marriage ) : 
							// Check if spouse is a saint (default true for backwards compatibility)
							$spouse_is_saint = isset( $marriage['spouse_is_saint'] ) ? (bool) $marriage['spouse_is_saint'] : true;
							
							$spouse_field = $marriage['spouse'] ?? $marriage['spouse_id'] ?? null;
							$spouse_id = is_array( $spouse_field ) ? ( $spouse_field[0] ?? null ) : $spouse_field;
							$spouse_name_text = $marriage['spouse_name'] ?? null;
							$children = $marriage['children'] ?? array();
							$children_counts = wasmo_get_children_counts( $marriage );
							$displayable_children = wasmo_get_displayable_children( $marriage );
							
							// Determine spouse name to display
							if ( $spouse_is_saint && $spouse_id ) {
								$spouse_name = get_the_title( $spouse_id );
							} elseif ( ! $spouse_is_saint && $spouse_name_text ) {
								$spouse_name = $spouse_name_text;
							} else {
								$spouse_name = 'Unknown';
							}
							
							if ( ! empty( $children ) && is_array( $children ) ) :
						?>
							<div class="marriage-children-group">
								<h4>
									With 
									<?php if ( $spouse_is_saint && $spouse_id ) : ?>
										<a href="<?php echo get_permalink( $spouse_id ); ?>"><?php echo esc_html( $spouse_name ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $spouse_name ); ?>
									<?php endif; ?>
									(<?php echo esc_html( $children_counts['total'] ); ?> <?php echo $children_counts['total'] === 1 ? 'child' : 'children'; ?>)
								</h4>
								<?php if ( ! empty( $displayable_children ) ) : ?>
								<ol class="children-list">
									<?php foreach ( $displayable_children as $child ) : 
										$child_name = $child['child_name'] ?? '';
										$child_birthdate = $child['child_birthdate'] ?? '';
										$child_link_field = $child['child_link'] ?? null;
										$child_link = is_array( $child_link_field ) ? ( $child_link_field[0] ?? null ) : $child_link_field;
										
										// Auto-link: if no explicit link, try to find a saint by name
										if ( ! $child_link && $child_name ) {
											$child_link = wasmo_find_saint_by_child_name( $child_name );
										}
									?>
										<li class="child-item">
											<?php if ( $child_link ) : ?>
												<a href="<?php echo esc_url( get_permalink( $child_link ) ); ?>" class="child-name">
													<?php echo esc_html( $child_name ); ?>
												</a>
											<?php else : ?>
												<span class="child-name"><?php echo esc_html( $child_name ); ?></span>
											<?php endif; ?>
											<?php if ( $child_birthdate ) : 
												$bd_timestamp = strtotime( $child_birthdate );
												$formatted_bd = $bd_timestamp ? date( 'M j, Y', $bd_timestamp ) : $child_birthdate;
											?>
												<span class="child-birthdate">(b. <?php echo esc_html( $formatted_bd ); ?>)</span>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ol>
								<?php elseif ( $children_counts['placeholder'] > 0 ) : ?>
								<!-- <p class="children-placeholder-note">
									<em><?php echo esc_html( $children_counts['placeholder'] ); ?> <?php echo $children_counts['placeholder'] === 1 ? 'child' : 'children'; ?> recorded (names not yet documented)</em>
								</p> -->
								<?php endif; ?>
							</div>
						<?php 
							endif;
						endforeach; 
						?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $related_posts ) ) : ?>
				<?php 
				$featured_posts = array_slice( $related_posts, 0, 6 );
				$remaining_posts = array_slice( $related_posts, 6 );
				?>
				<section class="leader-related-posts">
					<h2>Related Posts (<?php echo count( $related_posts ); ?>)</h2>
					
					<?php if ( ! empty( $featured_posts ) ) : ?>
						<div class="related-posts-grid">
							<?php foreach ( $featured_posts as $related_post ) : ?>
								<article class="related-post-card related-post">
									<?php if ( has_post_thumbnail( $related_post->ID ) ) : ?>
										<a href="<?php echo get_permalink( $related_post->ID ); ?>" class="related-post-thumbnail">
											<?php echo get_the_post_thumbnail( $related_post->ID, 'medium' ); ?>
										</a>
									<?php endif; ?>
									<h3 class="related-post-title">
										<a href="<?php echo get_permalink( $related_post->ID ); ?>">
											<?php echo esc_html( $related_post->post_title ); ?>
										</a>
									</h3>
									<p class="related-post-date">
										<?php echo get_the_date( 'F j, Y', $related_post->ID ); ?>
									</p>
								</article>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $remaining_posts ) ) : ?>
						<ul class="related-posts-list">
							<?php foreach ( $remaining_posts as $related_post ) : ?>
								<li>
									<a href="<?php echo get_permalink( $related_post->ID ); ?>">
										<?php echo esc_html( $related_post->post_title ); ?>
									</a>
									<span class="related-post-list-date">(<?php echo get_the_date( 'M j, Y', $related_post->ID ); ?>)</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $related_media ) ) : ?>
				<section class="leader-related-media">
					<h2>Related Images</h2>
					<div class="media-gallery">
						<?php foreach ( $related_media as $media ) : 
							$image_url = wp_get_attachment_image_url( $media->ID, 'medium' );
							$alt_text = get_post_meta( $media->ID, '_wp_attachment_image_alt', true );
							// Link to parent post if it exists, otherwise link to the attachment page
							$parent_id = $media->post_parent;
							$link_url = $parent_id ? get_permalink( $parent_id ) : get_attachment_link( $media->ID );
							if ( $image_url ) :
						?>
							<a href="<?php echo esc_url( $link_url ); ?>" class="media-gallery-item" title="<?php echo esc_html( $alt_text ); ?>">
								<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $alt_text ); ?>">
							</a>
						<?php 
						endif;
						endforeach; 
						?>
					</div>
				</section>
			<?php endif; ?>

		</div>

		<aside class="leader-sidebar">
			<div class="leader-metadata">
				<h3>Details</h3>
				<dl class="leader-meta-list">
					<?php 
					// Birth date
					$birthdate = get_field( 'birthdate' );
					$birthdate_approx = get_field( 'birthdate_approximate' );
					if ( $birthdate ) : ?>
						<dt>Born</dt>
						<dd><?php echo esc_html( wasmo_format_saint_date_with_approx( $birthdate, 'F j, Y', false, $birthdate_approx ) ); ?></dd>
					<?php endif; ?>

					<?php 
					// Death date
					$deathdate = get_field( 'deathdate' );
					$deathdate_approx = get_field( 'deathdate_approximate' );
					if ( $deathdate ) : ?>
						<dt>Died</dt>
						<dd><?php echo esc_html( wasmo_format_saint_date_with_approx( $deathdate, 'F j, Y', false, $deathdate_approx ) ); ?></dd>
					<?php endif; ?>

					<?php 
					// Age
					$age = wasmo_get_leader_age( $saint_id );
					if ( $age !== null ) : ?>
						<dt><?php echo $is_living ? 'Age' : 'Age at Death'; ?></dt>
						<dd><?php echo esc_html( $age ); ?> years</dd>
					<?php endif; ?>

					<?php if ( $is_apostle ) : ?>
						<?php 
						// Ordained date (show time if specified for seniority disambiguation)
						$ordained_date = get_field( 'ordained_date' );
						if ( $ordained_date ) : ?>
							<dt>Ordained Apostle</dt>
							<dd><?php echo esc_html( wasmo_format_saint_date( $ordained_date, 'F j, Y', true ) ); ?></dd>
						<?php endif; ?>

						<?php 
						// Service ended early (excommunication, resignation, etc.)
						$ordain_end = get_field( 'ordain_end' );
						if ( $ordain_end ) : ?>
							<dt>Service Ended</dt>
							<dd><?php echo esc_html( wasmo_format_saint_date( $ordain_end ) ); ?></dd>
						<?php endif; ?>

						<?php 
						// Note about service end
						$ordain_note = get_field( 'ordain_note' );
						if ( $ordain_note ) : ?>
							<dt>Status Note</dt>
							<dd class="leader-ordain-note"><?php echo wp_kses_post( $ordain_note ); ?></dd>
						<?php endif; ?>

						<?php 
						// Age at call
						$age_at_call = wasmo_get_saint_age_at_call( $saint_id );
						if ( $age_at_call !== null ) : ?>
							<dt>Age When Called</dt>
							<dd><?php echo esc_html( $age_at_call ); ?> years</dd>
						<?php endif; ?>

						<?php 
						// Years served
						$years_served = wasmo_get_saint_years_served( $saint_id );
						if ( $years_served !== null ) : ?>
							<dt>Years as Apostle</dt>
							<dd><?php echo esc_html( $years_served ); ?> years</dd>
						<?php endif; ?>

						<?php if ( $is_living ) : 
							$seniority = wasmo_get_saint_seniority( $saint_id );
							if ( $seniority !== null ) : ?>
								<dt>Current Seniority</dt>
								<dd>#<?php echo esc_html( $seniority ); ?></dd>
							<?php endif; ?>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( $is_president ) : ?>
						<?php 
						// Became president date
						$president_date = get_field( 'became_president_date' );
						if ( $president_date ) : ?>
							<dt>Became President</dt>
							<dd><?php echo esc_html( wasmo_format_saint_date( $president_date ) ); ?></dd>
						<?php endif; ?>

						<?php 
						// Years as president
						$years_as_president = wasmo_get_saint_years_as_president( $saint_id );
						if ( $years_as_president !== null ) : ?>
							<dt>Years as President</dt>
							<dd><?php echo esc_html( $years_as_president ); ?> years</dd>
						<?php endif; ?>
					<?php endif; ?>

					<?php 
					// Mission
					$mission = get_field( 'mission' );
					if ( $mission ) : ?>
						<dt>Mission</dt>
						<dd><?php echo esc_html( $mission ); ?></dd>
					<?php endif; ?>

					<?php 
					// Education
					$education = get_field( 'education' );
					if ( $education ) : ?>
						<dt>Education</dt>
						<dd><?php echo esc_html( $education ); ?></dd>
					<?php endif; ?>

					<?php 
					// Profession
					$profession = get_field( 'profession' );
					if ( $profession ) : ?>
						<dt>Profession</dt>
						<dd><?php echo esc_html( $profession ); ?></dd>
					<?php endif; ?>

					<?php 
					// Military
					$military = get_field( 'military' );
					if ( $military ) : ?>
						<dt>Military Service</dt>
						<dd><?php echo esc_html( $military ); ?></dd>
					<?php endif; ?>

					<?php 
					// FamilySearch ID
					$familysearch_id = get_field( 'familysearch_id' );
					if ( $familysearch_id ) : ?>
						<dt>FamilySearch</dt>
						<dd>
							<a href="https://www.familysearch.org/tree/person/details/<?php echo esc_attr( $familysearch_id ); ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;justify-content:center;gap:8px;">
								<?php echo wasmo_get_icon_svg( 'familysearch', 32 ); ?>
								<?php echo esc_html( $familysearch_id ); ?>
							</a>
						</dd>
					<?php endif; ?>

					<?php 
					// Polygamy stats (computed from marriages)
					if ( $polygamy_stats['was_polygamist'] ) : ?>
						<dt>Polygamist</dt>
						<dd>Yes (<?php echo esc_html( $polygamy_stats['number_of_marriages'] ); ?> <?php echo $gender === 'male' ? 'wives' : 'husbands'; ?>)</dd>
						
						<?php if ( $polygamy_stats['number_of_children'] > 0 ) : ?>
							<dt>Total Children</dt>
							<dd><?php echo esc_html( $polygamy_stats['number_of_children'] ); ?></dd>
						<?php endif; ?>
						
						<?php if ( $polygamy_stats['teenage_brides_count'] > 0 && $gender === 'male' ) : ?>
							<dt>Teenage Brides</dt>
							<dd><?php echo esc_html( $polygamy_stats['teenage_brides_count'] ); ?></dd>
						<?php endif; ?>
						
						<?php if ( $polygamy_stats['largest_age_diff'] > 0 ) : ?>
							<dt>Largest Age Gap</dt>
							<dd><?php echo esc_html( $polygamy_stats['largest_age_diff'] ); ?> years</dd>
						<?php endif; ?>
						
						<?php if ( $polygamy_stats['avg_age_diff'] > 0 ) : ?>
							<dt>Avg Age Difference</dt>
							<dd><?php echo esc_html( $polygamy_stats['avg_age_diff'] ); ?> years</dd>
						<?php endif; ?>
						
						<?php if ( $polygamy_stats['age_first_marriage'] !== null ) : ?>
							<dt>Age at First Marriage</dt>
							<dd><?php echo esc_html( $polygamy_stats['age_first_marriage'] ); ?> years</dd>
						<?php endif; ?>
					<?php elseif ( $polygamy_stats['number_of_marriages'] === 1 ) : ?>
						<?php if ( $polygamy_stats['number_of_children'] > 0 ) : ?>
							<dt>Children</dt>
							<dd><?php echo esc_html( $polygamy_stats['number_of_children'] ); ?></dd>
						<?php endif; ?>
					<?php endif; ?>

					<?php 
					// Marital status at marriage (for wives)
					$marital_status = get_field( 'marital_status_at_marriage' );
					if ( $marital_status && $marital_status !== 'never_married' ) : 
						$status_labels = array(
							'widow' => 'Widow',
							'divorced' => 'Divorced',
							'separated' => 'Separated',
						);
					?>
						<dt>Status at Marriage</dt>
						<dd><?php echo esc_html( $status_labels[ $marital_status ] ?? $marital_status ); ?></dd>
					<?php endif; ?>
				</dl>
			</div>

			<div class="leader-navigation">
				<a href="<?php echo get_post_type_archive_link( 'saint' ); ?>" class="btn btn-secondary">
					← All Saints
				</a>
			</div>
		</aside>
	</div>
</div>
</article>

<?php
endwhile;

get_footer();
