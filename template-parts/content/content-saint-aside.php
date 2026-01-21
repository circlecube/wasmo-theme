<?php
/**
 * Template part for displaying the saint sidebar/aside
 *
 * @package wasmo
 * 
 * Expected variables (passed via get_template_part args):
 * - saint_id: The saint post ID
 * - is_living: Whether the saint is living
 * - is_apostle: Whether the saint has apostle role
 * - is_president: Whether the saint has president role
 * - gender: The saint's gender
 * - polygamy_stats: Array of polygamy statistics
 * - polygamy_type: Array with polygamy type info
 */

// Get passed variables
$saint_id = $args['saint_id'] ?? get_the_ID();
$is_living = $args['is_living'] ?? false;
$is_apostle = $args['is_apostle'] ?? false;
$is_president = $args['is_president'] ?? false;
$gender = $args['gender'] ?? 'male';
$polygamy_stats = $args['polygamy_stats'] ?? array();
$polygamy_type = $args['polygamy_type'] ?? array();
?>

<aside class="leader-sidebar">
	<div class="leader-metadata">
		<h3>Details</h3>
		<dl class="leader-meta-list">
			<?php 
			// Birth date
			$birthdate = get_field( 'birthdate', $saint_id );
			$birthdate_approx = get_field( 'birthdate_approximate', $saint_id );
			if ( $birthdate ) : ?>
				<dt>Born</dt>
				<dd><?php echo esc_html( wasmo_format_saint_date_with_approx( $birthdate, 'F j, Y', false, $birthdate_approx ) ); ?></dd>
			<?php endif; ?>

			<?php 
			// Death date
			$deathdate = get_field( 'deathdate', $saint_id );
			$deathdate_approx = get_field( 'deathdate_approximate', $saint_id );
			if ( $deathdate ) : ?>
				<dt>Died</dt>
				<dd><?php echo esc_html( wasmo_format_saint_date_with_approx( $deathdate, 'F j, Y', false, $deathdate_approx ) ); ?></dd>
			<?php endif; ?>

			<?php 
			// Age
			$age = wasmo_get_leader_age( $saint_id );
			if ( $age !== null ) : ?>
				<dt><?php echo $is_living ? 'Age' : 'Age at Death'; ?></dt>
				<dd><?php echo esc_html( $age ); ?> years</dd>
			<?php endif; ?>

			<?php if ( $is_apostle ) : ?>
				<?php 
				// Ordained date (show time if specified for seniority disambiguation)
				$ordained_date = get_field( 'ordained_date', $saint_id );
				if ( $ordained_date ) : ?>
					<dt>Ordained Apostle</dt>
					<dd><?php echo esc_html( wasmo_format_saint_date( $ordained_date, 'F j, Y', true ) ); ?></dd>
				<?php endif; ?>

				<?php 
				// Service ended early (excommunication, resignation, etc.)
				$ordain_end = get_field( 'ordain_end', $saint_id );
				if ( $ordain_end ) : ?>
					<dt>Service Ended</dt>
					<dd><?php echo esc_html( wasmo_format_saint_date( $ordain_end ) ); ?></dd>
				<?php endif; ?>

				<?php 
				// Note about service end
				$ordain_note = get_field( 'ordain_note', $saint_id );
				if ( $ordain_note ) : ?>
					<dt>Status Note</dt>
					<dd class="leader-ordain-note"><?php echo wp_kses_post( $ordain_note ); ?></dd>
				<?php endif; ?>

				<?php 
				// Age at call
				$age_at_call = wasmo_get_saint_age_at_call( $saint_id );
				if ( $age_at_call !== null ) : ?>
					<dt>Age When Called</dt>
					<dd><?php echo esc_html( $age_at_call ); ?> years</dd>
				<?php endif; ?>

				<?php 
				// Years served
				$years_served = wasmo_get_saint_years_served( $saint_id );
				if ( $years_served !== null ) : ?>
					<dt>Years as Apostle</dt>
					<dd><?php echo esc_html( $years_served ); ?> years</dd>
				<?php endif; ?>

				<?php if ( $is_living ) : 
					$seniority = wasmo_get_saint_seniority( $saint_id );
					if ( $seniority !== null ) : ?>
						<dt>Current Seniority</dt>
						<dd>#<?php echo esc_html( $seniority ); ?></dd>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( $is_president ) : ?>
				<?php 
				// Became president date
				$president_date = get_field( 'became_president_date', $saint_id );
				if ( $president_date ) : ?>
					<dt>Became President</dt>
					<dd><?php echo esc_html( wasmo_format_saint_date( $president_date ) ); ?></dd>
				<?php endif; ?>

				<?php 
				// Years as president
				$years_as_president = wasmo_get_saint_years_as_president( $saint_id );
				if ( $years_as_president !== null ) : ?>
					<dt>Years as President</dt>
					<dd><?php echo esc_html( $years_as_president ); ?> years</dd>
				<?php endif; ?>
			<?php endif; ?>

			<?php 
			// Mission
			$mission = get_field( 'mission', $saint_id );
			if ( $mission ) : ?>
				<dt>Mission</dt>
				<dd><?php echo esc_html( $mission ); ?></dd>
			<?php endif; ?>

			<?php 
			// Education
			$education = get_field( 'education', $saint_id );
			if ( $education ) : ?>
				<dt>Education</dt>
				<dd><?php echo esc_html( $education ); ?></dd>
			<?php endif; ?>

			<?php 
			// Profession
			$profession = get_field( 'profession', $saint_id );
			if ( $profession ) : ?>
				<dt>Profession</dt>
				<dd><?php echo esc_html( $profession ); ?></dd>
			<?php endif; ?>

			<?php 
			// Military
			$military = get_field( 'military', $saint_id );
			if ( $military ) : ?>
				<dt>Military Service</dt>
				<dd><?php echo esc_html( $military ); ?></dd>
			<?php endif; ?>

			<?php 
			// FamilySearch ID
			$familysearch_id = get_field( 'familysearch_id', $saint_id );
			if ( $familysearch_id ) : ?>
				<dt>FamilySearch</dt>
				<dd>
					<a href="https://www.familysearch.org/tree/person/details/<?php echo esc_attr( $familysearch_id ); ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;justify-content:center;gap:8px;">
						<?php echo wasmo_get_icon_svg( 'familysearch', 32 ); ?>
						<?php echo esc_html( $familysearch_id ); ?>
					</a>
				</dd>
			<?php endif; ?>

			<?php 
			// Polygamy stats (computed from marriages)
			if ( ! empty( $polygamy_stats['was_polygamist'] ) ) : 
				$polygamy_type_label = ( isset( $polygamy_type['type'] ) && $polygamy_type['type'] === 'celestial' ) ? 'Celestial Polygamist' : 'Polygamist';
			?>
				<dt><?php echo esc_html( $polygamy_type_label ); ?></dt>
				<dd>Yes (<?php echo esc_html( $polygamy_stats['number_of_marriages'] ); ?> <?php echo $gender === 'male' ? 'wives' : 'husbands'; ?>)</dd>
				
				<?php if ( ! empty( $polygamy_stats['number_of_children'] ) && $polygamy_stats['number_of_children'] > 0 ) : ?>
					<dt>Total Children</dt>
					<dd><?php echo esc_html( $polygamy_stats['number_of_children'] ); ?></dd>
				<?php endif; ?>
				
				<?php if ( ! empty( $polygamy_stats['teenage_brides_count'] ) && $polygamy_stats['teenage_brides_count'] > 0 && $gender === 'male' ) : ?>
					<dt>Teenage Brides</dt>
					<dd><?php echo esc_html( $polygamy_stats['teenage_brides_count'] ); ?></dd>
				<?php endif; ?>
				
				<?php if ( ! empty( $polygamy_stats['largest_age_diff'] ) && $polygamy_stats['largest_age_diff'] > 0 ) : ?>
					<dt>Largest Age Gap</dt>
					<dd><?php echo esc_html( $polygamy_stats['largest_age_diff'] ); ?> years</dd>
				<?php endif; ?>
				
				<?php if ( ! empty( $polygamy_stats['avg_age_diff'] ) && $polygamy_stats['avg_age_diff'] > 0 ) : ?>
					<dt>Avg Age Difference</dt>
					<dd><?php echo esc_html( $polygamy_stats['avg_age_diff'] ); ?> years</dd>
				<?php endif; ?>
				
				<?php if ( isset( $polygamy_stats['age_first_marriage'] ) && $polygamy_stats['age_first_marriage'] !== null ) : ?>
					<dt>Age at First Marriage</dt>
					<dd><?php echo esc_html( $polygamy_stats['age_first_marriage'] ); ?> years</dd>
				<?php endif; ?>
			<?php elseif ( isset( $polygamy_stats['number_of_marriages'] ) && $polygamy_stats['number_of_marriages'] === 1 ) : ?>
				<?php if ( ! empty( $polygamy_stats['number_of_children'] ) && $polygamy_stats['number_of_children'] > 0 ) : ?>
					<dt>Children</dt>
					<dd><?php echo esc_html( $polygamy_stats['number_of_children'] ); ?></dd>
				<?php endif; ?>
			<?php endif; ?>

			<?php 
			// Marital status at marriage (for wives)
			$marital_status = get_field( 'marital_status_at_marriage', $saint_id );
			if ( $marital_status && $marital_status !== 'never_married' ) : 
				$status_labels = array(
					'widow' => 'Widow',
					'divorced' => 'Divorced',
					'separated' => 'Separated',
				);
			?>
				<dt>Status at Marriage</dt>
				<dd><?php echo esc_html( $status_labels[ $marital_status ] ?? $marital_status ); ?></dd>
			<?php endif; ?>
		</dl>
	</div>

	<div class="leader-navigation">
		<a href="<?php echo get_post_type_archive_link( 'saint' ); ?>" class="btn btn-secondary">
			← All Saints
		</a>
	</div>
</aside>
