<?php
/**
 * Saints JSON Import/Export
 * 
 * Admin page for importing/exporting church leaders from/to JSON data.
 *
 * @package wasmo
 */

/**
 * Find a saint by exact title match
 *
 * @param string $title The exact title to search for.
 * @return WP_Post|null The found post or null.
 */
function wasmo_find_saint_by_exact_title( $title ) {
	global $wpdb;
	
	$title = trim( $title );
	if ( empty( $title ) ) {
		return null;
	}
	
	$post_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} 
		WHERE post_type = 'saint' 
		AND post_status IN ('publish', 'draft', 'pending', 'private')
		AND post_title = %s 
		ORDER BY ID ASC 
		LIMIT 1",
		$title
	) );
	
	if ( $post_id ) {
		return get_post( $post_id );
	}
	
	return null;
}

/**
 * Normalize a name for fuzzy comparison
 * Removes periods, extra spaces, and lowercases
 *
 * @param string $name The name to normalize.
 * @return string Normalized name.
 */
function wasmo_normalize_name_for_matching( $name ) {
	$name = strtolower( trim( $name ) );
	// Remove periods (handles "M." vs "M")
	$name = str_replace( '.', '', $name );
	// Normalize whitespace
	$name = preg_replace( '/\s+/', ' ', $name );
	return $name;
}

/**
 * Find a saint by fuzzy title match
 * Tries exact match first, then falls back to normalized comparison
 *
 * @param string $title The title to search for.
 * @return WP_Post|null The found post or null.
 */
function wasmo_find_saint_by_fuzzy_title( $title ) {
	// First try exact match
	$exact = wasmo_find_saint_by_exact_title( $title );
	if ( $exact ) {
		return $exact;
	}
	
	// Normalize the search title
	$normalized_search = wasmo_normalize_name_for_matching( $title );
	if ( empty( $normalized_search ) ) {
		return null;
	}
	
	// Get all saints and compare normalized names
	$saints = get_posts( array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'fields'         => 'ids',
	) );
	
	$best_match = null;
	$best_score = 0;
	
	foreach ( $saints as $saint_id ) {
		$saint_title = get_the_title( $saint_id );
		$normalized_title = wasmo_normalize_name_for_matching( $saint_title );
		
		// Exact normalized match
		if ( $normalized_search === $normalized_title ) {
			return get_post( $saint_id );
		}
		
		// Calculate similarity for close matches
		similar_text( $normalized_search, $normalized_title, $percent );
		
		// Only consider matches above 85% similar
		if ( $percent > 85 && $percent > $best_score ) {
			$best_score = $percent;
			$best_match = $saint_id;
		}
	}
	
	// Return best match if found and above threshold
	if ( $best_match && $best_score >= 90 ) {
		return get_post( $best_match );
	}
	
	return null;
}

/**
 * Find duplicate saints (saints with the same or very similar titles)
 *
 * @return array Array of title => array of post IDs.
 */
function wasmo_find_duplicate_saints() {
	global $wpdb;
	
	// First, find exact title duplicates
	$duplicates = $wpdb->get_results(
		"SELECT TRIM(post_title) as clean_title, GROUP_CONCAT(ID ORDER BY ID ASC) as post_ids, COUNT(*) as count
		FROM {$wpdb->posts}
		WHERE post_type = 'saint'
		AND post_status IN ('publish', 'draft', 'pending', 'private')
		GROUP BY TRIM(post_title)
		HAVING count > 1
		ORDER BY count DESC, clean_title ASC"
	);
	
	$result = array();
	foreach ( $duplicates as $dup ) {
		$ids = array_map( 'intval', explode( ',', $dup->post_ids ) );
		$result[ $dup->clean_title ] = array(
			'count' => (int) $dup->count,
			'ids'   => $ids,
		);
	}
	
	// Also check for similar titles (case-insensitive)
	$case_insensitive_dups = $wpdb->get_results(
		"SELECT LOWER(TRIM(post_title)) as lower_title, GROUP_CONCAT(DISTINCT ID ORDER BY ID ASC) as post_ids, COUNT(DISTINCT ID) as count
		FROM {$wpdb->posts}
		WHERE post_type = 'saint'
		AND post_status IN ('publish', 'draft', 'pending', 'private')
		GROUP BY LOWER(TRIM(post_title))
		HAVING count > 1
		ORDER BY count DESC, lower_title ASC"
	);
	
	foreach ( $case_insensitive_dups as $dup ) {
		$ids = array_map( 'intval', explode( ',', $dup->post_ids ) );
		// Get the actual title from the first post
		$first_post = get_post( $ids[0] );
		$title_key = $first_post ? $first_post->post_title : $dup->lower_title;
		
		if ( ! isset( $result[ $title_key ] ) ) {
			$result[ $title_key . ' (case-insensitive)' ] = array(
				'count' => (int) $dup->count,
				'ids'   => $ids,
			);
		}
	}
	
	return $result;
}

/**
 * Merge duplicate saints, keeping the first (lowest ID) and reassigning relationships
 *
 * @param string $title The saint title to merge duplicates for.
 * @return array Result with 'kept', 'merged', and 'errors'.
 */
function wasmo_merge_duplicate_saints( $title ) {
	global $wpdb;
	
	$result = array(
		'kept'   => null,
		'merged' => array(),
		'errors' => array(),
	);
	
	$saints = $wpdb->get_results( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts}
		WHERE post_type = 'saint'
		AND post_status IN ('publish', 'draft', 'pending', 'private')
		AND post_title = %s
		ORDER BY ID ASC",
		$title
	) );
	
	if ( count( $saints ) <= 1 ) {
		$result['errors'][] = 'No duplicates found';
		return $result;
	}
	
	// Keep the first one (lowest ID, likely the original)
	$keeper_id = $saints[0]->ID;
	$result['kept'] = $keeper_id;
	
	// Get all duplicates to merge
	for ( $i = 1; $i < count( $saints ); $i++ ) {
		$duplicate_id = $saints[ $i ]->ID;
		
		// Update any marriage spouse relationships that point to the duplicate
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->postmeta} 
			SET meta_value = %s 
			WHERE meta_key LIKE 'marriages_%%_spouse' 
			AND meta_value = %s",
			$keeper_id,
			$duplicate_id
		) );
		
		// Also update serialized spouse arrays
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->postmeta} 
			SET meta_value = REPLACE(meta_value, %s, %s) 
			WHERE meta_key LIKE 'marriages_%%_spouse'",
			'"' . $duplicate_id . '"',
			'"' . $keeper_id . '"'
		) );
		
		// Trash the duplicate
		wp_trash_post( $duplicate_id );
		$result['merged'][] = $duplicate_id;
	}
	
	return $result;
}

/**
 * Find and clean up orphaned spouse references (references to deleted/trashed posts)
 *
 * @return array Result with 'cleaned' count and 'errors'.
 */
function wasmo_cleanup_orphaned_spouse_refs() {
	global $wpdb;
	
	$result = array(
		'cleaned' => 0,
		'errors'  => array(),
	);
	
	// Find all spouse references in marriages repeater
	$spouse_refs = $wpdb->get_results(
		"SELECT post_id, meta_key, meta_value 
		FROM {$wpdb->postmeta} 
		WHERE meta_key LIKE 'marriages_%_spouse'"
	);
	
	foreach ( $spouse_refs as $ref ) {
		$spouse_id = null;
		
		// Check if it's a serialized array or plain value
		$value = maybe_unserialize( $ref->meta_value );
		if ( is_array( $value ) && ! empty( $value[0] ) ) {
			$spouse_id = intval( $value[0] );
		} elseif ( is_numeric( $ref->meta_value ) ) {
			$spouse_id = intval( $ref->meta_value );
		}
		
		if ( ! $spouse_id ) {
			continue;
		}
		
		// Check if this post exists and is published
		$spouse_post = get_post( $spouse_id );
		if ( ! $spouse_post || $spouse_post->post_status === 'trash' || $spouse_post->post_type !== 'saint' ) {
			// This is an orphaned reference - need to remove this marriage entry
			// Extract the index from the meta_key (e.g., marriages_0_spouse -> 0)
			if ( preg_match( '/marriages_(\d+)_spouse/', $ref->meta_key, $matches ) ) {
				$marriage_index = $matches[1];
				$parent_post_id = $ref->post_id;
				
				// Get the marriages array and remove this entry
				$marriages = get_field( 'marriages', $parent_post_id );
				if ( is_array( $marriages ) && isset( $marriages[ $marriage_index ] ) ) {
					unset( $marriages[ $marriage_index ] );
					// Re-index array
					$marriages = array_values( $marriages );
					update_field( 'marriages', $marriages, $parent_post_id );
					$result['cleaned']++;
				}
			}
		}
	}
	
	return $result;
}

/**
 * Add admin menu page for leader import
 */
function wasmo_add_leader_import_page() {
	add_submenu_page(
		'edit.php?post_type=saint',
		'Import/Export Leaders',
		'Import/Export JSON',
		'manage_options',
		'import-leaders',
		'wasmo_render_leader_import_page'
	);
}
add_action( 'admin_menu', 'wasmo_add_leader_import_page' );

/**
 * Render the import admin page
 */
