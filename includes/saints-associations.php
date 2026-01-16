<?php
/**
 * Saints Association Tool
 * 
 * Admin page for bulk associating existing posts and media with church leaders
 * based on existing tag relationships.
 *
 * @package wasmo
 */

/**
 * Add admin menu page for associations
 */
function wasmo_add_leader_associations_page() {
	add_submenu_page(
		'edit.php?post_type=saint',
		'Associate Content',
		'Associate Content',
		'manage_options',
		'associate-leaders',
		'wasmo_render_leader_associations_page'
	);
}
add_action( 'admin_menu', 'wasmo_add_leader_associations_page' );

/**
 * Render the associations admin page
 */
function wasmo_render_leader_associations_page() {
	$message = '';
	$results = array();

	// Handle preview request
	if ( isset( $_POST['wasmo_preview_associations'] ) && check_admin_referer( 'wasmo_associations_nonce' ) ) {
		$include_text = isset( $_POST['include_text_search'] );
		$results = wasmo_preview_leader_associations( $include_text );
	}

	// Handle bulk association
	if ( isset( $_POST['wasmo_run_associations'] ) && check_admin_referer( 'wasmo_associations_nonce' ) ) {
		$post_type = sanitize_text_field( $_POST['content_type'] );
		$include_text = isset( $_POST['include_text_search'] );
		$result = wasmo_run_leader_associations( $post_type, $include_text );
		
		$message = '<div class="notice notice-success"><p>';
		$message .= sprintf( '<strong>Association complete!</strong> %d items updated.<br>', $result['updated'] );
		$message .= sprintf( '&bull; Posts (by tag): %d<br>', $result['updated_posts'] );
		$message .= sprintf( '&bull; Media (by tag): %d<br>', $result['updated_media_tag'] );
		$message .= sprintf( '&bull; Media (by text search): %d', $result['updated_media_text'] );
		$message .= '</p></div>';
	}

	// Get leaders with associated tags
	$leaders_with_tags = wasmo_get_leaders_with_tags();
	?>
	<div class="wrap">
		<h1>Associate Content with Saints</h1>
		
		<?php echo $message; ?>

		<div class="card" style="max-width: 900px; margin-bottom: 20px;">
			<h2>How It Works</h2>
			<p>
				This tool finds existing posts and media that are tagged with leader names and creates
				ACF relationship links to the corresponding church leader posts. This allows for a 
				gradual migration from tag-based to relationship-based content linking.
			</p>
			<ol>
				<li>Each church leader has an "Associated Tag" field linking to a WordPress tag</li>
				<li>Posts/media with that tag will be linked to the leader via the "Related Leaders" field</li>
				<li>The original tags remain intact for backward compatibility</li>
			</ol>
		</div>

		<div class="card" style="max-width: 900px; margin-bottom: 20px;">
			<h2>Leaders with Associated Tags</h2>
			<?php if ( empty( $leaders_with_tags ) ) : ?>
				<p><em>No leaders have associated tags set. Edit church leader posts to link them to existing WordPress tags.</em></p>
			<?php else : ?>
				<table class="widefat">
					<thead>
						<tr>
							<th>Leader</th>
							<th>Associated Tag</th>
							<th>Tagged Posts</th>
							<th>Tagged Media</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $leaders_with_tags as $leader ) : ?>
							<tr>
								<td>
									<a href="<?php echo get_edit_post_link( $leader['id'] ); ?>">
										<?php echo esc_html( $leader['name'] ); ?>
									</a>
								</td>
								<td>
									<a href="<?php echo get_edit_term_link( $leader['tag_id'], 'post_tag' ); ?>">
										<?php echo esc_html( $leader['tag_name'] ); ?>
									</a>
								</td>
								<td><?php echo $leader['post_count']; ?></td>
								<td><?php echo $leader['media_count']; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width: 900px; margin-bottom: 20px;">
			<h2>Preview Associations</h2>
			<p>Preview which content would be associated with leaders before running the bulk update.</p>
			
			<form method="post">
				<?php wp_nonce_field( 'wasmo_associations_nonce' ); ?>
				<p>
					<label>
						<input type="checkbox" name="include_text_search" value="1" checked>
						<strong>Include media text search</strong> - Search media alt text, captions, and filenames for leader names
					</label>
				</p>
				<p>
					<button type="submit" name="wasmo_preview_associations" class="button">
						Preview Associations
					</button>
				</p>
			</form>

			<?php if ( ! empty( $results ) ) : ?>
				<?php 
				$total_posts = 0;
				$total_media_tag = 0;
				$total_media_text = 0;
				foreach ( $results as $data ) {
					$total_posts += count( $data['posts'] );
					$total_media_tag += count( $data['media_by_tag'] );
					$total_media_text += count( $data['media_by_text'] );
				}
				?>
				<h3>Preview Results Summary</h3>
				<p>
					<strong>Total items found:</strong> <?php echo $total_posts + $total_media_tag + $total_media_text; ?><br>
					&bull; Posts (by tag): <?php echo $total_posts; ?><br>
					&bull; Media (by tag): <?php echo $total_media_tag; ?><br>
					&bull; Media (by text search): <?php echo $total_media_text; ?>
				</p>
				
				<div style="max-height: 500px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #f9f9f9;">
					<?php foreach ( $results as $leader_id => $data ) : ?>
						<div style="background: #fff; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
							<h4 style="margin-top: 0;"><?php echo esc_html( $data['leader_name'] ); ?></h4>
							
							<?php if ( ! empty( $data['posts'] ) ) : ?>
								<p><strong>📝 Posts by tag (<?php echo count( $data['posts'] ); ?>):</strong></p>
								<ul style="margin-left: 20px; margin-bottom: 10px;">
									<?php foreach ( array_slice( $data['posts'], 0, 5 ) as $post_id ) : ?>
										<li>
											<a href="<?php echo get_edit_post_link( $post_id ); ?>" target="_blank">
												<?php echo esc_html( get_the_title( $post_id ) ); ?>
											</a>
										</li>
									<?php endforeach; ?>
									<?php if ( count( $data['posts'] ) > 5 ) : ?>
										<li><em>...and <?php echo count( $data['posts'] ) - 5; ?> more</em></li>
									<?php endif; ?>
								</ul>
							<?php endif; ?>

							<?php if ( ! empty( $data['media_by_tag'] ) ) : ?>
								<p><strong>🏷️ Media by tag (<?php echo count( $data['media_by_tag'] ); ?>):</strong></p>
								<ul style="margin-left: 20px; margin-bottom: 10px;">
									<?php foreach ( array_slice( $data['media_by_tag'], 0, 5 ) as $media_id ) : ?>
										<li>
											<a href="<?php echo get_edit_post_link( $media_id ); ?>" target="_blank">
												<?php echo esc_html( get_the_title( $media_id ) ); ?>
											</a>
										</li>
									<?php endforeach; ?>
									<?php if ( count( $data['media_by_tag'] ) > 5 ) : ?>
										<li><em>...and <?php echo count( $data['media_by_tag'] ) - 5; ?> more</em></li>
									<?php endif; ?>
								</ul>
							<?php endif; ?>

							<?php if ( ! empty( $data['media_by_text'] ) ) : ?>
								<p><strong>🔍 Media by text search (<?php echo count( $data['media_by_text'] ); ?>):</strong></p>
								<ul style="margin-left: 20px; margin-bottom: 0;">
									<?php 
									$count = 0;
									foreach ( $data['media_by_text'] as $media_id => $match_info ) : 
										if ( $count >= 5 ) break;
										$count++;
									?>
										<li>
											<a href="<?php echo get_edit_post_link( $media_id ); ?>" target="_blank">
												<?php echo esc_html( get_the_title( $media_id ) ); ?>
											</a>
											<br>
											<small style="color: #666;">
												Matched "<em><?php echo esc_html( $match_info['match_term'] ); ?></em>" 
												in: <?php echo esc_html( implode( ', ', $match_info['match_in'] ) ); ?>
											</small>
										</li>
									<?php endforeach; ?>
									<?php if ( count( $data['media_by_text'] ) > 5 ) : ?>
										<li><em>...and <?php echo count( $data['media_by_text'] ) - 5; ?> more</em></li>
									<?php endif; ?>
								</ul>
							<?php endif; ?>

							<?php if ( empty( $data['posts'] ) && empty( $data['media_by_tag'] ) && empty( $data['media_by_text'] ) ) : ?>
								<p><em>No content found</em></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width: 900px;">
			<h2>Run Bulk Association</h2>
			<p>This will update the "Related Leaders" field on all matching content.</p>
			
			<form method="post">
				<?php wp_nonce_field( 'wasmo_associations_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">Content type</th>
						<td>
							<select name="content_type" id="content_type">
								<option value="all">All (Posts + Media)</option>
								<option value="post">Posts only</option>
								<option value="attachment">Media only</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">Media matching</th>
						<td>
							<label>
								<input type="checkbox" name="include_text_search" value="1" checked>
								<strong>Include text search</strong>
							</label>
							<p class="description">
								Search media alt text, captions, descriptions, and filenames for leader names.
								This finds media that may not be tagged but contains the leader's name.
							</p>
						</td>
					</tr>
				</table>
				<p>
					<button type="submit" name="wasmo_run_associations" class="button button-primary" onclick="return confirm('This will update content associations. Continue?');">
						Run Bulk Association
					</button>
				</p>
			</form>
		</div>

		<div class="card" style="max-width: 900px; margin-top: 20px;">
			<h2>Manual Association</h2>
			<p>
				You can also manually associate content with leaders by editing individual posts or media items
				and selecting leaders in the "Related Leaders" field in the sidebar.
			</p>
			<p>
				<a href="<?php echo admin_url( 'edit.php' ); ?>" class="button">Edit Posts</a>
				<a href="<?php echo admin_url( 'upload.php' ); ?>" class="button">Edit Media</a>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Get leaders that have associated tags
 *
 * @return array Array of leader data with tag info.
 */
function wasmo_get_leaders_with_tags() {
	$leaders = get_posts( array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'     => 'leader_tag',
				'compare' => 'EXISTS',
			),
			array(
				'key'     => 'leader_tag',
				'value'   => '',
				'compare' => '!=',
			),
		),
	) );

	$result = array();

	foreach ( $leaders as $leader ) {
		$tag_id = get_field( 'leader_tag', $leader->ID );
		if ( ! $tag_id ) {
			continue;
		}

		$tag = get_term( $tag_id, 'post_tag' );
		if ( ! $tag || is_wp_error( $tag ) ) {
			continue;
		}

		// Count posts with this tag
		$post_count = get_posts( array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $tag_id,
				),
			),
			'fields'         => 'ids',
		) );

		// Count media with this tag
		$media_count = get_posts( array(
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_status'    => 'inherit',
			'tax_query'      => array(
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $tag_id,
				),
			),
			'fields'         => 'ids',
		) );

		$result[] = array(
			'id'          => $leader->ID,
			'name'        => $leader->post_title,
			'tag_id'      => $tag_id,
			'tag_name'    => $tag->name,
			'post_count'  => count( $post_count ),
			'media_count' => count( $media_count ),
		);
	}

	// Sort by name
	usort( $result, function( $a, $b ) {
		return strcmp( $a['name'], $b['name'] );
	} );

	return $result;
}

