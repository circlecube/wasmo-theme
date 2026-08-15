<?php
/**
 * Filter bar for the full profiles directory.
 *
 * Expects query vars: directory_filter_state, directory_total, filtered_total.
 *
 * @package wasmo-theme
 */

$directory_filter_state = get_query_var( 'directory_filter_state' );
$directory_total        = (int) get_query_var( 'directory_total' );
$filtered_total         = (int) get_query_var( 'filtered_total' );

if ( empty( $directory_filter_state ) || ! is_array( $directory_filter_state ) ) {
	$directory_filter_state = wasmo_get_directory_filter_state();
}

$filters_active = wasmo_directory_filter_is_active( $directory_filter_state );
$form_action    = wasmo_get_directory_base_url();

$shelf_terms = get_terms(
	array(
		'taxonomy'   => 'shelf',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);

$spectrum_terms = get_terms(
	array(
		'taxonomy'   => 'spectrum',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);

$active_pills = array();

if ( wasmo_directory_sort_is_non_default( $directory_filter_state ) ) {
	$active_pills[] = array(
		'label' => wasmo_get_directory_sort_label( $directory_filter_state ),
		'url'   => wasmo_directory_filter_url( array(), array( 'sort' ) ),
	);
}

if ( 'all' !== $directory_filter_state['media'] ) {
	$active_pills[] = array(
		'label' => wasmo_get_directory_media_label( $directory_filter_state['media'] ),
		'url'   => wasmo_directory_filter_url( array(), array( 'media' ) ),
	);
}

if ( ! empty( $directory_filter_state['imported'] ) ) {
	$active_pills[] = array(
		'label' => __( 'Imported profiles', 'wasmo-theme' ),
		'url'   => wasmo_directory_filter_url( array(), array( 'imported' ) ),
	);
}

if ( ! empty( $directory_filter_state['shelf'] ) ) {
	$shelf_term = get_term_by( 'slug', $directory_filter_state['shelf'], 'shelf' );
	if ( $shelf_term && ! is_wp_error( $shelf_term ) ) {
		$active_pills[] = array(
			'label' => $shelf_term->name,
			'url'   => wasmo_directory_filter_url( array(), array( 'shelf' ) ),
		);
	}
}

if ( ! empty( $directory_filter_state['spectrum'] ) ) {
	$spectrum_term = get_term_by( 'slug', $directory_filter_state['spectrum'], 'spectrum' );
	if ( $spectrum_term && ! is_wp_error( $spectrum_term ) ) {
		$active_pills[] = array(
			'label' => $spectrum_term->name,
			'url'   => wasmo_directory_filter_url( array(), array( 'spectrum' ) ),
		);
	}
}
?>

<section class="directory-filters entry-content" aria-label="<?php esc_attr_e( 'Filter profiles', 'wasmo-theme' ); ?>">
	<div class="directory-filter-accordion" id="directory-filter-accordion">
		<button
			type="button"
			class="directory-filter-accordion-toggle"
			id="directory-filter-toggle"
			aria-expanded="false"
			aria-controls="directory-filter-panel"
		>
			<span class="directory-filter-accordion-title"><?php esc_html_e( 'Filter profiles', 'wasmo-theme' ); ?></span>
			<span class="directory-filter-accordion-icon" aria-hidden="true"><?php wasmo_echo_icon_svg( 'chevron_right', 18 ); ?></span>
		</button>

		<div class="directory-filter-accordion-panel" id="directory-filter-panel" hidden>
			<form
				class="directory-filter-form"
				method="get"
				action="<?php echo esc_url( $form_action ); ?>"
			>
				<?php if ( ! empty( $directory_filter_state['imported'] ) ) : ?>
				<input type="hidden" name="imported" value="1">
				<?php endif; ?>

				<div class="directory-filter-row">
					<?php if ( ! empty( $shelf_terms ) && ! is_wp_error( $shelf_terms ) ) : ?>
					<div class="directory-filter-field directory-filter-field-shelf">
						<label class="directory-filter-label" for="directory-filter-shelf">
							<?php wasmo_echo_icon_svg( 'shelf', 20 ); ?>
							<?php esc_html_e( 'Shelf', 'wasmo-theme' ); ?>
						</label>
						<select class="directory-filter-control" name="shelf" id="directory-filter-shelf">
							<option value=""><?php esc_html_e( 'Any', 'wasmo-theme' ); ?></option>
							<?php foreach ( $shelf_terms as $filter_shelf_term ) : ?>
							<option value="<?php echo esc_attr( $filter_shelf_term->slug ); ?>" <?php selected( $directory_filter_state['shelf'], $filter_shelf_term->slug ); ?>>
								<?php echo esc_html( $filter_shelf_term->name ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $spectrum_terms ) && ! is_wp_error( $spectrum_terms ) ) : ?>
					<div class="directory-filter-field directory-filter-field-spectrum">
						<label class="directory-filter-label" for="directory-filter-spectrum">
							<?php wasmo_echo_icon_svg( 'spectrum', 20 ); ?>
							<?php esc_html_e( 'Spectrum', 'wasmo-theme' ); ?>
						</label>
						<select class="directory-filter-control" name="spectrum" id="directory-filter-spectrum">
							<option value=""><?php esc_html_e( 'Any', 'wasmo-theme' ); ?></option>
							<?php foreach ( $spectrum_terms as $filter_spectrum_term ) : ?>
							<option value="<?php echo esc_attr( $filter_spectrum_term->slug ); ?>" <?php selected( $directory_filter_state['spectrum'], $filter_spectrum_term->slug ); ?>>
								<?php echo esc_html( $filter_spectrum_term->name ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>

					<div class="directory-filter-field directory-filter-field-media">
						<label class="directory-filter-label" for="directory-filter-media">
							<?php wasmo_echo_icon_svg( 'video', 20 ); ?>
							<?php esc_html_e( 'Media', 'wasmo-theme' ); ?>
						</label>
						<select class="directory-filter-control" name="media" id="directory-filter-media">
							<option value="" <?php selected( $directory_filter_state['media'], 'all' ); ?>><?php esc_html_e( 'All profiles', 'wasmo-theme' ); ?></option>
							<option value="video" <?php selected( $directory_filter_state['media'], 'video' ); ?>><?php esc_html_e( 'With video', 'wasmo-theme' ); ?></option>
							<option value="photo" <?php selected( $directory_filter_state['media'], 'photo' ); ?>><?php esc_html_e( 'With photo', 'wasmo-theme' ); ?></option>
						</select>
					</div>

					<div class="directory-filter-field directory-filter-field-sort">
						<label class="directory-filter-label" for="directory-filter-sort">
							<?php wasmo_echo_icon_svg( 'sort', 20 ); ?>
							<?php esc_html_e( 'Sort by', 'wasmo-theme' ); ?>
						</label>
						<select class="directory-filter-control" name="sort" id="directory-filter-sort">
							<option value="updated" <?php selected( $directory_filter_state['sort'], 'updated' ); ?>><?php esc_html_e( 'Recently updated', 'wasmo-theme' ); ?></option>
							<option value="name" <?php selected( $directory_filter_state['sort'], 'name' ); ?>><?php esc_html_e( 'Name (A–Z)', 'wasmo-theme' ); ?></option>
						</select>
					</div>

					<div class="directory-filter-actions">
						<button type="submit" class="directory-filter-submit button"><?php esc_html_e( 'Apply', 'wasmo-theme' ); ?></button>
					</div>
				</div>
			</form>

			<?php if ( $filters_active || ! empty( $active_pills ) ) : ?>
			<div class="directory-filter-meta-bar">
				<?php if ( $filters_active && $directory_total > 0 ) : ?>
				<span class="directory-filter-count">
					<?php
					printf(
						/* translators: 1: filtered profile count, 2: total profile count */
						esc_html__( 'Showing %1$s of %2$s profiles', 'wasmo-theme' ),
						number_format_i18n( $filtered_total ),
						number_format_i18n( $directory_total )
					);
					?>
				</span>
				<?php endif; ?>

				<?php if ( ! empty( $active_pills ) ) : ?>
				<ul class="directory-filter-pills">
					<?php foreach ( $active_pills as $active_pill ) : ?>
					<li>
						<a class="directory-filter-pill" href="<?php echo esc_url( $active_pill['url'] ); ?>">
							<span class="screen-reader-text"><?php esc_html_e( 'Remove filter:', 'wasmo-theme' ); ?></span>
							<span class="directory-filter-pill-label"><?php echo esc_html( $active_pill['label'] ); ?></span>
							<span class="directory-filter-remove" aria-hidden="true">&times;</span>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>

				<?php if ( $filters_active ) : ?>
				<a
					class="directory-filter-clear"
					href="<?php echo esc_url( wasmo_get_directory_base_url() ); ?>"
					title="<?php esc_attr_e( 'Clear all filters', 'wasmo-theme' ); ?>"
					aria-label="<?php esc_attr_e( 'Clear all filters', 'wasmo-theme' ); ?>"
				>
					<?php wasmo_echo_icon_svg( 'dismiss', 16 ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
