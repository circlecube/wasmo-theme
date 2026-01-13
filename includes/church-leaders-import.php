<?php
/**
 * Church Leaders JSON Import/Export
 * 
 * Admin page for importing/exporting church leaders from/to JSON data.
 *
 * @package wasmo
 */

/**
 * Add admin menu page for leader import
 */
function wasmo_add_leader_import_page() {
	add_submenu_page(
		'edit.php?post_type=church-leader',
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
				$update_existing = isset( $_POST['update_existing'] ) && $_POST['update_existing'];
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
	$existing_count = wp_count_posts( 'church-leader' )->publish;
	?>
	<div class="wrap">
		<h1>Import/Export Church Leaders</h1>
		
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
						<input type="checkbox" name="update_existing" value="1">
						Update existing leaders (matched by name) instead of skipping them
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
	</div>
	<?php
}

/**
 * Parse various date formats to Y-m-d
 *
 * @param string $date_string Date string in various formats.
 * @return string|null Date in Y-m-d format, or null if unparseable.
 */
function wasmo_parse_leader_date( $date_string ) {
	if ( empty( $date_string ) ) {
		return null;
	}

	// Try to parse the date
	$timestamp = strtotime( $date_string );
	
	if ( $timestamp === false ) {
		return null;
	}

	return date( 'Y-m-d', $timestamp );
}

/**
 * Map JSON group names to leader-role taxonomy slugs
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
	header( 'Content-Disposition: attachment; filename="church-leaders-export-' . date( 'Y-m-d' ) . '.json"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	echo json_encode( $leaders_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}
add_action( 'wp_ajax_wasmo_export_leaders_json', 'wasmo_export_leaders_json_ajax' );

/**
 * Get all leaders data for export
 *
 * @return array Array of leader data.
 */
function wasmo_get_all_leaders_export_data() {
	$leaders = get_posts( array(
		'post_type'      => 'church-leader',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	$export_data = array();

	foreach ( $leaders as $leader ) {
		$leader_id = $leader->ID;

		// Get roles
		$role_terms = wp_get_post_terms( $leader_id, 'leader-role', array( 'fields' => 'slugs' ) );

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

		$export_data[] = array(
			'name'                  => $leader->post_title,
			'slug'                  => $leader->post_name,
			'first_name'            => get_field( 'first_name', $leader_id ) ?: null,
			'middle_name'           => get_field( 'middle_name', $leader_id ) ?: null,
			'last_name'             => get_field( 'last_name', $leader_id ) ?: null,
			'birthdate'             => get_field( 'birthdate', $leader_id ) ?: null,
			'deathdate'             => get_field( 'deathdate', $leader_id ) ?: null,
			'hometown'              => get_field( 'hometown', $leader_id ) ?: null,
			'ordained_date'         => get_field( 'ordained_date', $leader_id ) ?: null,
			'ordain_end'            => get_field( 'ordain_end', $leader_id ) ?: null,
			'ordain_note'           => get_field( 'ordain_note', $leader_id ) ?: null,
			'became_president_date' => get_field( 'became_president_date', $leader_id ) ?: null,
			'education'             => get_field( 'education', $leader_id ) ?: null,
			'mission'               => get_field( 'mission', $leader_id ) ?: null,
			'profession'            => get_field( 'profession', $leader_id ) ?: null,
			'military'              => get_field( 'military', $leader_id ) ?: null,
			'polygamist'            => (bool) get_field( 'polygamist', $leader_id ),
			'number_of_wives'       => get_field( 'number_of_wives', $leader_id ) ?: null,
			'roles'                 => $role_terms,
			'leader_tag_slug'       => $leader_tag_slug,
			'bio'                   => $leader->post_content,
			'featured_image_url'    => $featured_image_url,
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
		'post_type'      => 'church-leader',
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
			'post_type'    => 'church-leader',
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
		'education', 'mission', 'profession', 'military',
	);

	foreach ( $text_fields as $field ) {
		if ( isset( $data[ $field ] ) && $data[ $field ] !== null ) {
			update_field( $field, sanitize_text_field( $data[ $field ] ), $post_id );
		}
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
			$term = get_term_by( 'slug', $role, 'leader-role' );
			if ( $term ) {
				$term_ids[] = $term->term_id;
			} else {
				// Try mapping from group name
				$role_slug = wasmo_map_group_to_role( $role );
				if ( $role_slug ) {
					$term = get_term_by( 'slug', $role_slug, 'leader-role' );
					if ( $term ) {
						$term_ids[] = $term->term_id;
					}
				}
			}
		}

		if ( ! empty( $term_ids ) ) {
			wp_set_post_terms( $post_id, $term_ids, 'leader-role' );
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