/**
 * Get all church leaders with search terms for media matching
 *
 * @return array Array of leader data with search terms.
 */
function wasmo_get_all_leaders_for_search() {
	$leaders = get_posts( array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	) );

	$result = array();

	foreach ( $leaders as $leader ) {
		$first_name = get_field( 'first_name', $leader->ID );
		$middle_name = get_field( 'middle_name', $leader->ID );
		$last_name = get_field( 'last_name', $leader->ID );
		$tag_id = get_field( 'leader_tag', $leader->ID );
		$tag_name = '';
		
		if ( $tag_id ) {
			$tag = get_term( $tag_id, 'post_tag' );
			if ( $tag && ! is_wp_error( $tag ) ) {
				$tag_name = $tag->name;
			}
		}

		// Build search terms - various name combinations
		$search_terms = array();
		
		// Full name from post title
		$search_terms[] = $leader->post_title;
		
		// Tag name if different
		if ( $tag_name && $tag_name !== $leader->post_title ) {
			$search_terms[] = $tag_name;
		}
		
		// Last name + first name
		if ( $first_name && $last_name ) {
			$search_terms[] = $first_name . ' ' . $last_name;
			// $search_terms[] = $last_name . ', ' . $first_name;
			// $search_terms[] = $last_name . ' ' . $first_name;
		}
		
		// With middle name/initial
		if ( $first_name && $middle_name && $last_name ) {
			$search_terms[] = $first_name . ' ' . $middle_name . ' ' . $last_name;
			$search_terms[] = $first_name . ' ' . substr( $middle_name, 0, 1 ) . '. ' . $last_name;
			$search_terms[] = $first_name . ' ' . substr( $middle_name, 0, 1 ) . ' ' . $last_name;
		}

		// Remove duplicates and empty values
		$search_terms = array_unique( array_filter( $search_terms ) );

		$result[] = array(
			'id'           => $leader->ID,
			'name'         => $leader->post_title,
			'tag_id'       => $tag_id,
			'tag_name'     => $tag_name,
			'search_terms' => $search_terms,
			'last_name'    => $last_name,
		);
	}

	// Sort by name
	usort( $result, function( $a, $b ) {
		return strcmp( $a['name'], $b['name'] );
	} );

	return $result;
}

