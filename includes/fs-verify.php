<?php
/**
 * FamilySearch Verification Tool
 * 
 * Admin page for verifying and completing saint data from FamilySearch.org.
 * Includes ID discovery for saints without FamilySearch IDs.
 *
 * @package wasmo
 */

// ============================================
// ADMIN MENU & PAGE
// ============================================

/**
 * Add admin menu page for FamilySearch verification
 */
function wasmo_add_fs_verify_page() {
	add_submenu_page(
		'edit.php?post_type=saint',
		'FamilySearch Verify',
		'FamilySearch Verify',
		'manage_options',
		'familysearch-verify',
		'wasmo_render_fs_verify_page'
	);
}
add_action( 'admin_menu', 'wasmo_add_fs_verify_page' );

/**
 * Get saints needing verification
 *
 * @param int $days_since Days since last verification (default 30).
 * @return array Array of saint post IDs.
 */
function wasmo_get_saints_needing_verification( $days_since = 30 ) {
	$cutoff = date( 'Y-m-d H:i:s', strtotime( "-{$days_since} days" ) );
	
	// Saints with FS ID but not verified or verified before cutoff
	$args = array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'     => 'familysearch_id',
				'value'   => '',
				'compare' => '!=',
			),
			array(
				'relation' => 'OR',
				array(
					'key'     => 'familysearch_verified',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'familysearch_verified',
					'value'   => '',
					'compare' => '=',
				),
				array(
					'key'     => 'familysearch_verified',
					'value'   => $cutoff,
					'compare' => '<',
					'type'    => 'DATETIME',
				),
			),
		),
		'fields'         => 'ids',
	);
	
	return get_posts( $args );
}

/**
 * Get deceased saints without FamilySearch ID (candidates for discovery)
 *
 * @return array Array of saint post objects.
 */
function wasmo_get_saints_for_fs_discovery() {
	// Calculate cutoff date (90 years ago) for likely deceased saints
	$cutoff_date = date( 'Y-m-d', strtotime( '-90 years' ) );
	
	$args = array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			'relation' => 'AND',
			// Must be likely deceased: has death date OR birthdate > 90 years ago
			array(
				'relation' => 'OR',
				// Has death date (definitely deceased)
				array(
					'key'     => 'deathdate',
					'value'   => '',
					'compare' => '!=',
				),
				// Or birthdate older than 90 years ago (likely deceased)
				array(
					'key'     => 'birthdate',
					'value'   => $cutoff_date,
					'compare' => '<',
					'type'    => 'DATE',
				),
			),
			// No FamilySearch ID
			array(
				'relation' => 'OR',
				array(
					'key'     => 'familysearch_id',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'familysearch_id',
					'value'   => '',
					'compare' => '=',
				),
			),
		),
		'orderby'        => 'title',
		'order'          => 'ASC',
	);
	
	return get_posts( $args );
}

/**
 * Get saints that have been verified
 *
 * @return array Array of saint post IDs.
 */
function wasmo_get_verified_saints() {
	$args = array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'     => 'familysearch_verified',
				'value'   => '',
				'compare' => '!=',
			),
		),
		'orderby'        => 'meta_value',
		'meta_key'       => 'familysearch_verified',
		'order'          => 'DESC',
		'fields'         => 'ids',
	);
	
	return get_posts( $args );
}

/**
 * Render the FamilySearch verification page
 */
