<?php
/**
 * Render callback for the Taxonomy Cloud block
 * 
 * @package Wasmo_Theme
 * @subpackage Blocks
 */

// Get block attributes with defaults
$taxonomy      = $attributes['taxonomy'] ?? 'shelf';
$custom_title  = $attributes['title'] ?? '';
$heading_level = $attributes['headingLevel'] ?? 3;
$show_icon     = $attributes['showIcon'] ?? true;
$order_by      = $attributes['orderBy'] ?? 'name';
$order         = $attributes['order'] ?? 'ASC';
$hide_empty    = $attributes['hideEmpty'] ?? false;

// Default titles and icons per taxonomy
$taxonomy_config = array(
    'shelf' => array(
        'title' => 'Mormon shelf issues:',
        'icon'  => 'shelf',
    ),
    'spectrum' => array(
        'title' => 'Mormon Spectrum:',
        'icon'  => 'spectrum',
    ),
    'question' => array(
        'title' => 'Questions about the Mormon Church:',
        'icon'  => 'question',
    ),
);

// Get config for selected taxonomy
$config = $taxonomy_config[ $taxonomy ] ?? $taxonomy_config['shelf'];
$title  = ! empty( $custom_title ) ? $custom_title : $config['title'];
$icon   = $config['icon'];

// Build cache key based on query parameters
$cache_key = 'wasmo_tax_cloud_' . md5( $taxonomy . $order_by . $order . ( $hide_empty ? '1' : '0' ) );
$terms = get_transient( $cache_key );

if ( false === $terms ) {
    // Get terms
    $terms = get_terms( array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => $hide_empty,
        'orderby'    => $order_by,
        'order'      => $order,
        'count'      => true,
    ) );

    if ( is_wp_error( $terms ) ) {
        $terms = array();
    }

    // Cache for 12 hours
    set_transient( $cache_key, $terms, 12 * HOUR_IN_SECONDS );
}

if ( empty( $terms ) ) {
    return;
}

// Get wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => 'wp-block-wasmo-taxonomy-cloud taxonomy-cloud-' . esc_attr( $taxonomy ),
) );

$heading_tag = 'h' . intval( $heading_level );
?>
<div <?php echo $wrapper_attributes; ?>>
    <<?php echo $heading_tag; ?> class="taxonomy-cloud-title">
        <?php if ( $show_icon ) : ?>
            <?php echo wasmo_get_icon_svg( $icon, 24 ); ?>
        <?php endif; ?>
        <?php echo esc_html( $title ); ?>
    </<?php echo $heading_tag; ?>>

    <?php if ( $taxonomy === 'question' ) : ?>
        <ul class="questions">
            <li><a href="<?php echo esc_url( home_url( '/why-i-left/' ) ); ?>" class="question">Why I left?</a></li>
            <?php foreach ( $terms as $term ) : ?>
                <?php if ( $term->count > 0 ) : ?>
                    <li>
                        <a class="question question-<?php echo esc_attr( $term->term_id ); ?>" 
                           href="<?php echo esc_url( get_term_link( $term ) ); ?>">
                            <?php echo esc_html( $term->name ); ?>
                        </a>
                    </li>
                <?php else : ?>
                    <li><?php echo esc_html( $term->name ); ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <ul class="tags">
            <?php foreach ( $terms as $term ) : ?>
                <li>
                    <a class="tag" 
                       data-id="<?php echo esc_attr( $term->term_id ); ?>" 
                       href="<?php echo esc_url( get_term_link( $term ) ); ?>">
                        <?php echo esc_html( $term->name ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
