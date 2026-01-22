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
	$polygamy_type = wasmo_get_polygamy_type( $saint_id );
	
	// Get parents (reverse lookup from marriage/children records)
	$parents = wasmo_get_saint_parents( $saint_id );
	$has_parents = ! empty( $parents['mother'] ) || ! empty( $parents['father'] );
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

			<?php if ( $has_parents ) : ?>
				<section class="leader-parents-section">
					<h2>Parents</h2>
					<div class="leaders-grid leaders-grid-small">
						<?php if ( ! empty( $parents['father'] ) ) : ?>
							<?php wasmo_render_saint_card( $parents['father'], 'small', false, false, false, 'Father' ); ?>
						<?php endif; ?>
						<?php if ( ! empty( $parents['mother'] ) ) : ?>
							<?php wasmo_render_saint_card( $parents['mother'], 'small', false, false, false, 'Mother' ); ?>
						<?php endif; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $served_under ) ) : ?>
				<section class="leader-served-under">
					<h2>Served Under</h2>
					<p class="section-description">Church presidents during this leader's apostolic service:</p>
					<div class="leaders-grid leaders-grid-small">
						<?php foreach ( $served_under as $prophet_id ) : ?>
							<?php wasmo_render_saint_card( $prophet_id, 'small', false, false, false ); ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $apostles_under ) ) : ?>
				<section class="leader-apostles-under">
					<h2>Apostles Who Served During This Presidency</h2>
					<p class="section-description">Apostles who served under this leader during their presidency:</p>
					<div class="leaders-grid leaders-grid-small">
						<?php 
						foreach ( $apostles_under as $apostle_id ) : 
							wasmo_render_saint_card( $apostle_id, 'small', true, false, false );
						endforeach; 
						?>
					</div>
				</section>
			<?php endif; ?>
		</div> <!-- leader-main-content -->
		<?php
		get_template_part( 'template-parts/content/content-saint-aside', null, array(
			'saint_id'      => $saint_id,
			'is_living'     => $is_living,
			'is_apostle'    => $is_apostle,
			'is_president'  => $is_president,
			'gender'        => $gender,
			'polygamy_stats' => $polygamy_stats,
			'polygamy_type' => $polygamy_type,
		) );
		?>
	</div> <!-- leader-content-wrapper -->

			<div class="leader-marriages-section content-full-width">
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
						$spouse_deathdate = get_field( 'deathdate', $spouse_id );
						
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
							'spouse_birthdate'          => $spouse_birthdate,
							'spouse_deathdate'          => $spouse_deathdate,
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
						$spouse_deathdate = null; // Non-saint spouses don't have death dates tracked
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
							'spouse_birthdate'          => $spouse_birthdate,
							'spouse_deathdate'          => $spouse_deathdate,
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
				<section class="saint-marriages content-full-width">
					<h2><?php echo esc_html( $spouse_label ); ?> (<?php echo count( $marriages ); ?>)</h2>
					<?php if ( $polygamy_stats['number_of_marriages'] > 1 ) : 
						$is_or_was = $is_living ? 'is' : 'was';
						$polygamy_label = ( $polygamy_type['type'] === 'celestial' ) ? 'celestial polygamist' : 'polygamist';
						$spouse_word = ( $gender === 'male' ) ? 'wives' : 'husbands';
					?>
						<p class="section-description">
							<?php echo esc_html( get_the_title( $saint_id ) ); ?> <?php echo $is_or_was; ?> a <strong><?php echo esc_html( $polygamy_label ); ?></strong> with <?php echo esc_html( $polygamy_stats['number_of_marriages'] ); ?> <?php echo $spouse_word; ?>.
							<?php if ( $polygamy_type['type'] === 'celestial' ) : ?>
								<em>Note these are sequential marriages &mdash; each previous spouse died before the next marriage, meaning no simultaneous living plural marriages.</em>
							<?php else : ?>
								<em>Note these are simultaneous marriages &mdash; married to multiple living spouses at the same time.</em>
							<?php endif; ?>
							<?php if ( $polygamy_stats['teenage_brides_count'] > 0 && $gender === 'male' ) : ?>
								<strong><?php echo esc_html( $polygamy_stats['teenage_brides_count'] ); ?></strong> of these wives were a teenager (18 or less) at the time of marriage.
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
						<table class="marriages-table sortable-table" id="marriages-table">
							<thead>
								<tr>
									<th data-sort="int">#</th>
									<th data-sort="string">Name</th>
									<th data-sort="date"><?php echo ( $gender === 'male' ) ? 'Born' : 'Spouse Born'; ?></th>
									<th data-sort="date"><?php echo ( $gender === 'male' ) ? 'Died' : 'Spouse Died'; ?></th>
									<th data-sort="date">Married</th>
									<?php if ( $gender === 'female' ) : ?>
										<th data-sort="int">His Age</th>
										<th data-sort="int">Her Age</th>
									<?php else : ?>
										<th data-sort="int">Her Age</th>
										<th data-sort="int">His Age</th>
									<?php endif; ?>
									<th data-sort="int">Age Diff</th>
									<th data-sort="int">Children</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $chart_marriages as $m ) : 
									$spouse_birth_formatted = $m['spouse_birthdate'] ? wasmo_format_saint_date_with_approx( $m['spouse_birthdate'], 'M j, Y', false, false ) : null;
									$spouse_death_formatted = $m['spouse_deathdate'] ? wasmo_format_saint_date_with_approx( $m['spouse_deathdate'], 'M j, Y', false, false ) : null;
								?>
								<tr class="<?php echo $m['is_teenage'] ? 'teenage-row' : ''; ?>" 
									data-birth="<?php echo esc_attr( $m['spouse_birthdate'] ?: '9999-12-31' ); ?>"
									data-death="<?php echo esc_attr( $m['spouse_deathdate'] ?: '9999-12-31' ); ?>"
									data-married="<?php echo esc_attr( $m['marriage_date'] ?: '9999-12-31' ); ?>">
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
									<td class="date-cell"><?php echo $spouse_birth_formatted ? esc_html( $spouse_birth_formatted ) : '—'; ?></td>
									<td class="date-cell"><?php echo $spouse_death_formatted ? esc_html( $spouse_death_formatted ) : '<span class="living-indicator" title="Still living">●</span>'; ?></td>
									<td class="date-cell">
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
										// if ( ! $child_link && $child_name ) {
										// 	$child_link = wasmo_find_saint_by_child_name( $child_name );
										// }
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
					<h2>Posts Related to <?php the_title(); ?></h2>
					
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
					<h2>Images Related to <?php the_title(); ?></h2>
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

	</div>
</div>
</article>

<?php
endwhile;

get_footer();
