<?php
/**
 * Duplicate Saints Admin Tool
 * 
 * Admin page for finding and managing duplicate saints, with focus on
 * duplicate wives and extraneous marriage relationships.
 *
 * @package wasmo
 */

// ============================================
// ADMIN MENU & PAGE
// ============================================

/**
 * Add admin menu page for duplicate saints
 */
function wasmo_add_duplicates_page() {
	add_submenu_page(
		'edit.php?post_type=saint',
		'Duplicate Saints',
		'Duplicate Saints',
		'manage_options',
		'saints-duplicates',
		'wasmo_render_duplicates_page'
	);
}
add_action( 'admin_menu', 'wasmo_add_duplicates_page' );

/**
 * Render the duplicates admin page
 */
function wasmo_render_duplicates_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}

	// Handle actions
	if ( isset( $_POST['wasmo_scan_duplicates'] ) && check_admin_referer( 'wasmo_scan_duplicates_nonce' ) ) {
		// Clear cache and trigger scan
		delete_transient( 'wasmo_duplicates_scan_results' );
		$duplicates = wasmo_find_all_duplicates();
		set_transient( 'wasmo_duplicates_scan_results', $duplicates, HOUR_IN_SECONDS );
		$scan_complete = true;
	} elseif ( isset( $_POST['wasmo_clear_ignored'] ) && check_admin_referer( 'wasmo_clear_ignored_nonce' ) ) {
		wasmo_clear_ignored_duplicates();
		$ignored_cleared = true;
	} else {
		// Load cached results or scan
		$duplicates = get_transient( 'wasmo_duplicates_scan_results' );
		if ( false === $duplicates ) {
			$duplicates = wasmo_find_all_duplicates();
			set_transient( 'wasmo_duplicates_scan_results', $duplicates, HOUR_IN_SECONDS );
		}
		$scan_complete = false;
		$ignored_cleared = false;
	}

	// Get ignored pairs
	$ignored = get_option( 'wasmo_ignored_duplicates', array() );
	$ignored_count = count( $ignored );

	// Group duplicates by type
	$grouped = array(
		'all' => $duplicates,
		'fs_id' => array_filter( $duplicates, function( $dup ) { return $dup['match_type'] === 'fs_id'; } ),
		'name_dates' => array_filter( $duplicates, function( $dup ) { 
			return in_array( $dup['match_type'], array( 'name_birthdate', 'name_deathdate', 'name_birthdate_deathdate' ) ); 
		} ),
		'wives' => array_filter( $duplicates, function( $dup ) { 
			return in_array( $dup['match_type'], array( 'wife_same_husband', 'wife_different_husbands' ) ); 
		} ),
		'extraneous' => array_filter( $duplicates, function( $dup ) { 
			return $dup['match_type'] === 'extraneous_wife'; 
		} ),
	);

	$stats = array(
		'total' => count( $duplicates ),
		'very_high' => count( array_filter( $duplicates, function( $d ) { return $d['confidence'] === 'very_high'; } ) ),
		'high' => count( array_filter( $duplicates, function( $d ) { return $d['confidence'] === 'high'; } ) ),
		'medium' => count( array_filter( $duplicates, function( $d ) { return $d['confidence'] === 'medium'; } ) ),
		'low' => count( array_filter( $duplicates, function( $d ) { return $d['confidence'] === 'low'; } ) ),
	);

	?>
	<div class="wrap">
		<h1>Duplicate Saints</h1>
		
		<?php if ( isset( $scan_complete ) && $scan_complete ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>Scan completed. Found <?php echo esc_html( count( $duplicates ) ); ?> potential duplicates.</p>
			</div>
		<?php endif; ?>
		
		<?php if ( isset( $ignored_cleared ) && $ignored_cleared ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>Ignored duplicates cleared.</p>
			</div>
		<?php endif; ?>

		<div class="wasmo-duplicates-header" style="margin: 20px 0;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
				<div>
					<p class="description">Find and resolve duplicate saints, with special focus on duplicate wives and extraneous marriage relationships.</p>
				</div>
				<div>
					<form method="post" style="display: inline-block; margin-right: 10px;">
						<?php wp_nonce_field( 'wasmo_scan_duplicates_nonce' ); ?>
						<input type="submit" name="wasmo_scan_duplicates" class="button button-primary" value="Scan for Duplicates" />
					</form>
					<?php if ( $ignored_count > 0 ) : ?>
						<form method="post" style="display: inline-block;">
							<?php wp_nonce_field( 'wasmo_clear_ignored_nonce' ); ?>
							<input type="submit" name="wasmo_clear_ignored" class="button" value="Clear Ignored (<?php echo esc_html( $ignored_count ); ?>)" onclick="return confirm('Clear all ignored duplicates?');" />
						</form>
					<?php endif; ?>
				</div>
			</div>

			<div class="wasmo-duplicates-stats" style="display: flex; gap: 20px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
				<div><strong>Total:</strong> <?php echo esc_html( $stats['total'] ); ?></div>
				<div><strong>Very High:</strong> <span style="color: #d63638;"><?php echo esc_html( $stats['very_high'] ); ?></span></div>
				<div><strong>High:</strong> <span style="color: #d63638;"><?php echo esc_html( $stats['high'] ); ?></span></div>
				<div><strong>Medium:</strong> <span style="color: #f0b849;"><?php echo esc_html( $stats['medium'] ); ?></span></div>
				<div><strong>Low:</strong> <span style="color: #72aee6;"><?php echo esc_html( $stats['low'] ); ?></span></div>
			</div>
		</div>

		<div class="wasmo-duplicates-tabs" style="margin: 20px 0; border-bottom: 1px solid #ccc;">
			<a href="#" class="nav-tab nav-tab-active" data-tab="all">All Duplicates (<?php echo esc_html( count( $grouped['all'] ) ); ?>)</a>
			<a href="#" class="nav-tab" data-tab="fs_id">FS ID Duplicates (<?php echo esc_html( count( $grouped['fs_id'] ) ); ?>)</a>
			<a href="#" class="nav-tab" data-tab="name_dates">Name + Date Matches (<?php echo esc_html( count( $grouped['name_dates'] ) ); ?>)</a>
			<a href="#" class="nav-tab" data-tab="wives">Duplicate Wives (<?php echo esc_html( count( $grouped['wives'] ) ); ?>)</a>
			<a href="#" class="nav-tab" data-tab="extraneous">Extraneous Wives (<?php echo esc_html( count( $grouped['extraneous'] ) ); ?>)</a>
		</div>

		<div id="wasmo-duplicates-content">
			<?php if ( empty( $duplicates ) ) : ?>
				<div class="notice notice-info">
					<p>No duplicates found. Click "Scan for Duplicates" to run a new scan.</p>
				</div>
			<?php else : ?>
				<?php foreach ( $grouped as $type => $items ) : ?>
					<div class="wasmo-duplicates-tab-content" data-tab="<?php echo esc_attr( $type ); ?>" style="<?php echo $type !== 'all' ? 'display: none;' : ''; ?>">
						<?php if ( empty( $items ) ) : ?>
							<p>No duplicates in this category.</p>
						<?php else : ?>
							<?php foreach ( $items as $dup ) : ?>
								<?php wasmo_render_duplicate_card( $dup ); ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<!-- Merge Modal -->
	<div id="wasmo-merge-modal" style="display: none;">
		<div class="wasmo-modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000;">
			<div class="wasmo-modal-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 4px; max-width: 800px; max-height: 90vh; overflow-y: auto; z-index: 100001;">
				<h2>Merge Duplicate Saints</h2>
				<p><strong>Warning:</strong> This action cannot be undone. The source saint will be deleted after merging.</p>
				<div id="wasmo-merge-saints-comparison"></div>
				<div style="margin-top: 20px;">
					<label><strong>Select Primary Saint (data will be preserved):</strong></label><br>
					<input type="radio" name="wasmo_merge_primary" value="" id="wasmo-merge-primary-1" checked>
					<label for="wasmo-merge-primary-1" id="wasmo-merge-label-1"></label><br>
					<input type="radio" name="wasmo_merge_primary" value="" id="wasmo-merge-primary-2">
					<label for="wasmo-merge-primary-2" id="wasmo-merge-label-2"></label>
				</div>
				<div style="margin-top: 20px;">
					<button type="button" class="button button-primary" id="wasmo-merge-confirm">Confirm Merge</button>
					<button type="button" class="button" id="wasmo-merge-cancel">Cancel</button>
				</div>
			</div>
		</div>
	</div>

	<style>
		.wasmo-duplicate-card {
			border: 1px solid #ccc;
			border-radius: 4px;
			padding: 20px;
			margin-bottom: 20px;
			background: white;
		}
		.wasmo-duplicate-card-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 15px;
			padding-bottom: 15px;
			border-bottom: 1px solid #eee;
		}
		.wasmo-confidence-badge {
			display: inline-block;
			padding: 4px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: bold;
			text-transform: uppercase;
		}
		.wasmo-confidence-very_high { background: #d63638; color: white; }
		.wasmo-confidence-high { background: #d63638; color: white; }
		.wasmo-confidence-medium { background: #f0b849; color: #000; }
		.wasmo-confidence-low { background: #72aee6; color: white; }
		.wasmo-duplicate-comparison {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
			margin: 15px 0;
		}
		.wasmo-saint-info {
			padding: 15px;
			background: #f9f9f9;
			border-radius: 4px;
		}
		.wasmo-saint-info h3 {
			margin-top: 0;
		}
		.wasmo-saint-info img {
			max-width: 100px;
			height: auto;
			border-radius: 4px;
		}
		.wasmo-difference {
			color: #d63638;
			font-weight: bold;
		}
		.wasmo-duplicate-actions {
			margin-top: 15px;
			padding-top: 15px;
			border-top: 1px solid #eee;
		}
		.wasmo-extraneous-issues {
			background: #fff3cd;
			border-left: 4px solid #f0b849;
			padding: 10px;
			margin: 10px 0;
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		// Tab switching
		$('.wasmo-duplicates-tabs .nav-tab').on('click', function(e) {
			e.preventDefault();
			var tab = $(this).data('tab');
			$('.wasmo-duplicates-tabs .nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');
			$('.wasmo-duplicates-tab-content').hide();
			$('.wasmo-duplicates-tab-content[data-tab="' + tab + '"]').show();
		});

		// Ignore duplicate
		$('.wasmo-ignore-duplicate').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var saint1 = $btn.data('saint1');
			var saint2 = $btn.data('saint2');
			var matchType = $btn.data('match-type');

			if (!confirm('Mark this pair as not duplicates? They will be hidden from future scans.')) {
				return;
			}

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wasmo_ignore_duplicate',
					saint1_id: saint1,
					saint2_id: saint2,
					match_type: matchType,
					nonce: '<?php echo wp_create_nonce( 'wasmo_ignore_duplicate' ); ?>'
				},
				success: function(response) {
					if (response.success) {
						$btn.closest('.wasmo-duplicate-card').fadeOut();
					} else {
						alert('Error: ' + (response.data || 'Unknown error'));
					}
				},
				error: function() {
					alert('Error ignoring duplicate.');
				}
			});
		});

		// Merge duplicate
		$('.wasmo-merge-duplicate').on('click', function(e) {
			e.preventDefault();
			var saint1 = $(this).data('saint1');
			var saint2 = $(this).data('saint2');

			// Load comparison
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wasmo_get_duplicate_details',
					saint1_id: saint1,
					saint2_id: saint2,
					nonce: '<?php echo wp_create_nonce( 'wasmo_get_duplicate_details' ); ?>'
				},
				success: function(response) {
					if (response.success) {
						$('#wasmo-merge-saints-comparison').html(response.data.comparison);
						$('#wasmo-merge-primary-1').val(saint1);
						$('#wasmo-merge-primary-2').val(saint2);
						$('#wasmo-merge-label-1').text(response.data.saint1.name + ' (ID: ' + saint1 + ')');
						$('#wasmo-merge-label-2').text(response.data.saint2.name + ' (ID: ' + saint2 + ')');
						$('#wasmo-merge-modal').show();
					} else {
						alert('Error: ' + (response.data || 'Unknown error'));
					}
				}
			});
		});

		// Confirm merge
		$('#wasmo-merge-confirm').on('click', function() {
			var primaryId = $('input[name="wasmo_merge_primary"]:checked').val();
			var secondaryId = primaryId == $('#wasmo-merge-primary-1').val() 
				? $('#wasmo-merge-primary-2').val() 
				: $('#wasmo-merge-primary-1').val();

			if (!primaryId || !secondaryId) {
				alert('Please select a primary saint.');
				return;
			}

			if (!confirm('Are you sure you want to merge these saints? This cannot be undone.')) {
				return;
			}

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wasmo_merge_duplicates',
					primary_id: primaryId,
					merge_from_id: secondaryId,
					nonce: '<?php echo wp_create_nonce( 'wasmo_merge_duplicates' ); ?>'
				},
				success: function(response) {
					if (response.success) {
						alert('Saints merged successfully!');
						location.reload();
					} else {
						alert('Error: ' + (response.data || 'Unknown error'));
					}
				},
				error: function() {
					alert('Error merging saints.');
				}
			});
		});

		// Cancel merge
		$('#wasmo-merge-cancel, .wasmo-modal-overlay').on('click', function(e) {
			if (e.target === this) {
				$('#wasmo-merge-modal').hide();
			}
		});
	});
	</script>
	<?php
}