/**
 * Search media library for items matching a leader's name
 *
 * @param array $leader Leader data with search_terms.
 * @return array Array of matching media IDs with match info.
 */
function wasmo_search_media_for_leader( $leader ) {
	global $wpdb;
	
	$matches = array();
	$found_ids = array();
	
	foreach ( $leader['search_terms'] as $search_term ) {
		// Skip very short terms (like single initials) to avoid false positives
		if ( strlen( $search_term ) < 4 ) {
			continue;
		}
		
		$like_term = '%' . $wpdb->esc_like( $search_term ) . '%';
		
		// Search in post_title, post_excerpt (caption), and post_content (description)
		$sql = $wpdb->prepare(
			"SELECT ID, post_title, post_excerpt, post_content 
			 FROM {$wpdb->posts} 
			 WHERE post_type = 'attachment' 
			 AND post_status = 'inherit'
			 AND (
				 post_title LIKE %s 
				 OR post_excerpt LIKE %s 
				 OR post_content LIKE %s
			 )",
			$like_term,
			$like_term,
			$like_term
		);
		
		$results = $wpdb->get_results( $sql );
		
		foreach ( $results as $row ) {
			if ( ! in_array( $row->ID, $found_ids ) ) {
				$found_ids[] = $row->ID;
				$match_locations = array();
				
				if ( stripos( $row->post_title, $search_term ) !== false ) {
					$match_locations[] = 'title';
				}
				if ( stripos( $row->post_excerpt, $search_term ) !== false ) {
					$match_locations[] = 'caption';
				}
				if ( stripos( $row->post_content, $search_term ) !== false ) {
					$match_locations[] = 'description';
				}
				
				$matches[ $row->ID ] = array(
					'id'         => $row->ID,
					'match_term' => $search_term,
					'match_in'   => $match_locations,
				);
			}
		}
		
		// Search in alt text (post meta _wp_attachment_image_alt)
		$alt_sql = $wpdb->prepare(
			"SELECT p.ID, p.post_title, m.meta_value as alt_text
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
			 WHERE p.post_type = 'attachment'
			 AND p.post_status = 'inherit'
			 AND m.meta_key = '_wp_attachment_image_alt'
			 AND m.meta_value LIKE %s",
			$like_term
		);
		
		$alt_results = $wpdb->get_results( $alt_sql );
		
		foreach ( $alt_results as $row ) {
			if ( ! in_array( $row->ID, $found_ids ) ) {
				$found_ids[] = $row->ID;
				$matches[ $row->ID ] = array(
					'id'         => $row->ID,
					'match_term' => $search_term,
					'match_in'   => array( 'alt_text' ),
				);
			} elseif ( isset( $matches[ $row->ID ] ) && ! in_array( 'alt_text', $matches[ $row->ID ]['match_in'] ) ) {
				$matches[ $row->ID ]['match_in'][] = 'alt_text';
			}
		}
		
		// Search in filename (stored in guid or _wp_attached_file meta)
		$file_sql = $wpdb->prepare(
			"SELECT p.ID, p.post_title, m.meta_value as file_path
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id
			 WHERE p.post_type = 'attachment'
			 AND p.post_status = 'inherit'
			 AND m.meta_key = '_wp_attached_file'
			 AND m.meta_value LIKE %s",
			$like_term
		);
		
		$file_results = $wpdb->get_results( $file_sql );
		
		foreach ( $file_results as $row ) {
			if ( ! in_array( $row->ID, $found_ids ) ) {
				$found_ids[] = $row->ID;
				$matches[ $row->ID ] = array(
					'id'         => $row->ID,
					'match_term' => $search_term,
					'match_in'   => array( 'filename' ),
				);
			} elseif ( isset( $matches[ $row->ID ] ) && ! in_array( 'filename', $matches[ $row->ID ]['match_in'] ) ) {
				$matches[ $row->ID ]['match_in'][] = 'filename';
			}
		}
	}
	
	return $matches;
}

