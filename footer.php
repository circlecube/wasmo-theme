<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0
 */
?>
	</div><!-- #content -->

	<footer id="colophon" class="site-footer">
		<?php get_template_part( 'template-parts/footer/footer', 'widgets' ); ?>
		<div class="site-info">
			<?php $blog_info = get_bloginfo( 'name' ); ?>
			<?php if ( ! empty( $blog_info ) ) : ?>
				<a class="site-name" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
			<?php if ( get_field( 'legal_disclaimer', 'option' ) ) { ?>
				<p class="legal_disclaimer"><?php the_field( 'legal_disclaimer', 'option' ); ?></p>
			<?php } else { ?>
				<p class="legal_disclaimer">Though this site discusses mormonism, topics related to mormons, the mormon church and people who refer to themselves as unorthodox mormons, ex-mormons, post-mormons or any other form of wasmormon, it is not officially affiliated with or managed by The Church of Jesus Christ of Latter-day Saints or even the Corporation of the Presiding Bishop. They don't want to be called mormon anymore anyways. All of the content, stories or opinions expressed, implied or included in this site are solely credited to those sharing their own personal stories and not those of Intellectual Reserve, Inc. or The Church of Jesus Christ of Latter-day Saints.</p>
			<?php } ?>
			<?php
			if ( function_exists( 'the_privacy_policy_link' ) ) {
				the_privacy_policy_link( '', '<span role="separator" aria-hidden="true"></span>' );
			}
			?>
			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<nav class="footer-navigation" aria-label="<?php esc_attr_e( 'Footer Menu', 'twentynineteen' ); ?>">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'menu_class'     => 'footer-menu',
							'depth'          => 1,
						)
					);
					?>
				</nav><!-- .footer-navigation -->
			<?php endif; ?>
		</div><!-- .site-info -->
	</footer><!-- #colophon -->

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>