/**
 * Render a duplicate card
 */
function wasmo_render_duplicate_card( $dup ) {
	$saint1 = $dup['saint1'];
	$saint2 = $dup['saint2'];
	$confidence_class = 'wasmo-confidence-' . $dup['confidence'];
	?>
	<div class="wasmo-duplicate-card">
		<div class="wasmo-duplicate-card-header">
			<div>
				<span class="wasmo-confidence-badge <?php echo esc_attr( $confidence_class ); ?>">
					<?php echo esc_html( ucfirst( str_replace( '_', ' ', $dup['confidence'] ) ) ); ?>
				</span>
				<span style="margin-left: 10px; color: #666;">
					<?php echo esc_html( ucfirst( str_replace( '_', ' ', $dup['match_type'] ) ) ); ?>
					<?php if ( isset( $dup['similarity_score'] ) ) : ?>
						(<?php echo esc_html( round( $dup['similarity_score'], 1 ) ); ?>% similar)
					<?php endif; ?>
				</span>
			</div>
		</div>

		<?php if ( ! empty( $dup['extraneous_wife_issues'] ) ) : ?>
			<div class="wasmo-extraneous-issues">
				<strong>Issues Found:</strong>
				<ul>
					<?php foreach ( $dup['extraneous_wife_issues'] as $issue ) : ?>
						<li><?php echo esc_html( $issue ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="wasmo-duplicate-comparison">
			<div class="wasmo-saint-info">
				<h3>
					<a href="<?php echo esc_url( $saint1['edit_url'] ); ?>" target="_blank">
						<?php echo esc_html( $saint1['name'] ); ?>
					</a>
					(ID: <?php echo esc_html( $saint1['id'] ); ?>)
				</h3>
				<?php if ( $saint1['has_portrait'] ) : ?>
					<img src="<?php echo esc_url( $saint1['portrait_url'] ); ?>" alt="<?php echo esc_attr( $saint1['name'] ); ?>" />
				<?php endif; ?>
				<p><strong>Birthdate:</strong> 
					<?php echo $saint1['birthdate'] ? esc_html( $saint1['birthdate'] ) : '<span class="wasmo-difference">Missing</span>'; ?>
					<?php if ( ! empty( $dup['date_differences']['birthdate'] ) ) : ?>
						<span class="wasmo-difference">(Differs)</span>
					<?php endif; ?>
				</p>
				<p><strong>Deathdate:</strong> 
					<?php echo $saint1['deathdate'] ? esc_html( $saint1['deathdate'] ) : '<span class="wasmo-difference">Missing</span>'; ?>
					<?php if ( ! empty( $dup['date_differences']['deathdate'] ) ) : ?>
						<span class="wasmo-difference">(Differs)</span>
					<?php endif; ?>
				</p>
				<p><strong>FS ID:</strong> 
					<?php echo $saint1['familysearch_id'] ? esc_html( $saint1['familysearch_id'] ) : '<span class="wasmo-difference">Missing</span>'; ?>
				</p>
				<p><strong>Gender:</strong> <?php echo esc_html( $saint1['gender'] ); ?></p>
				<p><strong>Roles:</strong> <?php echo esc_html( implode( ', ', $saint1['roles'] ) ); ?></p>
				<p><strong>Marriages:</strong> <?php echo esc_html( $saint1['marriages_count'] ); ?></p>
			</div>

			<div class="wasmo-saint-info">
				<h3>
					<a href="<?php echo esc_url( $saint2['edit_url'] ); ?>" target="_blank">
						<?php echo esc_html( $saint2['name'] ); ?>
					</a>
					(ID: <?php echo esc_html( $saint2['id'] ); ?>)
				</h3>
				<?php if ( $saint2['has_portrait'] ) : ?>
					<img src="<?php echo esc_url( $saint2['portrait_url'] ); ?>" alt="<?php echo esc_attr( $saint2['name'] ); ?>" />
				<?php endif; ?>
				<p><strong>Birthdate:</strong> 
					<?php echo $saint2['birthdate'] ? esc_html( $saint2['birthdate'] ) : '<span class="wasmo-difference">Missing</span>'; ?>
					<?php if ( ! empty( $dup['date_differences']['birthdate'] ) ) : ?>
						<span class="wasmo-difference">(Differs)</span>
					<?php endif; ?>
				</p>
				<p><strong>Deathdate:</strong> 
					<?php echo $saint2['deathdate'] ? esc_html( $saint2['deathdate'] ) : '<span class="wasmo-difference">Missing</span>'; ?>
					<?php if ( ! empty( $dup['date_differences']['deathdate'] ) ) : ?>
						<span class="wasmo-difference">(Differs)</span>
					<?php endif; ?>
				</p>
				<p><strong>FS ID:</strong> 
					<?php echo $saint2['familysearch_id'] ? esc_html( $saint2['familysearch_id'] ) : '<span class="wasmo-difference">Missing</span>'; ?>
				</p>
				<p><strong>Gender:</strong> <?php echo esc_html( $saint2['gender'] ); ?></p>
				<p><strong>Roles:</strong> <?php echo esc_html( implode( ', ', $saint2['roles'] ) ); ?></p>
				<p><strong>Marriages:</strong> <?php echo esc_html( $saint2['marriages_count'] ); ?></p>
			</div>
		</div>

		<div class="wasmo-duplicate-actions">
			<button type="button" class="button wasmo-ignore-duplicate" 
				data-saint1="<?php echo esc_attr( $saint1['id'] ); ?>"
				data-saint2="<?php echo esc_attr( $saint2['id'] ); ?>"
				data-match-type="<?php echo esc_attr( $dup['match_type'] ); ?>">
				Ignore
			</button>
			<button type="button" class="button button-primary wasmo-merge-duplicate"
				data-saint1="<?php echo esc_attr( $saint1['id'] ); ?>"
				data-saint2="<?php echo esc_attr( $saint2['id'] ); ?>">
				Merge
			</button>
			<a href="<?php echo esc_url( $saint1['edit_url'] ); ?>" class="button" target="_blank">View Saint 1</a>
			<a href="<?php echo esc_url( $saint2['edit_url'] ); ?>" class="button" target="_blank">View Saint 2</a>
		</div>
	</div>
	<?php
}

// ============================================
// DETECTION FUNCTIONS
// ============================================

/**
 * Main function to find all duplicates
 */
function wasmo_find_all_duplicates() {
	$duplicates = array();
	$ignored = wasmo_get_ignored_duplicates();

	// 1. FS ID duplicates
	$fs_duplicates = wasmo_find_duplicates_by_fs_id();
	foreach ( $fs_duplicates as $dup ) {
		if ( ! wasmo_is_duplicate_ignored( $dup['saint1_id'], $dup['saint2_id'], $ignored ) ) {
			$duplicates[] = $dup;
		}
	}

	// 2. Name + date matches
	$name_duplicates = wasmo_find_duplicates_by_name_dates();
	foreach ( $name_duplicates as $dup ) {
		if ( ! wasmo_is_duplicate_ignored( $dup['saint1_id'], $dup['saint2_id'], $ignored ) ) {
			$duplicates[] = $dup;
		}
	}

	// 3. Duplicate wives
	$wife_duplicates = wasmo_find_duplicate_wives();
	foreach ( $wife_duplicates as $dup ) {
		if ( ! wasmo_is_duplicate_ignored( $dup['saint1_id'], $dup['saint2_id'], $ignored ) ) {
			$duplicates[] = $dup;
		}
	}

	// 4. Extraneous wives
	$extraneous = wasmo_find_extraneous_wives();
	foreach ( $extraneous as $dup ) {
		if ( ! wasmo_is_duplicate_ignored( $dup['saint1_id'], $dup['saint2_id'], $ignored ) ) {
			$duplicates[] = $dup;
		}
	}

	// Remove exact duplicates (same pair) and filter out deleted saints
	$unique = array();
	$seen = array();
	foreach ( $duplicates as $dup ) {
		// Skip if either saint no longer exists or is deleted
		$saint1_exists = get_post( $dup['saint1_id'] ) && get_post_status( $dup['saint1_id'] ) !== false;
		$saint2_exists = get_post( $dup['saint2_id'] ) && get_post_status( $dup['saint2_id'] ) !== false;
		
		if ( ! $saint1_exists || ! $saint2_exists ) {
			continue; // Skip pairs with deleted saints
		}
		
		$key = min( $dup['saint1_id'], $dup['saint2_id'] ) . '-' . max( $dup['saint1_id'], $dup['saint2_id'] );
		if ( ! isset( $seen[ $key ] ) ) {
			$seen[ $key ] = true;
			$unique[] = $dup;
		}
	}

	return $unique;
}

/**
 * Find duplicates by FamilySearch ID
 */
function wasmo_find_duplicates_by_fs_id() {
	global $wpdb;
	
	$duplicates = array();
	
	// Find FS IDs that appear more than once
	$query = $wpdb->prepare(
		"SELECT pm.meta_value as fs_id, COUNT(*) as count
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
		WHERE pm.meta_key = %s
		AND pm.meta_value != ''
		AND p.post_type = 'saint'
		AND p.post_status = 'publish'
		GROUP BY pm.meta_value
		HAVING COUNT(*) > 1",
		'familysearch_id'
	);
	
	$duplicate_fs_ids = $wpdb->get_results( $query );
	
	foreach ( $duplicate_fs_ids as $dup ) {
		$saints = get_posts( array(
			'post_type'      => 'saint',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'   => 'familysearch_id',
					'value' => $dup->fs_id,
				),
			),
		) );
		
		// Create pairs
		for ( $i = 0; $i < count( $saints ); $i++ ) {
			for ( $j = $i + 1; $j < count( $saints ); $j++ ) {
				$pair = wasmo_build_duplicate_pair(
					$saints[ $i ]->ID,
					$saints[ $j ]->ID,
					'fs_id',
					'very_high',
					100
				);
				if ( $pair ) {
					$duplicates[] = $pair;
				}
			}
		}
	}
	
	return $duplicates;
}

