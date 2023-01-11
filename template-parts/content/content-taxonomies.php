<?php
// Show tag lists for shelf items and spectrum.

?>
<section class="entry-content the-directory tax-directory">
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:heading {"level":3} -->
<h3>Issues on the mormon shelf:</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"tags"} -->
<ul class="tags"><?php
        $terms = get_terms([
            'taxonomy'   => 'shelf',
            // 'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC'
        ]);
        foreach ( $terms as $term ) : 
    ?><!-- wp:list-item -->
<li><a class="tag" data-id="<?php echo esc_attr( $term->term_id) ?>" href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a></li>
<!-- /wp:list-item --><?php endforeach; ?></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:heading {"level":3} -->
<h3>Mormon Spectrum:</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"tags"} -->
<ul class="tags"><?php
        $terms = get_terms([
            'taxonomy'   => 'spectrum',
            // 'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC'
        ]);
        foreach ( $terms as $term ) : 
    ?><!-- wp:list-item -->
<li><a class="tag" data-id="<?php echo esc_attr( $term->term_id) ?>" href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a></li>
<!-- /wp:list-item --><?php endforeach; ?></ul>
<!-- /wp:list --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
</section>