function wasmo_render_fs_verify_page() {
	// Handle tab switching
	$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'verified';
	$tabs = array(
		'verified' => 'Verified Saints',
		'pending'  => 'Needs Verification',
		'discover' => 'Missing FS IDs',
		'settings' => 'Settings',
	);
	
	// Get stats
	$saints_verified = wasmo_get_verified_saints();
	$saints_need_verify = wasmo_get_saints_needing_verification();
	$saints_for_discovery = wasmo_get_saints_for_fs_discovery();
	
	?>
	<div class="wrap">
		<h1>FamilySearch Verification</h1>
		
		<div class="notice notice-info">
			<p><strong>Note:</strong> Verification is performed using the local <code>fs-verify</code> tool and synced via REST API. 
			See <code>scripts/fs-verify/README.md</code> for usage instructions.</p>
		</div>
		
		<!-- Tabs -->
		<nav class="nav-tab-wrapper wp-clearfix">
			<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_key, admin_url( 'edit.php?post_type=saint&page=familysearch-verify' ) ) ); ?>" 
				   class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( $tab_label ); ?>
					<?php if ( $tab_key === 'verified' && count( $saints_verified ) > 0 ) : ?>
						<span class="count">(<?php echo count( $saints_verified ); ?>)</span>
					<?php endif; ?>
					<?php if ( $tab_key === 'pending' && count( $saints_need_verify ) > 0 ) : ?>
						<span class="count">(<?php echo count( $saints_need_verify ); ?>)</span>
					<?php endif; ?>
					<?php if ( $tab_key === 'discover' && count( $saints_for_discovery ) > 0 ) : ?>
						<span class="count">(<?php echo count( $saints_for_discovery ); ?>)</span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</nav>
		
		<div class="tab-content" style="margin-top: 20px;">
			<?php
			switch ( $current_tab ) {
				case 'verified':
					wasmo_render_fs_verified_tab( $saints_verified );
					break;
				case 'pending':
					wasmo_render_fs_pending_tab( $saints_need_verify );
					break;
				case 'discover':
					wasmo_render_fs_discover_tab( $saints_for_discovery );
					break;
				case 'settings':
					wasmo_render_fs_settings_tab();
					break;
			}
			?>
		</div>
	</div>
	
	<style>
		.fs-verify-table { width: 100%; border-collapse: collapse; }
		.fs-verify-table th, .fs-verify-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
		.fs-verify-table th { background: #f5f5f5; }
		.fs-verify-status { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; }
		.fs-verify-status.needs-verify { background: #ffeaa7; color: #856404; }
		.fs-verify-status.verified { background: #d4edda; color: #155724; }
		.fs-verify-status.not-found { background: #f8d7da; color: #721c24; }
		.fs-verify-notes { max-width: 350px; font-size: 12px; color: #666; line-height: 1.4; }
		.fs-verify-notes .note-updated { color: #0073aa; }
		.fs-verify-notes .note-family { color: #46b450; }
		.fs-verify-notes .note-matched { color: #826eb4; }
		.fs-verify-notes .note-photo { color: #f56e28; }
		.fs-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
		.fs-stat-card { background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 15px; text-align: center; }
		.fs-stat-card h3 { margin: 0 0 5px 0; font-size: 32px; color: #0073aa; }
		.fs-stat-card p { margin: 0; color: #666; }
	</style>
	<?php
}

/**
 * Render the Verified Saints tab
 */
function wasmo_render_fs_verified_tab( $saints_verified ) {
	?>
	<div class="card" style="max-width: 1200px;">
		<h2>Verified Saints</h2>
		<p>These saints have been verified against FamilySearch data using the local <code>fs-verify</code> tool.</p>
		
		<?php if ( empty( $saints_verified ) ) : ?>
			<p><em>No saints have been verified yet. Run the fs-verify tool locally to verify saints.</em></p>
			<p>Example: <code>node index.js sync KWJH-9QN --local</code></p>
		<?php else : ?>
			<table class="fs-verify-table widefat">
				<thead>
					<tr>
						<th>Name</th>
						<th>FamilySearch ID</th>
						<th>Birth Date</th>
						<th>Death Date</th>
						<th>Verified On</th>
						<th>Notes</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $saints_verified as $saint_id ) : 
						$fs_id = get_field( 'familysearch_id', $saint_id );
						$birthdate = get_field( 'birthdate', $saint_id );
						$deathdate = get_field( 'deathdate', $saint_id );
						$verified = get_field( 'familysearch_verified', $saint_id );
						$notes = get_field( 'familysearch_notes', $saint_id );
					?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $saint_id ) ); ?>">
									<?php echo esc_html( get_the_title( $saint_id ) ); ?>
								</a>
							</td>
							<td>
								<?php if ( $fs_id ) : ?>
									<a href="https://www.familysearch.org/tree/person/details/<?php echo esc_attr( $fs_id ); ?>" target="_blank">
										<?php echo esc_html( $fs_id ); ?>
									</a>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $birthdate ?: '—' ); ?></td>
							<td><?php echo esc_html( $deathdate ?: '—' ); ?></td>
							<td>
								<span class="fs-verify-status verified">
									<?php echo esc_html( date( 'M j, Y g:i a', strtotime( $verified ) ) ); ?>
								</span>
							</td>
							<td class="fs-verify-notes">
								<?php 
								if ( $notes ) {
									// Format notes with color coding
									$formatted = esc_html( $notes );
									$formatted = preg_replace( '/^(Updated:)/', '<span class="note-updated">$1</span>', $formatted );
									$formatted = preg_replace( '/(Family:)/', '<span class="note-family">$1</span>', $formatted );
									$formatted = preg_replace( '/(Matched \d+ children)/', '<span class="note-matched">$1</span>', $formatted );
									$formatted = preg_replace( '/(Portrait uploaded)/', '<span class="note-photo">$1</span>', $formatted );
									$formatted = str_replace( '. ', '.<br>', $formatted );
									echo $formatted;
								} else {
									echo '—';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			
			<p style="margin-top: 15px; color: #666;">
				Showing <?php echo count( $saints_verified ); ?> verified saints.
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render the Pending Verification tab (read-only list)
 */
function wasmo_render_fs_pending_tab( $saints_need_verify ) {
	?>
	<div class="card" style="max-width: 1200px;">
		<h2>Saints Needing Verification</h2>
		<p>These saints have FamilySearch IDs but haven't been verified recently (or at all).</p>
		<p><strong>To verify:</strong> Run the local fs-verify tool: <code>node index.js sync-batch ids.json --local</code></p>
		
		<?php if ( empty( $saints_need_verify ) ) : ?>
			<p><em>All saints with FamilySearch IDs are up to date!</em></p>
		<?php else : ?>
			<table class="fs-verify-table widefat">
				<thead>
					<tr>
						<th>Name</th>
						<th>FamilySearch ID</th>
						<th>Birth Date</th>
						<th>Death Date</th>
						<th>Last Verified</th>
						<th>View</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $saints_need_verify as $saint_id ) : 
						$fs_id = get_field( 'familysearch_id', $saint_id );
						$birthdate = get_field( 'birthdate', $saint_id );
						$deathdate = get_field( 'deathdate', $saint_id );
						$verified = get_field( 'familysearch_verified', $saint_id );
					?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $saint_id ) ); ?>">
									<?php echo esc_html( get_the_title( $saint_id ) ); ?>
								</a>
							</td>
							<td>
								<a href="https://www.familysearch.org/tree/person/details/<?php echo esc_attr( $fs_id ); ?>" target="_blank">
									<?php echo esc_html( $fs_id ); ?>
								</a>
							</td>
							<td><?php echo esc_html( $birthdate ?: '—' ); ?></td>
							<td><?php echo esc_html( $deathdate ?: '—' ); ?></td>
							<td>
								<?php if ( $verified ) : ?>
									<span class="fs-verify-status needs-verify"><?php echo esc_html( date( 'M j, Y', strtotime( $verified ) ) ); ?></span>
								<?php else : ?>
									<span class="fs-verify-status needs-verify">Never</span>
								<?php endif; ?>
							</td>
							<td>
								<a href="https://www.familysearch.org/tree/person/details/<?php echo esc_attr( $fs_id ); ?>" target="_blank" class="button button-small">
									View on FS
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			
			<p style="margin-top: 15px; color: #666;">
				Showing <?php echo count( $saints_need_verify ); ?> saints needing verification.
			</p>
			
			<div style="margin-top: 15px; padding: 15px; background: #f0f0f1; border-radius: 5px;">
				<strong>Quick Export for fs-verify:</strong>
				<p>Copy this JSON array of FamilySearch IDs to use with the batch sync command:</p>
				<textarea readonly style="width: 100%; height: 80px; font-family: monospace; font-size: 12px;"><?php 
					$fs_ids = array();
					foreach ( $saints_need_verify as $saint_id ) {
						$fs_id = get_field( 'familysearch_id', $saint_id );
						if ( $fs_id ) {
							$fs_ids[] = $fs_id;
						}
					}
					echo json_encode( array_slice( $fs_ids, 0, 50 ) ); // Limit to 50 for manageable batches
				?></textarea>
				<?php if ( count( $fs_ids ) > 50 ) : ?>
					<p class="description">Showing first 50 of <?php echo count( $fs_ids ); ?> IDs.</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render the Missing IDs tab (read-only list)
 */
function wasmo_render_fs_discover_tab( $saints_for_discovery ) {
	?>
	<div class="card" style="max-width: 1200px;">
		<h2>Saints Missing FamilySearch IDs</h2>
		<p>These saints don't have FamilySearch IDs assigned. Includes saints with death dates or birthdates older than 90 years (likely deceased).</p>
		<p><strong>To search:</strong> <code>node index.js search --name "Name Here" --birth-year 1840</code></p>
		<p><strong>Batch search:</strong> <code>node index.js search-batch saints-to-search.json --local</code></p>
		
		<?php if ( empty( $saints_for_discovery ) ) : ?>
			<p><em>All deceased saints have FamilySearch IDs assigned!</em></p>
		<?php else : ?>
			<table class="fs-verify-table widefat">
				<thead>
					<tr>
						<th>Name</th>
						<th>Birth Date</th>
						<th>Death Date</th>
						<th>Gender</th>
						<th>Search Link</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $saints_for_discovery as $saint ) : 
						$birthdate = get_field( 'birthdate', $saint->ID );
						$deathdate = get_field( 'deathdate', $saint->ID );
						$gender = get_field( 'gender', $saint->ID );
						$birth_year = $birthdate ? date( 'Y', strtotime( $birthdate ) ) : '';
						$death_year = $deathdate ? date( 'Y', strtotime( $deathdate ) ) : '';
						
						// Build FamilySearch search URL
						$search_params = array(
							'q.givenName' => explode( ' ', $saint->post_title )[0],
							'q.surname' => end( explode( ' ', $saint->post_title ) ),
						);
						if ( $birth_year ) {
							$search_params['q.birthLikeDate.from'] = $birth_year;
							$search_params['q.birthLikeDate.to'] = $birth_year;
						}
						if ( $death_year ) {
							$search_params['q.deathLikeDate.from'] = $death_year;
							$search_params['q.deathLikeDate.to'] = $death_year;
						}
						$search_url = 'https://www.familysearch.org/search/tree/results?' . http_build_query( $search_params );
					?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $saint->ID ) ); ?>">
									<?php echo esc_html( $saint->post_title ); ?>
								</a>
							</td>
							<td><?php echo esc_html( $birthdate ?: '—' ); ?></td>
							<td><?php echo esc_html( $deathdate ?: '—' ); ?></td>
							<td><?php echo esc_html( ucfirst( $gender ) ?: '—' ); ?></td>
							<td>
								<a href="<?php echo esc_url( $search_url ); ?>" target="_blank" class="button button-small">
									Search on FS
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			
			<p style="margin-top: 15px; color: #666;">
				Showing <?php echo count( $saints_for_discovery ); ?> saints without FamilySearch IDs.
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render the Settings tab
 */
function wasmo_render_fs_settings_tab() {
	?>
	<div class="card" style="max-width: 600px;">
		<h2>About fs-verify</h2>
		<p>The FamilySearch verification tool runs <strong>locally</strong> on your development machine and syncs data to this WordPress site via REST API.</p>
		
		<h3>Why Local?</h3>
		<ul>
			<li>FamilySearch blocks automated/headless browsers</li>
			<li>The tool requires a visible Chrome browser window</li>
			<li>Running locally avoids server configuration complexity</li>
		</ul>
		
		<h3>Setup Instructions</h3>
		<ol>
			<li>Navigate to <code>scripts/fs-verify/</code> in your theme</li>
			<li>Run <code>npm install</code></li>
			<li>Copy <code>.env.example</code> to <code>.env</code> and add your FamilySearch credentials</li>
			<li>Run <code>node index.js login</code> to authenticate</li>
			<li>Run <code>node index.js sync FSID --local</code> to verify saints</li>
		</ol>
		
		<p>See <code>scripts/fs-verify/README.md</code> for full documentation.</p>
	</div>
	
	<div class="card" style="max-width: 600px; margin-top: 20px;">
		<h2>REST API Status</h2>
		<p>The local fs-verify tool connects to this site via REST API endpoints.</p>
		
		<table class="form-table">
			<tr>
				<th>API Base URL</th>
				<td>
					<code><?php echo esc_html( rest_url( 'wasmo/v1/' ) ); ?></code>
				</td>
			</tr>
			<tr>
				<th>Endpoints Available</th>
				<td>
					<ul style="margin: 0; padding-left: 20px;">
						<li><code>GET /saints</code> - List saints</li>
						<li><code>GET /saints/{id}</code> - Get saint by ID</li>
						<li><code>GET /saints/by-fs-id/{fs_id}</code> - Get saint by FS ID</li>
						<li><code>POST /saints/{id}</code> - Update saint</li>
						<li><code>POST /saints/{id}/portrait</code> - Upload portrait</li>
						<li><code>POST /saints/{id}/verify</code> - Mark as verified</li>
					</ul>
				</td>
			</tr>
			<tr>
				<th>Authentication</th>
				<td>
					<p>Uses WordPress Application Passwords.</p>
					<a href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>" class="button button-small">
						Manage Application Passwords
					</a>
				</td>
			</tr>
		</table>
	</div>
	<?php
}

// ============================================
// AJAX HANDLERS
// ============================================

/**
 * AJAX: Apply verified data to saint
 */
function wasmo_ajax_apply_fs_data() {
	check_ajax_referer( 'wasmo_fs_verify', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permission denied.' );
	}
	
	$saint_id = isset( $_POST['saint_id'] ) ? intval( $_POST['saint_id'] ) : 0;
	$updates = isset( $_POST['updates'] ) ? $_POST['updates'] : array();
	
	if ( ! $saint_id ) {
		wp_send_json_error( 'Invalid saint ID.' );
	}
	
	// Apply selected updates
	foreach ( $updates as $field => $value ) {
		update_field( $field, sanitize_text_field( $value ), $saint_id );
	}
	
	// Set verified timestamp
	update_field( 'familysearch_verified', current_time( 'mysql' ), $saint_id );
	
	wp_send_json_success( array( 'message' => 'Saint data updated successfully.' ) );
}
add_action( 'wp_ajax_wasmo_apply_fs_data', 'wasmo_ajax_apply_fs_data' );

/**
 * AJAX: Assign FamilySearch ID to saint
 */
function wasmo_ajax_assign_fs_id() {
	check_ajax_referer( 'wasmo_fs_verify', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permission denied.' );
	}
	
	$saint_id = isset( $_POST['saint_id'] ) ? intval( $_POST['saint_id'] ) : 0;
	$fs_id = isset( $_POST['fs_id'] ) ? sanitize_text_field( $_POST['fs_id'] ) : '';
	
	if ( ! $saint_id || ! $fs_id ) {
		wp_send_json_error( 'Invalid parameters.' );
	}
	
	update_field( 'familysearch_id', $fs_id, $saint_id );
	
	wp_send_json_success( array( 
		'message' => 'FamilySearch ID assigned.',
		'fs_id'   => $fs_id,
	) );
}
add_action( 'wp_ajax_wasmo_assign_fs_id', 'wasmo_ajax_assign_fs_id' );

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Compare saint data with FamilySearch data
 *
 * @param array $current Current saint data.
 * @param array $fs_data FamilySearch data.
 * @return array Array of differences.
 */
function wasmo_compare_saint_data( $current, $fs_data ) {
	$differences = array();
	
	// Compare birthdate
	if ( isset( $fs_data['birth']['date'] ) && $fs_data['birth']['date'] !== $current['birthdate'] ) {
		$differences['birthdate'] = array(
			'current' => $current['birthdate'],
			'new'     => $fs_data['birth']['date'],
		);
	}
	
	// Compare deathdate
	if ( isset( $fs_data['death']['date'] ) && $fs_data['death']['date'] !== $current['deathdate'] ) {
		$differences['deathdate'] = array(
			'current' => $current['deathdate'],
			'new'     => $fs_data['death']['date'],
		);
	}
	
	// Compare gender
	if ( isset( $fs_data['sex'] ) && $fs_data['sex'] !== $current['gender'] ) {
		$differences['gender'] = array(
			'current' => $current['gender'],
			'new'     => $fs_data['sex'],
		);
	}
	
	return $differences;
}

/**
 * Compare full saint data including marriages and children
 *
 * @param int   $saint_id The saint post ID.
 * @param array $fs_data  FamilySearch data.
 * @return array Comprehensive comparison results.
 */
function wasmo_compare_saint_full( $saint_id, $fs_data ) {
	$result = array(
		'basic_differences' => array(),
		'spouse_matches'    => array(),
		'child_matches'     => array(),
		'missing_spouses'   => array(),
		'missing_children'  => array(),
		'photo_available'   => ! empty( $fs_data['photo_url'] ),
	);
	
	// Basic field comparison
	$current_data = array(
		'name'      => get_the_title( $saint_id ),
		'birthdate' => get_field( 'birthdate', $saint_id ),
		'deathdate' => get_field( 'deathdate', $saint_id ),
		'gender'    => get_field( 'gender', $saint_id ),
	);
	$result['basic_differences'] = wasmo_compare_saint_data( $current_data, $fs_data );
	
	// Get current marriages (for women who store marriages, or reverse lookup for men)
	$current_marriages = wasmo_get_all_marriage_data( $saint_id );
	
	// Compare spouses
	if ( ! empty( $fs_data['spouses'] ) ) {
		foreach ( $fs_data['spouses'] as $fs_spouse ) {
			$match = wasmo_find_spouse_match( $saint_id, $fs_spouse, $current_marriages );
			if ( $match['found'] ) {
				$result['spouse_matches'][] = array(
					'fs_spouse'     => $fs_spouse,
					'local_spouse'  => $match['spouse'],
					'marriage_diff' => $match['differences'],
				);
				
				// Compare children for this marriage
				if ( ! empty( $fs_spouse['children'] ) ) {
					foreach ( $fs_spouse['children'] as $fs_child ) {
						$child_match = wasmo_find_child_match( $fs_child );
						$result['child_matches'][] = array(
							'fs_child'    => $fs_child,
							'local_match' => $child_match,
						);
					}
				}
			} else {
				$result['missing_spouses'][] = $fs_spouse;
			}
		}
	}
	
	return $result;
}

/**
 * Find a matching spouse in current marriages
 *
 * @param int   $saint_id         The saint post ID.
 * @param array $fs_spouse        FamilySearch spouse data.
 * @param array $current_marriages Current marriage data.
 * @return array Match result with 'found', 'spouse', and 'differences' keys.
 */
function wasmo_find_spouse_match( $saint_id, $fs_spouse, $current_marriages ) {
	$result = array(
		'found'       => false,
		'spouse'      => null,
		'differences' => array(),
	);
	
	foreach ( $current_marriages as $marriage ) {
		$spouse_id = null;
		$spouse_name = '';
		
		// Get spouse info from marriage
		if ( ! empty( $marriage['spouse'] ) ) {
			$spouse_id = is_array( $marriage['spouse'] ) ? ( $marriage['spouse'][0] ?? null ) : $marriage['spouse'];
			if ( $spouse_id ) {
				$spouse_name = get_the_title( $spouse_id );
				$spouse_fs_id = get_field( 'familysearch_id', $spouse_id );
				
				// Match by FamilySearch ID first (most reliable)
				if ( $spouse_fs_id && $spouse_fs_id === $fs_spouse['familysearch_id'] ) {
					$result['found'] = true;
					$result['spouse'] = array(
						'id'   => $spouse_id,
						'name' => $spouse_name,
					);
					
					// Check for marriage date differences
					if ( isset( $fs_spouse['marriage_date'] ) && $marriage['marriage_date'] !== $fs_spouse['marriage_date'] ) {
						$result['differences']['marriage_date'] = array(
							'current' => $marriage['marriage_date'],
							'new'     => $fs_spouse['marriage_date'],
						);
					}
					
					return $result;
				}
			}
		} elseif ( ! empty( $marriage['spouse_name'] ) ) {
			$spouse_name = $marriage['spouse_name'];
		}
		
		// Fallback: Match by name similarity
		if ( $spouse_name && wasmo_names_match( $spouse_name, $fs_spouse['name'] ) ) {
			$result['found'] = true;
			$result['spouse'] = array(
				'id'   => $spouse_id,
				'name' => $spouse_name,
			);
			
			// Suggest adding FS ID to spouse
			if ( $spouse_id && empty( get_field( 'familysearch_id', $spouse_id ) ) ) {
				$result['differences']['spouse_fs_id'] = array(
					'current' => null,
					'new'     => $fs_spouse['familysearch_id'],
					'message' => 'Spouse missing FamilySearch ID',
				);
			}
			
			return $result;
		}
	}
	
	return $result;
}

/**
 * Find a matching child saint by FamilySearch ID or name+birthdate
 *
 * @param array $fs_child FamilySearch child data.
 * @return array|null Match result or null if not found.
 */
function wasmo_find_child_match( $fs_child ) {
	// Method 1: Match by FamilySearch ID (most reliable)
	if ( ! empty( $fs_child['familysearch_id'] ) ) {
		$saints = get_posts( array(
			'post_type'      => 'saint',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'   => 'familysearch_id',
					'value' => $fs_child['familysearch_id'],
				),
			),
		) );
		
		if ( ! empty( $saints ) ) {
			return array(
				'match_type' => 'familysearch_id',
				'saint_id'   => $saints[0]->ID,
				'saint_name' => $saints[0]->post_title,
				'confidence' => 'high',
			);
		}
	}
	
	// Method 2: Match by name + birth year
	if ( ! empty( $fs_child['name'] ) ) {
		$saints = get_posts( array(
			'post_type'      => 'saint',
			'posts_per_page' => 10,
			'post_status'    => 'publish',
			's'              => $fs_child['name'],
		) );
		
		foreach ( $saints as $saint ) {
			// Check name similarity
			if ( ! wasmo_names_match( $saint->post_title, $fs_child['name'] ) ) {
				continue;
			}
			
			// Check birth year if available
			$saint_birthdate = get_field( 'birthdate', $saint->ID );
			if ( $saint_birthdate && ! empty( $fs_child['birth_year'] ) ) {
				$saint_birth_year = (int) date( 'Y', strtotime( $saint_birthdate ) );
				$year_diff = abs( $saint_birth_year - $fs_child['birth_year'] );
				
				if ( $year_diff <= 1 ) {
					return array(
						'match_type' => 'name_birthdate',
						'saint_id'   => $saint->ID,
						'saint_name' => $saint->post_title,
						'confidence' => $year_diff === 0 ? 'high' : 'medium',
						'fs_id_missing' => empty( get_field( 'familysearch_id', $saint->ID ) ),
						'suggested_fs_id' => $fs_child['familysearch_id'] ?? null,
					);
				}
			} else {
				// Name match only (lower confidence)
				return array(
					'match_type' => 'name_only',
					'saint_id'   => $saint->ID,
					'saint_name' => $saint->post_title,
					'confidence' => 'low',
					'fs_id_missing' => empty( get_field( 'familysearch_id', $saint->ID ) ),
					'suggested_fs_id' => $fs_child['familysearch_id'] ?? null,
				);
			}
		}
	}
	
	return null;
}

/**
 * Check if two names match (accounting for variations)
 *
 * @param string $name1 First name.
 * @param string $name2 Second name.
 * @return bool True if names match.
 */
function wasmo_names_match( $name1, $name2 ) {
	// Normalize names
	$normalize = function( $name ) {
		$name = strtolower( trim( $name ) );
		$name = preg_replace( '/[^a-z\s]/', '', $name ); // Remove punctuation
		$name = preg_replace( '/\s+/', ' ', $name ); // Normalize whitespace
		return $name;
	};
	
	$n1 = $normalize( $name1 );
	$n2 = $normalize( $name2 );
	
	// Exact match
	if ( $n1 === $n2 ) {
		return true;
	}
	
	// One contains the other
	if ( strpos( $n1, $n2 ) !== false || strpos( $n2, $n1 ) !== false ) {
		return true;
	}
	
	// Compare individual name parts
	$parts1 = explode( ' ', $n1 );
	$parts2 = explode( ' ', $n2 );
	
	// Check if first and last names match
	if ( count( $parts1 ) >= 2 && count( $parts2 ) >= 2 ) {
		$first_match = $parts1[0] === $parts2[0];
		$last_match = end( $parts1 ) === end( $parts2 );
		
		if ( $first_match && $last_match ) {
			return true;
		}
	}
	
	// Similarity check (for typos)
	similar_text( $n1, $n2, $percent );
	return $percent >= 85;
}

// ============================================
// IMAGE SIDELOADING
// ============================================

/**
 * Sideload a FamilySearch portrait image for a saint
 *
 * @param int    $saint_id  The saint post ID.
 * @param string $image_url The image URL to sideload.
 * @param bool   $force     Whether to replace existing featured image.
 * @return int|WP_Error Attachment ID on success, WP_Error on failure.
 */
function wasmo_sideload_fs_portrait( $saint_id, $image_url, $force = false ) {
	// Check if saint already has featured image
	if ( ! $force && has_post_thumbnail( $saint_id ) ) {
		return new WP_Error( 'has_image', 'Saint already has a featured image. Use force=true to replace.' );
	}
	
	if ( empty( $image_url ) ) {
		return new WP_Error( 'no_url', 'No image URL provided.' );
	}
	
	// Include required files for media_sideload_image
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	
	// Generate a descriptive filename
	$saint_slug = sanitize_title( get_the_title( $saint_id ) );
	$fs_id = get_field( 'familysearch_id', $saint_id );
	$filename = $saint_slug . '-familysearch-portrait';
	if ( $fs_id ) {
		$filename .= '-' . strtolower( $fs_id );
	}
	
	// Get file extension from URL
	$ext = pathinfo( parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION );
	if ( ! $ext || ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ), true ) ) {
		$ext = 'jpg';
	}
	
	// Download image to temp file
	$tmp = download_url( $image_url );
	if ( is_wp_error( $tmp ) ) {
		return $tmp;
	}
	
	// Prepare file array
	$file_array = array(
		'name'     => $filename . '.' . $ext,
		'tmp_name' => $tmp,
	);
	
	// Sideload the image
	$attachment_id = media_handle_sideload( $file_array, $saint_id, get_the_title( $saint_id ) . ' - FamilySearch Portrait' );
	
	// Clean up temp file if still exists
	if ( file_exists( $tmp ) ) {
		@unlink( $tmp );
	}
	
	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}
	
	// Set as featured image
	set_post_thumbnail( $saint_id, $attachment_id );
	
	// Add metadata to track source
	update_post_meta( $attachment_id, '_wasmo_source', 'familysearch' );
	update_post_meta( $attachment_id, '_wasmo_fs_original_url', $image_url );
	
	return $attachment_id;
}