function wasmo_render_leader_import_page() {
	// Handle form submission
	$message = '';
	$imported = 0;
	$skipped = 0;
	$updated = 0;
	$errors = array();

	// Handle JSON file upload import
	if ( isset( $_POST['wasmo_import_file'] ) && check_admin_referer( 'wasmo_import_file_nonce' ) ) {
		if ( ! empty( $_FILES['leaders_json_file']['tmp_name'] ) ) {
			$file_content = file_get_contents( $_FILES['leaders_json_file']['tmp_name'] );
			$leaders_data = json_decode( $file_content, true );
			
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$message = '<div class="notice notice-error"><p>Invalid JSON file: ' . json_last_error_msg() . '</p></div>';
			} else {
				// Update existing is ON by default (unless explicitly unchecked via skip_existing)
				$update_existing = ! isset( $_POST['skip_existing'] ) || ! $_POST['skip_existing'];
				$import_images = isset( $_POST['import_images'] ) && $_POST['import_images'];
				$result = wasmo_import_leaders_from_array( $leaders_data, $update_existing, $import_images );
				
				$message = '<div class="notice notice-success"><p>';
				$message .= sprintf( 
					'Import complete! %d created, %d updated, %d skipped.', 
					$result['imported'], 
					$result['updated'],
					$result['skipped']
				);
				$message .= '</p></div>';
				
				if ( ! empty( $result['errors'] ) ) {
					$message .= '<div class="notice notice-warning"><p>Some errors occurred:</p><ul>';
					foreach ( array_slice( $result['errors'], 0, 10 ) as $error ) {
						$message .= '<li>' . esc_html( $error ) . '</li>';
					}
					if ( count( $result['errors'] ) > 10 ) {
						$message .= '<li>... and ' . ( count( $result['errors'] ) - 10 ) . ' more errors</li>';
					}
					$message .= '</ul></div>';
				}
			}
		} else {
			$message = '<div class="notice notice-error"><p>Please select a JSON file to upload.</p></div>';
		}
	}

	// Handle single import
	if ( isset( $_POST['wasmo_import_single'] ) && check_admin_referer( 'wasmo_import_single_nonce' ) ) {
		$json_data = stripslashes( $_POST['leader_json'] );
		$leader_data = json_decode( $json_data, true );
		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$message = '<div class="notice notice-error"><p>Invalid JSON format.</p></div>';
		} else {
			$result = wasmo_import_single_leader_full( $leader_data, true, false );
			if ( is_wp_error( $result ) ) {
				$message = '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				$message = '<div class="notice notice-success"><p>Leader imported successfully! <a href="' . get_edit_post_link( $result ) . '">Edit now</a></p></div>';
			}
		}
	}

	// Get existing leaders count
	$existing_count = wp_count_posts( 'saint' )->publish;
	?>
	<div class="wrap">
		<h1>Import/Export Saints</h1>
		
		<?php echo $message; ?>

		<!-- Export Section -->
		<div class="card" style="max-width: 800px; margin-bottom: 20px; background: #e7f3e7;">
			<h2>📤 Export All Leaders to JSON</h2>
			<p>
				Export all church leaders with their complete data to a JSON file. 
				This includes all ACF fields, bio content, roles, and featured image URLs.
			</p>
			<p><strong>Current leaders in database:</strong> <?php echo $existing_count; ?></p>
			<p>
				<a href="<?php echo admin_url( 'admin-ajax.php?action=wasmo_export_leaders_json&_wpnonce=' . wp_create_nonce( 'wasmo_export_leaders' ) ); ?>" 
				   class="button button-primary" download>
					Download Leaders JSON Export
				</a>
			</p>
		</div>

		<!-- Import from File Section -->
		<div class="card" style="max-width: 800px; margin-bottom: 20px; background: #e7f0f3;">
			<h2>📥 Import from JSON File</h2>
			<p>
				Upload a JSON file exported from another site (or the export above) to import all leaders with their complete data.
			</p>
			
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'wasmo_import_file_nonce' ); ?>
				<p>
					<label for="leaders_json_file"><strong>Select JSON file:</strong></label><br>
					<input type="file" name="leaders_json_file" id="leaders_json_file" accept=".json,application/json" style="margin-top: 5px;">
				</p>
				<p>
					<label>
						<input type="checkbox" name="skip_existing" value="1">
						Skip existing leaders (by default, existing leaders are updated with imported data)
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" name="import_images" value="1">
						Import featured images from URLs (may take longer)
					</label>
				</p>
				<p>
					<button type="submit" name="wasmo_import_file" class="button button-primary">
						Import from JSON File
					</button>
				</p>
			</form>
		</div>

		<div class="card" style="max-width: 800px; margin-bottom: 20px;">
			<h2>Import Single Leader (JSON)</h2>
			<p>Paste JSON data for a single leader to import:</p>
			
			<form method="post">
				<?php wp_nonce_field( 'wasmo_import_single_nonce' ); ?>
				<p>
					<textarea name="leader_json" rows="10" style="width: 100%; font-family: monospace;" placeholder='{
  "name": "Leader Name",
  "first_name": "First",
  "last_name": "Last",
  "birthdate": "1900-01-01",
  "deathdate": "1980-12-31",
  "hometown": "City, State",
  "ordained_date": "1930-04-06",
  "became_president_date": "1970-01-23",
  "roles": ["president", "apostle"],
  "bio": "Brief biography content...",
  "featured_image_url": "https://example.com/image.jpg"
}'></textarea>
				</p>
				<p>
					<button type="submit" name="wasmo_import_single" class="button">
						Import Single Leader
					</button>
				</p>
			</form>
		</div>

		<div class="card" style="max-width: 800px;">
			<h2>Export JSON Format</h2>
			<p>The export includes the following fields for each leader:</p>
			<table class="widefat" style="max-width: 600px;">
				<thead>
					<tr>
						<th>JSON Field</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
					<tr><td><code>name</code></td><td>Post Title (full name)</td></tr>
					<tr><td><code>slug</code></td><td>Post slug/URL</td></tr>
					<tr><td><code>first_name</code></td><td>First name</td></tr>
					<tr><td><code>middle_name</code></td><td>Middle name</td></tr>
					<tr><td><code>last_name</code></td><td>Last name</td></tr>
					<tr><td><code>birthdate</code></td><td>Birth date (Y-m-d)</td></tr>
					<tr><td><code>deathdate</code></td><td>Death date (Y-m-d or null)</td></tr>
					<tr><td><code>hometown</code></td><td>Hometown</td></tr>
					<tr><td><code>ordained_date</code></td><td>Date ordained apostle (Y-m-d H:i:s)</td></tr>
					<tr><td><code>became_president_date</code></td><td>Date became president (Y-m-d)</td></tr>
					<tr><td><code>education</code></td><td>Education history</td></tr>
					<tr><td><code>mission</code></td><td>Mission served</td></tr>
					<tr><td><code>profession</code></td><td>Profession</td></tr>
					<tr><td><code>military</code></td><td>Military service</td></tr>
					<tr><td><code>polygamist</code></td><td>Boolean</td></tr>
					<tr><td><code>number_of_wives</code></td><td>Number (if polygamist)</td></tr>
					<tr><td><code>roles</code></td><td>Array of role slugs</td></tr>
					<tr><td><code>bio</code></td><td>Post content (biography)</td></tr>
					<tr><td><code>featured_image_url</code></td><td>Featured image URL</td></tr>
				</tbody>
			</table>
		</div>

		<?php
		// Handle orphaned references cleanup
		if ( isset( $_POST['wasmo_cleanup_orphans'] ) && check_admin_referer( 'wasmo_cleanup_orphans_nonce' ) ) {
			$cleanup_result = wasmo_cleanup_orphaned_spouse_refs();
			if ( $cleanup_result['cleaned'] > 0 ) {
				echo '<div class="notice notice-success"><p>Cleaned up ' . esc_html( $cleanup_result['cleaned'] ) . ' orphaned spouse reference(s).</p></div>';
			} else {
				echo '<div class="notice notice-info"><p>No orphaned references found to clean up.</p></div>';
			}
		}
		
		// Handle duplicate merge action
		if ( isset( $_POST['wasmo_merge_duplicates'] ) && check_admin_referer( 'wasmo_merge_duplicates_nonce' ) ) {
			$title_to_merge = sanitize_text_field( $_POST['duplicate_title'] );
			$merge_result = wasmo_merge_duplicate_saints( $title_to_merge );
			
			if ( ! empty( $merge_result['errors'] ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( implode( ', ', $merge_result['errors'] ) ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success"><p>Merged ' . count( $merge_result['merged'] ) . ' duplicate(s) of "' . esc_html( $title_to_merge ) . '". Kept ID: ' . esc_html( $merge_result['kept'] ) . '</p></div>';
			}
		}
		
		// Handle merge all duplicates action
		if ( isset( $_POST['wasmo_merge_all_duplicates'] ) && check_admin_referer( 'wasmo_merge_all_duplicates_nonce' ) ) {
			$all_duplicates = wasmo_find_duplicate_saints();
			$total_merged = 0;
			foreach ( $all_duplicates as $title => $info ) {
				$merge_result = wasmo_merge_duplicate_saints( $title );
				$total_merged += count( $merge_result['merged'] );
			}
			echo '<div class="notice notice-success"><p>Merged ' . esc_html( $total_merged ) . ' duplicate records.</p></div>';
		}
		
		// Handle Marriage Migration Preview
		$migration_preview = null;
		if ( isset( $_POST['wasmo_preview_marriage_migration'] ) && check_admin_referer( 'wasmo_marriage_migration_nonce' ) ) {
			$migration_preview = wasmo_preview_marriage_migration();
		}
		
		// Handle Marriage Migration Execute
		$migration_result = null;
		if ( isset( $_POST['wasmo_execute_marriage_migration'] ) && check_admin_referer( 'wasmo_marriage_migration_nonce' ) ) {
			$dry_run = isset( $_POST['dry_run'] ) && $_POST['dry_run'] === '1';
			$migration_result = wasmo_migrate_marriages_to_wives( $dry_run );
		}
		
		// Find duplicates
		$duplicates = wasmo_find_duplicate_saints();
		?>
		
		<!-- Marriage Migration Section -->
		<div class="card" style="max-width: 800px; margin-bottom: 20px;">
			<h2>🔄 Marriage Data Migration</h2>
			<p>Moves marriage data from <strong>husband's records</strong> to <strong>wife's records</strong>. After migration:</p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li>Wives will store their own marriage entries (with husband as "spouse")</li>
				<li>Husbands will use reverse lookup to find their wives</li>
				<li>Children data will be stored on the wife's page</li>
			</ul>
			
			<form method="post" style="margin-top: 15px;">
				<?php wp_nonce_field( 'wasmo_marriage_migration_nonce' ); ?>
				<button type="submit" name="wasmo_preview_marriage_migration" class="button">
					👁️ Preview Migration
				</button>
				<button type="submit" name="wasmo_execute_marriage_migration" class="button button-primary" onclick="return confirm('This will move all marriage data from men to women. This action creates backups but cannot be easily undone. Continue?');" style="margin-left: 10px;">
					▶️ Execute Migration
				</button>
				<label style="margin-left: 15px;">
					<input type="checkbox" name="dry_run" value="1"> Dry Run (simulate only)
				</label>
			</form>
			
			<?php if ( $migration_preview ) : ?>
			<div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
				<h3>Migration Preview</h3>
				<table class="widefat" style="margin-top: 10px;">
					<tr><th>Men with marriages</th><td><?php echo esc_html( $migration_preview['men_with_marriages'] ); ?></td></tr>
					<tr><th>Total marriages to migrate</th><td><?php echo esc_html( $migration_preview['total_marriages'] ); ?></td></tr>
					<tr><th>Total children to migrate</th><td><?php echo esc_html( $migration_preview['total_children'] ); ?></td></tr>
					<tr><th>Wives to update</th><td><?php echo esc_html( $migration_preview['wives_to_update'] ); ?></td></tr>
					<?php if ( $migration_preview['orphaned_marriages'] > 0 ) : ?>
					<tr style="color: orange;"><th>Orphaned marriages (no spouse)</th><td><?php echo esc_html( $migration_preview['orphaned_marriages'] ); ?></td></tr>
					<?php endif; ?>
				</table>
				
				<?php if ( ! empty( $migration_preview['details'] ) ) : ?>
				<h4 style="margin-top: 15px;">Details by Husband:</h4>
				<table class="widefat striped" style="margin-top: 5px;">
					<thead>
						<tr><th>Husband</th><th>Wife</th><th>Marriage Date</th><th>Children</th></tr>
					</thead>
					<tbody>
						<?php foreach ( $migration_preview['details'] as $man ) : ?>
							<?php foreach ( $man['marriages'] as $i => $m ) : ?>
							<tr>
								<?php if ( $i === 0 ) : ?>
								<td rowspan="<?php echo count( $man['marriages'] ); ?>">
									<a href="<?php echo get_edit_post_link( $man['id'] ); ?>" target="_blank"><?php echo esc_html( $man['name'] ); ?></a>
								</td>
								<?php endif; ?>
								<td><a href="<?php echo get_edit_post_link( $m['wife_id'] ); ?>" target="_blank"><?php echo esc_html( $m['wife_name'] ); ?></a></td>
								<td><?php echo esc_html( $m['marriage_date'] ?: 'Unknown' ); ?></td>
								<td><?php echo esc_html( $m['children_count'] ); ?></td>
							</tr>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
			<?php endif; ?>
			
			<?php if ( $migration_result ) : ?>
			<div style="margin-top: 20px; padding: 15px; background: <?php echo empty( $migration_result['errors'] ) ? '#d4edda' : '#fff3cd'; ?>; border: 1px solid <?php echo empty( $migration_result['errors'] ) ? '#c3e6cb' : '#ffc107'; ?>;">
				<h3>Migration Results</h3>
				<table class="widefat" style="margin-top: 10px;">
					<tr><th>Men processed</th><td><?php echo esc_html( $migration_result['processed_men'] ); ?></td></tr>
					<tr><th>Marriages migrated</th><td><?php echo esc_html( $migration_result['migrated_marriages'] ); ?></td></tr>
					<tr><th>Children migrated</th><td><?php echo esc_html( $migration_result['migrated_children'] ); ?></td></tr>
					<tr><th>Men cleared</th><td><?php echo esc_html( $migration_result['cleared_men'] ); ?></td></tr>
				</table>
				
				<?php if ( ! empty( $migration_result['errors'] ) ) : ?>
				<h4 style="margin-top: 15px; color: #856404;">Warnings/Errors:</h4>
				<ul style="color: #856404; margin-left: 20px;">
					<?php foreach ( $migration_result['errors'] as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
				
				<?php if ( ! empty( $migration_result['log'] ) ) : ?>
				<details style="margin-top: 15px;">
					<summary style="cursor: pointer;">View Log (<?php echo count( $migration_result['log'] ); ?> entries)</summary>
					<pre style="background: #fff; padding: 10px; margin-top: 5px; max-height: 300px; overflow: auto; font-size: 12px;"><?php echo esc_html( implode( "\n", $migration_result['log'] ) ); ?></pre>
				</details>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
		
		<div class="card" style="max-width: 800px;">
			<h2>🧹 Data Cleanup</h2>
			
			<h3>Orphaned References</h3>
			<p>Removes marriage entries that reference deleted or trashed saints.</p>
			<form method="post" style="display: inline;">
				<?php wp_nonce_field( 'wasmo_cleanup_orphans_nonce' ); ?>
				<button type="submit" name="wasmo_cleanup_orphans" class="button" onclick="return confirm('Clean up orphaned spouse references? This will remove marriage entries that point to deleted posts.');">
					🗑️ Clean Up Orphaned References
				</button>
			</form>
			
			<h3 style="margin-top: 20px;">Duplicate Saints</h3>
			<?php if ( empty( $duplicates ) ) : ?>
				<p style="color: green;">✓ No duplicate saints found.</p>
			<?php else : ?>
				<p style="color: orange;">⚠ Found <?php echo count( $duplicates ); ?> saints with duplicate entries:</p>
				<table class="widefat">
					<thead>
						<tr>
							<th>Name</th>
							<th>Count</th>
							<th>Post IDs</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $duplicates as $title => $info ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $title ); ?></strong></td>
							<td><?php echo esc_html( $info['count'] ); ?></td>
							<td>
								<?php foreach ( $info['ids'] as $i => $id ) : ?>
									<a href="<?php echo get_edit_post_link( $id ); ?>" target="_blank"><?php echo esc_html( $id ); ?></a><?php echo $i === 0 ? ' (keep)' : ''; ?><?php echo $i < count( $info['ids'] ) - 1 ? ', ' : ''; ?>
								<?php endforeach; ?>
							</td>
							<td>
								<form method="post" style="display: inline;">
									<?php wp_nonce_field( 'wasmo_merge_duplicates_nonce' ); ?>
									<input type="hidden" name="duplicate_title" value="<?php echo esc_attr( $title ); ?>">
									<button type="submit" name="wasmo_merge_duplicates" class="button button-small" onclick="return confirm('Merge all duplicates of \'<?php echo esc_js( $title ); ?>\'? The lowest ID will be kept and others will be trashed.');">
										Merge
									</button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p>
					<form method="post" style="display: inline;">
						<?php wp_nonce_field( 'wasmo_merge_all_duplicates_nonce' ); ?>
						<button type="submit" name="wasmo_merge_all_duplicates" class="button" onclick="return confirm('Merge ALL duplicates? This will keep the lowest ID for each duplicate and trash the rest.');">
							Merge All Duplicates
						</button>
					</form>
				</p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Parse various date formats to Y-m-d
 *
 * @param string $date_string Date string in various formats.
 * @return string|null Date in Y-m-d format, or null if unparseable.
 */
/**
 * Parse a date string from import data, normalizing partial dates.
 * 
 * @param string $date_string The date string to parse.
 * @param bool   $return_full Whether to return full result array with approximate flag.
 * @return string|array|null Date string, or array if $return_full is true.
 */
function wasmo_parse_leader_date( $date_string, $return_full = false ) {
	if ( empty( $date_string ) ) {
		return $return_full ? array( 'date' => null, 'approximate' => false ) : null;
	}

	// Use the normalization function from functions-saints.php
	$result = wasmo_normalize_date( $date_string );
	
	if ( $return_full ) {
		return $result;
	}
	
	return $result['date'];
}

/**
 * Map JSON group names to saint-role taxonomy slugs
 *
 * @param string $group Group name from JSON.
 * @return string|null Role slug, or null if no mapping.
 */
function wasmo_map_group_to_role( $group ) {
	$group = strtolower( trim( $group ) );

	$mappings = array(
		'latter day prophet'    => 'president',
		'latter day apostle'    => 'apostle',
		'living apostle'        => 'apostle',
		'first presidency'      => 'first-presidency',
		'seventy'               => 'seventy',
		'presiding bishopric'   => 'presiding-bishopric',
		'presiding bishop'      => 'presiding-bishopric',
		'general officer'       => 'general-officer',
	);

	foreach ( $mappings as $pattern => $slug ) {
		if ( strpos( $group, $pattern ) !== false ) {
			return $slug;
		}
	}

	return null;
}

/**
 * AJAX handler for exporting leaders to JSON
 */
function wasmo_export_leaders_json_ajax() {
	// Verify nonce
	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wasmo_export_leaders' ) ) {
		wp_die( 'Security check failed' );
	}

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Permission denied' );
	}

	$leaders_data = wasmo_get_all_leaders_export_data();

	// Set headers for JSON download
	header( 'Content-Type: application/json' );
	header( 'Content-Disposition: attachment; filename="saints-export-' . date( 'Y-m-d' ) . '.json"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	echo json_encode( $leaders_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}
add_action( 'wp_ajax_wasmo_export_leaders_json', 'wasmo_export_leaders_json_ajax' );

/**
 * Get all leaders data for export
 * 
 * Exports in a specific order to ensure relationship fields work on import:
 * 1. Presidents (so they exist when referenced as spouses)
 * 2. Apostles (so they exist when referenced as spouses)
 * 3. Others (any saints without president, apostle, or wife roles)
 * 4. Wives last (since they have relationship fields referencing husbands)
 *
 * @return array Array of leader data.
 */
function wasmo_get_all_leaders_export_data() {
	$all_leaders = get_posts( array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	// Separate leaders by role for proper export ordering
	$presidents = array();
	$apostles = array();
	$plural_wives = array();
	$others = array();

	foreach ( $all_leaders as $leader ) {
		$role_terms = wp_get_post_terms( $leader->ID, 'saint-role', array( 'fields' => 'slugs' ) );
		
		if ( in_array( 'president', $role_terms, true ) ) {
			$presidents[] = $leader;
		} elseif ( in_array( 'apostle', $role_terms, true ) ) {
			$apostles[] = $leader;
		} elseif ( in_array( 'wife', $role_terms, true ) ) {
			$plural_wives[] = $leader;
		} else {
			$others[] = $leader;
		}
	}

	// Merge in the correct order: presidents, apostles, others, plural-wives
	$leaders = array_merge( $presidents, $apostles, $others, $plural_wives );

	$export_data = array();

	foreach ( $leaders as $leader ) {
		$leader_id = $leader->ID;

		// Get roles
		$role_terms = wp_get_post_terms( $leader_id, 'saint-role', array( 'fields' => 'slugs' ) );

		// Get featured image URL
		$featured_image_url = null;
		if ( has_post_thumbnail( $leader_id ) ) {
			$featured_image_url = get_the_post_thumbnail_url( $leader_id, 'full' );
		}

		// Get leader tag
		$leader_tag = get_field( 'leader_tag', $leader_id );
		$leader_tag_slug = null;
		if ( $leader_tag ) {
			$tag_term = get_term( $leader_tag, 'post_tag' );
			if ( $tag_term && ! is_wp_error( $tag_term ) ) {
				$leader_tag_slug = $tag_term->slug;
			}
		}

		// Get marriages data (repeater field)
		$marriages_raw = get_field( 'marriages', $leader_id );
		$marriages_export = array();
		if ( ! empty( $marriages_raw ) && is_array( $marriages_raw ) ) {
			foreach ( $marriages_raw as $marriage ) {
				// Check if spouse is a saint (default true for backwards compatibility)
				$spouse_is_saint = isset( $marriage['spouse_is_saint'] ) ? (bool) $marriage['spouse_is_saint'] : true;
				
				// Get spouse name - either from saint record or from text field
				$spouse_id = null;
				$spouse_name = null;
				
				if ( $spouse_is_saint ) {
					// Get spouse from relationship field
					$spouse_field = $marriage['spouse'] ?? null;
					if ( $spouse_field ) {
						$spouse_id = is_array( $spouse_field ) ? ( $spouse_field[0] ?? null ) : $spouse_field;
						if ( $spouse_id ) {
							$spouse_post = get_post( $spouse_id );
							if ( $spouse_post ) {
								$spouse_name = $spouse_post->post_title;
							}
						}
					}
				} else {
					// Get spouse name and birthdate from text fields (non-saint spouse)
					$spouse_name = $marriage['spouse_name'] ?? null;
				}
				
				// Get spouse birthdate for non-saint spouses
				$spouse_birthdate_export = null;
				if ( ! $spouse_is_saint ) {
					$spouse_birthdate_export = $marriage['spouse_birthdate'] ?? null;
				}

				// Get children - handle both nested repeater and simple array formats
				$children_export = array();
				$children_field = $marriage['children'] ?? array();
				if ( ! empty( $children_field ) && is_array( $children_field ) ) {
					foreach ( $children_field as $child ) {
						// Check if it's a nested repeater (has 'child_name' key) or simple ID
						if ( is_array( $child ) && isset( $child['child_name'] ) ) {
							$children_export[] = array(
								'name'      => $child['child_name'] ?? '',
								'birthdate' => $child['child_birthdate'] ?? null,
							);
						} elseif ( is_numeric( $child ) ) {
							// Legacy format - just an ID
							$child_post = get_post( $child );
							if ( $child_post ) {
								$children_export[] = array(
									'name' => $child_post->post_title,
								);
							}
						}
					}
				}

				$marriages_export[] = array(
					'spouse_is_saint'           => $spouse_is_saint,
					'spouse_name'               => $spouse_name,
					'spouse_birthdate'          => $spouse_birthdate_export,
					'marriage_date'             => $marriage['marriage_date'] ?? null,
					'marriage_date_approximate' => (bool) ( $marriage['marriage_date_approximate'] ?? false ),
					'marriage_notes'            => $marriage['marriage_notes'] ?? null,
					'divorce_date'              => $marriage['divorce_date'] ?? null,
					'children'                  => $children_export,
				);
			}
		}

		$export_data[] = array(
			'name'                       => $leader->post_title,
			'slug'                       => $leader->post_name,
			'first_name'                 => get_field( 'first_name', $leader_id ) ?: null,
			'middle_name'                => get_field( 'middle_name', $leader_id ) ?: null,
			'last_name'                  => get_field( 'last_name', $leader_id ) ?: null,
			'gender'                     => get_field( 'gender', $leader_id ) ?: null,
			'birthdate'                  => get_field( 'birthdate', $leader_id ) ?: null,
			'birthdate_approximate'      => (bool) get_field( 'birthdate_approximate', $leader_id ),
			'deathdate'                  => get_field( 'deathdate', $leader_id ) ?: null,
			'deathdate_approximate'      => (bool) get_field( 'deathdate_approximate', $leader_id ),
			'familysearch_id'            => get_field( 'familysearch_id', $leader_id ) ?: null,
			'hometown'                   => get_field( 'hometown', $leader_id ) ?: null,
			'ordained_date'              => get_field( 'ordained_date', $leader_id ) ?: null,
			'ordain_end'                 => get_field( 'ordain_end', $leader_id ) ?: null,
			'ordain_note'                => get_field( 'ordain_note', $leader_id ) ?: null,
			'became_president_date'      => get_field( 'became_president_date', $leader_id ) ?: null,
			'education'                  => get_field( 'education', $leader_id ) ?: null,
			'mission'                    => get_field( 'mission', $leader_id ) ?: null,
			'profession'                 => get_field( 'profession', $leader_id ) ?: null,
			'military'                   => get_field( 'military', $leader_id ) ?: null,
			'polygamist'                 => (bool) get_field( 'polygamist', $leader_id ),
			'number_of_wives'            => get_field( 'number_of_wives', $leader_id ) ?: null,
			'marital_status_at_marriage' => get_field( 'marital_status_at_marriage', $leader_id ) ?: null,
			'marriages'                  => ! empty( $marriages_export ) ? $marriages_export : null,
			'roles'                      => $role_terms,
			'leader_tag_slug'            => $leader_tag_slug,
			'bio'                        => $leader->post_content,
			'featured_image_url'         => $featured_image_url,
		);
	}

	return $export_data;
}

/**
 * Import leaders from array data (from file upload)
 *
 * @param array $leaders_data Array of leader data.
 * @param bool $update_existing Whether to update existing leaders.
 * @param bool $import_images Whether to import featured images.
 * @return array Result array with counts.
 */
function wasmo_import_leaders_from_array( $leaders_data, $update_existing = false, $import_images = false ) {
	$results = array(
		'imported' => 0,
		'updated'  => 0,
		'skipped'  => 0,
		'errors'   => array(),
	);

	foreach ( $leaders_data as $leader_data ) {
		$result = wasmo_import_single_leader_full( $leader_data, $update_existing, $import_images );

		if ( is_wp_error( $result ) ) {
			$error_code = $result->get_error_code();
			$name = isset( $leader_data['name'] ) ? $leader_data['name'] : 'Unknown';

			if ( $error_code === 'exists' ) {
				$results['skipped']++;
			} else {
				$results['errors'][] = $name . ': ' . $result->get_error_message();
			}
		} elseif ( is_array( $result ) && isset( $result['updated'] ) && $result['updated'] ) {
			$results['updated']++;
		} else {
			$results['imported']++;
		}
	}

	return $results;
}

/**
 * Import a single leader with full data support
 *
 * @param array $data Leader data array.
 * @param bool $update_existing Whether to update if exists.
 * @param bool $import_images Whether to import featured image.
 * @return int|array|WP_Error Post ID, result array, or error.
 */
function wasmo_import_single_leader_full( $data, $update_existing = false, $import_images = false ) {
	if ( empty( $data['name'] ) ) {
		return new WP_Error( 'no_name', 'Leader name is required.' );
	}

	// Check if leader already exists
	$existing = get_posts( array(
		'post_type'      => 'saint',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'title'          => $data['name'],
	) );

	$is_update = false;
	$post_id = null;

	if ( ! empty( $existing ) ) {
		if ( ! $update_existing ) {
			return new WP_Error( 'exists', 'Leader already exists.' );
		}
		$post_id = $existing[0]->ID;
		$is_update = true;

		// Update the post
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => isset( $data['bio'] ) ? $data['bio'] : '',
		) );
	} else {
		// Create the post
		$post_data = array(
			'post_title'   => sanitize_text_field( $data['name'] ),
			'post_name'    => isset( $data['slug'] ) ? sanitize_title( $data['slug'] ) : '',
			'post_type'    => 'saint',
			'post_status'  => 'publish',
			'post_content' => isset( $data['bio'] ) ? $data['bio'] : '',
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
	}

	// Update ACF text fields
	$text_fields = array(
		'first_name', 'middle_name', 'last_name', 'hometown',
		'education', 'mission', 'profession', 'military', 'familysearch_id',
	);

	foreach ( $text_fields as $field ) {
		if ( isset( $data[ $field ] ) && $data[ $field ] !== null ) {
			update_field( $field, sanitize_text_field( $data[ $field ] ), $post_id );
		}
	}

	// Update gender
	if ( isset( $data['gender'] ) && $data['gender'] !== null ) {
		update_field( 'gender', sanitize_text_field( $data['gender'] ), $post_id );
	}

	// Update marital status at marriage
	if ( isset( $data['marital_status_at_marriage'] ) && $data['marital_status_at_marriage'] !== null ) {
		update_field( 'marital_status_at_marriage', sanitize_text_field( $data['marital_status_at_marriage'] ), $post_id );
	}

	// Update approximate date flags
	if ( isset( $data['birthdate_approximate'] ) ) {
		$is_approx = $data['birthdate_approximate'] === true || $data['birthdate_approximate'] === 'true' || $data['birthdate_approximate'] === 1;
		update_field( 'birthdate_approximate', $is_approx ? 1 : 0, $post_id );
	}
	if ( isset( $data['deathdate_approximate'] ) ) {
		$is_approx = $data['deathdate_approximate'] === true || $data['deathdate_approximate'] === 'true' || $data['deathdate_approximate'] === 1;
		update_field( 'deathdate_approximate', $is_approx ? 1 : 0, $post_id );
	}

	// Update ordain_note (textarea - preserve line breaks)
	if ( isset( $data['ordain_note'] ) && $data['ordain_note'] !== null ) {
		update_field( 'ordain_note', wp_kses_post( $data['ordain_note'] ), $post_id );
	}

	// Update date fields (already in correct format from export)
	$date_fields = array( 'birthdate', 'deathdate', 'ordained_date', 'ordain_end', 'became_president_date' );

	foreach ( $date_fields as $field ) {
		if ( ! empty( $data[ $field ] ) ) {
			// If it's already in Y-m-d or Y-m-d H:i:s format, use directly
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}/', $data[ $field ] ) ) {
				update_field( $field, $data[ $field ], $post_id );
			} else {
				// Otherwise, try to parse it
				$parsed = wasmo_parse_leader_date( $data[ $field ] );
				if ( $parsed ) {
					update_field( $field, $parsed, $post_id );
				}
			}
		}
	}

	// Handle polygamist
	if ( isset( $data['polygamist'] ) ) {
		$is_polygamist = $data['polygamist'] === true || $data['polygamist'] === 'true' || $data['polygamist'] === 1;
		update_field( 'polygamist', $is_polygamist ? 1 : 0, $post_id );

		if ( $is_polygamist && ! empty( $data['number_of_wives'] ) ) {
			update_field( 'number_of_wives', intval( $data['number_of_wives'] ), $post_id );
		}
	}

	// Handle roles
	if ( ! empty( $data['roles'] ) ) {
		$roles = is_array( $data['roles'] ) ? $data['roles'] : explode( ',', $data['roles'] );
		$term_ids = array();

		foreach ( $roles as $role ) {
			$role = trim( $role );
			// Check if it's already a slug
			$term = get_term_by( 'slug', $role, 'saint-role' );
			if ( $term ) {
				$term_ids[] = $term->term_id;
			} else {
				// Try mapping from group name
				$role_slug = wasmo_map_group_to_role( $role );
				if ( $role_slug ) {
					$term = get_term_by( 'slug', $role_slug, 'saint-role' );
					if ( $term ) {
						$term_ids[] = $term->term_id;
					}
				}
			}
		}

		if ( ! empty( $term_ids ) ) {
			wp_set_post_terms( $post_id, $term_ids, 'saint-role' );
			update_field( 'leader_roles', $term_ids, $post_id );
		}
	}

	// Handle leader tag (by slug)
	if ( ! empty( $data['leader_tag_slug'] ) ) {
		$tag = get_term_by( 'slug', $data['leader_tag_slug'], 'post_tag' );
		if ( $tag ) {
			update_field( 'leader_tag', $tag->term_id, $post_id );
		}
	} elseif ( empty( $data['leader_tag_slug'] ) ) {
		// Try to match by name if no tag slug provided
		$tag = get_term_by( 'name', $data['name'], 'post_tag' );
		if ( $tag ) {
			update_field( 'leader_tag', $tag->term_id, $post_id );
		}
	}

	// Handle featured image
	if ( $import_images && ! empty( $data['featured_image_url'] ) ) {
		// Only import if doesn't already have one (or if updating)
		if ( ! has_post_thumbnail( $post_id ) || $is_update ) {
			$image_result = wasmo_import_leader_featured_image( $post_id, $data['featured_image_url'], $data['name'] );
			if ( is_wp_error( $image_result ) ) {
				// Log error but don't fail the whole import
				error_log( 'Failed to import image for ' . $data['name'] . ': ' . $image_result->get_error_message() );
			}
		}
	}

	// Handle marriages import
	if ( ! empty( $data['marriages'] ) && is_array( $data['marriages'] ) ) {
		$marriages_to_import = array();

		foreach ( $data['marriages'] as $marriage_data ) {
			// Check if spouse is a saint (default true for backwards compatibility)
			$spouse_is_saint = isset( $marriage_data['spouse_is_saint'] ) ? (bool) $marriage_data['spouse_is_saint'] : true;
			
			$spouse_id = null;
			$spouse_name_text = null;

			if ( $spouse_is_saint && ! empty( $marriage_data['spouse_name'] ) ) {
				// Look up spouse by name in saints database
				$spouse_post = wasmo_find_saint_by_fuzzy_title( $marriage_data['spouse_name'] );
				if ( $spouse_post ) {
					$spouse_id = $spouse_post->ID;
				}
			} elseif ( ! $spouse_is_saint && ! empty( $marriage_data['spouse_name'] ) ) {
				// Non-saint spouse - store name as text
				$spouse_name_text = $marriage_data['spouse_name'];
			}

			// Handle children - support both new format (array of objects) and legacy (array of names)
			$children_to_import = array();
			if ( ! empty( $marriage_data['children'] ) && is_array( $marriage_data['children'] ) ) {
				foreach ( $marriage_data['children'] as $child ) {
					if ( is_array( $child ) && isset( $child['name'] ) ) {
						// New format with name and birthdate
						$child_birthdate = $child['birthdate'] ?? '';
						// Normalize child birthdate to Y-m-d format
						if ( ! empty( $child_birthdate ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $child_birthdate ) ) {
							$parsed = wasmo_parse_leader_date( $child_birthdate );
							$child_birthdate = $parsed ?: $child_birthdate;
						}
						$children_to_import[] = array(
							'child_name'      => $child['name'],
							'child_birthdate' => $child_birthdate,
							'child_link'      => null,
						);
					} elseif ( is_string( $child ) ) {
						// Legacy format - just a name string
						$children_to_import[] = array(
							'child_name'      => $child,
							'child_birthdate' => '',
							'child_link'      => null,
						);
					}
				}
			}

			// Only add marriage if we have a spouse (saint or name) or a date
			if ( $spouse_id || $spouse_name_text || ! empty( $marriage_data['marriage_date'] ) ) {
				// Handle marriage_date_approximate - can be boolean, string, or integer
				$marriage_date_approx = $marriage_data['marriage_date_approximate'] ?? false;
				$marriage_date_approx = $marriage_date_approx === true || $marriage_date_approx === 'true' || $marriage_date_approx === 1 || $marriage_date_approx === '1';

				// Normalize all dates to ensure consistent Y-m-d format
				$marriage_date = $marriage_data['marriage_date'] ?? '';
				if ( ! empty( $marriage_date ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $marriage_date ) ) {
					$parsed = wasmo_parse_leader_date( $marriage_date );
					$marriage_date = $parsed ?: $marriage_date;
				}
				
				$divorce_date = $marriage_data['divorce_date'] ?? '';
				if ( ! empty( $divorce_date ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $divorce_date ) ) {
					$parsed = wasmo_parse_leader_date( $divorce_date );
					$divorce_date = $parsed ?: $divorce_date;
				}
				
				$spouse_birthdate = '';
				if ( ! $spouse_is_saint && ! empty( $marriage_data['spouse_birthdate'] ) ) {
					$spouse_birthdate = $marriage_data['spouse_birthdate'];
					if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $spouse_birthdate ) ) {
						$parsed = wasmo_parse_leader_date( $spouse_birthdate );
						$spouse_birthdate = $parsed ?: $spouse_birthdate;
					}
				}

				$marriages_to_import[] = array(
					'spouse_is_saint'           => $spouse_is_saint ? 1 : 0,
					'spouse'                    => $spouse_id ? array( $spouse_id ) : null,
					'spouse_name'               => $spouse_name_text,
					'spouse_birthdate'          => $spouse_birthdate,
					'marriage_date'             => $marriage_date,
					'marriage_date_approximate' => $marriage_date_approx ? 1 : 0,
					'marriage_notes'            => $marriage_data['marriage_notes'] ?? '',
					'divorce_date'              => $divorce_date,
					'children'                  => $children_to_import,
				);
			}
		}

		if ( ! empty( $marriages_to_import ) ) {
			// Get existing marriages to avoid duplicates
			$existing_marriages = get_field( 'marriages', $post_id ) ?: array();
			
			// Build lookup arrays for existing marriages (both saint IDs and non-saint names)
			$existing_spouse_ids = array();
			$existing_spouse_names = array();
			foreach ( $existing_marriages as $em ) {
				$em_is_saint = isset( $em['spouse_is_saint'] ) ? (bool) $em['spouse_is_saint'] : true;
				if ( $em_is_saint ) {
					$em_spouse = is_array( $em['spouse'] ?? null ) ? ( $em['spouse'][0] ?? null ) : ( $em['spouse'] ?? null );
					if ( $em_spouse ) {
						$existing_spouse_ids[] = intval( $em_spouse );
					}
				} else {
					$em_name = strtolower( trim( $em['spouse_name'] ?? '' ) );
					if ( $em_name ) {
						$existing_spouse_names[] = $em_name;
					}
				}
			}

			// Only add marriages that don't already exist
			foreach ( $marriages_to_import as $new_marriage ) {
				$new_is_saint = (bool) ( $new_marriage['spouse_is_saint'] ?? true );
				$new_spouse_id = is_array( $new_marriage['spouse'] ?? null ) ? ( $new_marriage['spouse'][0] ?? null ) : null;
				$new_spouse_name = strtolower( trim( $new_marriage['spouse_name'] ?? '' ) );
				
				$already_exists = false;
				
				if ( $new_is_saint && $new_spouse_id ) {
					$already_exists = in_array( intval( $new_spouse_id ), $existing_spouse_ids, true );
				} elseif ( ! $new_is_saint && $new_spouse_name ) {
					$already_exists = in_array( $new_spouse_name, $existing_spouse_names, true );
				}
				
				if ( $already_exists ) {
					// Marriage already exists, optionally update it
					if ( $is_update ) {
						// Find and update the existing marriage entry
						foreach ( $existing_marriages as &$em ) {
							$em_is_saint = isset( $em['spouse_is_saint'] ) ? (bool) $em['spouse_is_saint'] : true;
							$match = false;
							
							if ( $new_is_saint && $em_is_saint ) {
								$em_spouse = is_array( $em['spouse'] ?? null ) ? ( $em['spouse'][0] ?? null ) : ( $em['spouse'] ?? null );
								$match = ( intval( $em_spouse ) === intval( $new_spouse_id ) );
							} elseif ( ! $new_is_saint && ! $em_is_saint ) {
								$em_name = strtolower( trim( $em['spouse_name'] ?? '' ) );
								$match = ( $em_name === $new_spouse_name );
							}
							
							if ( $match ) {
								// Update marriage date if provided
								if ( ! empty( $new_marriage['marriage_date'] ) ) {
									$em['marriage_date'] = $new_marriage['marriage_date'];
								}
								// Update marriage date approximate flag
								if ( isset( $new_marriage['marriage_date_approximate'] ) ) {
									$em['marriage_date_approximate'] = $new_marriage['marriage_date_approximate'];
								}
								// Update notes if provided
								if ( ! empty( $new_marriage['marriage_notes'] ) ) {
									$em['marriage_notes'] = $new_marriage['marriage_notes'];
								}
								// Update divorce date if provided
								if ( ! empty( $new_marriage['divorce_date'] ) ) {
									$em['divorce_date'] = $new_marriage['divorce_date'];
								}
								// Update children if provided
								if ( ! empty( $new_marriage['children'] ) ) {
									$em['children'] = $new_marriage['children'];
								}
								break;
							}
						}
						unset( $em ); // Break the reference
					}
				} else {
					// Add new marriage
					$existing_marriages[] = $new_marriage;
				}
			}

			update_field( 'marriages', $existing_marriages, $post_id );
		}
	}

	if ( $is_update ) {
		return array( 'post_id' => $post_id, 'updated' => true );
	}

	return $post_id;
}

