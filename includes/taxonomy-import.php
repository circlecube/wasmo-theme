<?php
/**
 * Taxonomy JSON Import/Export
 * 
 * Admin page for importing/exporting taxonomy terms (tags, spectrum, shelf-items) from/to JSON data.
 *
 * @package wasmo
 */

/**
 * Add the taxonomy import/export admin menu page
 */
function wasmo_add_taxonomy_import_page() {
	add_submenu_page(
		'wasmormon',
		'Taxonomy Import/Export',
		'Taxonomy Import/Export',
		'manage_options',
		'taxonomy-import-export',
		'wasmo_render_taxonomy_import_page'
	);
}
add_action( 'admin_menu', 'wasmo_add_taxonomy_import_page' );

/**
 * Get exportable taxonomies
 *
 * @return array Array of taxonomy slugs and labels.
 */
function wasmo_get_exportable_taxonomies() {
	return array(
		'post_tag' => 'Post Tags',
		'spectrum' => 'Spectrum',
		'shelf'    => 'Shelf Items',
	);
}

/**
 * Export taxonomy terms to JSON format
 *
 * @param string $taxonomy The taxonomy to export.
 * @return array Array of term data.
 */
function wasmo_export_taxonomy_terms( $taxonomy ) {
	$terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	$export_data = array();

	foreach ( $terms as $term ) {
		$term_data = array(
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'parent_slug' => '',
			'count'       => $term->count,
		);

		// Get parent term slug if exists
		if ( $term->parent > 0 ) {
			$parent_term = get_term( $term->parent, $taxonomy );
			if ( $parent_term && ! is_wp_error( $parent_term ) ) {
				$term_data['parent_slug'] = $parent_term->slug;
			}
		}

		// Get term meta if any
		$term_meta = get_term_meta( $term->term_id );
		if ( ! empty( $term_meta ) ) {
			$clean_meta = array();
			foreach ( $term_meta as $key => $values ) {
				// Skip internal meta keys
				if ( strpos( $key, '_' ) === 0 ) {
					continue;
				}
				$clean_meta[ $key ] = count( $values ) === 1 ? $values[0] : $values;
			}
			if ( ! empty( $clean_meta ) ) {
				$term_data['meta'] = $clean_meta;
			}
		}

		$export_data[] = $term_data;
	}

	return $export_data;
}

/**
 * Import taxonomy terms from JSON data
 *
 * @param string $taxonomy The taxonomy to import to.
 * @param array  $terms_data Array of term data from JSON.
 * @param bool   $update_existing Whether to update existing terms.
 * @return array Import results with counts.
 */
function wasmo_import_taxonomy_terms( $taxonomy, $terms_data, $update_existing = true ) {
	$results = array(
		'imported' => 0,
		'updated'  => 0,
		'skipped'  => 0,
		'errors'   => array(),
	);

	if ( empty( $terms_data ) || ! is_array( $terms_data ) ) {
		$results['errors'][] = 'No valid term data provided.';
		return $results;
	}

	// First pass: create/update terms without parents
	$slug_to_id = array();
	
	foreach ( $terms_data as $term_data ) {
		if ( empty( $term_data['name'] ) ) {
			$results['errors'][] = 'Skipped term with empty name.';
			$results['skipped']++;
			continue;
		}

		$name = sanitize_text_field( $term_data['name'] );
		$slug = ! empty( $term_data['slug'] ) ? sanitize_title( $term_data['slug'] ) : sanitize_title( $name );
		$description = isset( $term_data['description'] ) ? wp_kses_post( $term_data['description'] ) : '';

		// Check if term exists by slug
		$existing_term = get_term_by( 'slug', $slug, $taxonomy );

		if ( $existing_term ) {
			if ( $update_existing ) {
				// Update existing term
				$update_args = array(
					'name'        => $name,
					'description' => $description,
				);

				$result = wp_update_term( $existing_term->term_id, $taxonomy, $update_args );

				if ( is_wp_error( $result ) ) {
					$results['errors'][] = "Failed to update term '{$name}': " . $result->get_error_message();
				} else {
					$slug_to_id[ $slug ] = $existing_term->term_id;
					
					// Update term meta if provided
					if ( ! empty( $term_data['meta'] ) && is_array( $term_data['meta'] ) ) {
						foreach ( $term_data['meta'] as $meta_key => $meta_value ) {
							update_term_meta( $existing_term->term_id, $meta_key, $meta_value );
						}
					}
					
					$results['updated']++;
				}
			} else {
				$slug_to_id[ $slug ] = $existing_term->term_id;
				$results['skipped']++;
			}
		} else {
			// Create new term
			$insert_args = array(
				'slug'        => $slug,
				'description' => $description,
			);

			$result = wp_insert_term( $name, $taxonomy, $insert_args );

			if ( is_wp_error( $result ) ) {
				$results['errors'][] = "Failed to create term '{$name}': " . $result->get_error_message();
			} else {
				$slug_to_id[ $slug ] = $result['term_id'];
				
				// Add term meta if provided
				if ( ! empty( $term_data['meta'] ) && is_array( $term_data['meta'] ) ) {
					foreach ( $term_data['meta'] as $meta_key => $meta_value ) {
						add_term_meta( $result['term_id'], $meta_key, $meta_value );
					}
				}
				
				$results['imported']++;
			}
		}
	}

	// Second pass: update parent relationships
	foreach ( $terms_data as $term_data ) {
		if ( empty( $term_data['parent_slug'] ) ) {
			continue;
		}

		$slug = ! empty( $term_data['slug'] ) ? sanitize_title( $term_data['slug'] ) : sanitize_title( $term_data['name'] );
		
		if ( ! isset( $slug_to_id[ $slug ] ) ) {
			continue;
		}

		$term_id = $slug_to_id[ $slug ];
		$parent_slug = sanitize_title( $term_data['parent_slug'] );

		// Find parent term
		$parent_term = get_term_by( 'slug', $parent_slug, $taxonomy );
		
		if ( $parent_term && ! is_wp_error( $parent_term ) ) {
			wp_update_term( $term_id, $taxonomy, array( 'parent' => $parent_term->term_id ) );
		}
	}

	return $results;
}

