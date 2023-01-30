<?php
/**
 * Template part for displaying shelf taxonomy list
 */

?>

<!-- wp:heading {"level":3} -->
<h3>
    <?php echo wasmo_get_icon_svg( 'shelf', 24 ); ?>
    Mormon shelf issues:
</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"tags"} -->
<ul class="tags"><?php
        $terms = get_terms([
            'taxonomy'   => 'shelf',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC'
        ]);
        foreach ( $terms as $term ) : 
    ?><!-- wp:list-item -->
<li><a class="tag" data-id="<?php echo esc_attr( $term->term_id) ?>" href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a></li>
<!-- /wp:list-item --><?php endforeach; ?></ul>
<!-- /wp:list -->