/**
 * AJAX: Sideload portrait image
 */
function wasmo_ajax_sideload_portrait() {
	check_ajax_referer( 'wasmo_fs_verify', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permission denied.' );
	}
	
	$saint_id = isset( $_POST['saint_id'] ) ? intval( $_POST['saint_id'] ) : 0;
	$image_url = isset( $_POST['image_url'] ) ? esc_url_raw( $_POST['image_url'] ) : '';
	$force = isset( $_POST['force'] ) && $_POST['force'] === 'true';
	
	if ( ! $saint_id || ! $image_url ) {
		wp_send_json_error( 'Invalid parameters.' );
	}
	
	$result = wasmo_sideload_fs_portrait( $saint_id, $image_url, $force );
	
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( $result->get_error_message() );
	}
	
	wp_send_json_success( array(
		'message'       => 'Portrait image sideloaded successfully.',
		'attachment_id' => $result,
		'thumbnail_url' => wp_get_attachment_image_url( $result, 'thumbnail' ),
	) );
}
add_action( 'wp_ajax_wasmo_sideload_portrait', 'wasmo_ajax_sideload_portrait' );

// ============================================
// BATCH PROCESSING
// ============================================

/**
 * AJAX: Start batch verification
 */
function wasmo_ajax_batch_verify_start() {
	check_ajax_referer( 'wasmo_fs_verify', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permission denied.' );
	}
	
	$saint_ids = isset( $_POST['saint_ids'] ) ? array_map( 'intval', $_POST['saint_ids'] ) : array();
	
	if ( empty( $saint_ids ) ) {
		wp_send_json_error( 'No saints specified.' );
	}
	
	// Store batch in transient
	$batch_id = 'wasmo_fs_batch_' . wp_generate_password( 8, false );
	set_transient( $batch_id, array(
		'saint_ids' => $saint_ids,
		'current'   => 0,
		'results'   => array(),
		'started'   => current_time( 'mysql' ),
	), HOUR_IN_SECONDS );
	
	wp_send_json_success( array(
		'batch_id' => $batch_id,
		'total'    => count( $saint_ids ),
	) );
}
add_action( 'wp_ajax_wasmo_batch_verify_start', 'wasmo_ajax_batch_verify_start' );

