<?php
/**
 * Media Auto-Tagging
 * 
 * Admin page for automatically adding tags to media based on caption/alt text.
 *
 * @package wasmo
 */

/**
 * Add the media auto-tag admin menu page
 */
function wasmo_add_media_auto_tag_page() {
	add_submenu_page(
		'upload.php',
		'Auto-Tag Media',
		'Auto-Tag Media',
		'manage_options',
		'media-auto-tag',
		'wasmo_render_media_auto_tag_page'
	);
}
add_action( 'admin_menu', 'wasmo_add_media_auto_tag_page' );

/**
 * Find tags in text
 *
 * @param string $text The text to search for tags.
 * @return array Array of matching tag term objects.
 */
function wasmo_find_tags_in_text( $text ) {
	if ( empty( trim( $text ) ) ) {
		return array();
	}

	// Get all tags
	static $all_tags = null;
	if ( $all_tags === null ) {
		$all_tags = get_terms( array(
			'taxonomy'   => 'post_tag',
			'hide_empty' => false,
		) );
		if ( is_wp_error( $all_tags ) ) {
			$all_tags = array();
		}
	}

	$text_lower = strtolower( $text );
	$found_tags = array();

	foreach ( $all_tags as $tag ) {
		$tag_name_lower = strtolower( $tag->name );
		
		// Check for exact word match (with word boundaries)
		// This prevents "art" matching "smart" etc.
		$pattern = '/\b' . preg_quote( $tag_name_lower, '/' ) . '\b/i';
		
		if ( preg_match( $pattern, $text_lower ) ) {
			$found_tags[] = $tag;
		}
	}

	return $found_tags;
}

/**
 * Get media items with their caption/alt text and current tags
 *
 * @param int $limit Number of items to retrieve.
 * @param int $offset Offset for pagination.
 * @param string $filter Filter type: 'all', 'untagged', 'has_text'.
 * @return array Array of media data.
 */
function wasmo_get_media_for_tagging( $limit = 50, $offset = 0, $filter = 'all' ) {
	global $wpdb;

	$args = array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'post_status'    => 'inherit',
		'posts_per_page' => $limit,
		'offset'         => $offset,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	// Filter for untagged media
	if ( $filter === 'untagged' ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'post_tag',
				'operator' => 'NOT EXISTS',
			),
		);
	}

	$media_items = get_posts( $args );
	$results = array();

	foreach ( $media_items as $media ) {
		$alt_text = get_post_meta( $media->ID, '_wp_attachment_image_alt', true );
		$caption = $media->post_excerpt;
		$title = $media->post_title;
		$description = $media->post_content;
		
		// Get the filename (without extension) for matching
		$attached_file = get_post_meta( $media->ID, '_wp_attached_file', true );
		$filename = '';
		if ( $attached_file ) {
			$filename = pathinfo( $attached_file, PATHINFO_FILENAME );
			// Replace common separators with spaces for better matching
			$filename = str_replace( array( '-', '_', '.', '+' ), ' ', $filename );
		}
		
		// Combine all text fields including filename
		$all_text = implode( ' ', array_filter( array( $title, $alt_text, $caption, $description, $filename ) ) );
		
		// Skip if no text and filtering for has_text
		if ( $filter === 'has_text' && empty( trim( $all_text ) ) ) {
			continue;
		}

		// Get current tags
		$current_tags = wp_get_post_tags( $media->ID, array( 'fields' => 'all' ) );
		
		// Find potential tags from text
		$potential_tags = wasmo_find_tags_in_text( $all_text );
		
		// Filter out tags already assigned
		$current_tag_ids = wp_list_pluck( $current_tags, 'term_id' );
		$new_tags = array_filter( $potential_tags, function( $tag ) use ( $current_tag_ids ) {
			return ! in_array( $tag->term_id, $current_tag_ids );
		} );

		$results[] = array(
			'id'            => $media->ID,
			'title'         => $title,
			'alt_text'      => $alt_text,
			'caption'       => $caption,
			'description'   => $description,
			'thumbnail_url' => wp_get_attachment_image_url( $media->ID, 'thumbnail' ),
			'edit_url'      => get_edit_post_link( $media->ID ),
			'current_tags'  => $current_tags,
			'potential_tags'=> $new_tags,
			'all_text'      => $all_text,
		);
	}

	return $results;
}