/**
 * Import featured image for a leader from URL
 *
 * @param int $post_id The post ID.
 * @param string $image_url The image URL.
 * @param string $leader_name The leader's name for alt text.
 * @return int|WP_Error Attachment ID or error.
 */
function wasmo_import_leader_featured_image( $post_id, $image_url, $leader_name ) {
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	// Download the image
	$tmp = download_url( $image_url, 60 );

	if ( is_wp_error( $tmp ) ) {
		return $tmp;
	}

	// Get file extension from URL
	$path_info = pathinfo( parse_url( $image_url, PHP_URL_PATH ) );
	$ext = isset( $path_info['extension'] ) ? $path_info['extension'] : 'jpg';

	$file_array = array(
		'name'     => sanitize_file_name( $leader_name . '-portrait.' . $ext ),
		'tmp_name' => $tmp,
	);

	// Upload to media library
	$attachment_id = media_handle_sideload( $file_array, $post_id, $leader_name . ' Portrait' );

	// Clean up temp file
	if ( file_exists( $tmp ) ) {
		@unlink( $tmp );
	}

	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	// Set as featured image
	set_post_thumbnail( $post_id, $attachment_id );

	// Set alt text
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $leader_name );

	return $attachment_id;
}

// ============================================
// CSV IMPORT FOR POLYGAMY DATA
// ============================================

