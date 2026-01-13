<?php
/**
 * The template for displaying single Church Leader posts
 *
 * @package wasmo
 */

get_header();

while ( have_posts() ) :
	the_post();
	
	$leader_id = get_the_ID();
	$is_living = wasmo_is_leader_living( $leader_id );
	$is_apostle = wasmo_leader_has_role( $leader_id, 'apostle' );
	$is_president = wasmo_leader_has_role( $leader_id, 'president' );
	
	// Get role terms for badges
	$roles = wp_get_post_terms( $leader_id, 'leader-role' );
	
	// Get related content
	$related_posts = wasmo_get_leader_related_posts( $leader_id, -1 );
	$related_media = wasmo_get_leader_related_media( $leader_id, 100 );
	
	// Get prophets this leader served under (if apostle)
	$served_under = array();
	if ( $is_apostle ) {
		$served_under = wasmo_get_served_with_prophets( $leader_id );
	}
	
	// Get apostles who served under this leader (if prophet)
	$apostles_under = array();
	if ( $is_president ) {
		$apostles_under = wasmo_get_apostles_who_served_under( $leader_id );
	}
?>

<?php
// Filter out leader-role-* classes from post_class to avoid archive card styling
$classes = get_post_class( 'church-leader-single' );
$classes = array_filter( $classes, function( $class ) {
	return strpos( $class, 'leader-role-' ) !== 0;
});
?>
<article id="post-<?php the_ID(); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	
	<header class="leader-header">
		<div class="leader-header-content">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="leader-portrait">
					<?php the_post_thumbnail( 'medium_large' ); ?>
				</div>
			<?php endif; ?>
			
			<div class="leader-header-info">
				
				<h1 class="leader-name">
					<?php the_title(); ?>
				</h1>
				
				<p class="leader-lifespan">
					<?php echo esc_html( wasmo_get_leader_lifespan( $leader_id ) ); ?>
					<?php if ( $is_living ) : ?>
						<span class="leader-status-badge leader-status-living">Living</span>
					<?php endif; ?>
				</p>
				
				<?php 
				$hometown = get_field( 'hometown' );
				if ( $hometown ) : ?>
					<p class="leader-hometown">
						<span class="label">Hometown:</span> <?php echo esc_html( $hometown ); ?>
					</p>
				<?php endif; ?>
				
				<div class="leader-roles">
					<?php echo wasmo_get_icon_svg( 'church-leader', 30 ); ?>
					<?php foreach ( $roles as $role ) : ?>
						<a href="<?php echo esc_url( get_term_link( $role ) ); ?>" class="leader-role-badge leader-role-<?php echo esc_attr( $role->slug ); ?>">
							<?php echo esc_html( $role->name ); ?>
						</a>
					<?php endforeach; ?>
					
				</div>
			</div>
		</div>
	</header>

	<div class="leader-content-wrapper">
		<div class="leader-main-content">
			
			<?php if ( get_the_content() ) : ?>
				<section class="leader-bio">
					<h2>Biography</h2>
					<div class="leader-bio-content">
						<?php the_content(); ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $served_under ) ) : ?>
				<section class="leader-served-under">
					<h2>Served Under</h2>
					<p class="section-description">Church presidents during this leader's apostolic service:</p>
					<div class="leaders-grid">
						<?php foreach ( $served_under as $prophet_id ) : 
							$prophet = get_post( $prophet_id );
							$prophet_thumbnail = get_the_post_thumbnail_url( $prophet_id, 'thumbnail' );
						?>
							<a href="<?php echo get_permalink( $prophet_id ); ?>" class="leader-card leader-card-small">
								<?php if ( $prophet_thumbnail ) : ?>
									<img src="<?php echo esc_url( $prophet_thumbnail ); ?>" alt="<?php echo esc_attr( $prophet->post_title ); ?>" class="leader-card-image">
								<?php else : ?>
									<div class="leader-card-placeholder"></div>
								<?php endif; ?>
								<span class="leader-card-name"><?php echo esc_html( $prophet->post_title ); ?></span>
								<span class="leader-card-dates"><?php echo esc_html( wasmo_get_leader_lifespan( $prophet_id ) ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $apostles_under ) ) : ?>
				<section class="leader-apostles-under">
					<h2>Apostles Who Served During This Presidency</h2>
					<div class="leaders-grid">
						<?php 
						$count = 0;
						foreach ( $apostles_under as $apostle_id ) : 
							if ( $count >= 15 ) break; // Limit display
							$apostle = get_post( $apostle_id );
							$apostle_thumbnail = get_the_post_thumbnail_url( $apostle_id, 'thumbnail' );
						?>
							<a href="<?php echo get_permalink( $apostle_id ); ?>" class="leader-card leader-card-small">
								<?php if ( $apostle_thumbnail ) : ?>
									<img src="<?php echo esc_url( $apostle_thumbnail ); ?>" alt="<?php echo esc_attr( $apostle->post_title ); ?>" class="leader-card-image">
								<?php else : ?>
									<div class="leader-card-placeholder"></div>
								<?php endif; ?>
								<span class="leader-card-name"><?php echo esc_html( $apostle->post_title ); ?></span>
							</a>
						<?php 
							$count++;
						endforeach; 
						?>
					</div>
					<?php if ( count( $apostles_under ) > 15 ) : ?>
						<p class="view-all-link">
							<a href="<?php echo get_post_type_archive_link( 'church-leader' ); ?>">View all church leaders →</a>
						</p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $related_posts ) ) : ?>
				<?php 
				$featured_posts = array_slice( $related_posts, 0, 6 );
				$remaining_posts = array_slice( $related_posts, 6 );
				?>
				<section class="leader-related-posts">
					<h2>Related Posts (<?php echo count( $related_posts ); ?>)</h2>
					
					<?php if ( ! empty( $featured_posts ) ) : ?>
						<div class="related-posts-grid">
							<?php foreach ( $featured_posts as $related_post ) : ?>
								<article class="related-post-card related-post">
									<?php if ( has_post_thumbnail( $related_post->ID ) ) : ?>
										<a href="<?php echo get_permalink( $related_post->ID ); ?>" class="related-post-thumbnail">
											<?php echo get_the_post_thumbnail( $related_post->ID, 'medium' ); ?>
										</a>
									<?php endif; ?>
									<h3 class="related-post-title">
										<a href="<?php echo get_permalink( $related_post->ID ); ?>">
											<?php echo esc_html( $related_post->post_title ); ?>
										</a>
									</h3>
									<p class="related-post-date">
										<?php echo get_the_date( 'F j, Y', $related_post->ID ); ?>
									</p>
								</article>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $remaining_posts ) ) : ?>
						<ul class="related-posts-list">
							<?php foreach ( $remaining_posts as $related_post ) : ?>
								<li>
									<a href="<?php echo get_permalink( $related_post->ID ); ?>">
										<?php echo esc_html( $related_post->post_title ); ?>
									</a>
									<span class="related-post-list-date">(<?php echo get_the_date( 'M j, Y', $related_post->ID ); ?>)</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $related_media ) ) : ?>
				<section class="leader-related-media">
					<h2>Related Images</h2>
					<div class="media-gallery">
						<?php foreach ( $related_media as $media ) : 
							$image_url = wp_get_attachment_image_url( $media->ID, 'medium' );
							$alt_text = get_post_meta( $media->ID, '_wp_attachment_image_alt', true );
							// Link to parent post if it exists, otherwise link to the attachment page
							$parent_id = $media->post_parent;
							$link_url = $parent_id ? get_permalink( $parent_id ) : get_attachment_link( $media->ID );
							if ( $image_url ) :
						?>
							<a href="<?php echo esc_url( $link_url ); ?>" class="media-gallery-item" title="<?php echo esc_html( $alt_text ); ?>">
								<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $alt_text ); ?>">
							</a>
						<?php 
						endif;
						endforeach; 
						?>
					</div>
				</section>
			<?php endif; ?>

		</div>

		<aside class="leader-sidebar">
			<div class="leader-metadata">
				<h3>Details</h3>
				<dl class="leader-meta-list">
					<?php 
					// Birth date
					$birthdate = get_field( 'birthdate' );
					if ( $birthdate ) : ?>
						<dt>Born</dt>
						<dd><?php echo esc_html( wasmo_format_leader_date( $birthdate ) ); ?></dd>
					<?php endif; ?>

					<?php 
					// Death date
					$deathdate = get_field( 'deathdate' );
					if ( $deathdate ) : ?>
						<dt>Died</dt>
						<dd><?php echo esc_html( wasmo_format_leader_date( $deathdate ) ); ?></dd>
					<?php endif; ?>

					<?php 
					// Age
					$age = wasmo_get_leader_age( $leader_id );
					if ( $age !== null ) : ?>
						<dt><?php echo $is_living ? 'Age' : 'Age at Death'; ?></dt>
						<dd><?php echo esc_html( $age ); ?> years</dd>
					<?php endif; ?>

					<?php if ( $is_apostle ) : ?>
						<?php 
						// Ordained date (show time if specified for seniority disambiguation)
						$ordained_date = get_field( 'ordained_date' );
						if ( $ordained_date ) : ?>
							<dt>Ordained Apostle</dt>
							<dd><?php echo esc_html( wasmo_format_leader_date( $ordained_date, 'F j, Y', true ) ); ?></dd>
						<?php endif; ?>

						<?php 
						// Service ended early (excommunication, resignation, etc.)
						$ordain_end = get_field( 'ordain_end' );
						if ( $ordain_end ) : ?>
							<dt>Service Ended</dt>
							<dd><?php echo esc_html( wasmo_format_leader_date( $ordain_end ) ); ?></dd>
						<?php endif; ?>

						<?php 
						// Note about service end
						$ordain_note = get_field( 'ordain_note' );
						if ( $ordain_note ) : ?>
							<dt>Status Note</dt>
							<dd class="leader-ordain-note"><?php echo wp_kses_post( $ordain_note ); ?></dd>
						<?php endif; ?>

						<?php 
						// Age at call
						$age_at_call = wasmo_get_leader_age_at_call( $leader_id );
						if ( $age_at_call !== null ) : ?>
							<dt>Age When Called</dt>
							<dd><?php echo esc_html( $age_at_call ); ?> years</dd>
						<?php endif; ?>

						<?php 
						// Years served
						$years_served = wasmo_get_leader_years_served( $leader_id );
						if ( $years_served !== null ) : ?>
							<dt>Years as Apostle</dt>
							<dd><?php echo esc_html( $years_served ); ?> years</dd>
						<?php endif; ?>

						<?php if ( $is_living ) : 
							$seniority = wasmo_get_leader_seniority( $leader_id );
							if ( $seniority !== null ) : ?>
								<dt>Current Seniority</dt>
								<dd>#<?php echo esc_html( $seniority ); ?></dd>
							<?php endif; ?>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( $is_president ) : ?>
						<?php 
						// Became president date
						$president_date = get_field( 'became_president_date' );
						if ( $president_date ) : ?>
							<dt>Became President</dt>
							<dd><?php echo esc_html( wasmo_format_leader_date( $president_date ) ); ?></dd>
						<?php endif; ?>

						<?php 
						// Years as president
						$years_as_president = wasmo_get_leader_years_as_president( $leader_id );
						if ( $years_as_president !== null ) : ?>
							<dt>Years as President</dt>
							<dd><?php echo esc_html( $years_as_president ); ?> years</dd>
						<?php endif; ?>
					<?php endif; ?>

					<?php 
					// Mission
					$mission = get_field( 'mission' );
					if ( $mission ) : ?>
						<dt>Mission</dt>
						<dd><?php echo esc_html( $mission ); ?></dd>
					<?php endif; ?>

					<?php 
					// Education
					$education = get_field( 'education' );
					if ( $education ) : ?>
						<dt>Education</dt>
						<dd><?php echo esc_html( $education ); ?></dd>
					<?php endif; ?>

					<?php 
					// Profession
					$profession = get_field( 'profession' );
					if ( $profession ) : ?>
						<dt>Profession</dt>
						<dd><?php echo esc_html( $profession ); ?></dd>
					<?php endif; ?>

					<?php 
					// Military
					$military = get_field( 'military' );
					if ( $military ) : ?>
						<dt>Military Service</dt>
						<dd><?php echo esc_html( $military ); ?></dd>
					<?php endif; ?>

					<?php 
					// Polygamy
					$polygamist = get_field( 'polygamist' );
					if ( $polygamist ) : 
						$num_wives = get_field( 'number_of_wives' );
					?>
						<dt>Polygamist</dt>
						<dd>Yes<?php echo $num_wives ? ' (' . esc_html( $num_wives ) . ' wives)' : ''; ?></dd>
					<?php endif; ?>
				</dl>
			</div>

			<div class="leader-navigation">
				<a href="<?php echo get_post_type_archive_link( 'church-leader' ); ?>" class="btn btn-secondary">
					← All Church Leaders
				</a>
			</div>
		</aside>
	</div>

</article>

<?php
endwhile;

get_footer();