/**
 * Find duplicates by name and dates
 */
function wasmo_find_duplicates_by_name_dates() {
	$duplicates = array();
	
	// Get all saints
	$saints = get_posts( array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	) );
	
	// Compare each pair
	for ( $i = 0; $i < count( $saints ); $i++ ) {
		for ( $j = $i + 1; $j < count( $saints ); $j++ ) {
			$saint1 = $saints[ $i ];
			$saint2 = $saints[ $j ];
			
			// Skip if both have FS IDs and they're different
			$fs1 = get_field( 'familysearch_id', $saint1->ID );
			$fs2 = get_field( 'familysearch_id', $saint2->ID );
			if ( $fs1 && $fs2 && $fs1 !== $fs2 ) {
				continue;
			}
			
			// Check name similarity
			$similarity = 0;
			if ( ! function_exists( 'wasmo_names_match' ) ) {
				// Fallback if function doesn't exist
				similar_text( strtolower( $saint1->post_title ), strtolower( $saint2->post_title ), $similarity );
				if ( $similarity < 85 ) {
					continue;
				}
			} else {
				if ( ! wasmo_names_match( $saint1->post_title, $saint2->post_title ) ) {
					continue;
				}
				if ( function_exists( 'wasmo_normalize_name_for_matching' ) ) {
					similar_text( 
						wasmo_normalize_name_for_matching( $saint1->post_title ),
						wasmo_normalize_name_for_matching( $saint2->post_title ),
						$similarity
					);
				} else {
					similar_text( strtolower( $saint1->post_title ), strtolower( $saint2->post_title ), $similarity );
				}
			}
			
			// Get dates
			$birth1 = get_field( 'birthdate', $saint1->ID );
			$death1 = get_field( 'deathdate', $saint1->ID );
			$birth2 = get_field( 'birthdate', $saint2->ID );
			$death2 = get_field( 'deathdate', $saint2->ID );
			
			$birth_match = false;
			$death_match = false;
			
			// Check birthdate match (within ±1 year)
			if ( $birth1 && $birth2 ) {
				$year1 = (int) date( 'Y', strtotime( $birth1 ) );
				$year2 = (int) date( 'Y', strtotime( $birth2 ) );
				$birth_match = abs( $year1 - $year2 ) <= 1;
			}
			
			// Check deathdate match (within ±1 year)
			if ( $death1 && $death2 ) {
				$year1 = (int) date( 'Y', strtotime( $death1 ) );
				$year2 = (int) date( 'Y', strtotime( $death2 ) );
				$death_match = abs( $year1 - $year2 ) <= 1;
			}
			
			// Determine match type and confidence
			$match_type = null;
			$confidence = 'low';
			
			if ( $birth_match && $death_match ) {
				$match_type = 'name_birthdate_deathdate';
				$confidence = 'high';
			} elseif ( $birth_match ) {
				$match_type = 'name_birthdate';
				$confidence = 'medium';
			} elseif ( $death_match ) {
				$match_type = 'name_deathdate';
				$confidence = 'medium';
			} else {
				// Name only match
				if ( $similarity >= 90 ) {
					$match_type = 'name_only';
					$confidence = 'low';
				} else {
					continue; // Not similar enough
				}
			}
			
			if ( $match_type ) {
				$pair = wasmo_build_duplicate_pair(
					$saint1->ID,
					$saint2->ID,
					$match_type,
					$confidence,
					$similarity,
					array(
						'birthdate' => $birth_match ? null : array(
							'saint1' => $birth1,
							'saint2' => $birth2,
						),
						'deathdate' => $death_match ? null : array(
							'saint1' => $death1,
							'saint2' => $death2,
						),
					)
				);
				if ( $pair ) {
					$duplicates[] = $pair;
				}
			}
		}
	}
	
	return $duplicates;
}