/**
 * Handle AJAX export request
 */
function wasmo_export_taxonomy_ajax() {
	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wasmo_export_taxonomy' ) ) {
		wp_die( 'Security check failed' );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Permission denied' );
	}

	$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( $_GET['taxonomy'] ) : '';
	$taxonomies = wasmo_get_exportable_taxonomies();

	if ( empty( $taxonomy ) || ! isset( $taxonomies[ $taxonomy ] ) ) {
		wp_die( 'Invalid taxonomy' );
	}

	$export_data = wasmo_export_taxonomy_terms( $taxonomy );

	$filename = $taxonomy . '-export-' . date( 'Y-m-d' ) . '.json';

	header( 'Content-Type: application/json' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	echo json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}
add_action( 'wp_ajax_wasmo_export_taxonomy', 'wasmo_export_taxonomy_ajax' );

/**
 * Render the taxonomy import/export admin page
 */
function wasmo_render_taxonomy_import_page() {
	$message = '';
	$message_type = 'info';
	$taxonomies = wasmo_get_exportable_taxonomies();

	// Handle import form submission
	if ( isset( $_POST['wasmo_import_taxonomy'] ) && check_admin_referer( 'wasmo_import_taxonomy_nonce' ) ) {
		$taxonomy = isset( $_POST['import_taxonomy'] ) ? sanitize_key( $_POST['import_taxonomy'] ) : '';
		$update_existing = isset( $_POST['update_existing'] );

		if ( empty( $taxonomy ) || ! isset( $taxonomies[ $taxonomy ] ) ) {
			$message = 'Please select a valid taxonomy.';
			$message_type = 'error';
		} elseif ( empty( $_FILES['json_file']['tmp_name'] ) ) {
			$message = 'Please select a JSON file to import.';
			$message_type = 'error';
		} else {
			$json_content = file_get_contents( $_FILES['json_file']['tmp_name'] );
			$terms_data = json_decode( $json_content, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$message = 'Invalid JSON file: ' . json_last_error_msg();
				$message_type = 'error';
			} else {
				$results = wasmo_import_taxonomy_terms( $taxonomy, $terms_data, $update_existing );

				$message = sprintf(
					'Import complete for %s: %d imported, %d updated, %d skipped.',
					$taxonomies[ $taxonomy ],
					$results['imported'],
					$results['updated'],
					$results['skipped']
				);

				if ( ! empty( $results['errors'] ) ) {
					$message .= '<br><br><strong>Errors:</strong><br>' . implode( '<br>', array_slice( $results['errors'], 0, 10 ) );
					if ( count( $results['errors'] ) > 10 ) {
						$message .= '<br>... and ' . ( count( $results['errors'] ) - 10 ) . ' more errors.';
					}
					$message_type = 'warning';
				} else {
					$message_type = 'success';
				}
			}
		}
	}

	?>
	<div class="wrap">
		<h1>Taxonomy Import/Export</h1>
		<p>Export and import taxonomy terms (tags, spectrum, shelf items) with their descriptions.</p>

		<?php if ( $message ) : ?>
			<div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
				<p><?php echo wp_kses_post( $message ); ?></p>
			</div>
		<?php endif; ?>

		<div style="display: flex; gap: 20px; flex-wrap: wrap;">
			
			<!-- Export Section -->
			<div class="card" style="max-width: 500px; flex: 1;">
				<h2>📤 Export Taxonomy</h2>
				<p>Export all terms from a taxonomy to JSON format. The export includes name, slug, description, parent relationships, and any term meta.</p>
				
				<form method="get" action="<?php echo admin_url( 'admin-ajax.php' ); ?>">
					<input type="hidden" name="action" value="wasmo_export_taxonomy">
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'wasmo_export_taxonomy' ); ?>">
					
					<p>
						<label for="export_taxonomy"><strong>Select Taxonomy:</strong></label><br>
						<select name="taxonomy" id="export_taxonomy" style="width: 100%; margin-top: 5px;">
							<?php foreach ( $taxonomies as $slug => $label ) : 
								$term_count = wp_count_terms( array( 'taxonomy' => $slug, 'hide_empty' => false ) );
								$term_count = is_wp_error( $term_count ) ? 0 : $term_count;
							?>
								<option value="<?php echo esc_attr( $slug ); ?>">
									<?php echo esc_html( $label ); ?> (<?php echo $term_count; ?> terms)
								</option>
							<?php endforeach; ?>
						</select>
					</p>
					
					<p>
						<button type="submit" class="button button-primary">Download JSON Export</button>
					</p>
				</form>
			</div>

			<!-- Import Section -->
			<div class="card" style="max-width: 500px; flex: 1;">
				<h2>📥 Import Taxonomy</h2>
				<p>Import terms from a JSON file. You can update existing terms or only add new ones.</p>
				
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'wasmo_import_taxonomy_nonce' ); ?>
					
					<p>
						<label for="import_taxonomy"><strong>Select Taxonomy:</strong></label><br>
						<select name="import_taxonomy" id="import_taxonomy" style="width: 100%; margin-top: 5px;">
							<?php foreach ( $taxonomies as $slug => $label ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					
					<p>
						<label for="json_file"><strong>JSON File:</strong></label><br>
						<input type="file" name="json_file" id="json_file" accept=".json" style="margin-top: 5px;">
					</p>
					
					<p>
						<label>
							<input type="checkbox" name="update_existing" value="1" checked>
							Update existing terms (match by slug)
						</label>
					</p>
					
					<p>
						<button type="submit" name="wasmo_import_taxonomy" class="button button-primary">Import JSON</button>
					</p>
				</form>
			</div>

		</div>

		<!-- Term Statistics -->
		<div class="card" style="max-width: 100%; margin-top: 20px;">
			<h2>📊 Taxonomy Statistics</h2>
			<table class="widefat striped" style="max-width: 800px;">
				<thead>
					<tr>
						<th>Taxonomy</th>
						<th>Total Terms</th>
						<th>With Description</th>
						<th>Missing Description</th>
						<th>% Complete</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $taxonomies as $slug => $label ) : 
						$terms = get_terms( array(
							'taxonomy'   => $slug,
							'hide_empty' => false,
						) );
						
						if ( is_wp_error( $terms ) ) {
							$terms = array();
						}
						
						$total = count( $terms );
						$with_desc = 0;
						$without_desc = 0;
						
						foreach ( $terms as $term ) {
							if ( ! empty( trim( $term->description ) ) ) {
								$with_desc++;
							} else {
								$without_desc++;
							}
						}
						
						$pct_complete = $total > 0 ? round( ( $with_desc / $total ) * 100, 1 ) : 0;
						$pct_class = $pct_complete >= 80 ? 'color: green;' : ( $pct_complete >= 50 ? 'color: orange;' : 'color: red;' );
					?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong></td>
						<td><?php echo $total; ?></td>
						<td><?php echo $with_desc; ?></td>
						<td><?php echo $without_desc; ?></td>
						<td style="<?php echo $pct_class; ?> font-weight: bold;"><?php echo $pct_complete; ?>%</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<!-- JSON Format Example -->
		<div class="card" style="max-width: 100%; margin-top: 20px;">
			<h2>📋 JSON Format</h2>
			<p>The JSON file should be an array of term objects with the following structure:</p>
			<pre style="background: #f5f5f5; padding: 15px; overflow: auto; max-height: 300px;">[
    {
        "name": "Term Name",
        "slug": "term-slug",
        "description": "The description for this term. Can include HTML.",
        "parent_slug": "parent-term-slug",
        "meta": {
            "custom_field": "value"
        }
    },
    {
        "name": "Another Term",
        "slug": "another-term",
        "description": "Another description here."
    }
]</pre>
			<p><strong>Notes:</strong></p>
			<ul>
				<li><code>name</code> - Required. The display name of the term.</li>
				<li><code>slug</code> - Optional. Auto-generated from name if not provided.</li>
				<li><code>description</code> - Optional. The term description (HTML allowed).</li>
				<li><code>parent_slug</code> - Optional. Slug of the parent term for hierarchical taxonomies.</li>
				<li><code>meta</code> - Optional. Object of term meta key/value pairs.</li>
			</ul>
		</div>

	</div>
	<?php
}