/**
 * Add admin menu page for polygamy CSV import
 */
function wasmo_add_polygamy_import_page() {
	add_submenu_page(
		'edit.php?post_type=saint',
		'Import Polygamy Data',
		'Import Polygamy CSV',
		'manage_options',
		'import-polygamy',
		'wasmo_render_polygamy_import_page'
	);
}
add_action( 'admin_menu', 'wasmo_add_polygamy_import_page' );

/**
 * Render the polygamy CSV import page
 */
function wasmo_render_polygamy_import_page() {
	$message = '';

	// Handle summary.csv import
	if ( isset( $_POST['wasmo_import_summary'] ) && check_admin_referer( 'wasmo_import_summary_nonce' ) ) {
		if ( ! empty( $_FILES['summary_csv']['tmp_name'] ) ) {
			$result = wasmo_import_polygamy_summary_csv( $_FILES['summary_csv']['tmp_name'] );
			$message = '<div class="notice notice-success"><p>';
			$message .= sprintf( 'Summary import complete! %d updated, %d skipped, %d not found.', 
				$result['updated'], $result['skipped'], $result['not_found'] );
			$message .= '</p></div>';
			
			if ( ! empty( $result['errors'] ) ) {
				$message .= '<div class="notice notice-warning"><p>Some errors:</p><ul>';
				foreach ( array_slice( $result['errors'], 0, 10 ) as $error ) {
					$message .= '<li>' . esc_html( $error ) . '</li>';
				}
				$message .= '</ul></div>';
			}
		}
	}

	// Handle wives CSV import
	if ( isset( $_POST['wasmo_import_wives'] ) && check_admin_referer( 'wasmo_import_wives_nonce' ) ) {
		if ( ! empty( $_FILES['wives_csv']['tmp_name'] ) && ! empty( $_POST['leader_name'] ) ) {
			$leader_name = sanitize_text_field( $_POST['leader_name'] );
			$result = wasmo_import_wives_csv( $_FILES['wives_csv']['tmp_name'], $leader_name );
			
			$message = '<div class="notice notice-success"><p>';
			$message .= sprintf( 'Wives import complete for %s! %d created, %d updated, %d skipped.', 
				$leader_name, $result['created'], $result['updated'], $result['skipped'] );
			$message .= '</p></div>';
			
			if ( ! empty( $result['errors'] ) ) {
				$message .= '<div class="notice notice-warning"><p>Some errors:</p><ul>';
				foreach ( $result['errors'] as $error ) {
					$message .= '<li>' . esc_html( $error ) . '</li>';
				}
				$message .= '</ul></div>';
			}
		}
	}

	// Handle batch wives import
	if ( isset( $_POST['wasmo_import_wives_batch'] ) && check_admin_referer( 'wasmo_import_wives_batch_nonce' ) ) {
		if ( ! empty( $_FILES['wives_csv_files']['tmp_name'][0] ) ) {
			$total = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array() );
			
			foreach ( $_FILES['wives_csv_files']['tmp_name'] as $i => $tmp_name ) {
				if ( empty( $tmp_name ) ) continue;
				
				$filename = $_FILES['wives_csv_files']['name'][$i];
				// Extract leader name from filename (e.g., "joseph-smith-wives.csv" -> "Joseph Smith")
				$leader_slug = preg_replace( '/-wives\.csv$/', '', $filename );
				$leader_name = ucwords( str_replace( '-', ' ', $leader_slug ) );
				
				// Handle middle initials: "Russell M Nelson" -> "Russell M. Nelson"
				$leader_name = preg_replace( '/\b([A-Z])\s/', '$1. ', $leader_name );
				
				$result = wasmo_import_wives_csv( $tmp_name, $leader_name );
				$total['created'] += $result['created'];
				$total['updated'] += $result['updated'];
				$total['skipped'] += $result['skipped'];
				$total['errors'] = array_merge( $total['errors'], $result['errors'] );
			}
			
			$message = '<div class="notice notice-success"><p>';
			$message .= sprintf( 'Batch import complete! %d wives created, %d updated, %d skipped.', 
				$total['created'], $total['updated'], $total['skipped'] );
			$message .= '</p></div>';
		}
	}

	?>
	<div class="wrap">
		<h1>Import Polygamy Data</h1>
		
		<?php echo $message; ?>

		<!-- Summary CSV Import -->
		<div class="card" style="max-width: 800px; margin-bottom: 20px; background: #f0f3e7;">
			<h2>📊 Import Summary CSV</h2>
			<p>
				Upload <code>summary.csv</code> to enrich existing saints with FamilySearch IDs and other polygamy statistics.
				This will match by name and update existing records only.
			</p>
			<p><strong>Expected columns:</strong> Name, FamilySearch ID, Number of Wives, Number of Children, Teenage Brides, etc.</p>
			
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'wasmo_import_summary_nonce' ); ?>
				<p>
					<label for="summary_csv"><strong>Select summary.csv:</strong></label><br>
					<input type="file" name="summary_csv" id="summary_csv" accept=".csv" style="margin-top: 5px;">
				</p>
				<p>
					<button type="submit" name="wasmo_import_summary" class="button button-primary">
						Import Summary Data
					</button>
				</p>
			</form>
		</div>

		<!-- Single Wives CSV Import -->
		<div class="card" style="max-width: 800px; margin-bottom: 20px; background: #e7f0f3;">
			<h2>👰 Import Single Wives CSV</h2>
			<p>
				Upload a <code>*-wives.csv</code> file to create wife records and link them to a specific leader.
			</p>
			
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'wasmo_import_wives_nonce' ); ?>
				<p>
					<label for="leader_name"><strong>Leader Name:</strong></label><br>
					<input type="text" name="leader_name" id="leader_name" placeholder="e.g., Joseph Smith" style="width: 300px; margin-top: 5px;">
				</p>
				<p>
					<label for="wives_csv"><strong>Select wives CSV:</strong></label><br>
					<input type="file" name="wives_csv" id="wives_csv" accept=".csv" style="margin-top: 5px;">
				</p>
				<p>
					<button type="submit" name="wasmo_import_wives" class="button button-primary">
						Import Wives
					</button>
				</p>
			</form>
		</div>

		<!-- Batch Wives CSV Import -->
		<div class="card" style="max-width: 800px; margin-bottom: 20px; background: #f3e7f0;">
			<h2>📁 Batch Import Wives CSVs</h2>
			<p>
				Upload multiple <code>*-wives.csv</code> files at once. Leader names will be extracted from filenames.
				<br><em>Example: <code>joseph-smith-wives.csv</code> → Leader "Joseph Smith"</em>
			</p>
			
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'wasmo_import_wives_batch_nonce' ); ?>
				<p>
					<label for="wives_csv_files"><strong>Select CSV files:</strong></label><br>
					<input type="file" name="wives_csv_files[]" id="wives_csv_files" accept=".csv" multiple style="margin-top: 5px;">
				</p>
				<p>
					<button type="submit" name="wasmo_import_wives_batch" class="button button-primary">
						Import All Wives Files
					</button>
				</p>
			</form>
		</div>

		<!-- CSV Format Reference -->
		<div class="card" style="max-width: 800px;">
			<h2>📋 CSV Format Reference</h2>
			
			<h3>summary.csv columns:</h3>
			<table class="widefat" style="max-width: 600px;">
				<tbody>
					<tr><td><code>Name</code></td><td>Leader's full name (for matching)</td></tr>
					<tr><td><code>FamilySearch ID</code></td><td>FamilySearch person ID</td></tr>
					<tr><td><code>Birth Date</code></td><td>Birth date</td></tr>
					<tr><td><code>Death Date</code></td><td>Death date</td></tr>
					<tr><td><code>Number of Wives</code></td><td>Total wife count</td></tr>
					<tr><td><code>CSV File</code></td><td>Associated wives CSV filename</td></tr>
				</tbody>
			</table>

			<h3>*-wives.csv columns:</h3>
			<table class="widefat" style="max-width: 600px;">
				<tbody>
					<tr><td><code>Wife Name</code></td><td>Wife's full name</td></tr>
					<tr><td><code>Birthday</code></td><td>Birth date</td></tr>
					<tr><td><code>Marriage Date</code></td><td>Wedding date</td></tr>
					<tr><td><code>Death Date</code></td><td>Death date</td></tr>
					<tr><td><code>Divorce Date</code></td><td>Divorce date (if any)</td></tr>
					<tr><td><code>Number of Children*</code></td><td>Children from this marriage</td></tr>
					<tr><td><code>Marital Status at Marriage</code></td><td>Never Married, Widow, etc.</td></tr>
					<tr><td><code>Teenage Bride</code></td><td>TRUE/FALSE</td></tr>
					<tr><td><code>FamilySearch ID</code></td><td>Wife's FamilySearch ID</td></tr>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}

