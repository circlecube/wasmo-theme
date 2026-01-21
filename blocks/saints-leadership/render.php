<?php
/**
 * Render callback for the Saints Leadership block
 * 
 * @package Wasmo_Theme
 * @subpackage Blocks
 */

// Get block attributes with defaults
$leadership_group = $attributes['leadershipGroup'] ?? 'first-presidency';
$show_title       = $attributes['showTitle'] ?? true;
$show_description = $attributes['showDescription'] ?? true;
$show_badges      = $attributes['showBadges'] ?? true;
$card_size        = $attributes['cardSize'] ?? 'large';

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

switch ( $leadership_group ) {
    case 'first-presidency':
        $first_presidency = wasmo_get_current_first_presidency();
        
        if ( $first_presidency['president'] || $first_presidency['first-counselor'] || $first_presidency['second-counselor'] ) :
            if ( $show_title ) : ?>
                <h3 class="leadership-title">The First Presidency</h3>
            <?php endif;
            
            if ( $show_description ) : ?>
                <p class="leadership-description">The highest governing body of The Church of Jesus Christ of Latter-day Saints</p>
            <?php endif; ?>
            
            <div class="leadership-group first-presidency-group">
                <div class="leaders-grid leaders-grid-3">
                    <?php if ( $first_presidency['first-counselor'] ) : ?>
                        <div class="fp-card-wrapper">
                            <?php if ( $show_badges ) : ?>
                                <span class="fp-badge fp-badge-counselor">First Counselor</span>
                            <?php endif; ?>
                            <?php wasmo_render_saint_card( $first_presidency['first-counselor'], $card_size, true, true, false ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $first_presidency['president'] ) : ?>
                        <div class="fp-card-wrapper president-feature">
                            <?php if ( $show_badges ) : ?>
                                <span class="fp-badge fp-badge-president">President</span>
                            <?php endif; ?>
                            <?php wasmo_render_saint_card( $first_presidency['president'], $card_size, true, true, false ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $first_presidency['second-counselor'] ) : ?>
                        <div class="fp-card-wrapper">
                            <?php if ( $show_badges ) : ?>
                                <span class="fp-badge fp-badge-counselor">Second Counselor</span>
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
                <h3 class="leadership-title">Quorum of the Twelve Apostles</h3>
            <?php endif;
            
            if ( $show_description ) : ?>
                <p class="leadership-description">Listed in order of seniority</p>
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

?>
</div>