/**
 * Find duplicate wives
 */
function wasmo_find_duplicate_wives() {
	$duplicates = array();
	
	// Get all wives
	$wives = get_posts( array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'saint-role',
				'field'    => 'slug',
				'terms'    => 'wife',
			),
		),
	) );
	
	// Compare each pair of wives
	for ( $i = 0; $i < count( $wives ); $i++ ) {
		for ( $j = $i + 1; $j < count( $wives ); $j++ ) {
			$wife1 = $wives[ $i ];
			$wife2 = $wives[ $j ];
			
			// Check name similarity
			$name_match = false;
			$similarity = 0;
			if ( function_exists( 'wasmo_names_match' ) ) {
				$name_match = wasmo_names_match( $wife1->post_title, $wife2->post_title );
				if ( $name_match && function_exists( 'wasmo_normalize_name_for_matching' ) ) {
					similar_text( 
						wasmo_normalize_name_for_matching( $wife1->post_title ),
						wasmo_normalize_name_for_matching( $wife2->post_title ),
						$similarity
					);
				} else {
					similar_text( strtolower( $wife1->post_title ), strtolower( $wife2->post_title ), $similarity );
				}
			} else {
				similar_text( strtolower( $wife1->post_title ), strtolower( $wife2->post_title ), $similarity );
				$name_match = $similarity >= 85;
			}
			
			if ( ! $name_match ) {
				continue;
			}
			
			// Get marriages
			$marriages1 = get_field( 'marriages', $wife1->ID ) ?: array();
			$marriages2 = get_field( 'marriages', $wife2->ID ) ?: array();
			
			// Get husbands
			$husbands1 = array();
			$husbands2 = array();
			
			foreach ( $marriages1 as $m ) {
				$spouse_id = is_array( $m['spouse'] ) ? ( $m['spouse'][0] ?? null ) : $m['spouse'];
				if ( $spouse_id ) {
					$husbands1[] = $spouse_id;
				}
			}
			
			foreach ( $marriages2 as $m ) {
				$spouse_id = is_array( $m['spouse'] ) ? ( $m['spouse'][0] ?? null ) : $m['spouse'];
				if ( $spouse_id ) {
					$husbands2[] = $spouse_id;
				}
			}
			
			// Check if linked to same husband
			$same_husband = ! empty( array_intersect( $husbands1, $husbands2 ) );
			
			// Check FS IDs
			$fs1 = get_field( 'familysearch_id', $wife1->ID );
			$fs2 = get_field( 'familysearch_id', $wife2->ID );
			$same_fs_id = $fs1 && $fs2 && $fs1 === $fs2;
			
			if ( $same_husband || $same_fs_id ) {
				$match_type = $same_husband ? 'wife_same_husband' : 'wife_different_husbands';
				$confidence = $same_fs_id ? 'very_high' : 'high';
				
				$pair = wasmo_build_duplicate_pair(
					$wife1->ID,
					$wife2->ID,
					$match_type,
					$confidence,
					$similarity ?? 85
				);
				if ( $pair ) {
					$duplicates[] = $pair;
				}
			}
		}
	}
	
	return $duplicates;
}