/**
 * Import polygamy summary CSV to enrich existing saints
 *
 * @param string $csv_path Path to the CSV file.
 * @return array Results array.
 */
function wasmo_import_polygamy_summary_csv( $csv_path ) {
	$results = array(
		'updated'   => 0,
		'skipped'   => 0,
		'not_found' => 0,
		'errors'    => array(),
	);

	$handle = fopen( $csv_path, 'r' );
	if ( ! $handle ) {
		$results['errors'][] = 'Could not open CSV file';
		return $results;
	}

	// Read header row
	$headers = fgetcsv( $handle );
	if ( ! $headers ) {
		fclose( $handle );
		$results['errors'][] = 'Could not read CSV headers';
		return $results;
	}

	// Normalize headers
	$headers = array_map( 'trim', $headers );
	$headers = array_map( 'strtolower', $headers );
	$headers = array_map( function( $h ) {
		return str_replace( ' ', '_', $h );
	}, $headers );

	$header_count = count( $headers );
	
	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		// Skip empty rows
		if ( empty( $row ) || ( count( $row ) === 1 && empty( trim( $row[0] ) ) ) ) {
			continue;
		}
		
		// Ensure row has same number of elements as headers
		$row_count = count( $row );
		if ( $row_count < $header_count ) {
			// Pad with empty values
			$row = array_pad( $row, $header_count, '' );
		} elseif ( $row_count > $header_count ) {
			// Trim extra columns
			$row = array_slice( $row, 0, $header_count );
		}
		
		$data = array_combine( $headers, $row );
		
		$name = trim( $data['name'] ?? '' );
		if ( empty( $name ) ) continue;

		// Find existing saint by name
		$existing = get_posts( array(
			'post_type'      => 'saint',
			'posts_per_page' => 1,
			'title'          => $name,
			'post_status'    => 'any',
		) );

		if ( empty( $existing ) ) {
			$results['not_found']++;
			$results['errors'][] = "Not found: $name";
			continue;
		}

		$saint_id = $existing[0]->ID;

		// Update FamilySearch ID
		if ( ! empty( $data['familysearch_id'] ) ) {
			update_field( 'familysearch_id', trim( $data['familysearch_id'] ), $saint_id );
		}

		// Update gender (all in summary are male leaders)
		update_field( 'gender', 'male', $saint_id );

		$results['updated']++;
	}

	fclose( $handle );
	return $results;
}

