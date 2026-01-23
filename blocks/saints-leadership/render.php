<?php
/**
 * Render callback for the Saints Leadership block
 * 
 * @package Wasmo_Theme
 * @subpackage Blocks
 */

// Get block attributes with defaults
$filter_mode      = $attributes['filterMode'] ?? 'preset';
$leadership_group = $attributes['leadershipGroup'] ?? 'first-presidency';
$show_title       = $attributes['showTitle'] ?? true;
$show_description = $attributes['showDescription'] ?? true;
$show_badges      = $attributes['showBadges'] ?? true;
$card_size        = $attributes['cardSize'] ?? 'large';
$show_age_dates   = $attributes['showAgeDates'] ?? true;
$show_service_dates = $attributes['showServiceDates'] ?? true;
$show_role_badge  = $attributes['showRoleBadge'] ?? false;

// Get wrapper attributes
$wrapper_classes = array(
    'wp-block-wasmo-saints-leadership',
    'saints-leadership-' . esc_attr( $leadership_group ),
);

$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => implode( ' ', $wrapper_classes ),
) );

?>
<div <?php echo $wrapper_attributes; ?>>
<?php

// Handle custom filter mode
if ( $filter_mode === 'custom' ) {
    // Get filter attributes
    $role_filter         = $attributes['roleFilter'] ?? array();
    $role_filter_operator = $attributes['roleFilterOperator'] ?? 'IN';
    $living_status       = $attributes['livingStatus'] ?? 'all';
    $exclude_ids         = $attributes['excludeIds'] ?? array();
    $order_by            = $attributes['orderBy'] ?? 'title';
    $order_by_meta_key   = $attributes['orderByMetaKey'] ?? '';
    $order               = $attributes['order'] ?? 'ASC';
    $grid_columns        = $attributes['gridColumns'] ?? 5;
    $layout              = $attributes['layout'] ?? 'grid';
    
    // Build cache key from filter criteria
    $cache_key = 'wasmo_block_filter_' . md5( serialize( array(
        'roles' => $role_filter,
        'role_op' => $role_filter_operator,
        'living' => $living_status,
        'exclude' => $exclude_ids,
        'orderby' => $order_by,
        'orderby_meta' => $order_by_meta_key,
        'order' => $order,
    ) ) );
    
    // Try to get from cache
    $saint_ids = get_transient( $cache_key );
    
    if ( false === $saint_ids ) {
        // Build query args
        $query_args = array(
            'post_type'      => 'saint',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        );
        
        // Role filter
        if ( ! empty( $role_filter ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'saint-role',
                    'field'    => 'slug',
                    'terms'    => $role_filter,
                    'operator' => $role_filter_operator,
                ),
            );
        }
        
        // Living status filter
        if ( $living_status === 'living' ) {
            $query_args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key'     => 'deathdate',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => 'deathdate',
                    'value'   => '',
                    'compare' => '=',
                ),
            );
        } elseif ( $living_status === 'deceased' ) {
            $query_args['meta_query'] = array(
                array(
                    'key'     => 'deathdate',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => 'deathdate',
                    'value'   => '',
                    'compare' => '!=',
                ),
            );
        }
        
        // Order by
        if ( $order_by === 'meta_value' && ! empty( $order_by_meta_key ) ) {
            $query_args['meta_key'] = $order_by_meta_key;
            $query_args['orderby']  = 'meta_value';
        } else {
            $query_args['orderby'] = $order_by;
        }
        $query_args['order'] = $order;
        
        // Get posts
        $saint_ids = get_posts( $query_args );
        
        // Exclude IDs
        if ( ! empty( $exclude_ids ) ) {
            $saint_ids = array_diff( $saint_ids, $exclude_ids );
            $saint_ids = array_values( $saint_ids ); // Re-index
        }
        
        // Cache for 1 week
        set_transient( $cache_key, $saint_ids, WEEK_IN_SECONDS );
    }
    
    // Render the list
    if ( ! empty( $saint_ids ) ) :
        if ( $show_title && ! empty( $attributes['customTitle'] ) ) : ?>
            <h3 class="group-title"><?php echo esc_html( $attributes['customTitle'] ); ?></h3>
        <?php endif;
        
        if ( $show_description && ! empty( $attributes['customDescription'] ) ) : ?>
            <p class="group-description"><?php echo esc_html( $attributes['customDescription'] ); ?></p>
        <?php endif; ?>
        
        <div class="leadership-group custom-filter-group">
            <?php if ( $layout === 'timeline' ) : ?>
                <div class="leaders-timeline">
                    <?php 
                    $count = 1;
                    foreach ( $saint_ids as $saint_id ) : 
                    ?>
                        <div class="timeline-item">
                            <?php if ( $show_badges ) : ?>
                                <span class="timeline-number"><?php echo $count; ?></span>
                            <?php endif; ?>
                            <?php wasmo_render_saint_card( $saint_id, $card_size, $show_age_dates, $show_service_dates, $show_role_badge ); ?>
                        </div>
                    <?php 
                        $count++;
                    endforeach; 
                    ?>
                </div>
            <?php else : ?>
                <div class="leaders-grid leaders-grid-<?php echo esc_attr( $grid_columns ); ?>">
                    <?php foreach ( $saint_ids as $saint_id ) : ?>
                        <?php wasmo_render_saint_card( $saint_id, $card_size, $show_age_dates, $show_service_dates, $show_role_badge ); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif;
    
} else {
    // Use preset groups
    switch ( $leadership_group ) {
    case 'first-presidency':
        $first_presidency = wasmo_get_current_first_presidency();
        
        if ( $first_presidency['president'] || $first_presidency['first-counselor'] || $first_presidency['second-counselor'] ) :
            if ( $show_title ) : ?>
                <h3 class="group-title">The First Presidency</h3>
            <?php endif;
            
            if ( $show_description ) : ?>
                <p class="group-description">The highest governing body of The Church of Jesus Christ of Latter-day Saints</p>
            <?php endif; ?>
            
            <div class="leadership-group first-presidency-group">
                <div class="leaders-grid leaders-grid-3 fp-grid">
                    <?php if ( $first_presidency['president'] ) : ?>
                        <div class="fp-card-wrapper fp-president">
                            <?php if ( $show_badges ) : ?>
                                <span class="fp-badge fp-badge-president">President</span>
                            <?php endif; ?>
                            <?php wasmo_render_saint_card( $first_presidency['president'], $card_size, true, true, false ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $first_presidency['first-counselor'] ) : ?>
                        <div class="fp-card-wrapper fp-first-counselor">
                            <?php if ( $show_badges ) : ?>
                                <span class="fp-badge fp-badge-counselor">1st Counselor</span>
                            <?php endif; ?>
                            <?php wasmo_render_saint_card( $first_presidency['first-counselor'], $card_size, true, true, false ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $first_presidency['second-counselor'] ) : ?>
                        <div class="fp-card-wrapper fp-second-counselor">
                            <?php if ( $show_badges ) : ?>
                                <span class="fp-badge fp-badge-counselor">2nd Counselor</span>
                            <?php endif; ?>
                            <?php wasmo_render_saint_card( $first_presidency['second-counselor'], $card_size, true, true, false ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif;
        break;

    case 'quorum-of-twelve':
        $quorum_of_twelve = wasmo_get_current_quorum_of_twelve();
        
        if ( ! empty( $quorum_of_twelve ) ) :
            if ( $show_title ) : ?>
                <h3 class="group-title">Quorum of the Twelve Apostles</h3>
            <?php endif;
            
            if ( $show_description ) : ?>
                <p class="group-description">Listed in order of seniority</p>
            <?php endif; ?>
            
            <div class="leadership-group twelve-apostles-group">
                <div class="leaders-grid leaders-grid-6">
                    <?php 
                    $position = 1;
                    foreach ( $quorum_of_twelve as $apostle_id ) : 
                    ?>
                        <div class="apostle-card-wrapper">
                            <?php if ( $show_badges ) : ?>
                                <span class="seniority-number"><?php echo $position; ?></span>
                            <?php endif; ?>
                            <?php wasmo_render_saint_card( $apostle_id, $card_size, true, true, false ); ?>
                        </div>
                    <?php 
                        $position++;
                    endforeach; 
                    ?>
                </div>
            </div>
        <?php endif;
        break;

    case 'past-presidents':
        // Get past presidents (deceased, ordered by became_president_date) - cached
        $past_presidents = wasmo_get_cached_past_presidents();
        
        if ( ! empty( $past_presidents ) ) :
            if ( $show_title ) : ?>
                <h3 class="group-title">Past Church Presidents</h3>
            <?php endif;
            
            if ( $show_description ) : ?>
                <p class="group-description">In chronological order by presidency</p>
            <?php endif; ?>
            
            <div class="leadership-group past-presidents-group">
                <div class="leaders-timeline">
                    <?php 
                    $count = 1;
                    foreach ( $past_presidents as $president_id ) : 
                    ?>
                        <div class="timeline-item">
                            <?php if ( $show_badges ) : ?>
                                <span class="timeline-number"><?php echo $count; ?></span>
                            <?php endif; ?>
                            <?php wasmo_render_saint_card( $president_id, $card_size, true, true, false ); ?>
                        </div>
                    <?php 
                        $count++;
                    endforeach; 
                    ?>
                </div>
            </div>
        <?php endif;
        break;

    case 'all-presidents':
        // Get current president
        $first_presidency = wasmo_get_current_first_presidency();
        $current_president_id = $first_presidency['president'];
        
        // Get past presidents
        $past_presidents = wasmo_get_cached_past_presidents();
        
        // Combine: past presidents + current president
        $all_presidents = $past_presidents;
        if ( $current_president_id && ! in_array( $current_president_id, $all_presidents ) ) {
            $all_presidents[] = $current_president_id;
        }
        
        if ( ! empty( $all_presidents ) ) :
            if ( $show_title ) : ?>
                <h3 class="leadership-title">Church Presidents</h3>
            <?php endif;
            
            if ( $show_description ) : ?>
                <p class="leadership-description">All presidents of The Church of Jesus Christ of Latter-day Saints, in chronological order</p>
            <?php endif; ?>
            
            <div class="leadership-group all-presidents-group">
                <div class="leaders-timeline">
                    <?php 
                    $count = 1;
                    foreach ( $all_presidents as $president_id ) : 
                    ?>
                        <div class="timeline-item">
                            <?php if ( $show_badges ) : ?>
                                <span class="timeline-number"><?php echo $count; ?></span>
                            <?php endif; ?>
                            <?php wasmo_render_saint_card( $president_id, $card_size, true, true, false ); ?>
                        </div>
                    <?php 
                        $count++;
                    endforeach; 
                    ?>
                </div>
            </div>
        <?php endif;
        break;
    }
}

?>
</div>