/**
 * Find extraneous wives
 */
function wasmo_find_extraneous_wives() {
	$issues = array();
	
	// Get all wives
	$wives = get_posts( array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'saint-role',
				'field'    => 'slug',
				'terms'    => 'wife',
			),
		),
	) );
	
	foreach ( $wives as $wife ) {
		$marriages = get_field( 'marriages', $wife->ID ) ?: array();
		$issues_found = array();
		
		foreach ( $marriages as $marriage ) {
			$spouse_id = is_array( $marriage['spouse'] ) ? ( $marriage['spouse'][0] ?? null ) : $marriage['spouse'];
			$spouse_fs_id = $marriage['spouse_familysearch_id'] ?? '';
			
			// Issue: Marriage has no spouse linked
			if ( ! $spouse_id ) {
				$issues_found[] = 'Marriage entry has no spouse linked';
				continue;
			}
			
			// Issue: FS ID mismatch
			$husband_fs_id = get_field( 'familysearch_id', $spouse_id );
			if ( $spouse_fs_id && $husband_fs_id && $spouse_fs_id !== $husband_fs_id ) {
				$issues_found[] = sprintf(
					'Spouse FS ID (%s) does not match husband FS ID (%s)',
					$spouse_fs_id,
					$husband_fs_id
				);
			}
			
			// Issue: Reverse lookup doesn't find wife
			$husband_marriages = wasmo_get_all_marriage_data( $spouse_id );
			$found_in_reverse = false;
			foreach ( $husband_marriages as $hm ) {
				$wife_id_in_marriage = is_array( $hm['spouse'] ) ? ( $hm['spouse'][0] ?? null ) : $hm['spouse'];
				if ( $wife_id_in_marriage == $wife->ID ) {
					$found_in_reverse = true;
					break;
				}
			}
			
			if ( ! $found_in_reverse ) {
				$issues_found[] = 'Wife not found in husband\'s reverse lookup';
			}
		}
		
		if ( ! empty( $issues_found ) ) {
			// Create an issue entry for each problematic marriage
			// For now, we'll create a single entry per wife with all issues
			$wife_data = wasmo_get_saint_data_for_duplicate( $wife->ID );
			if ( $wife_data ) { // Only add if wife still exists
				$issues[] = array(
					'saint1_id' => $wife->ID,
					'saint2_id' => $wife->ID, // Same saint, but we need the structure
					'saint1' => $wife_data,
					'saint2' => $wife_data,
					'match_type' => 'extraneous_wife',
					'confidence' => 'high',
					'similarity_score' => 100,
					'extraneous_wife_issues' => $issues_found,
				);
			}
		}
	}
	
	return $issues;
}