/**
 * Preview what associations would be created
 *
 * @param bool $include_text_search Include text-based media search.
 * @return array Preview data.
 */
function wasmo_preview_leader_associations( $include_text_search = true ) {
	$leaders_with_tags = wasmo_get_leaders_with_tags();
	$all_leaders = wasmo_get_all_leaders_for_search();
	$results = array();

	// First, process tag-based associations
	foreach ( $leaders_with_tags as $leader ) {
		$tag_id = $leader['tag_id'];

		// Get posts with this tag
		$posts = get_posts( array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $tag_id,
				),
			),
			'fields'         => 'ids',
		) );

		// Get media with this tag
		$media_by_tag = get_posts( array(
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_status'    => 'inherit',
			'tax_query'      => array(
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'term_id',
					'terms'    => $tag_id,
				),
			),
			'fields'         => 'ids',
		) );

		if ( ! empty( $posts ) || ! empty( $media_by_tag ) ) {
			$results[ $leader['id'] ] = array(
				'leader_name'   => $leader['name'],
				'posts'         => $posts,
				'media_by_tag'  => $media_by_tag,
				'media_by_text' => array(),
			);
		}
	}

	// Then, search media by text for all leaders
	if ( $include_text_search ) {
		foreach ( $all_leaders as $leader ) {
			$text_matches = wasmo_search_media_for_leader( $leader );
			
			if ( ! empty( $text_matches ) ) {
				// Filter out any already found by tag
				$existing_media = isset( $results[ $leader['id'] ]['media_by_tag'] ) 
					? $results[ $leader['id'] ]['media_by_tag'] 
					: array();
				
				$new_matches = array();
				foreach ( $text_matches as $media_id => $match_info ) {
					if ( ! in_array( $media_id, $existing_media ) ) {
						$new_matches[ $media_id ] = $match_info;
					}
				}
				
				if ( ! empty( $new_matches ) ) {
					if ( ! isset( $results[ $leader['id'] ] ) ) {
						$results[ $leader['id'] ] = array(
							'leader_name'   => $leader['name'],
							'posts'         => array(),
							'media_by_tag'  => array(),
							'media_by_text' => $new_matches,
						);
					} else {
						$results[ $leader['id'] ]['media_by_text'] = $new_matches;
					}
				}
			}
		}
	}

	// Sort results by leader name
	uasort( $results, function( $a, $b ) {
		return strcmp( $a['leader_name'], $b['leader_name'] );
	} );

	return $results;
}

