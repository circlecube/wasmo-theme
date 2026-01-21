<?php
/**
 * Saints Image Importer
 * 
 * Admin page for fetching and importing leader images from Wikipedia/Wikimedia Commons
 * and the Church History Biographical Database.
 *
 * @package wasmo
 */

// Image source constants
define( 'WASMO_IMAGE_SOURCE_WIKIPEDIA', 'wikipedia' );
define( 'WASMO_IMAGE_SOURCE_CHURCH_HISTORY', 'church_history' );
define( 'WASMO_IMAGE_SOURCE_AUTO', 'auto' );

/**
 * Add admin menu page for image import
 */
function wasmo_add_leader_images_page() {
	add_submenu_page(
		'edit.php?post_type=saint',
		'Import Leader Images',
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
		$image_source = isset( $_POST['image_source'] ) ? sanitize_text_field( $_POST['image_source'] ) : WASMO_IMAGE_SOURCE_AUTO;
		$result = wasmo_bulk_import_images( $image_source );
		
		$source_label = wasmo_get_source_label( $image_source );
		$message = '<div class="notice notice-success"><p>';
		$message .= sprintf( 
			'Import complete! %d images imported, %d already had images, %d not found (%s).', 
			$result['imported'], 
			$result['skipped'], 
			$result['not_found'],
			$source_label
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
		$custom_url = isset( $_POST['custom_url'] ) ? sanitize_text_field( $_POST['custom_url'] ) : '';
		$image_source = isset( $_POST['single_image_source'] ) ? sanitize_text_field( $_POST['single_image_source'] ) : WASMO_IMAGE_SOURCE_AUTO;
		
		if ( ! empty( $custom_url ) ) {
			// Import from custom URL (detect source from URL)
			$result = wasmo_import_image_from_custom_url( $leader_id, $custom_url );
		} else {
			// Use automatic search with selected source
			$result = wasmo_import_image_for_leader( $leader_id, $image_source );
		}
		
		if ( is_wp_error( $result ) ) {
			$message = '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		} else {
			$message = '<div class="notice notice-success"><p>Image imported successfully for ' . get_the_title( $leader_id ) . '!</p></div>';
		}
	}
	
	// Get leaders without featured images (excluding wives)
	$leaders_without_images = get_posts( array(
		'post_type' => 'saint',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => '_thumbnail_id',
				'compare' => 'NOT EXISTS',
			),
		),
		'tax_query' => array(
			array(
				'taxonomy' => 'saint-role',
				'field'    => 'slug',
				'terms'    => 'wife',
				'operator' => 'NOT IN',
			),
		),
		'orderby' => 'title',
		'order' => 'ASC',
	) );
	
	// Get leaders with featured images (excluding wives)
	$leaders_with_images = get_posts( array(
		'post_type' => 'saint',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => '_thumbnail_id',
				'compare' => 'EXISTS',
			),
		),
		'tax_query' => array(
			array(
				'taxonomy' => 'saint-role',
				'field'    => 'slug',
				'terms'    => 'wife',
				'operator' => 'NOT IN',
			),
		),
		'orderby' => 'title',
		'order' => 'ASC',
	) );
	
	$total_leaders = count( $leaders_without_images ) + count( $leaders_with_images );
	?>
	<div class="wrap">
		<h1>Import Leader Images</h1>
		
		<?php echo $message; ?>

		<div class="card" style="max-width: 800px; margin-bottom: 20px;">
			<h2>Bulk Import Images</h2>
			<p>
				This tool searches for images of church leaders and imports them as featured images.
				Images can be sourced from:
			</p>
			<ul style="margin-left: 20px;">
				<li><strong>Wikipedia/Wikimedia Commons</strong> - Public domain or freely licensed images</li>
				<li><strong>Church History Biographical Database</strong> - Official church historical images from history.churchofjesuschrist.org</li>
				<li><strong>Auto (Both Sources)</strong> - Try Wikipedia first, then Church History Database as fallback</li>
			</ul>
			<p>
				<strong>Leaders with images:</strong> <?php echo count( $leaders_with_images ); ?> / <?php echo $total_leaders; ?><br>
				<strong>Leaders without images:</strong> <?php echo count( $leaders_without_images ); ?>
			</p>
			<p><em>Note: This process may take several minutes for many leaders due to API rate limits.</em></p>
			
			<form method="post">
				<?php wp_nonce_field( 'wasmo_import_images_nonce' ); ?>
				<p>
					<label for="image_source"><strong>Image Source:</strong></label><br>
					<select name="image_source" id="image_source" style="width: 100%; max-width: 400px;">
						<option value="<?php echo esc_attr( WASMO_IMAGE_SOURCE_AUTO ); ?>">Auto (Try Both - Wikipedia first, then Church History)</option>
						<option value="<?php echo esc_attr( WASMO_IMAGE_SOURCE_WIKIPEDIA ); ?>">Wikipedia Only</option>
						<option value="<?php echo esc_attr( WASMO_IMAGE_SOURCE_CHURCH_HISTORY ); ?>">Church History Database Only</option>
					</select>
				</p>
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
					<label for="single_image_source"><strong>Image Source:</strong></label><br>
					<select name="single_image_source" id="single_image_source" style="width: 100%; max-width: 400px;">
						<option value="<?php echo esc_attr( WASMO_IMAGE_SOURCE_AUTO ); ?>">Auto (Try Both Sources)</option>
						<option value="<?php echo esc_attr( WASMO_IMAGE_SOURCE_WIKIPEDIA ); ?>">Wikipedia Only</option>
						<option value="<?php echo esc_attr( WASMO_IMAGE_SOURCE_CHURCH_HISTORY ); ?>">Church History Database Only</option>
					</select>
				</p>
				<p>
					<label for="custom_url">Custom URL (optional):</label><br>
					<input type="url" name="custom_url" id="custom_url" 
						   placeholder="https://example.com/image.jpg or Wikipedia/Church History URL" 
						   style="width: 100%; max-width: 400px;">
					<br><small style="color: #666;">Leave empty to use automatic search. Accepts: direct image URLs (.jpg, .png, etc.), Wikipedia URLs, or Church History Database URLs.</small>
				</p>
				<p>
					<label>
						<input type="checkbox" name="overwrite_existing" value="1">
						Overwrite existing featured image (if any)
					</label>
				</p>
				<p>
					<button type="submit" name="wasmo_import_single_image" class="button button-secondary">
						Import Image for Selected Leader
					</button>
				</p>
			</form>
		</div>

		<div class="card" style="max-width: 1000px;">
			<h2>Leaders Without Featured Images (<?php echo count( $leaders_without_images ); ?>)</h2>
			<?php if ( empty( $leaders_without_images ) ) : ?>
				<p><em>All leaders have featured images!</em></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 25%;">Leader Name</th>
							<th style="width: 25%;">Search Term</th>
							<th style="width: 50%;">Quick Links</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $leaders_without_images as $leader ) : 
							$search_term = wasmo_get_wikipedia_search_term( $leader->ID );
							$church_history_search = wasmo_get_church_history_search_term( $leader->ID );
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
										Wikipedia
									</a>
									<a href="https://history.churchofjesuschrist.org/chd/search?query=<?php echo urlencode( $church_history_search ); ?>&tabFacet=people&lang=eng" 
									   target="_blank" class="button button-small">
										Church History DB
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
		'user-agent' => 'WasMormon.org Saints Image Importer/1.0 (https://wasmormon.org)',
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
			'user-agent' => 'WasMormon.org Saints Image Importer/1.0 (https://wasmormon.org)',
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
		'user-agent' => 'WasMormon.org Saints Image Importer/1.0 (https://wasmormon.org)',
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
 * Get Church History Biographical Database search term for a leader
 * 
 * @param int $leader_id The leader post ID.
 * @return string The search term.
 */