/**
 * Get total media count
 *
 * @param string $filter Filter type.
 * @return int Total count.
 */
function wasmo_get_media_count( $filter = 'all' ) {
	$args = array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	if ( $filter === 'untagged' ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'post_tag',
				'operator' => 'NOT EXISTS',
			),
		);
	}

	$query = new WP_Query( $args );
	return $query->found_posts;
}

/**
 * Preview auto-tagging results
 *
 * @param bool $untagged_only Only include media that has no tags yet.
 * @return array Preview data with counts and samples.
 */
function wasmo_preview_auto_tag( $untagged_only = false ) {
	$media_items = wasmo_get_media_for_tagging( 500, 0, 'all' );
	
	$would_tag = 0;
	$total_new_tags = 0;
	$samples = array();
	
	foreach ( $media_items as $media ) {
		if ( ! empty( $media['potential_tags'] ) ) {
			// Skip if filtering for untagged only and this media already has tags
			if ( $untagged_only && ! empty( $media['current_tags'] ) ) {
				continue;
			}
			
			$would_tag++;
			$total_new_tags += count( $media['potential_tags'] );
			
			if ( count( $samples ) < 20 ) {
				$samples[] = $media;
			}
		}
	}
	
	return array(
		'total_scanned'  => count( $media_items ),
		'would_tag'      => $would_tag,
		'total_new_tags' => $total_new_tags,
		'samples'        => $samples,
	);
}

/**
 * Apply specific selected tags to media items
 *
 * @param array $media_ids Array of media IDs to tag.
 * @param array $selected_tags Associative array of media_id => array of tag_ids.
 * @return array Results with counts.
 */
function wasmo_apply_selected_tags( $media_ids, $selected_tags ) {
	$results = array(
		'processed' => 0,
		'tagged'    => 0,
		'tags_added'=> 0,
		'details'   => array(),
	);

	foreach ( $media_ids as $media_id ) {
		$results['processed']++;
		
		// Get the tags selected for this specific media item
		$tag_ids = isset( $selected_tags[ $media_id ] ) ? array_map( 'absint', (array) $selected_tags[ $media_id ] ) : array();
		
		if ( empty( $tag_ids ) ) {
			continue;
		}

		// Get tag names for logging
		$tag_names = array();
		foreach ( $tag_ids as $tag_id ) {
			$term = get_term( $tag_id, 'post_tag' );
			if ( $term && ! is_wp_error( $term ) ) {
				$tag_names[] = $term->name;
			}
		}

		// Append tags (don't replace existing)
		wp_set_post_tags( $media_id, $tag_ids, true );
		
		$results['tagged']++;
		$results['tags_added'] += count( $tag_ids );
		$results['details'][] = array(
			'id'    => $media_id,
			'title' => get_the_title( $media_id ),
			'tags'  => $tag_names,
		);
	}

	return $results;
}

/**
 * Apply auto-tags to media
 *
 * @param array $media_ids Optional. Specific media IDs to tag. If empty, processes all.
 * @param bool  $dry_run If true, only returns what would be done.
 * @return array Results with counts.
 */