/**
 * Import wives from a CSV file
 * 
 * NEW ARCHITECTURE: Marriage data is stored on the WIFE's record, not the husband's.
 * This function creates wife saints and adds their marriage entry (with husband as spouse).
 *
 * @param string $csv_path Path to the CSV file.
 * @param string $leader_name Name of the husband/leader.
 * @return array Results array.
 */
function wasmo_import_wives_csv( $csv_path, $leader_name ) {
	$results = array(
		'created'  => 0,
		'updated'  => 0,
		'skipped'  => 0,
		'children' => 0,
		'errors'   => array(),
	);

	// Find the leader (husband) - use fuzzy title match
	$leader = wasmo_find_saint_by_fuzzy_title( $leader_name );

	if ( ! $leader ) {
		$results['errors'][] = "Leader not found: $leader_name (tried fuzzy matching)";
		return $results;
	}

	$leader_id = $leader->ID;

	$handle = fopen( $csv_path, 'r' );
	if ( ! $handle ) {
		$results['errors'][] = 'Could not open CSV file';
		return $results;
	}

	// Read header row
	$headers = fgetcsv( $handle );
	if ( ! $headers ) {
		fclose( $handle );
		$results['errors'][] = 'Could not read CSV headers';
		return $results;
	}

	// Normalize headers
	$headers = array_map( 'trim', $headers );
	$headers = array_map( 'strtolower', $headers );
	$headers = array_map( function( $h ) {
		return str_replace( ' ', '_', $h );
	}, $headers );

	$header_count = count( $headers );
	
	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		// Skip empty rows
		if ( empty( $row ) || ( count( $row ) === 1 && empty( trim( $row[0] ) ) ) ) {
			continue;
		}
		
		// Ensure row has same number of elements as headers
		$row_count = count( $row );
		if ( $row_count < $header_count ) {
			// Pad with empty values
			$row = array_pad( $row, $header_count, '' );
		} elseif ( $row_count > $header_count ) {
			// Trim extra columns
			$row = array_slice( $row, 0, $header_count );
		}
		
		$data = array_combine( $headers, $row );
		
		$wife_name = trim( $data['wife_name'] ?? '' );
		if ( empty( $wife_name ) ) continue;

		// Create or find wife saint record
		$wife_result = wasmo_create_or_update_wife( $data, $leader_name );
		
		if ( is_wp_error( $wife_result ) ) {
			$results['errors'][] = "$wife_name: " . $wife_result->get_error_message();
			continue;
		}

		$wife_id = $wife_result['id'];
		
		if ( $wife_result['created'] ) {
			$results['created']++;
		} else {
			$results['updated']++;
		}

		// Parse marriage date with approximate detection
		$marriage_date_result = wasmo_parse_leader_date( $data['marriage_date'] ?? '', true );
		$divorce_date = wasmo_parse_leader_date( $data['divorce_date'] ?? '' );
		
		// Get children count and create placeholder children
		$children_count = 0;
		foreach ( array_keys( $data ) as $key ) {
			if ( strpos( $key, 'number_of_children' ) !== false && is_numeric( $data[$key] ) ) {
				$children_count = intval( $data[$key] );
				break;
			}
		}
		
		// Generate placeholder children entries
		$children_entries = array();
		for ( $i = 1; $i <= $children_count; $i++ ) {
			$children_entries[] = array(
				'child_name'      => "[Child $i]",
				'child_birthdate' => '',
				'child_link'      => array(),
			);
			$results['children']++;
		}

		// Build marriage data for WIFE's record (husband as spouse)
		$new_marriage = array(
			'spouse'                    => array( $leader_id ), // Husband is the spouse
			'marriage_date'             => $marriage_date_result['date'],
			'marriage_date_approximate' => $marriage_date_result['approximate'] ? 1 : 0,
			'divorce_date'              => $divorce_date,
			'marriage_notes'            => '',
			'children'                  => $children_entries,
		);

		// Get wife's existing marriages
		$wife_marriages = get_field( 'marriages', $wife_id ) ?: array();
		
		// Check if this marriage already exists (avoid duplicates)
		$already_exists = false;
		foreach ( $wife_marriages as $em ) {
			$em_spouse = is_array( $em['spouse'] ?? null ) ? ( $em['spouse'][0] ?? null ) : ( $em['spouse'] ?? null );
			if ( intval( $em_spouse ) === intval( $leader_id ) ) {
				$already_exists = true;
				break;
			}
		}
		
		// Add marriage to wife's record if not exists
		if ( ! $already_exists ) {
			$wife_marriages[] = $new_marriage;
			update_field( 'marriages', $wife_marriages, $wife_id );
		}
	}

	fclose( $handle );

	return $results;
}