/**
 * Build a duplicate pair structure
 */
function wasmo_build_duplicate_pair( $saint1_id, $saint2_id, $match_type, $confidence, $similarity_score, $date_differences = array() ) {
	$saint1_data = wasmo_get_saint_data_for_duplicate( $saint1_id );
	$saint2_data = wasmo_get_saint_data_for_duplicate( $saint2_id );
	
	// Return null if either saint doesn't exist (will be filtered out)
	if ( ! $saint1_data || ! $saint2_data ) {
		return null;
	}
	
	return array(
		'saint1_id' => $saint1_id,
		'saint2_id' => $saint2_id,
		'saint1' => $saint1_data,
		'saint2' => $saint2_data,
		'match_type' => $match_type,
		'confidence' => $confidence,
		'similarity_score' => $similarity_score,
		'date_differences' => $date_differences,
		'extraneous_wife_issues' => array(),
	);
}

/**
 * Get saint data for duplicate comparison
 */
function wasmo_get_saint_data_for_duplicate( $saint_id ) {
	$saint = get_post( $saint_id );
	if ( ! $saint || get_post_status( $saint_id ) === false || get_post_status( $saint_id ) === 'trash' ) {
		return null;
	}
	
	$roles = wp_get_post_terms( $saint_id, 'saint-role', array( 'fields' => 'names' ) );
	$marriages = get_field( 'marriages', $saint_id ) ?: array();
	
	return array(
		'id' => $saint_id,
		'name' => $saint->post_title,
		'birthdate' => get_field( 'birthdate', $saint_id ),
		'deathdate' => get_field( 'deathdate', $saint_id ),
		'familysearch_id' => get_field( 'familysearch_id', $saint_id ),
		'gender' => get_field( 'gender', $saint_id ) ?: 'male',
		'roles' => $roles ?: array(),
		'marriages_count' => count( $marriages ),
		'has_portrait' => has_post_thumbnail( $saint_id ),
		'portrait_url' => get_the_post_thumbnail_url( $saint_id, 'thumbnail' ) ?: '',
		'edit_url' => get_edit_post_link( $saint_id, 'raw' ),
		'view_url' => get_permalink( $saint_id ),
	);
}

// ============================================
// MERGE FUNCTIONS
// ============================================

/**
 * Merge duplicate saints by ID
 * 
 * Enhanced version that merges by specific IDs (for admin tool)
 * 
 * @param int $primary_id The ID of the saint to keep.
 * @param int $merge_from_id The ID of the saint to merge into primary.
 * @return array|WP_Error Result array or WP_Error on failure.
 */
function wasmo_merge_duplicate_saints_by_id( $primary_id, $merge_from_id ) {
	// Verify both posts exist
	$primary = get_post( $primary_id );
	$source = get_post( $merge_from_id );
	
	if ( ! $primary || $primary->post_type !== 'saint' ) {
		return new WP_Error( 'not_found', 'Primary saint not found' );
	}
	
	if ( ! $source || $source->post_type !== 'saint' ) {
		return new WP_Error( 'not_found', 'Source saint not found' );
	}
	
	$updates = array(
		'relationships_updated' => 0,
		'fields_merged' => array(),
	);
	
	// Step 1: Update all relationships pointing to source
	global $wpdb;
	$all_saints = get_posts( array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	) );
	
	foreach ( $all_saints as $saint ) {
		$marriages = get_field( 'marriages', $saint->ID );
		if ( empty( $marriages ) ) continue;
		
		$updated = false;
		
		foreach ( $marriages as $idx => $marriage ) {
			// Update spouse relationships
			$spouse_id = is_array( $marriage['spouse'] ) ? ( $marriage['spouse'][0] ?? null ) : $marriage['spouse'];
			if ( $spouse_id == $merge_from_id ) {
				$marriages[ $idx ]['spouse'] = array( $primary_id );
				$updated = true;
				$updates['relationships_updated']++;
			}
			
			// Update child relationships
			if ( ! empty( $marriage['children'] ) ) {
				foreach ( $marriage['children'] as $child_idx => $child ) {
					$child_link = is_array( $child['child_link'] ) ? ( $child['child_link'][0] ?? null ) : $child['child_link'];
					if ( $child_link == $merge_from_id ) {
						$marriages[ $idx ]['children'][ $child_idx ]['child_link'] = array( $primary_id );
						$updated = true;
						$updates['relationships_updated']++;
					}
				}
			}
		}
		
		if ( $updated ) {
			update_field( 'marriages', $marriages, $saint->ID );
		}
	}
	
	// Step 2: Merge all ACF fields
	wasmo_merge_all_acf_fields( $primary_id, $merge_from_id, $updates );
	
	// Step 3: Merge marriages
	wasmo_merge_marriages( $primary_id, $merge_from_id, $updates );
	
	// Step 4: Clear familysearch_verified
	update_field( 'familysearch_verified', '', $primary_id );
	
	// Step 5: Clear transients
	wasmo_clear_saint_transients( $primary_id );
	wasmo_clear_saint_transients( $merge_from_id );
	
	// Step 6: Add merge note
	$current_notes = get_field( 'familysearch_notes', $primary_id ) ?: '';
	$merge_note = sprintf(
		"\nMerged duplicate saint \"%s\" (ID: %d) on %s",
		$source->post_title,
		$merge_from_id,
		current_time( 'Y-m-d H:i' )
	);
	update_field( 'familysearch_notes', trim( $current_notes . $merge_note ), $primary_id );
	
	// Step 7: Delete source saint
	$deleted = wp_delete_post( $merge_from_id, true );
	
	// Step 8: Clear duplicate scan cache (so merged saints don't appear in list)
	delete_transient( 'wasmo_duplicates_scan_results' );
	
	return array(
		'success' => $deleted !== false,
		'updates' => $updates,
		'deleted_id' => $merge_from_id,
		'primary_id' => $primary_id,
	);
}