function wasmo_get_church_history_search_term( $leader_id ) {
	$title = get_the_title( $leader_id );
	
	// Get first, middle, last name from ACF fields if available
	$first_name = get_field( 'first_name', $leader_id );
	$middle_name = get_field( 'middle_name', $leader_id );
	$last_name = get_field( 'last_name', $leader_id );
	
	// If we have ACF names, construct full name
	if ( $first_name && $last_name ) {
		// For Church History DB, use first name + middle initial + last name
		$search_name = $first_name;
		if ( $middle_name ) {
			// Use middle initial if available
			$search_name .= ' ' . substr( $middle_name, 0, 1 ) . '.';
		}
		$search_name .= ' ' . $last_name;
		return $search_name;
	}
	
	return $title;
}

/**
 * Search the Church History Biographical Database for a person and get their image
 * 
 * @param string $search_term The name to search for.
 * @return string|WP_Error The image URL or error.
 */
function wasmo_search_church_history_image( $search_term ) {
	// First, search for the person using the CHD search API
	$search_url = 'https://history.churchofjesuschrist.org/chd/api/proxyToBackend';
	
	// The API uses a specific request format
	$search_body = array(
		'uri' => 'https://mach-api.pvu.cf.churchofjesuschrist.org/api/v1/search/basic',
		'queryParams' => array(
			'tabFacet' => 'people',
			'query' => $search_term,
			'lang' => 'eng',
			'searchType' => 'startsWith',
			'offset' => '0',
			'limit' => '5',
		),
	);
	
	$response = wp_remote_post( $search_url, array(
		'timeout' => 30,
		'headers' => array(
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
		),
		'body' => json_encode( $search_body ),
		'user-agent' => 'WasMormon.org Saints Image Importer/1.0 (https://wasmormon.org)',
	) );
	
	if ( is_wp_error( $response ) ) {
		// Fallback to direct page scraping if API fails
		return wasmo_search_church_history_image_via_scrape( $search_term );
	}
	
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	
	if ( empty( $data['results'] ) ) {
		// Try scraping as fallback
		return wasmo_search_church_history_image_via_scrape( $search_term );
	}
	
	// Look for the first result with an image
	foreach ( $data['results'] as $result ) {
		if ( ! empty( $result['profileImageUrl'] ) ) {
			return $result['profileImageUrl'];
		}
		if ( ! empty( $result['thumbnailUrl'] ) ) {
			return $result['thumbnailUrl'];
		}
		// If we have a slug, try to get the full profile page
		if ( ! empty( $result['slug'] ) ) {
			$image_url = wasmo_get_church_history_profile_image( $result['slug'] );
			if ( ! is_wp_error( $image_url ) ) {
				return $image_url;
			}
		}
	}
	
	return new WP_Error( 'no_image', 'No image found in Church History Database for: ' . $search_term );
}