/**
 * Create or update a wife saint record
 *
 * @param array $data Wife data from CSV.
 * @param string $husband_name For context.
 * @return array|WP_Error Array with 'id' and 'created' flag, or error.
 */
function wasmo_create_or_update_wife( $data, $husband_name ) {
	$wife_name = trim( $data['wife_name'] ?? '' );
	
	if ( empty( $wife_name ) ) {
		return new WP_Error( 'no_name', 'Wife name is required' );
	}

	// Check if wife already exists - use fuzzy title match to avoid duplicates
	$existing = wasmo_find_saint_by_fuzzy_title( $wife_name );

	$created = false;
	$wife_id = null;

	if ( $existing ) {
		$wife_id = $existing->ID;
	} else {
		// Create new wife post
		$post_data = array(
			'post_title'  => $wife_name,
			'post_type'   => 'saint',
			'post_status' => 'publish',
			'post_content' => '',
		);

		$wife_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $wife_id ) ) {
			return $wife_id;
		}

		$created = true;
	}

	// Update ACF fields
	update_field( 'gender', 'female', $wife_id );

	// Parse name parts
	$name_parts = explode( ' ', $wife_name );
	if ( count( $name_parts ) >= 2 ) {
		update_field( 'first_name', $name_parts[0], $wife_id );
		update_field( 'last_name', end( $name_parts ), $wife_id );
		if ( count( $name_parts ) > 2 ) {
			$middle = array_slice( $name_parts, 1, -1 );
			update_field( 'middle_name', implode( ' ', $middle ), $wife_id );
		}
	}

	// Dates (with approximate detection)
	$birthdate_result = wasmo_parse_leader_date( $data['birthday'] ?? '', true );
	if ( $birthdate_result['date'] ) {
		update_field( 'birthdate', $birthdate_result['date'], $wife_id );
		update_field( 'birthdate_approximate', $birthdate_result['approximate'] ? 1 : 0, $wife_id );
	}

	$deathdate_result = wasmo_parse_leader_date( $data['death_date'] ?? '', true );
	if ( $deathdate_result['date'] ) {
		update_field( 'deathdate', $deathdate_result['date'], $wife_id );
		update_field( 'deathdate_approximate', $deathdate_result['approximate'] ? 1 : 0, $wife_id );
	}

	// FamilySearch ID
	if ( ! empty( $data['familysearch_id'] ) ) {
		update_field( 'familysearch_id', trim( $data['familysearch_id'] ), $wife_id );
	}

	// Marital status at marriage
	$status = strtolower( trim( $data['marital_status_at_marriage'] ?? $data['marital_status'] ?? '' ) );
	if ( $status ) {
		$status_map = array(
			'never married' => 'never_married',
			'widow'         => 'widow',
			'divorced'      => 'divorced',
			'separated'     => 'separated',
		);
		$status_value = $status_map[ $status ] ?? 'never_married';
		update_field( 'marital_status_at_marriage', $status_value, $wife_id );
	}

	// Set wife role
	$wife_term = get_term_by( 'slug', 'wife', 'saint-role' );
	if ( $wife_term ) {
		wp_set_post_terms( $wife_id, array( $wife_term->term_id ), 'saint-role', true );
	}

	return array( 'id' => $wife_id, 'created' => $created );
}

