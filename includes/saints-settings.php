<?php
/**
 * Saints Settings
 * 
 * Admin page for configuring the current First Presidency and other leadership settings.
 *
 * @package wasmo
 */

/**
 * Add admin menu page for leadership settings
 */
function wasmo_add_leader_settings_page() {
	add_submenu_page(
		'edit.php?post_type=saint',
		'First Presidency Settings',
		'First Presidency Settings',
		'manage_options',
		'leader-settings',
		'wasmo_render_leader_settings_page'
	);
}
add_action( 'admin_menu', 'wasmo_add_leader_settings_page' );

/**
 * Register settings
 */
function wasmo_register_leader_settings() {
	register_setting( 'wasmo_leader_settings', 'wasmo_current_president' );
	register_setting( 'wasmo_leader_settings', 'wasmo_current_first_counselor' );
	register_setting( 'wasmo_leader_settings', 'wasmo_current_second_counselor' );
}
add_action( 'admin_init', 'wasmo_register_leader_settings' );

/**
 * Clear First Presidency transient when settings are saved
 */
function wasmo_clear_fp_transient_on_save( $old_value, $value, $option ) {
	if ( in_array( $option, array( 'wasmo_current_president', 'wasmo_current_first_counselor', 'wasmo_current_second_counselor' ) ) ) {
		delete_transient( 'wasmo_first_presidency' );
	}
}
add_action( 'update_option', 'wasmo_clear_fp_transient_on_save', 10, 3 );

/**
 * Render the leadership settings page
 */
