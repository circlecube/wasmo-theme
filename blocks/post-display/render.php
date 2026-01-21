<?php
/**
 * Render callback for the Post Display block
 * 
 * @package Wasmo_Theme
 * @subpackage Blocks
 */

// Get block attributes with defaults
$title               = $attributes['title'] ?? 'Latest Posts';
$heading_level       = $attributes['headingLevel'] ?? 2;
$show_title          = $attributes['showTitle'] ?? true;
$posts_to_show       = $attributes['postsToShow'] ?? 3;
$category_id         = $attributes['categoryId'] ?? 0;
$exclude_category_ids = $attributes['excludeCategoryIds'] ?? array();
$tag_id              = $attributes['tagId'] ?? 0;
$display_layout      = $attributes['displayLayout'] ?? 'list';
$grid_size           = $attributes['gridSize'] ?? 'medium';
$show_excerpt        = $attributes['showExcerpt'] ?? true;
$show_date           = $attributes['showDate'] ?? true;
$show_author         = $attributes['showAuthor'] ?? false;
$show_featured_image = $attributes['showFeaturedImage'] ?? true;
$image_align         = $attributes['featuredImageAlign'] ?? 'left';
$show_button         = $attributes['showButton'] ?? true;
$button_text         = $attributes['buttonText'] ?? 'See More Posts';
$button_url          = $attributes['buttonUrl'] ?? '';
$order_by            = $attributes['orderBy'] ?? 'date';
$order               = $attributes['order'] ?? 'DESC';
$description         = $attributes['description'] ?? '';
$excerpt_length      = $attributes['excerptLength'] ?? 25;

// Determine thumbnail size based on layout
$thumbnail_size = ( $display_layout === 'grid' ) ? 'thumbnail' : 'medium';

// Build query args
$query_args = array(
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => $posts_to_show,
    'orderby'        => $order_by,
    'order'          => $order,
);

// Add category filter
if ( $category_id > 0 ) {
    $query_args['cat'] = $category_id;
    
    // Auto-generate button URL if not set
    if ( empty( $button_url ) && $show_button ) {
        $button_url = get_category_link( $category_id );
    }
}

// Exclude categories
if ( ! empty( $exclude_category_ids ) && is_array( $exclude_category_ids ) ) {
    $query_args['category__not_in'] = $exclude_category_ids;
}

// Add tag filter
if ( $tag_id > 0 ) {
    $query_args['tag_id'] = $tag_id;
    
    // Auto-generate button URL if not set
    if ( empty( $button_url ) && $show_button ) {
        $button_url = get_tag_link( $tag_id );
    }
}

// Default button URL to blog page
if ( empty( $button_url ) && $show_button ) {
    $button_url = get_permalink( get_option( 'page_for_posts' ) );
    if ( ! $button_url ) {
        $button_url = home_url( '/blog/' );
    }
}

// Run query
$posts_query = new WP_Query( $query_args );

if ( ! $posts_query->have_posts() ) {
    return;
}

// Get wrapper attributes
$wrapper_classes = array(
    'wp-block-wasmo-post-display',
    'post-display-layout-' . esc_attr( $display_layout ),
    'post-display-image-' . esc_attr( $image_align ),
);

// Add grid size class when in grid layout
if ( $display_layout === 'grid' ) {
    $wrapper_classes[] = 'post-display-grid-' . esc_attr( $grid_size );
}

$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => implode( ' ', $wrapper_classes ),
) );

$heading_tag = 'h' . intval( $heading_level );
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <<?php echo $heading_tag; ?> class="post-display-title"><?php echo esc_html( $title ); ?></<?php echo $heading_tag; ?>>
        <?php if ( ! empty( $description ) ) : ?>
            <p class="post-display-description"><?php echo esc_html( $description ); ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <ul class="post-display-list <?php echo $display_layout === 'grid' ? 'post-display-grid' : ''; ?>">
        <?php while ( $posts_query->have_posts() ) : $posts_query->the_post(); ?>
            <li class="post-display-item">
                <?php if ( $show_featured_image && has_post_thumbnail() ) : ?>
                    <div class="post-display-image">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail( $thumbnail_size ); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="post-display-content">
                    <a class="post-display-link" href="<?php the_permalink(); ?>">
                        <?php the_title(); ?>
                    </a>
                    
                    <?php if ( $show_date || $show_author ) : ?>
                        <div class="post-display-meta">
                            <?php if ( $show_date ) : ?>
                                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                    <?php echo esc_html( get_the_date() ); ?>
                                </time>
                            <?php endif; ?>
                            <?php if ( $show_author ) : ?>
                                <span class="post-display-author">
                                    <?php echo esc_html( get_the_author() ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( $show_excerpt ) : ?>
                        <div class="post-display-excerpt">
                            <?php echo wp_trim_words( get_the_excerpt(), $excerpt_length, '...' ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </li>
        <?php endwhile; ?>
    </ul>

    <?php if ( $show_button && ! empty( $button_url ) ) : ?>
        <div class="is-layout-flex wp-block-buttons">
            <div class="wp-block-button has-custom-font-size is-style-outline" style="font-size:20px">
                <a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $button_url ); ?>" style="border-radius:100px">
                    <?php echo esc_html( $button_text ); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
wp_reset_postdata();
