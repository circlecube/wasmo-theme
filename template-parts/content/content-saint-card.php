<?php
/**
 * Template part for displaying a saint card
 *
 * @package wasmo
 * 
 * Expected variables (passed via get_template_part args):
 * - saint_id: The saint post ID (required)
 * - size: Card size - 'small', 'medium', or 'large' (default: 'medium')
 * - show_age_dates: Whether to show birth/death dates (default: true)
 * - show_service_dates: Whether to show service dates (default: true)
 * - show_role: Whether to show role badge (default: false)
 * - role_override: Custom role text to display (default: '')
 * - content: Additional HTML content to display (default: '')
 */

// Get passed variables with defaults
$saint_id           = $args['saint_id'] ?? null;
$size               = $args['size'] ?? 'medium';
$show_age_dates     = $args['show_age_dates'] ?? true;
$show_service_dates = $args['show_service_dates'] ?? true;
$show_role          = $args['show_role'] ?? false;
$role_override      = $args['role_override'] ?? '';
$content            = $args['content'] ?? '';

// Bail if no saint ID
if ( ! $saint_id ) {
	return;
}

// Get saint data
$saint = get_post( $saint_id );
if ( ! $saint ) {
	return;
}
$thumbnail_size = $size === 'small' ? 'thumbnail' : 'medium';
$thumbnail  = get_the_post_thumbnail_url( $saint_id, $thumbnail_size );
$roles      = wp_get_post_terms( $saint_id, 'saint-role', array( 'fields' => 'names' ) );
$role_slugs = wp_get_post_terms( $saint_id, 'saint-role', array( 'fields' => 'slugs' ) );
$is_living  = wasmo_is_saint_living( $saint_id );

// Check first presidency status
$fp            = wasmo_get_current_first_presidency();
$is_current_pr = $saint_id === $fp['president'];
$is_fc         = $saint_id === $fp['first-counselor'];
$is_sc         = $saint_id === $fp['second-counselor'];
$is_fp         = $is_current_pr || $is_fc || $is_sc;

// Check if saint has president role (current or past)
$has_president_role = is_array( $role_slugs ) && in_array( 'president', $role_slugs, true );

// Build CSS classes
$classes = array(
	'saint-card',
	'saint-card-' . esc_attr( $size ),
	$is_living ? 'saint-living' : 'saint-deceased',
);

if ( $is_fp ) {
	$classes[] = 'saint-first-presidency';
}
if ( $has_president_role ) {
	$classes[] = 'saint-president';
}
if ( $is_fc ) {
	$classes[] = 'saint-first-counselor';
}
if ( $is_sc ) {
	$classes[] = 'saint-second-counselor';
}
?>
<a
	href="<?php echo esc_url( get_permalink( $saint_id ) ); ?>" 
	class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	title="<?php echo esc_attr( $saint->post_title ); ?>"
>
	<div class="saint-card-image-wrapper">
		<?php if ( $thumbnail ) : ?>
			<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $saint->post_title ); ?>" class="saint-card-image">
		<?php else : ?>
			<?php echo wasmo_get_saint_placeholder( $saint_id ); ?>
		<?php endif; ?>
	</div>
	<div class="saint-card-info">
		<span class="saint-card-name"><?php echo esc_html( $saint->post_title ); ?></span>
		<?php if ( $show_age_dates ) : ?>
			<span class="saint-card-dates"><?php echo esc_html( wasmo_get_saint_lifespan( $saint_id ) ); ?></span>
		<?php endif; ?>
		<?php if ( $show_role && ! empty( $roles ) && is_array( $roles ) ) : ?>
			<span class="saint-card-role"><?php echo esc_html( implode( ', ', $roles ) ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $role_override ) ) : ?>
			<span class="saint-card-role"><?php echo esc_html( $role_override ); ?></span>
		<?php endif; ?>
		<?php if ( $show_service_dates ) : ?>
			<span class="saint-card-dates"><?php echo esc_html( wasmo_get_saint_service_date( $saint_id ) ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $content ) ) : ?>
			<div class="saint-card-content"><?php echo $content; ?></div>
		<?php endif; ?>
	</div>
</a>
