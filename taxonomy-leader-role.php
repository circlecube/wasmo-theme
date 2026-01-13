<?php
/**
 * The template for displaying archive of church leaders for the leader-role taxonomy
 *
 * @package wasmo
 */

get_header();

$term = get_queried_object();
$term_id = $term->term_id;

// Get all leaders with this role
$leaders = get_posts( array(
	'post_type'      => 'church-leader',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'tax_query'      => array(
		array(
			'taxonomy' => 'leader-role',
			'field'    => 'term_id',
			'terms'    => $term_id,
		),
	),
) );

// Sort by ordained_date first, then birthdate as fallback
usort( $leaders, function( $a, $b ) {
	$a_ordained = get_field( 'ordained_date', $a->ID );
	$b_ordained = get_field( 'ordained_date', $b->ID );
	$a_birth = get_field( 'birthdate', $a->ID );
	$b_birth = get_field( 'birthdate', $b->ID );
	
	// If both have ordained dates, sort by that
	if ( $a_ordained && $b_ordained ) {
		return strtotime( $a_ordained ) - strtotime( $b_ordained );
	}
	
	// If only one has ordained date, that one comes first
	if ( $a_ordained && ! $b_ordained ) {
		return -1;
	}
	if ( ! $a_ordained && $b_ordained ) {
		return 1;
	}
	
	// Neither has ordained date, fall back to birthdate
	if ( $a_birth && $b_birth ) {
		return strtotime( $a_birth ) - strtotime( $b_birth );
	}
	
	// Handle cases where birthdate might be missing
	if ( $a_birth && ! $b_birth ) {
		return -1;
	}
	if ( ! $a_birth && $b_birth ) {
		return 1;
	}
	
	return 0;
} );

// Get current First Presidency settings for counselor roles
$first_presidency = wasmo_get_current_first_presidency();

// Separate current and past leaders
// For first-counselor and second-counselor, use the settings to determine "current"
// For other roles, use living/deceased status
$current_leaders = array();
$past_leaders = array();

foreach ( $leaders as $leader ) {
	$is_living = wasmo_is_leader_living( $leader->ID );
	
	// Check if this is a counselor role that should use settings
	if ( $term->slug === 'first-counselor' ) {
		// Current = matches the setting for first counselor
		if ( $leader->ID === $first_presidency['first-counselor'] ) {
			$current_leaders[] = $leader;
		} else {
			$past_leaders[] = $leader;
		}
	} elseif ( $term->slug === 'second-counselor' ) {
		// Current = matches the setting for second counselor
		if ( $leader->ID === $first_presidency['second-counselor'] ) {
			$current_leaders[] = $leader;
		} else {
			$past_leaders[] = $leader;
		}
	} elseif ( $term->slug === 'president' ) {
		// Current = matches the setting for president
		if ( $leader->ID === $first_presidency['president'] ) {
			$current_leaders[] = $leader;
		} else {
			$past_leaders[] = $leader;
		}
	} else {
		// For other roles (apostle, seventy, etc.), use living status
		if ( $is_living ) {
			$current_leaders[] = $leader;
		} else {
			$past_leaders[] = $leader;
		}
	}
}

// Use CMS description if available, otherwise fallback to defaults
$fallback_descriptions = array(
	'president'          => 'The President of the Church is considered a prophet, seer, and revelator, and is the highest authority in The Church of Jesus Christ of Latter-day Saints.',
	'apostle'            => 'Apostles are ordained to the Melchizedek Priesthood office of Apostle and serve as special witnesses of Jesus Christ. The Quorum of the Twelve Apostles is the second-highest governing body of the Church.',
	'first-presidency'   => 'The First Presidency consists of the President of the Church and his counselors. Together they form the highest governing body of the Church.',
	'first-counselor'    => 'The First Counselor in the First Presidency assists the President of the Church in directing the affairs of the Church.',
	'second-counselor'   => 'The Second Counselor in the First Presidency assists the President of the Church in directing the affairs of the Church.',
	'seventy'            => 'Members of the Seventy are general authorities called to preach the gospel and assist in administering the Church under the direction of the Twelve Apostles.',
	'presiding-bishopric' => 'The Presiding Bishopric oversees the temporal affairs of the Church, including finances, welfare, and physical facilities.',
);

// Prioritize CMS description, fallback to hardcoded defaults
$role_description = ! empty( $term->description ) 
	? $term->description 
	: ( isset( $fallback_descriptions[ $term->slug ] ) ? $fallback_descriptions[ $term->slug ] : '' );
?>

<section id="primary" class="content-area">
	<main id="main" class="site-main leader-role-archive">
		<article class="entry">
			<header class="entry-header leader-role-header">
				<h1 class="entry-title no-line">
					<?php echo wasmo_get_icon_svg( 'church-leader', 36 ); ?>
					<?php echo esc_html( $term->name ); ?>
				</h1>
				
				<?php if ( $role_description ) : ?>
					<p class="entry-description role-description"><?php echo esc_html( $role_description ); ?></p>
				<?php endif; ?>
				
				<p class="leader-count">
					<strong><?php echo count( $leaders ); ?></strong> church leaders have held this role
					<?php if ( count( $current_leaders ) > 0 ) : ?>
						(<strong><?php echo count( $current_leaders ); ?></strong> currently serving)
					<?php endif; ?>
				</p>
			</header>

			<div class="leader-role-content">
				
				<?php if ( ! empty( $current_leaders ) ) : ?>
					<section class="leaders-section current-leaders">
						<h2>Current <?php echo esc_html( $term->name ); ?><?php echo count( $current_leaders ) !== 1 ? 's' : ''; ?></h2>
						<div class="leaders-grid">
							<?php foreach ( $current_leaders as $leader ) : ?>
								<?php wasmo_render_leader_card( $leader->ID, 'medium', true, true, false ); ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $past_leaders ) ) : ?>
					<section class="leaders-section historical-leaders">
						<h2>Past <?php echo esc_html( $term->name ); ?>s</h2>
						<div class="leaders-grid leaders-grid-small">
							<?php foreach ( $past_leaders as $leader ) : ?>
								<?php wasmo_render_leader_card( $leader->ID, 'small', true, true, false ); ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

			</div>

			<footer class="entry-footer leader-role-footer">
				<h3>
					<?php echo wasmo_get_icon_svg( 'church-leader', 24 ); ?>
					All Leadership Roles:
				</h3>
				<ul class="tags role-tags">
					<?php
					$all_roles = get_terms( array(
						'taxonomy'   => 'leader-role',
						'hide_empty' => true,
						'orderby'    => 'name',
						'order'      => 'ASC',
					) );
					
					foreach ( $all_roles as $role ) : 
						$is_current = ( $role->term_id === $term_id );
					?>
						<li>
							<a class="tag leader-role-tag <?php echo $is_current ? 'current-role' : ''; ?>" 
							   href="<?php echo esc_url( get_term_link( $role ) ); ?>">
								<?php echo esc_html( $role->name ); ?>
								<span class="role-count">(<?php echo esc_html( $role->count ); ?>)</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
				
				<p class="archive-link">
					<a href="<?php echo esc_url( get_post_type_archive_link( 'church-leader' ) ); ?>" class="btn btn-secondary">
						← View All Church Leaders
					</a>
				</p>
			</footer>

		</article>
	</main>
</section>

<?php
get_footer();