/**
 * AJAX: Process next item in batch
 */
function wasmo_ajax_batch_verify_next() {
	check_ajax_referer( 'wasmo_fs_verify', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permission denied.' );
	}
	
	$batch_id = isset( $_POST['batch_id'] ) ? sanitize_text_field( $_POST['batch_id'] ) : '';
	
	if ( empty( $batch_id ) ) {
		wp_send_json_error( 'Invalid batch ID.' );
	}
	
	$batch = get_transient( $batch_id );
	
	if ( ! $batch ) {
		wp_send_json_error( 'Batch not found or expired.' );
	}
	
	$current = $batch['current'];
	$total = count( $batch['saint_ids'] );
	
	if ( $current >= $total ) {
		// Batch complete
		delete_transient( $batch_id );
		wp_send_json_success( array(
			'complete' => true,
			'results'  => $batch['results'],
		) );
	}
	
	$saint_id = $batch['saint_ids'][ $current ];
	$fs_id = get_field( 'familysearch_id', $saint_id );
	
	// Fetch and compare
	$fs_data = wasmo_fetch_fs_person( $fs_id, true );
	$result = array(
		'saint_id'   => $saint_id,
		'saint_name' => get_the_title( $saint_id ),
		'fs_id'      => $fs_id,
	);
	
	if ( is_wp_error( $fs_data ) ) {
		$result['error'] = $fs_data->get_error_message();
	} else {
		$result['comparison'] = wasmo_compare_saint_full( $saint_id, $fs_data );
		$result['fs_data'] = $fs_data;
		
		// Auto-update verified timestamp (data was fetched)
		update_field( 'familysearch_verified', current_time( 'mysql' ), $saint_id );
	}
	
	// Update batch
	$batch['current']++;
	$batch['results'][] = $result;
	set_transient( $batch_id, $batch, HOUR_IN_SECONDS );
	
	wp_send_json_success( array(
		'complete'  => false,
		'current'   => $batch['current'],
		'total'     => $total,
		'result'    => $result,
	) );
}
add_action( 'wp_ajax_wasmo_batch_verify_next', 'wasmo_ajax_batch_verify_next' );