/**
 * Merge all ACF fields from source to target
 */
function wasmo_merge_all_acf_fields( $target_id, $source_id, &$updates ) {
	// Get all field groups for saint post type
	if ( ! function_exists( 'acf_get_field_groups' ) ) {
		// Fallback: merge specific known fields
		$fields_to_merge = array(
			'first_name', 'middle_name', 'last_name', 'gender',
			'birthdate', 'birthdate_approximate', 'deathdate', 'deathdate_approximate',
			'familysearch_id', 'familysearch_notes', 'hometown',
			'ordained_date', 'ordain_end', 'ordain_note', 'became_president_date',
			'education', 'mission', 'profession', 'military',
			'polygamist', 'number_of_wives', 'marital_status_at_marriage',
		);
		
		foreach ( $fields_to_merge as $field ) {
			$target_value = get_field( $field, $target_id );
			$source_value = get_field( $field, $source_id );
			
			if ( empty( $target_value ) && ! empty( $source_value ) ) {
				update_field( $field, $source_value, $target_id );
				$updates['fields_merged'][] = $field;
			}
		}
	} else {
		$field_groups = acf_get_field_groups( array( 'post_type' => 'saint' ) );
		
		foreach ( $field_groups as $group ) {
			$fields = acf_get_fields( $group );
			if ( ! $fields ) continue;
			
			foreach ( $fields as $field ) {
				// Skip repeaters (handled separately)
				if ( $field['type'] === 'repeater' ) continue;
				
				$target_value = get_field( $field['name'], $target_id );
				$source_value = get_field( $field['name'], $source_id );
				
				if ( empty( $target_value ) && ! empty( $source_value ) ) {
					update_field( $field['name'], $source_value, $target_id );
					$updates['fields_merged'][] = $field['name'];
				}
			}
		}
	}
	
	// Merge taxonomies (roles)
	$source_roles = wp_get_post_terms( $source_id, 'saint-role', array( 'fields' => 'ids' ) );
	$target_roles = wp_get_post_terms( $target_id, 'saint-role', array( 'fields' => 'ids' ) );
	if ( ! empty( $source_roles ) ) {
		$merged_roles = array_unique( array_merge( $target_roles, $source_roles ) );
		wp_set_object_terms( $target_id, $merged_roles, 'saint-role' );
		if ( count( $merged_roles ) > count( $target_roles ) ) {
			$updates['fields_merged'][] = 'saint_roles';
		}
	}
	
	// Merge featured image
	if ( ! has_post_thumbnail( $target_id ) && has_post_thumbnail( $source_id ) ) {
		$source_thumb_id = get_post_thumbnail_id( $source_id );
		set_post_thumbnail( $target_id, $source_thumb_id );
		$updates['fields_merged'][] = 'portrait';
	}
}

/**
 * Merge marriages from source to target
 */
function wasmo_merge_marriages( $target_id, $source_id, &$updates ) {
	$target_marriages = get_field( 'marriages', $target_id ) ?: array();
	$source_marriages = get_field( 'marriages', $source_id ) ?: array();
	
	if ( empty( $source_marriages ) ) {
		return;
	}
	
	foreach ( $source_marriages as $source_marriage ) {
		$source_spouse_id = is_array( $source_marriage['spouse'] ) 
			? ( $source_marriage['spouse'][0] ?? null ) 
			: $source_marriage['spouse'];
		$source_spouse_fs_id = $source_marriage['spouse_familysearch_id'] ?? '';
		
		// Try to find matching marriage in target
		$matched = false;
		foreach ( $target_marriages as $idx => $target_marriage ) {
			$target_spouse_id = is_array( $target_marriage['spouse'] ) 
				? ( $target_marriage['spouse'][0] ?? null ) 
				: $target_marriage['spouse'];
			$target_spouse_fs_id = $target_marriage['spouse_familysearch_id'] ?? '';
			
			// Match by spouse ID or FS ID
			if ( ( $source_spouse_id && $source_spouse_id == $target_spouse_id ) ||
				 ( $source_spouse_fs_id && $source_spouse_fs_id === $target_spouse_fs_id ) ) {
				// Merge children
				$target_children = $target_marriage['children'] ?: array();
				$source_children = $source_marriage['children'] ?: array();
				
				// Add children from source that aren't already in target
				foreach ( $source_children as $source_child ) {
					$found = false;
					$source_child_fs_id = $source_child['child_familysearch_id'] ?? '';
					$source_child_name = $source_child['child_name'] ?? '';
					
					foreach ( $target_children as $target_child ) {
						$target_child_fs_id = $target_child['child_familysearch_id'] ?? '';
						$target_child_name = $target_child['child_name'] ?? '';
						
						if ( ( $source_child_fs_id && $source_child_fs_id === $target_child_fs_id ) ||
							 ( $source_child_name && strtolower( $source_child_name ) === strtolower( $target_child_name ) ) ) {
							$found = true;
							break;
						}
					}
					
					if ( ! $found ) {
						$target_children[] = $source_child;
					}
				}
				
				$target_marriages[ $idx ]['children'] = $target_children;
				
				// Update dates if source is more specific
				if ( empty( $target_marriage['marriage_date'] ) && ! empty( $source_marriage['marriage_date'] ) ) {
					$target_marriages[ $idx ]['marriage_date'] = $source_marriage['marriage_date'];
				}
				
				$matched = true;
				break;
			}
		}
		
		// If no match, add as new marriage
		if ( ! $matched ) {
			$target_marriages[] = $source_marriage;
		}
	}
	
	update_field( 'marriages', $target_marriages, $target_id );
	$updates['fields_merged'][] = 'marriages';
}

// ============================================
// IGNORE FUNCTIONS
// ============================================

/**
 * Get ignored duplicates
 */
function wasmo_get_ignored_duplicates() {
	return get_option( 'wasmo_ignored_duplicates', array() );
}