// ============================================
// MARRIAGE DATA MIGRATION (Men -> Women)
// ============================================

/**
 * Preview what the marriage migration will do
 * Shows changes without actually modifying data
 *
 * @return array Preview data with counts and details
 */
function wasmo_preview_marriage_migration() {
	$preview = array(
		'men_with_marriages'   => 0,
		'total_marriages'      => 0,
		'total_children'       => 0,
		'wives_to_update'      => 0,
		'orphaned_marriages'   => 0,
		'details'              => array(),
	);

	// Find all male saints with marriages
	$men = get_posts( array(
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

	foreach ( $men as $man ) {
		$marriages = get_field( 'marriages', $man->ID );
		if ( empty( $marriages ) || ! is_array( $marriages ) ) {
			continue;
		}

		$preview['men_with_marriages']++;
		$man_detail = array(
			'id'         => $man->ID,
			'name'       => $man->post_title,
			'marriages'  => array(),
		);

		foreach ( $marriages as $marriage ) {
			$preview['total_marriages']++;
			
			$spouse_field = $marriage['spouse'] ?? null;
			$spouse_id = is_array( $spouse_field ) ? ( $spouse_field[0] ?? null ) : $spouse_field;
			$children = $marriage['children'] ?? array();
			$children_count = is_array( $children ) ? count( $children ) : 0;
			$preview['total_children'] += $children_count;

			if ( $spouse_id ) {
				$spouse_post = get_post( $spouse_id );
				if ( $spouse_post && $spouse_post->post_type === 'saint' ) {
					$preview['wives_to_update']++;
					$man_detail['marriages'][] = array(
						'wife_id'        => $spouse_id,
						'wife_name'      => $spouse_post->post_title,
						'marriage_date'  => $marriage['marriage_date'] ?? '',
						'children_count' => $children_count,
					);
				} else {
					$preview['orphaned_marriages']++;
				}
			} else {
				$preview['orphaned_marriages']++;
			}
		}

		if ( ! empty( $man_detail['marriages'] ) ) {
			$preview['details'][] = $man_detail;
		}
	}

	return $preview;
}

/**
 * Execute the marriage data migration from men to women
 * Moves marriage entries (including children) from husband's record to wife's record
 *
 * @param bool $dry_run If true, only simulate without making changes
 * @return array Results with counts and any errors
 */
function wasmo_migrate_marriages_to_wives( $dry_run = false ) {
	$results = array(
		'processed_men'      => 0,
		'migrated_marriages' => 0,
		'migrated_children'  => 0,
		'cleared_men'        => 0,
		'errors'             => array(),
		'log'                => array(),
	);

	// Find all male saints with marriages
	$men = get_posts( array(
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

	foreach ( $men as $man ) {
		$marriages = get_field( 'marriages', $man->ID );
		if ( empty( $marriages ) || ! is_array( $marriages ) ) {
			continue;
		}

		$results['processed_men']++;
		$results['log'][] = "Processing: {$man->post_title} (ID: {$man->ID}) - " . count( $marriages ) . " marriages";

		foreach ( $marriages as $marriage ) {
			$spouse_field = $marriage['spouse'] ?? null;
			$spouse_id = is_array( $spouse_field ) ? ( $spouse_field[0] ?? null ) : $spouse_field;

			if ( ! $spouse_id ) {
				$results['errors'][] = "Man {$man->post_title}: Marriage has no spouse linked";
				continue;
			}

			$spouse_post = get_post( $spouse_id );
			if ( ! $spouse_post || $spouse_post->post_type !== 'saint' ) {
				$results['errors'][] = "Man {$man->post_title}: Spouse ID {$spouse_id} not found or not a saint";
				continue;
			}

			// Build the new marriage entry for the wife (with husband as spouse)
			$new_marriage = array(
				'spouse'                    => array( $man->ID ), // Husband is now the spouse
				'marriage_date'             => $marriage['marriage_date'] ?? '',
				'marriage_date_approximate' => $marriage['marriage_date_approximate'] ?? 0,
				'divorce_date'              => $marriage['divorce_date'] ?? '',
				'marriage_notes'            => $marriage['marriage_notes'] ?? '',
				'children'                  => $marriage['children'] ?? array(),
			);

			$children_count = is_array( $new_marriage['children'] ) ? count( $new_marriage['children'] ) : 0;

			if ( ! $dry_run ) {
				// Get wife's existing marriages (if any)
				$wife_marriages = get_field( 'marriages', $spouse_id );
				if ( ! is_array( $wife_marriages ) ) {
					$wife_marriages = array();
				}

				// Check if this marriage already exists (avoid duplicates)
				$already_exists = false;
				foreach ( $wife_marriages as $existing ) {
					$existing_spouse = is_array( $existing['spouse'] ?? null ) ? ( $existing['spouse'][0] ?? null ) : ( $existing['spouse'] ?? null );
					if ( intval( $existing_spouse ) === $man->ID ) {
						$already_exists = true;
						break;
					}
				}

				if ( ! $already_exists ) {
					$wife_marriages[] = $new_marriage;
					update_field( 'marriages', $wife_marriages, $spouse_id );
					$results['migrated_marriages']++;
					$results['migrated_children'] += $children_count;
					$results['log'][] = "  → Migrated to {$spouse_post->post_title}: marriage + {$children_count} children";
				} else {
					$results['log'][] = "  → Skipped {$spouse_post->post_title}: marriage already exists";
				}
			} else {
				$results['migrated_marriages']++;
				$results['migrated_children'] += $children_count;
				$results['log'][] = "  [DRY RUN] Would migrate to {$spouse_post->post_title}: marriage + {$children_count} children";
			}
		}

		// Clear marriages from man's record (backup first)
		if ( ! $dry_run ) {
			// Store backup in post meta
			update_post_meta( $man->ID, '_marriages_backup_' . date( 'Y-m-d_H-i-s' ), $marriages );
			// Clear the marriages field
			update_field( 'marriages', array(), $man->ID );
			$results['cleared_men']++;
			$results['log'][] = "  ✓ Cleared marriages from {$man->post_title}";
		} else {
			$results['log'][] = "  [DRY RUN] Would clear marriages from {$man->post_title}";
		}
	}

	return $results;
}