/**
 * Update child's FamilySearch ID in mother's marriage record
 *
 * @param int    $mother_id   Mother's saint post ID.
 * @param int    $marriage_idx Marriage index in repeater.
 * @param int    $child_idx   Child index in children repeater.
 * @param string $fs_id       FamilySearch ID to set.
 * @return bool Success.
 */
function wasmo_update_child_fs_id( $mother_id, $marriage_idx, $child_idx, $fs_id ) {
	$field_key = "marriages_{$marriage_idx}_children_{$child_idx}_child_familysearch_id";
	return update_post_meta( $mother_id, $field_key, sanitize_text_field( $fs_id ) );
}

/**
 * Enqueue admin scripts for FamilySearch verification page
 */
function wasmo_enqueue_fs_verify_scripts( $hook ) {
	if ( 'saint_page_familysearch-verify' !== $hook ) {
		return;
	}
	
	wp_enqueue_script( 'jquery' );
	
	// Minimal script for copying textarea content
	$script = "
	jQuery(document).ready(function($) {
		// Auto-select textarea content on focus for easy copying
		$('textarea[readonly]').on('focus', function() {
			$(this).select();
		});
	});
	";
	
	wp_add_inline_script( 'jquery', $script );
}
add_action( 'admin_enqueue_scripts', 'wasmo_enqueue_fs_verify_scripts' );