function wasmo_apply_auto_tags( $media_ids = array(), $dry_run = false ) {
	$results = array(
		'processed' => 0,
		'tagged'    => 0,
		'tags_added'=> 0,
		'details'   => array(),
	);

	// Get media to process
	if ( ! empty( $media_ids ) ) {
		$media_items = array();
		foreach ( $media_ids as $id ) {
			$media = get_post( $id );
			if ( $media && $media->post_type === 'attachment' ) {
				$alt_text = get_post_meta( $media->ID, '_wp_attachment_image_alt', true );
				$all_text = implode( ' ', array_filter( array( 
					$media->post_title, 
					$alt_text, 
					$media->post_excerpt, 
					$media->post_content 
				) ) );
				
				$current_tags = wp_get_post_tags( $media->ID, array( 'fields' => 'all' ) );
				$potential_tags = wasmo_find_tags_in_text( $all_text );
				
				$current_tag_ids = wp_list_pluck( $current_tags, 'term_id' );
				$new_tags = array_filter( $potential_tags, function( $tag ) use ( $current_tag_ids ) {
					return ! in_array( $tag->term_id, $current_tag_ids );
				} );
				
				$media_items[] = array(
					'id'             => $media->ID,
					'title'          => $media->post_title,
					'potential_tags' => $new_tags,
				);
			}
		}
	} else {
		$media_items = wasmo_get_media_for_tagging( 1000, 0, 'all' );
	}

	foreach ( $media_items as $media ) {
		$results['processed']++;
		
		if ( empty( $media['potential_tags'] ) ) {
			continue;
		}

		$tag_ids = wp_list_pluck( $media['potential_tags'], 'term_id' );
		$tag_names = wp_list_pluck( $media['potential_tags'], 'name' );
		
		if ( ! $dry_run ) {
			// Append tags (don't replace existing)
			wp_set_post_tags( $media['id'], $tag_ids, true );
		}
		
		$results['tagged']++;
		$results['tags_added'] += count( $tag_ids );
		$results['details'][] = array(
			'id'    => $media['id'],
			'title' => $media['title'],
			'tags'  => $tag_names,
		);
	}

	return $results;
}

/**
 * Handle AJAX auto-tag request
 */
function wasmo_auto_tag_media_ajax() {
	check_ajax_referer( 'wasmo_auto_tag_media', '_wpnonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permission denied' );
	}

	$action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( $_POST['action_type'] ) : '';
	$media_ids = isset( $_POST['media_ids'] ) ? array_map( 'absint', (array) $_POST['media_ids'] ) : array();

	if ( $action_type === 'preview' ) {
		$preview = wasmo_preview_auto_tag();
		wp_send_json_success( $preview );
	} elseif ( $action_type === 'apply' ) {
		$results = wasmo_apply_auto_tags( $media_ids, false );
		wp_send_json_success( $results );
	} elseif ( $action_type === 'apply_selected' ) {
		$results = wasmo_apply_auto_tags( $media_ids, false );
		wp_send_json_success( $results );
	} else {
		wp_send_json_error( 'Invalid action' );
	}
}
add_action( 'wp_ajax_wasmo_auto_tag_media', 'wasmo_auto_tag_media_ajax' );

/**
 * Render the media auto-tag admin page
 */
