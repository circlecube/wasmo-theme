<?php
/**
 * Church Leaders Wikipedia Image Importer
 * 
 * Admin page for fetching and importing leader images from Wikipedia/Wikimedia Commons.
 *
 * @package wasmo
 */

/**
 * Add admin menu page for Wikipedia image import
 */
function wasmo_add_leader_images_page() {
	add_submenu_page(
		'edit.php?post_type=church-leader',
		'Import Wikipedia Images',
		'Import Images',
		'manage_options',
		'import-leader-images',
		'wasmo_render_leader_images_page'
	);
}
add_action( 'admin_menu', 'wasmo_add_leader_images_page' );

/**
 * Render the image import admin page
 */
function wasmo_render_leader_images_page() {
	$message = '';
	
	// Handle bulk import
	if ( isset( $_POST['wasmo_import_all_images'] ) && check_admin_referer( 'wasmo_import_images_nonce' ) ) {
		$result = wasmo_bulk_import_wikipedia_images();
		$message = '<div class="notice notice-success"><p>';
		$message .= sprintf( 
			'Import complete! %d images imported, %d already had images, %d not found on Wikipedia.', 
			$result['imported'], 
			$result['skipped'], 
			$result['not_found']
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
	
	// Handle single leader import
	if ( isset( $_POST['wasmo_import_single_image'] ) && check_admin_referer( 'wasmo_import_single_image_nonce' ) ) {
		$leader_id = intval( $_POST['leader_id'] );
		$custom_url = isset( $_POST['custom_wikipedia_url'] ) ? sanitize_text_field( $_POST['custom_wikipedia_url'] ) : '';
		
		if ( ! empty( $custom_url ) ) {
			// Import from custom Wikipedia URL
			$result = wasmo_import_wikipedia_image_from_url( $leader_id, $custom_url );
		} else {
			// Use automatic search
			$result = wasmo_import_wikipedia_image_for_leader( $leader_id );
		}
		
		if ( is_wp_error( $result ) ) {
			$message = '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		} else {
			$message = '<div class="notice notice-success"><p>Image imported successfully for ' . get_the_title( $leader_id ) . '!</p></div>';
		}
	}
	
	// Get leaders without featured images
	$leaders_without_images = get_posts( array(
		'post_type' => 'church-leader',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => '_thumbnail_id',
				'compare' => 'NOT EXISTS',
			),
		),
		'orderby' => 'title',
		'order' => 'ASC',
	) );
	
	// Get leaders with featured images
	$leaders_with_images = get_posts( array(
		'post_type' => 'church-leader',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => '_thumbnail_id',
				'compare' => 'EXISTS',
			),
		),
		'orderby' => 'title',
		'order' => 'ASC',
	) );
	
	$total_leaders = count( $leaders_without_images ) + count( $leaders_with_images );
	?>
	<div class="wrap">
		<h1>Import Wikipedia Images for Church Leaders</h1>
		
		<?php echo $message; ?>

		<div class="card" style="max-width: 800px; margin-bottom: 20px;">
			<h2>Bulk Import from Wikipedia</h2>
			<p>
				This tool searches Wikipedia for images of church leaders and imports them as featured images.
				Images are sourced from Wikimedia Commons and are typically public domain or freely licensed.
			</p>
			<p>
				<strong>Leaders with images:</strong> <?php echo count( $leaders_with_images ); ?> / <?php echo $total_leaders; ?><br>
				<strong>Leaders without images:</strong> <?php echo count( $leaders_without_images ); ?>
			</p>
			<p><em>Note: This process may take several minutes for many leaders. Wikipedia API has rate limits.</em></p>
			
			<form method="post">
				<?php wp_nonce_field( 'wasmo_import_images_nonce' ); ?>
				<p>
					<label>
						<input type="checkbox" name="overwrite_existing" value="1">
						Overwrite existing featured images
					</label>
				</p>
				<p>
					<button type="submit" name="wasmo_import_all_images" class="button button-primary" 
						onclick="return confirm('This will import images for <?php echo count( $leaders_without_images ); ?> leaders. This may take several minutes. Continue?');">
						Import Images for Leaders Without Photos
					</button>
				</p>
			</form>
		</div>

		<div class="card" style="max-width: 800px; margin-bottom: 20px;">
			<h2>Import Single Leader Image</h2>
			<form method="post">
				<?php wp_nonce_field( 'wasmo_import_single_image_nonce' ); ?>
				<p>
					<label for="leader_id">Select Leader:</label><br>
					<select name="leader_id" id="leader_id" style="width: 100%; max-width: 400px;">
						<optgroup label="Without Featured Image">
							<?php foreach ( $leaders_without_images as $leader ) : ?>
								<option value="<?php echo $leader->ID; ?>">
									<?php echo esc_html( $leader->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</optgroup>
						<optgroup label="With Featured Image">
							<?php foreach ( $leaders_with_images as $leader ) : ?>
								<option value="<?php echo $leader->ID; ?>">
									<?php echo esc_html( $leader->post_title ); ?> ✓
								</option>
							<?php endforeach; ?>
						</optgroup>
					</select>
				</p>
				<p>
					<label for="custom_wikipedia_url">Custom Wikipedia URL (optional):</label><br>
					<input type="url" name="custom_wikipedia_url" id="custom_wikipedia_url" 
						   placeholder="https://en.wikipedia.org/wiki/Person_Name_(disambiguation)" 
						   style="width: 100%; max-width: 400px;">
					<br><small style="color: #666;">Leave empty to use automatic search. Use this when the leader has a complex Wikipedia URL with disambiguation.</small>
				</p>
				<p>
					<button type="submit" name="wasmo_import_single_image" class="button button-secondary">
						Import Image for Selected Leader
					</button>
				</p>
			</form>
		</div>

		<div class="card" style="max-width: 800px;">
			<h2>Leaders Without Featured Images (<?php echo count( $leaders_without_images ); ?>)</h2>
			<?php if ( empty( $leaders_without_images ) ) : ?>
				<p><em>All leaders have featured images! 🎉</em></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Leader Name</th>
							<th>Wikipedia Search Term</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $leaders_without_images as $leader ) : 
							$search_term = wasmo_get_wikipedia_search_term( $leader->ID );
						?>
							<tr>
								<td>
									<a href="<?php echo get_edit_post_link( $leader->ID ); ?>">
										<?php echo esc_html( $leader->post_title ); ?>
									</a>
								</td>
								<td>
									<code><?php echo esc_html( $search_term ); ?></code>
								</td>
								<td>
									<a href="https://en.wikipedia.org/wiki/<?php echo urlencode( str_replace( ' ', '_', $search_term ) ); ?>" 
									   target="_blank" class="button button-small">
										View Wikipedia
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Get Wikipedia search term for a leader
 * 
 * @param int $leader_id The leader post ID.
 * @return string The search term.
 */