/**
 * Run bulk associations
 *
 * @param string $post_type Post type to process ('post', 'attachment', or 'all').
 * @param bool $include_text_search Include text-based media search.
 * @return array Result with counts.
 */
function wasmo_run_leader_associations( $post_type = 'all', $include_text_search = true ) {
	$leaders_with_tags = wasmo_get_leaders_with_tags();
	$all_leaders = wasmo_get_all_leaders_for_search();
	$updated_posts = 0;
	$updated_media_tag = 0;
	$updated_media_text = 0;

	// Process tag-based associations
	foreach ( $leaders_with_tags as $leader ) {
		$tag_id = $leader['tag_id'];
		$leader_id = $leader['id'];

		// Process posts
		if ( $post_type === 'all' || $post_type === 'post' ) {
			$posts = get_posts( array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'tax_query'      => array(
					array(
						'taxonomy' => 'post_tag',
						'field'    => 'term_id',
						'terms'    => $tag_id,
					),
				),
				'fields'         => 'ids',
			) );

			foreach ( $posts as $post_id ) {
				$result = wasmo_add_leader_to_content( $post_id, $leader_id );
				if ( $result ) {
					$updated_posts++;
				}
			}
		}

		// Process media by tag
		if ( $post_type === 'all' || $post_type === 'attachment' ) {
			$media = get_posts( array(
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
				'post_status'    => 'inherit',
				'tax_query'      => array(
					array(
						'taxonomy' => 'post_tag',
						'field'    => 'term_id',
						'terms'    => $tag_id,
					),
				),
				'fields'         => 'ids',
			) );

			foreach ( $media as $media_id ) {
				$result = wasmo_add_leader_to_content( $media_id, $leader_id );
				if ( $result ) {
					$updated_media_tag++;
				}
			}
		}
	}

	// Process text-based media associations
	if ( $include_text_search && ( $post_type === 'all' || $post_type === 'attachment' ) ) {
		foreach ( $all_leaders as $leader ) {
			$text_matches = wasmo_search_media_for_leader( $leader );
			
			foreach ( $text_matches as $media_id => $match_info ) {
				$result = wasmo_add_leader_to_content( $media_id, $leader['id'] );
				if ( $result ) {
					$updated_media_text++;
				}
			}
		}
	}

	return array( 
		'updated'            => $updated_posts + $updated_media_tag + $updated_media_text,
		'updated_posts'      => $updated_posts,
		'updated_media_tag'  => $updated_media_tag,
		'updated_media_text' => $updated_media_text,
	);
}

