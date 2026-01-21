<?php
/**
 * Render callback for the User Directory block
 * 
 * @package Wasmo_Theme
 * @subpackage Blocks
 */

// Get block attributes with defaults
$context        = $attributes['context'] ?? 'widget';
$max_profiles   = $attributes['maxProfiles'] ?? 9;
$show_load_more = $attributes['showLoadMore'] ?? false;
$show_buttons   = $attributes['showButtons'] ?? true;
$tax_filter     = $attributes['taxonomyFilter'] ?? '';
$term_id        = $attributes['termId'] ?? 0;
$require_image  = $attributes['requireImage'] ?? true;

// Set query vars for the template part
set_query_var( 'context', $context );
set_query_var( 'max_profiles', $max_profiles );
set_query_var( 'lazy', $show_load_more );
set_query_var( 'showall', false );
set_query_var( 'require_image', $require_image );
set_query_var( 'show_buttons', $show_buttons ); // Pass block attribute to template

// Set taxonomy filter if provided
if ( ! empty( $tax_filter ) && $term_id > 0 ) {
    set_query_var( 'tax', $tax_filter );
    set_query_var( 'termid', $term_id );
}

// Get wrapper attributes for block supports (spacing, alignment, etc.)
$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => 'wp-block-wasmo-user-directory',
) );

?>
<div <?php echo $wrapper_attributes; ?>>
    <?php
    // Load the existing directory template part (buttons handled by template based on show_buttons)
    get_template_part( 'template-parts/content/content', 'directory' );
    ?>
</div>