function wasmo_get_wikipedia_search_term( $leader_id ) {
	$title = get_the_title( $leader_id );
	
	// Get first, middle, last name from ACF fields if available
	$first_name = get_field( 'first_name', $leader_id );
	$middle_name = get_field( 'middle_name', $leader_id );
	$last_name = get_field( 'last_name', $leader_id );
	
	// If we have ACF names, construct full name
	if ( $first_name && $last_name ) {
		$full_name = trim( $first_name . ' ' . $middle_name . ' ' . $last_name );
	} else {
		$full_name = $title;
	}
	
	// Add disambiguation for common names or LDS context
	$is_president = wasmo_leader_has_role( $leader_id, 'president' );
	
	// Special cases for disambiguation
	$special_cases = array(
		'Joseph Smith' => 'Joseph Smith',
		'Brigham Young' => 'Brigham Young',
		'John Taylor' => 'John Taylor (Mormon)',
		'Spencer W. Kimball' => 'Spencer W. Kimball',
		'Russell M. Nelson' => 'Russell M. Nelson',
		'Dallin H. Oaks' => 'Dallin H. Oaks',
		'Thomas S. Monson' => 'Thomas S. Monson',
		'Gordon B. Hinckley' => 'Gordon B. Hinckley',
	);
	
	// Check if this name needs disambiguation
	foreach ( $special_cases as $name => $wiki_name ) {
		if ( stripos( $full_name, $name ) !== false ) {
			return $wiki_name;
		}
	}
	
	return $full_name;
}

/**
 * Search Wikipedia for a person and get their main image
 * 
 * @param string $search_term The name to search for.
 * @return string|WP_Error The image URL or error.
 */
