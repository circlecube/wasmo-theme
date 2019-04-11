<?php
/**
 * Displays the footer widget area
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0
 */

if ( is_active_sidebar( 'sidebar' ) ) : ?>

	<aside class="widget-area sidebar" role="complementary" aria-label="<?php esc_attr_e( 'Sidebar', 'twentynineteen' ); ?>">
		<?php
		if ( is_active_sidebar( 'sidebar' ) ) {
			?>
					<div class="widget-column sidebar-widgets">
					<?php dynamic_sidebar( 'sidebar' ); ?>
					</div>
				<?php
		}
		?>
	</aside><!-- .widget-area -->

<?php endif; ?>