function wasmo_render_media_auto_tag_page() {
	$message = '';
	$message_type = 'info';

	// Handle form submission
	if ( isset( $_POST['wasmo_auto_tag_submit'] ) && check_admin_referer( 'wasmo_auto_tag_nonce' ) ) {
		$selected_ids = isset( $_POST['media_ids'] ) ? array_map( 'absint', (array) $_POST['media_ids'] ) : array();
		$selected_tags = isset( $_POST['tags'] ) ? $_POST['tags'] : array();
		
		if ( empty( $selected_ids ) ) {
			$message = 'No media items selected.';
			$message_type = 'warning';
		} else {
			// Apply only the specifically selected tags
			$results = wasmo_apply_selected_tags( $selected_ids, $selected_tags );
			$message = sprintf(
				'Auto-tagging complete: %d items processed, %d items tagged with %d total tags added.',
				$results['processed'],
				$results['tagged'],
				$results['tags_added']
			);
			$message_type = 'success';
		}
	}

	// Handle "Tag All" submission
	if ( isset( $_POST['wasmo_auto_tag_all'] ) && check_admin_referer( 'wasmo_auto_tag_nonce' ) ) {
		$results = wasmo_apply_auto_tags( array(), false );
		$message = sprintf(
			'Auto-tagging complete: %d items processed, %d items tagged with %d total tags added.',
			$results['processed'],
			$results['tagged'],
			$results['tags_added']
		);
		$message_type = 'success';
	}

	// Get filter preference
	$show_untagged_only = isset( $_GET['untagged_only'] ) ? $_GET['untagged_only'] === '1' : true; // Default to untagged only

	// Get statistics
	$total_images = wasmo_get_media_count( 'all' );
	$untagged_images = wasmo_get_media_count( 'untagged' );
	$tagged_images = $total_images - $untagged_images;

	// Get preview data with filter
	$preview = wasmo_preview_auto_tag( $show_untagged_only );

	?>
	<div class="wrap">
		<h1>Auto-Tag Media</h1>
		<p>Automatically add tags to media items based on their title, alt text, caption, and description.</p>

		<?php if ( $message ) : ?>
			<div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
				<p><?php echo wp_kses_post( $message ); ?></p>
			</div>
		<?php endif; ?>

		<!-- Statistics -->
		<div class="card" style="max-width: 100%; margin-bottom: 20px;">
			<h2>📊 Media Statistics</h2>
			<div style="display: flex; gap: 30px; flex-wrap: wrap;">
				<div>
					<span style="font-size: 2em; font-weight: bold; color: #2271b1;"><?php echo number_format( $total_images ); ?></span>
					<br><span>Total Images</span>
				</div>
				<div>
					<span style="font-size: 2em; font-weight: bold; color: #00a32a;"><?php echo number_format( $tagged_images ); ?></span>
					<br><span>With Tags</span>
				</div>
				<div>
					<span style="font-size: 2em; font-weight: bold; color: #d63638;"><?php echo number_format( $untagged_images ); ?></span>
					<br><span>Without Tags</span>
				</div>
				<div>
					<span style="font-size: 2em; font-weight: bold; color: #dba617;"><?php echo number_format( $preview['would_tag'] ); ?></span>
					<br><span>Can Be Auto-Tagged</span>
				</div>
				<div>
					<span style="font-size: 2em; font-weight: bold; color: #8c8f94;"><?php echo number_format( $preview['total_new_tags'] ); ?></span>
					<br><span>Tags Would Be Added</span>
				</div>
			</div>
		</div>

		<!-- Quick Actions -->
		<div class="card" style="max-width: 100%; margin-bottom: 20px;">
			<h2>⚡ Quick Actions</h2>
			<form method="post">
				<?php wp_nonce_field( 'wasmo_auto_tag_nonce' ); ?>
				<p>
					<button type="submit" name="wasmo_auto_tag_all" class="button button-primary button-hero" 
					        onclick="return confirm('This will auto-tag up to 1000 images. Continue?');"
					        <?php echo $preview['would_tag'] === 0 ? 'disabled' : ''; ?>>
						🏷️ Auto-Tag All Media (<?php echo $preview['would_tag']; ?> items)
					</button>
				</p>
				<p class="description">
					This will scan all images and add any matching tags found in their title, alt text, caption, or description.
					Only adds new tags - existing tags are preserved.
				</p>
			</form>
		</div>

		<?php if ( ! empty( $preview['samples'] ) ) : ?>
		<!-- Preview / Select Items -->
		<div class="card" style="max-width: 100%; margin-bottom: 20px;">
			<h2>🔍 Preview: Media That Can Be Tagged</h2>
			<p>These images have text that matches existing tags. <strong>Click a green tag to remove it</strong> from being added. Select items to tag, or use "Tag All" above.</p>
			
			<!-- Filter Options -->
			<div style="background: #f0f0f1; padding: 10px 15px; margin-bottom: 15px; border-radius: 4px;">
				<strong>Filter:</strong>
				<label style="margin-left: 15px;">
					<input type="radio" name="filter_mode" value="untagged" <?php checked( $show_untagged_only, true ); ?> onchange="window.location.href='<?php echo esc_url( admin_url( 'upload.php?page=media-auto-tag&untagged_only=1' ) ); ?>'">
					Only show images with no tags yet
				</label>
				<label style="margin-left: 15px;">
					<input type="radio" name="filter_mode" value="all" <?php checked( $show_untagged_only, false ); ?> onchange="window.location.href='<?php echo esc_url( admin_url( 'upload.php?page=media-auto-tag&untagged_only=0' ) ); ?>'">
					Show all images that can receive tags
				</label>
			</div>
			
			<form method="post" id="auto-tag-form">
				<?php wp_nonce_field( 'wasmo_auto_tag_nonce' ); ?>
				
				<p>
					<button type="button" id="select-all-btn" class="button">Select All</button>
					<button type="button" id="select-none-btn" class="button">Select None</button>
					<button type="submit" name="wasmo_auto_tag_submit" class="button button-primary">Tag Selected Items</button>
					<a href="<?php echo esc_url( admin_url( 'upload.php?page=media-auto-tag&untagged_only=' . ( $show_untagged_only ? '1' : '0' ) ) ); ?>" class="button" style="margin-left: 10px;">🔄 Refresh List</a>
				</p>
				
				<table class="widefat striped" style="margin-top: 15px;">
					<thead>
						<tr>
							<th style="width: 30px;"><input type="checkbox" id="select-all-checkbox"></th>
							<th style="width: 80px;">Image</th>
							<th>Title / Alt Text</th>
							<th>Current Tags</th>
							<th>Tags to Add <small>(click to remove)</small></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $preview['samples'] as $media ) : ?>
						<tr data-media-id="<?php echo esc_attr( $media['id'] ); ?>" class="media-row">
							<td>
								<input type="checkbox" name="media_ids[]" value="<?php echo esc_attr( $media['id'] ); ?>" class="media-checkbox">
							</td>
							<td>
								<?php if ( $media['thumbnail_url'] ) : ?>
									<a href="<?php echo esc_url( $media['edit_url'] ); ?>" target="_blank">
										<img src="<?php echo esc_url( $media['thumbnail_url'] ); ?>" 
										     style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
									</a>
								<?php endif; ?>
							</td>
							<td>
								<strong><?php echo esc_html( $media['title'] ); ?></strong>
								<?php if ( $media['alt_text'] ) : ?>
									<br><small>Alt: <?php echo esc_html( substr( $media['alt_text'], 0, 100 ) ); ?></small>
								<?php endif; ?>
								<?php if ( $media['caption'] ) : ?>
									<br><small>Caption: <?php echo esc_html( substr( $media['caption'], 0, 100 ) ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $media['current_tags'] ) ) : ?>
									<?php foreach ( $media['current_tags'] as $tag ) : ?>
										<span class="tag current-tag" style="background: #e0e0e0; padding: 2px 8px; border-radius: 3px; margin: 2px; display: inline-block; font-size: 12px;">
											<?php echo esc_html( $tag->name ); ?>
										</span>
									<?php endforeach; ?>
								<?php else : ?>
									<em style="color: #888;">None</em>
								<?php endif; ?>
							</td>
							<td class="tags-to-add-cell">
								<?php foreach ( $media['potential_tags'] as $tag ) : ?>
									<span class="tag potential-tag" 
									      data-tag-id="<?php echo esc_attr( $tag->term_id ); ?>"
									      data-media-id="<?php echo esc_attr( $media['id'] ); ?>"
									      style="background: #c6f6d5; padding: 2px 8px; border-radius: 3px; margin: 2px; display: inline-block; font-size: 12px; color: #22543d; cursor: pointer;"
									      title="Click to remove this tag">
										+ <?php echo esc_html( $tag->name ); ?>
										<input type="hidden" name="tags[<?php echo esc_attr( $media['id'] ); ?>][]" value="<?php echo esc_attr( $tag->term_id ); ?>">
									</span>
								<?php endforeach; ?>
								<em class="no-tags-msg" style="color: #888; display: none;">No tags selected</em>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<?php if ( $preview['would_tag'] > count( $preview['samples'] ) ) : ?>
					<p style="margin-top: 15px;">
						<em>Showing <?php echo count( $preview['samples'] ); ?> of <?php echo $preview['would_tag']; ?> items that can be tagged.</em>
					</p>
				<?php endif; ?>
			</form>
		</div>
		<?php else : ?>
		<div class="card" style="max-width: 100%;">
			<h2>✅ All Done!</h2>
			<p>No media items were found that can be auto-tagged. Either all images already have appropriate tags, or no matching tags were found in their text.</p>
		</div>
		<?php endif; ?>

		<!-- How It Works -->
		<div class="card" style="max-width: 100%; margin-top: 20px;">
			<h2>ℹ️ How It Works</h2>
			<ol>
				<li>The tool scans the <strong>title</strong>, <strong>alt text</strong>, <strong>caption</strong>, and <strong>description</strong> of each image.</li>
				<li>It looks for exact word matches against your existing tags (case-insensitive).</li>
				<li>If a match is found, and the tag isn't already assigned, it will be added.</li>
				<li>Existing tags are never removed - only new tags are added.</li>
			</ol>
			<p><strong>Tip:</strong> For best results, make sure your images have descriptive alt text and captions!</p>
		</div>

	</div>

	<style>
		.potential-tag {
			transition: all 0.2s ease;
		}
		.potential-tag:hover {
			background: #feb2b2 !important;
			color: #742a2a !important;
			text-decoration: line-through;
		}
		.potential-tag.removed {
			background: #fed7d7 !important;
			color: #9b2c2c !important;
			text-decoration: line-through;
			opacity: 0.6;
		}
		.media-row.no-tags {
			opacity: 0.5;
		}
	</style>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const selectAllBtn = document.getElementById('select-all-btn');
		const selectNoneBtn = document.getElementById('select-none-btn');
		const selectAllCheckbox = document.getElementById('select-all-checkbox');
		const checkboxes = document.querySelectorAll('.media-checkbox');

		// Select All / None buttons
		if (selectAllBtn) {
			selectAllBtn.addEventListener('click', function() {
				checkboxes.forEach(cb => {
					// Only select rows that still have tags to add
					const row = cb.closest('tr');
					const activeTags = row.querySelectorAll('.potential-tag:not(.removed)');
					if (activeTags.length > 0) {
						cb.checked = true;
					}
				});
				updateSelectAllCheckbox();
			});
		}

		if (selectNoneBtn) {
			selectNoneBtn.addEventListener('click', function() {
				checkboxes.forEach(cb => cb.checked = false);
				if (selectAllCheckbox) selectAllCheckbox.checked = false;
			});
		}

		if (selectAllCheckbox) {
			selectAllCheckbox.addEventListener('change', function() {
				checkboxes.forEach(cb => {
					if (this.checked) {
						const row = cb.closest('tr');
						const activeTags = row.querySelectorAll('.potential-tag:not(.removed)');
						if (activeTags.length > 0) {
							cb.checked = true;
						}
					} else {
						cb.checked = false;
					}
				});
			});
		}

		function updateSelectAllCheckbox() {
			if (!selectAllCheckbox) return;
			const total = checkboxes.length;
			const checked = document.querySelectorAll('.media-checkbox:checked').length;
			selectAllCheckbox.checked = (checked === total && total > 0);
			selectAllCheckbox.indeterminate = (checked > 0 && checked < total);
		}

		// Handle clicking on potential tags to remove them
		document.querySelectorAll('.potential-tag').forEach(function(tagEl) {
			tagEl.addEventListener('click', function() {
				const isRemoved = this.classList.contains('removed');
				const hiddenInput = this.querySelector('input[type="hidden"]');
				
				if (isRemoved) {
					// Re-add the tag
					this.classList.remove('removed');
					this.innerHTML = '+ ' + this.textContent.replace('+ ', '').replace('×', '').trim();
					if (hiddenInput) {
						hiddenInput.disabled = false;
					} else {
						// Recreate the hidden input
						const mediaId = this.dataset.mediaId;
						const tagId = this.dataset.tagId;
						const input = document.createElement('input');
						input.type = 'hidden';
						input.name = 'tags[' + mediaId + '][]';
						input.value = tagId;
						this.appendChild(input);
					}
				} else {
					// Remove the tag
					this.classList.add('removed');
					this.innerHTML = '× ' + this.textContent.replace('+ ', '').trim();
					if (hiddenInput) {
						hiddenInput.disabled = true;
					}
				}
				
				// Update row state
				const row = this.closest('tr');
				const cell = this.closest('.tags-to-add-cell');
				const activeTags = cell.querySelectorAll('.potential-tag:not(.removed)');
				const noTagsMsg = cell.querySelector('.no-tags-msg');
				
				if (activeTags.length === 0) {
					row.classList.add('no-tags');
					if (noTagsMsg) noTagsMsg.style.display = 'inline';
					// Uncheck the row
					const checkbox = row.querySelector('.media-checkbox');
					if (checkbox) checkbox.checked = false;
				} else {
					row.classList.remove('no-tags');
					if (noTagsMsg) noTagsMsg.style.display = 'none';
				}
				
				updateSelectAllCheckbox();
			});
		});

		// Update checkbox state on manual change
		checkboxes.forEach(cb => {
			cb.addEventListener('change', updateSelectAllCheckbox);
		});
	});
	</script>
	<?php
}