function wasmo_search_wikipedia_image( $search_term ) {
	// First, search for the page
	$search_url = add_query_arg( array(
		'action' => 'query',
		'format' => 'json',
		'titles' => $search_term,
		'prop' => 'pageimages|pageterms',
		'piprop' => 'original',
		'pilicense' => 'any',
		'redirects' => 1,
	), 'https://en.wikipedia.org/w/api.php' );
	
	$response = wp_remote_get( $search_url, array(
		'timeout' => 30,
		'user-agent' => 'WasMormon.org Church Leaders Image Importer/1.0 (https://wasmormon.org)',
	) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	
	if ( empty( $data['query']['pages'] ) ) {
		return new WP_Error( 'no_results', 'No Wikipedia page found for: ' . $search_term );
	}
	
	$pages = $data['query']['pages'];
	$page = reset( $pages );
	
	// Check if page exists
	if ( isset( $page['missing'] ) ) {
		// Try with " (Latter-day Saint)" disambiguation
		return wasmo_search_wikipedia_image_with_disambiguation( $search_term );
	}
	
	// Get the original image
	if ( ! empty( $page['original']['source'] ) ) {
		return $page['original']['source'];
	}
	
	// If no image from pageimages, try getting images from the page
	return wasmo_get_wikipedia_page_image( $page['pageid'] );
}

/**
 * Try searching with LDS disambiguation
 * 
 * @param string $search_term The name to search for.
 * @return string|WP_Error The image URL or error.
 */
function wasmo_search_wikipedia_image_with_disambiguation( $search_term ) {
	$disambiguations = array(
		$search_term . ' (Latter-day Saint)',
		$search_term . ' (Mormon)',
		$search_term . ' (religious leader)',
		$search_term . ' (LDS Church)',
	);
	
	foreach ( $disambiguations as $term ) {
		$search_url = add_query_arg( array(
			'action' => 'query',
			'format' => 'json',
			'titles' => $term,
			'prop' => 'pageimages',
			'piprop' => 'original',
			'pilicense' => 'any',
			'redirects' => 1,
		), 'https://en.wikipedia.org/w/api.php' );
		
		$response = wp_remote_get( $search_url, array(
			'timeout' => 30,
			'user-agent' => 'WasMormon.org Church Leaders Image Importer/1.0 (https://wasmormon.org)',
		) );
		
		if ( is_wp_error( $response ) ) {
			continue;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if ( empty( $data['query']['pages'] ) ) {
			continue;
		}
		
		$pages = $data['query']['pages'];
		$page = reset( $pages );
		
		if ( ! isset( $page['missing'] ) && ! empty( $page['original']['source'] ) ) {
			return $page['original']['source'];
		}
	}
	
	return new WP_Error( 'no_image', 'No image found on Wikipedia for: ' . $search_term );
}

/**
 * Get image from Wikipedia page by page ID
 * 
 * @param int $page_id The Wikipedia page ID.
 * @return string|WP_Error The image URL or error.
 */
function wasmo_get_wikipedia_page_image( $page_id ) {
	// Get images from the page
	$images_url = add_query_arg( array(
		'action' => 'query',
		'format' => 'json',
		'pageids' => $page_id,
		'prop' => 'images',
		'imlimit' => 10,
	), 'https://en.wikipedia.org/w/api.php' );
	
	$response = wp_remote_get( $images_url, array(
		'timeout' => 30,
		'user-agent' => 'WasMormon.org Church Leaders Image Importer/1.0 (https://wasmormon.org)',
	) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	
	if ( empty( $data['query']['pages'][ $page_id ]['images'] ) ) {
		return new WP_Error( 'no_images', 'No images found on Wikipedia page' );
	}
	
	// Look for portrait/photo images (skip icons, flags, etc.)
	$images = $data['query']['pages'][ $page_id ]['images'];
	$candidate_image = null;
	
	foreach ( $images as $image ) {
		$title = $image['title'];
		
		// Skip common non-portrait images
		if ( preg_match( '/\.(svg|gif)$/i', $title ) ) {
			continue;
		}
		if ( stripos( $title, 'icon' ) !== false || 
			 stripos( $title, 'flag' ) !== false ||
			 stripos( $title, 'logo' ) !== false ||
			 stripos( $title, 'map' ) !== false ||
			 stripos( $title, 'seal' ) !== false ||
			 stripos( $title, 'coat' ) !== false ) {
			continue;
		}
		
		// Prefer images with portrait-related names
		if ( preg_match( '/portrait|photo|headshot|official/i', $title ) ) {
			$candidate_image = $title;
			break;
		}
		
		// Otherwise take first JPG image
		if ( ! $candidate_image && preg_match( '/\.(jpg|jpeg|png)$/i', $title ) ) {
			$candidate_image = $title;
		}
	}
	
	if ( ! $candidate_image ) {
		return new WP_Error( 'no_suitable_image', 'No suitable portrait image found' );
	}
	
	// Get the actual image URL from Wikimedia Commons
	return wasmo_get_wikimedia_image_url( $candidate_image );
}

/**
 * Get the actual URL for a Wikimedia Commons image
 * 
 * @param string $file_title The image file title (e.g., "File:Example.jpg").
 * @return string|WP_Error The image URL or error.
 */
function wasmo_get_wikimedia_image_url( $file_title ) {
	$url = add_query_arg( array(
		'action' => 'query',
		'format' => 'json',
		'titles' => $file_title,
		'prop' => 'imageinfo',
		'iiprop' => 'url|size|mime',
		'iiurlwidth' => 800, // Request a reasonable size
	), 'https://en.wikipedia.org/w/api.php' );
	
	$response = wp_remote_get( $url, array(
		'timeout' => 30,
		'user-agent' => 'WasMormon.org Church Leaders Image Importer/1.0 (https://wasmormon.org)',
	) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	
	if ( empty( $data['query']['pages'] ) ) {
		return new WP_Error( 'no_image_info', 'Could not get image info' );
	}
	
	$pages = $data['query']['pages'];
	$page = reset( $pages );
	
	if ( empty( $page['imageinfo'][0]['url'] ) ) {
		return new WP_Error( 'no_image_url', 'Could not get image URL' );
	}
	
	// Use thumburl if available (for reasonable size), otherwise original
	$image_info = $page['imageinfo'][0];
	return ! empty( $image_info['thumburl'] ) ? $image_info['thumburl'] : $image_info['url'];
}

/**
 * Download and attach an image to a leader post
 * 
 * @param string $image_url The URL of the image.
 * @param int $leader_id The leader post ID.
 * @return int|WP_Error The attachment ID or error.
 */
function wasmo_download_and_attach_image( $image_url, $leader_id ) {
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	
	$leader_title = get_the_title( $leader_id );
	
	// Download the image
	$tmp = download_url( $image_url, 60 );
	
	if ( is_wp_error( $tmp ) ) {
		return $tmp;
	}
	
	// Get file info
	$file_array = array(
		'name' => sanitize_file_name( $leader_title . '-portrait.jpg' ),
		'tmp_name' => $tmp,
	);
	
	// Check file type
	$file_type = wp_check_filetype( $image_url );
	if ( $file_type['ext'] ) {
		$file_array['name'] = sanitize_file_name( $leader_title . '-portrait.' . $file_type['ext'] );
	}
	
	// Upload to media library
	$attachment_id = media_handle_sideload( $file_array, $leader_id, $leader_title . ' Portrait' );
	
	// Clean up temp file
	if ( file_exists( $tmp ) ) {
		@unlink( $tmp );
	}
	
	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}
	
	// Set as featured image
	set_post_thumbnail( $leader_id, $attachment_id );
	
	// Set alt text
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $leader_title );
	
	return $attachment_id;
}

/**
 * Import Wikipedia image from a custom URL
 * 
 * @param int $leader_id The leader post ID.
 * @param string $wikipedia_url The full Wikipedia URL.
 * @return int|WP_Error The attachment ID or error.
 */
function wasmo_import_wikipedia_image_from_url( $leader_id, $wikipedia_url ) {
	// Check if leader already has a featured image
	if ( has_post_thumbnail( $leader_id ) && ! isset( $_POST['overwrite_existing'] ) ) {
		return new WP_Error( 'has_image', 'Leader already has a featured image' );
	}
	
	// Extract the page title from the URL
	$page_title = wasmo_extract_wikipedia_title_from_url( $wikipedia_url );
	
	if ( is_wp_error( $page_title ) ) {
		return $page_title;
	}
	
	// Search Wikipedia for image using the extracted title
	$image_url = wasmo_search_wikipedia_image( $page_title );
	
	if ( is_wp_error( $image_url ) ) {
		return $image_url;
	}
	
	// Download and attach image
	return wasmo_download_and_attach_image( $image_url, $leader_id );
}

/**
 * Extract Wikipedia page title from a URL
 * 
 * @param string $url The Wikipedia URL.
 * @return string|WP_Error The page title or error.
 */
function wasmo_extract_wikipedia_title_from_url( $url ) {
	// Validate it's a Wikipedia URL
	if ( ! preg_match( '/^https?:\/\/([\w]+\.)?wikipedia\.org\/wiki\/(.+)$/i', $url, $matches ) ) {
		return new WP_Error( 'invalid_url', 'Please provide a valid Wikipedia URL (e.g., https://en.wikipedia.org/wiki/Person_Name)' );
	}
	
	// Get the page title (last part of URL)
	$page_title = $matches[2];
	
	// Remove any anchor/fragment
	$page_title = preg_replace( '/#.*$/', '', $page_title );
	
	// URL decode the title
	$page_title = urldecode( $page_title );
	
	// Convert underscores to spaces (Wikipedia uses underscores in URLs)
	$page_title = str_replace( '_', ' ', $page_title );
	
	if ( empty( $page_title ) ) {
		return new WP_Error( 'empty_title', 'Could not extract page title from URL' );
	}
	
	return $page_title;
}

/**
 * Import Wikipedia image for a single leader
 * 
 * @param int $leader_id The leader post ID.
 * @return int|WP_Error The attachment ID or error.
 */
function wasmo_import_wikipedia_image_for_leader( $leader_id ) {
	// Check if leader already has a featured image
	if ( has_post_thumbnail( $leader_id ) && ! isset( $_POST['overwrite_existing'] ) ) {
		return new WP_Error( 'has_image', 'Leader already has a featured image' );
	}
	
	// Get search term
	$search_term = wasmo_get_wikipedia_search_term( $leader_id );
	
	// Search Wikipedia for image
	$image_url = wasmo_search_wikipedia_image( $search_term );
	
	if ( is_wp_error( $image_url ) ) {
		return $image_url;
	}
	
	// Download and attach image
	return wasmo_download_and_attach_image( $image_url, $leader_id );
}

/**
 * Bulk import Wikipedia images for all leaders without featured images
 * 
 * @return array Results array with counts.
 */
function wasmo_bulk_import_wikipedia_images() {
	$results = array(
		'imported' => 0,
		'skipped' => 0,
		'not_found' => 0,
		'errors' => array(),
	);
	
	$overwrite = isset( $_POST['overwrite_existing'] ) && $_POST['overwrite_existing'];
	
	// Build query based on overwrite setting
	$args = array(
		'post_type' => 'church-leader',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'orderby' => 'title',
		'order' => 'ASC',
	);
	
	if ( ! $overwrite ) {
		$args['meta_query'] = array(
			array(
				'key' => '_thumbnail_id',
				'compare' => 'NOT EXISTS',
			),
		);
	}
	
	$leaders = get_posts( $args );
	
	foreach ( $leaders as $leader ) {
		// Check if already has image (when not overwriting)
		if ( ! $overwrite && has_post_thumbnail( $leader->ID ) ) {
			$results['skipped']++;
			continue;
		}
		
		// Small delay to avoid hitting rate limits
		usleep( 500000 ); // 0.5 second delay
		
		$result = wasmo_import_wikipedia_image_for_leader( $leader->ID );
		
		if ( is_wp_error( $result ) ) {
			$error_code = $result->get_error_code();
			
			if ( $error_code === 'has_image' ) {
				$results['skipped']++;
			} elseif ( in_array( $error_code, array( 'no_results', 'no_image', 'no_suitable_image', 'no_images' ) ) ) {
				$results['not_found']++;
				$results['errors'][] = $leader->post_title . ': ' . $result->get_error_message();
			} else {
				$results['errors'][] = $leader->post_title . ': ' . $result->get_error_message();
			}
		} else {
			$results['imported']++;
		}
	}
	
	return $results;
}
