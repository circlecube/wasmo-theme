<?php
/**
 * Template part for displaying shelf and spectrum taxonomies in columns
 */

?>

<section class="entry-content the-directory tax-directory">
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%">
<?php get_template_part( 'template-parts/content/content', 'tax-shelf' ); ?>
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%">
<?php get_template_part( 'template-parts/content/content', 'tax-spectrum' ); ?>
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
</section>