/**
 * Add a leader to content's related_leaders field
 *
 * @param int $content_id Post or attachment ID.
 * @param int $leader_id Church leader post ID.
 * @return bool True if updated, false if already associated.
 */
function wasmo_add_leader_to_content( $content_id, $leader_id ) {
	$existing = get_field( 'related_leaders', $content_id );
	
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}

	// Check if already associated
	if ( in_array( $leader_id, $existing ) ) {
		return false;
	}

	// Add the leader
	$existing[] = $leader_id;
	update_field( 'related_leaders', $existing, $content_id );

	return true;
}

/**
 * AJAX handler for checking tag matches
 */
function wasmo_ajax_check_tag_match() {
	check_ajax_referer( 'wasmo_tag_match_nonce', 'nonce' );

	$leader_name = sanitize_text_field( $_POST['leader_name'] );
	
	// Try to find matching tag
	$tag = get_term_by( 'name', $leader_name, 'post_tag' );
	
	if ( $tag ) {
		wp_send_json_success( array(
			'found'    => true,
			'tag_id'   => $tag->term_id,
			'tag_name' => $tag->name,
			'count'    => $tag->count,
		) );
	} else {
		// Try partial match
		$tags = get_terms( array(
			'taxonomy'   => 'post_tag',
			'name__like' => $leader_name,
			'hide_empty' => false,
		) );

		if ( ! empty( $tags ) ) {
			$suggestions = array();
			foreach ( $tags as $t ) {
				$suggestions[] = array(
					'id'    => $t->term_id,
					'name'  => $t->name,
					'count' => $t->count,
				);
			}
			wp_send_json_success( array(
				'found'       => false,
				'suggestions' => $suggestions,
			) );
		}

		wp_send_json_success( array( 'found' => false ) );
	}
}
add_action( 'wp_ajax_wasmo_check_tag_match', 'wasmo_ajax_check_tag_match' );