function wasmo_render_leader_settings_page() {
	// Get all church leaders for the dropdown
	$all_leaders = get_posts( array(
		'post_type'      => 'saint',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	// Separate living and deceased for better UX
	$living_leaders = array();
	$deceased_leaders = array();
	
	foreach ( $all_leaders as $leader ) {
		if ( wasmo_is_saint_living( $leader->ID ) ) {
			$living_leaders[] = $leader;
		} else {
			$deceased_leaders[] = $leader;
		}
	}

	// Get current settings
	$current_president = get_option( 'wasmo_current_president', '' );
	$current_first_counselor = get_option( 'wasmo_current_first_counselor', '' );
	$current_second_counselor = get_option( 'wasmo_current_second_counselor', '' );
	
	// Get the computed First Presidency for comparison
	$computed_fp = wasmo_get_current_first_presidency();
	?>
	<div class="wrap">
		<h1>Saintship Settings</h1>
		
		<p>Configure the current First Presidency. These settings override the automatic detection based on taxonomy roles.</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'wasmo_leader_settings' ); ?>
			
			<div class="card" style="max-width: 600px; margin-bottom: 20px;">
				<h2>Current First Presidency</h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="wasmo_current_president">President of the Church</label>
						</th>
						<td>
							<select name="wasmo_current_president" id="wasmo_current_president" style="width: 100%; max-width: 400px;">
								<option value="">— Select President —</option>
								<optgroup label="Living Leaders">
									<?php foreach ( $living_leaders as $leader ) : ?>
										<option value="<?php echo esc_attr( $leader->ID ); ?>" <?php selected( $current_president, $leader->ID ); ?>>
											<?php echo esc_html( $leader->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</optgroup>
								<optgroup label="Deceased Leaders (Historical)">
									<?php foreach ( $deceased_leaders as $leader ) : ?>
										<option value="<?php echo esc_attr( $leader->ID ); ?>" <?php selected( $current_president, $leader->ID ); ?>>
											<?php echo esc_html( $leader->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</optgroup>
							</select>
							<?php if ( $computed_fp['president'] && empty( $current_president ) ) : ?>
								<p class="description">
									Auto-detected: <strong><?php echo esc_html( get_the_title( $computed_fp['president'] ) ); ?></strong>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="wasmo_current_first_counselor">First Counselor</label>
						</th>
						<td>
							<select name="wasmo_current_first_counselor" id="wasmo_current_first_counselor" style="width: 100%; max-width: 400px;">
								<option value="">— Select First Counselor —</option>
								<optgroup label="Living Leaders">
									<?php foreach ( $living_leaders as $leader ) : ?>
										<option value="<?php echo esc_attr( $leader->ID ); ?>" <?php selected( $current_first_counselor, $leader->ID ); ?>>
											<?php echo esc_html( $leader->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</optgroup>
								<optgroup label="Deceased Leaders (Historical)">
									<?php foreach ( $deceased_leaders as $leader ) : ?>
										<option value="<?php echo esc_attr( $leader->ID ); ?>" <?php selected( $current_first_counselor, $leader->ID ); ?>>
											<?php echo esc_html( $leader->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</optgroup>
							</select>
							<?php if ( $computed_fp['first-counselor'] && empty( $current_first_counselor ) ) : ?>
								<p class="description">
									Auto-detected: <strong><?php echo esc_html( get_the_title( $computed_fp['first-counselor'] ) ); ?></strong>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="wasmo_current_second_counselor">Second Counselor</label>
						</th>
						<td>
							<select name="wasmo_current_second_counselor" id="wasmo_current_second_counselor" style="width: 100%; max-width: 400px;">
								<option value="">— Select Second Counselor —</option>
								<optgroup label="Living Leaders">
									<?php foreach ( $living_leaders as $leader ) : ?>
										<option value="<?php echo esc_attr( $leader->ID ); ?>" <?php selected( $current_second_counselor, $leader->ID ); ?>>
											<?php echo esc_html( $leader->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</optgroup>
								<optgroup label="Deceased Leaders (Historical)">
									<?php foreach ( $deceased_leaders as $leader ) : ?>
										<option value="<?php echo esc_attr( $leader->ID ); ?>" <?php selected( $current_second_counselor, $leader->ID ); ?>>
											<?php echo esc_html( $leader->post_title ); ?>
										</option>
									<?php endforeach; ?>
								</optgroup>
							</select>
							<?php if ( $computed_fp['second-counselor'] && empty( $current_second_counselor ) ) : ?>
								<p class="description">
									Auto-detected: <strong><?php echo esc_html( get_the_title( $computed_fp['second-counselor'] ) ); ?></strong>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Save First Presidency Settings' ); ?>
			</div>
		</form>

		<div class="card" style="max-width: 600px; background: #f0f6fc;">
			<h3>Current First Presidency Display</h3>
			<p>This is how the First Presidency will appear on the site:</p>
			
			<?php 
			// Re-fetch after potential save
			$display_fp = wasmo_get_current_first_presidency();
			?>
			
			<div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 15px;">
				<?php if ( $display_fp['president'] ) : ?>
					<div style="text-align: center;">
						<?php 
						$thumb = get_the_post_thumbnail_url( $display_fp['president'], 'thumbnail' );
						if ( $thumb ) : ?>
							<img src="<?php echo esc_url( $thumb ); ?>" alt="" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
						<?php endif; ?>
						<p style="margin: 5px 0 0;"><strong><?php echo esc_html( get_the_title( $display_fp['president'] ) ); ?></strong></p>
						<small>President</small>
					</div>
				<?php else : ?>
					<div style="text-align: center; opacity: 0.5;">
						<div style="width: 80px; height: 80px; border-radius: 50%; background: #ddd; margin: 0 auto;"></div>
						<p style="margin: 5px 0 0;"><em>Not set</em></p>
						<small>President</small>
					</div>
				<?php endif; ?>

				<?php if ( $display_fp['first-counselor'] ) : ?>
					<div style="text-align: center;">
						<?php 
						$thumb = get_the_post_thumbnail_url( $display_fp['first-counselor'], 'thumbnail' );
						if ( $thumb ) : ?>
							<img src="<?php echo esc_url( $thumb ); ?>" alt="" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
						<?php endif; ?>
						<p style="margin: 5px 0 0;"><strong><?php echo esc_html( get_the_title( $display_fp['first-counselor'] ) ); ?></strong></p>
						<small>First Counselor</small>
					</div>
				<?php else : ?>
					<div style="text-align: center; opacity: 0.5;">
						<div style="width: 80px; height: 80px; border-radius: 50%; background: #ddd; margin: 0 auto;"></div>
						<p style="margin: 5px 0 0;"><em>Not set</em></p>
						<small>First Counselor</small>
					</div>
				<?php endif; ?>

				<?php if ( $display_fp['second-counselor'] ) : ?>
					<div style="text-align: center;">
						<?php 
						$thumb = get_the_post_thumbnail_url( $display_fp['second-counselor'], 'thumbnail' );
						if ( $thumb ) : ?>
							<img src="<?php echo esc_url( $thumb ); ?>" alt="" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
						<?php endif; ?>
						<p style="margin: 5px 0 0;"><strong><?php echo esc_html( get_the_title( $display_fp['second-counselor'] ) ); ?></strong></p>
						<small>Second Counselor</small>
					</div>
				<?php else : ?>
					<div style="text-align: center; opacity: 0.5;">
						<div style="width: 80px; height: 80px; border-radius: 50%; background: #ddd; margin: 0 auto;"></div>
						<p style="margin: 5px 0 0;"><em>Not set</em></p>
						<small>Second Counselor</small>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="card" style="max-width: 600px; margin-top: 20px;">
			<h3>How It Works</h3>
			<ul>
				<li><strong>Manual Selection:</strong> Use the dropdowns above to explicitly set who is in the current First Presidency.</li>
				<li><strong>Auto-Detection Fallback:</strong> If no selection is made, the system falls back to finding the first living person with each role taxonomy.</li>
				<li><strong>Cache:</strong> The First Presidency is cached. Saving these settings automatically clears the cache.</li>
			</ul>
			<p><em>Note: The taxonomy roles (President, First Counselor, Second Counselor) are still useful for historical tracking, but this settings page determines who appears on the current leadership displays.</em></p>
		</div>
	</div>
	<?php
}