/**
 * Check if duplicate pair is ignored
 */
function wasmo_is_duplicate_ignored( $saint1_id, $saint2_id, $ignored = null ) {
	if ( $ignored === null ) {
		$ignored = wasmo_get_ignored_duplicates();
	}
	
	$key1 = min( $saint1_id, $saint2_id ) . '-' . max( $saint1_id, $saint2_id );
	$key2 = max( $saint1_id, $saint2_id ) . '-' . min( $saint1_id, $saint2_id );
	
	return isset( $ignored[ $key1 ] ) || isset( $ignored[ $key2 ] );
}

/**
 * Ignore a duplicate pair
 */
function wasmo_ignore_duplicate_pair( $saint1_id, $saint2_id, $match_type ) {
	$ignored = wasmo_get_ignored_duplicates();
	$key = min( $saint1_id, $saint2_id ) . '-' . max( $saint1_id, $saint2_id );
	
	$ignored[ $key ] = array(
		'timestamp' => current_time( 'mysql' ),
		'match_type' => $match_type,
	);
	
	update_option( 'wasmo_ignored_duplicates', $ignored );
	
	// Clear scan cache
	delete_transient( 'wasmo_duplicates_scan_results' );
}

/**
 * Clear all ignored duplicates
 */
function wasmo_clear_ignored_duplicates() {
	delete_option( 'wasmo_ignored_duplicates' );
	delete_transient( 'wasmo_duplicates_scan_results' );
}

// ============================================
// AJAX HANDLERS
// ============================================

add_action( 'wp_ajax_wasmo_ignore_duplicate', 'wasmo_ajax_ignore_duplicate' );
function wasmo_ajax_ignore_duplicate() {
	check_ajax_referer( 'wasmo_ignore_duplicate', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Insufficient permissions' );
	}
	
	$saint1_id = absint( $_POST['saint1_id'] ?? 0 );
	$saint2_id = absint( $_POST['saint2_id'] ?? 0 );
	$match_type = sanitize_text_field( $_POST['match_type'] ?? '' );
	
	if ( ! $saint1_id || ! $saint2_id ) {
		wp_send_json_error( 'Invalid saint IDs' );
	}
	
	wasmo_ignore_duplicate_pair( $saint1_id, $saint2_id, $match_type );
	
	wp_send_json_success();
}

add_action( 'wp_ajax_wasmo_merge_duplicates', 'wasmo_ajax_merge_duplicates' );
function wasmo_ajax_merge_duplicates() {
	check_ajax_referer( 'wasmo_merge_duplicates', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Insufficient permissions' );
	}
	
	$primary_id = absint( $_POST['primary_id'] ?? 0 );
	$merge_from_id = absint( $_POST['merge_from_id'] ?? 0 );
	
	if ( ! $primary_id || ! $merge_from_id ) {
		wp_send_json_error( 'Invalid saint IDs' );
	}
	
	if ( $primary_id === $merge_from_id ) {
		wp_send_json_error( 'Cannot merge saint with itself' );
	}
	
	$result = wasmo_merge_duplicate_saints_by_id( $primary_id, $merge_from_id );
	
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( $result->get_error_message() );
	}
	
	// Clear duplicate scan cache so merged saints don't appear in list
	delete_transient( 'wasmo_duplicates_scan_results' );
	
	wp_send_json_success( $result );
}

add_action( 'wp_ajax_wasmo_get_duplicate_details', 'wasmo_ajax_get_duplicate_details' );
function wasmo_ajax_get_duplicate_details() {
	check_ajax_referer( 'wasmo_get_duplicate_details', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Insufficient permissions' );
	}
	
	$saint1_id = absint( $_POST['saint1_id'] ?? 0 );
	$saint2_id = absint( $_POST['saint2_id'] ?? 0 );
	
	if ( ! $saint1_id || ! $saint2_id ) {
		wp_send_json_error( 'Invalid saint IDs' );
	}
	
	$saint1_data = wasmo_get_saint_data_for_duplicate( $saint1_id );
	$saint2_data = wasmo_get_saint_data_for_duplicate( $saint2_id );
	
	ob_start();
	?>
	<div class="wasmo-duplicate-comparison">
		<div class="wasmo-saint-info">
			<h3><?php echo esc_html( $saint1_data['name'] ); ?> (ID: <?php echo esc_html( $saint1_id ); ?>)</h3>
			<?php if ( $saint1_data['has_portrait'] ) : ?>
				<img src="<?php echo esc_url( $saint1_data['portrait_url'] ); ?>" alt="<?php echo esc_attr( $saint1_data['name'] ); ?>" />
			<?php endif; ?>
			<p><strong>Birthdate:</strong> <?php echo esc_html( $saint1_data['birthdate'] ?: 'Missing' ); ?></p>
			<p><strong>Deathdate:</strong> <?php echo esc_html( $saint1_data['deathdate'] ?: 'Missing' ); ?></p>
			<p><strong>FS ID:</strong> <?php echo esc_html( $saint1_data['familysearch_id'] ?: 'Missing' ); ?></p>
			<p><strong>Marriages:</strong> <?php echo esc_html( $saint1_data['marriages_count'] ); ?></p>
		</div>
		<div class="wasmo-saint-info">
			<h3><?php echo esc_html( $saint2_data['name'] ); ?> (ID: <?php echo esc_html( $saint2_id ); ?>)</h3>
			<?php if ( $saint2_data['has_portrait'] ) : ?>
				<img src="<?php echo esc_url( $saint2_data['portrait_url'] ); ?>" alt="<?php echo esc_attr( $saint2_data['name'] ); ?>" />
			<?php endif; ?>
			<p><strong>Birthdate:</strong> <?php echo esc_html( $saint2_data['birthdate'] ?: 'Missing' ); ?></p>
			<p><strong>Deathdate:</strong> <?php echo esc_html( $saint2_data['deathdate'] ?: 'Missing' ); ?></p>
			<p><strong>FS ID:</strong> <?php echo esc_html( $saint2_data['familysearch_id'] ?: 'Missing' ); ?></p>
			<p><strong>Marriages:</strong> <?php echo esc_html( $saint2_data['marriages_count'] ); ?></p>
		</div>
	</div>
	<?php
	$comparison = ob_get_clean();
	
	wp_send_json_success( array(
		'saint1' => $saint1_data,
		'saint2' => $saint2_data,
		'comparison' => $comparison,
	) );
}