/**
 * Search Church History Database via HTML scraping (fallback method)
 * 
 * @param string $search_term The name to search for.
 * @return string|WP_Error The image URL or error.
 */
function wasmo_search_church_history_image_via_scrape( $search_term ) {
	$search_url = add_query_arg( array(
		'query' => $search_term,
		'tabFacet' => 'people',
		'lang' => 'eng',
	), 'https://history.churchofjesuschrist.org/chd/search' );
	
	$response = wp_remote_get( $search_url, array(
		'timeout' => 30,
		'user-agent' => 'Mozilla/5.0 (compatible; WasMormon.org Saints Image Importer/1.0)',
	) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$body = wp_remote_retrieve_body( $response );
	
	// Look for profile image URLs in the HTML
	// Pattern: https://history.churchofjesuschrist.org/church-history-people/bc/...
	if ( preg_match_all( '/https:\/\/history\.churchofjesuschrist\.org\/church-history-people\/bc\/[^"\']+\.(?:jpeg|jpg|png)/i', $body, $matches ) ) {
		// Filter out icon images and get unique results
		$image_urls = array_filter( $matches[0], function( $url ) {
			return stripos( $url, 'icons/' ) === false;
		});
		
		if ( ! empty( $image_urls ) ) {
			// Return the first portrait image found
			return reset( $image_urls );
		}
	}
	
	// Try to find individual profile links and fetch from there
	if ( preg_match( '/\/chd\/individual\/([^"\'?]+)/', $body, $match ) ) {
		return wasmo_get_church_history_profile_image( $match[1] );
	}
	
	return new WP_Error( 'no_results', 'No results found in Church History Database for: ' . $search_term );
}

/**
 * Get image from a Church History individual profile page
 * 
 * @param string $slug The individual's profile slug (e.g., "russell-m-nelson-1924").
 * @return string|WP_Error The image URL or error.
 */
function wasmo_get_church_history_profile_image( $slug ) {
	$profile_url = 'https://history.churchofjesuschrist.org/chd/individual/' . $slug . '?lang=eng';
	
	$response = wp_remote_get( $profile_url, array(
		'timeout' => 30,
		'user-agent' => 'Mozilla/5.0 (compatible; WasMormon.org Saints Image Importer/1.0)',
	) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$body = wp_remote_retrieve_body( $response );
	
	// Look for profile image URLs in the HTML
	// These are typically in the format: /church-history-people/bc/Category/Name/filename.jpeg
	if ( preg_match_all( '/https:\/\/history\.churchofjesuschrist\.org\/church-history-people\/bc\/[^"\']+\.(?:jpeg|jpg|png)/i', $body, $matches ) ) {
		// Filter out icons
		$image_urls = array_filter( $matches[0], function( $url ) {
			return stripos( $url, 'icons/' ) === false;
		});
		
		if ( ! empty( $image_urls ) ) {
			// Prefer images without size constraints in the URL
			foreach ( $image_urls as $url ) {
				if ( ! preg_match( '/\/\d+x\d+\//', $url ) ) {
					return $url;
				}
			}
			// Otherwise return the first one
			return reset( $image_urls );
		}
	}
	
	return new WP_Error( 'no_profile_image', 'No profile image found for: ' . $slug );
}

/**
 * Import image from a Church History Database URL
 * 
 * @param int $leader_id The leader post ID.
 * @param string $chd_url The Church History Database URL.
 * @return int|WP_Error The attachment ID or error.
 */
function wasmo_import_church_history_image_from_url( $leader_id, $chd_url ) {
	// Check if leader already has a featured image
	if ( has_post_thumbnail( $leader_id ) && ! isset( $_POST['overwrite_existing'] ) ) {
		return new WP_Error( 'has_image', 'Leader already has a featured image' );
	}
	
	// Extract the slug from the URL
	if ( preg_match( '/\/chd\/individual\/([^?]+)/', $chd_url, $matches ) ) {
		$slug = $matches[1];
		$image_url = wasmo_get_church_history_profile_image( $slug );
		
		if ( is_wp_error( $image_url ) ) {
			return $image_url;
		}
		
		return wasmo_download_and_attach_image( $image_url, $leader_id );
	}
	
	return new WP_Error( 'invalid_url', 'Invalid Church History Database URL format' );
}

/**
 * Import Church History image for a single leader
 * 
 * @param int $leader_id The leader post ID.
 * @return int|WP_Error The attachment ID or error.
 */
function wasmo_import_church_history_image_for_leader( $leader_id ) {
	// Check if leader already has a featured image
	if ( has_post_thumbnail( $leader_id ) && ! isset( $_POST['overwrite_existing'] ) ) {
		return new WP_Error( 'has_image', 'Leader already has a featured image' );
	}
	
	// Get search term
	$search_term = wasmo_get_church_history_search_term( $leader_id );
	
	// Search Church History Database for image
	$image_url = wasmo_search_church_history_image( $search_term );
	
	if ( is_wp_error( $image_url ) ) {
		return $image_url;
	}
	
	// Download and attach image
	return wasmo_download_and_attach_image( $image_url, $leader_id );
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
		'user-agent' => 'WasMormon.org Saints Image Importer/1.0 (https://wasmormon.org)',
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
 * Get human-readable label for image source
 * 
 * @param string $source The image source constant.
 * @return string The human-readable label.
 */
function wasmo_get_source_label( $source ) {
	switch ( $source ) {
		case WASMO_IMAGE_SOURCE_WIKIPEDIA:
			return 'Wikipedia';
		case WASMO_IMAGE_SOURCE_CHURCH_HISTORY:
			return 'Church History Database';
		case WASMO_IMAGE_SOURCE_AUTO:
		default:
			return 'Auto (Both Sources)';
	}
}

/**
 * Import image from a custom URL (auto-detect source or direct image)
 * 
 * @param int $leader_id The leader post ID.
 * @param string $custom_url The custom URL.
 * @return int|WP_Error The attachment ID or error.
 */
function wasmo_import_image_from_custom_url( $leader_id, $custom_url ) {
	// Check if leader already has a featured image
	if ( has_post_thumbnail( $leader_id ) && ! isset( $_POST['overwrite_existing'] ) ) {
		return new WP_Error( 'has_image', 'Leader already has a featured image. Check "Overwrite" to replace it.' );
	}
	
	// Detect the source from URL
	if ( preg_match( '/wikipedia\.org/i', $custom_url ) ) {
		return wasmo_import_wikipedia_image_from_url( $leader_id, $custom_url );
	} elseif ( preg_match( '/history\.churchofjesuschrist\.org\/chd\//i', $custom_url ) ) {
		return wasmo_import_church_history_image_from_url( $leader_id, $custom_url );
	} elseif ( wasmo_is_direct_image_url( $custom_url ) ) {
		// Direct image URL - download and attach
		return wasmo_download_and_attach_image( $custom_url, $leader_id );
	}
	
	return new WP_Error( 'unknown_source', 'Unrecognized URL format. Please use a Wikipedia URL, Church History Database URL, or a direct link to an image file.' );
}

/**
 * Check if a URL is a direct link to an image file
 * 
 * @param string $url The URL to check.
 * @return bool True if URL appears to be a direct image link.
 */
function wasmo_is_direct_image_url( $url ) {
	// Check for common image extensions in URL
	if ( preg_match( '/\.(jpg|jpeg|png|gif|webp)(\?.*)?$/i', $url ) ) {
		return true;
	}
	
	// Check for image hosting services that may not have extensions
	$image_hosts = array(
		'imgur.com',
		'i.imgur.com',
		'images.unsplash.com',
		'pbs.twimg.com',
		'media.churchofjesuschrist.org',
		'churchofjesuschrist.org/bc/',
		'church-history-people/bc/',
	);
	
	foreach ( $image_hosts as $host ) {
		if ( stripos( $url, $host ) !== false ) {
			return true;
		}
	}
	
	// Try to verify by checking the content type via HEAD request
	$response = wp_remote_head( $url, array(
		'timeout' => 10,
		'redirection' => 3,
		'user-agent' => 'WasMormon.org Saints Image Importer/1.0',
	) );
	
	if ( ! is_wp_error( $response ) ) {
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( $content_type && strpos( $content_type, 'image/' ) === 0 ) {
			return true;
		}
	}
	
	return false;
}

/**
 * Import image for a leader from specified source(s)
 * 
 * @param int $leader_id The leader post ID.
 * @param string $source The image source (wikipedia, church_history, or auto).
 * @return int|WP_Error The attachment ID or error.
 */
function wasmo_import_image_for_leader( $leader_id, $source = WASMO_IMAGE_SOURCE_AUTO ) {
	// Check if leader already has a featured image
	if ( has_post_thumbnail( $leader_id ) && ! isset( $_POST['overwrite_existing'] ) ) {
		return new WP_Error( 'has_image', 'Leader already has a featured image' );
	}
	
	$errors = array();
	
	// Try Wikipedia first if source is wikipedia or auto
	if ( $source === WASMO_IMAGE_SOURCE_WIKIPEDIA || $source === WASMO_IMAGE_SOURCE_AUTO ) {
		$result = wasmo_import_wikipedia_image_for_leader( $leader_id );
		if ( ! is_wp_error( $result ) ) {
			return $result;
		}
		$errors[] = 'Wikipedia: ' . $result->get_error_message();
	}
	
	// Try Church History Database if source is church_history or auto
	if ( $source === WASMO_IMAGE_SOURCE_CHURCH_HISTORY || $source === WASMO_IMAGE_SOURCE_AUTO ) {
		$result = wasmo_import_church_history_image_for_leader( $leader_id );
		if ( ! is_wp_error( $result ) ) {
			return $result;
		}
		$errors[] = 'Church History: ' . $result->get_error_message();
	}
	
	// Return combined error message
	return new WP_Error( 'no_image_found', implode( '; ', $errors ) );
}

/**
 * Bulk import images for all leaders without featured images
 * 
 * @param string $source The image source preference.
 * @return array Results array with counts.
 */
function wasmo_bulk_import_images( $source = WASMO_IMAGE_SOURCE_AUTO ) {
	$results = array(
		'imported' => 0,
		'skipped' => 0,
		'not_found' => 0,
		'errors' => array(),
	);
	
	$overwrite = isset( $_POST['overwrite_existing'] ) && $_POST['overwrite_existing'];
	
	// Build query based on overwrite setting (excluding wives)
	$args = array(
		'post_type' => 'saint',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'orderby' => 'title',
		'order' => 'ASC',
		'tax_query' => array(
			array(
				'taxonomy' => 'saint-role',
				'field'    => 'slug',
				'terms'    => 'wife',
				'operator' => 'NOT IN',
			),
		),
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
		
		$result = wasmo_import_image_for_leader( $leader->ID, $source );
		
		if ( is_wp_error( $result ) ) {
			$error_code = $result->get_error_code();
			
			if ( $error_code === 'has_image' ) {
				$results['skipped']++;
			} elseif ( in_array( $error_code, array( 'no_results', 'no_image', 'no_suitable_image', 'no_images', 'no_image_found' ) ) ) {
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

