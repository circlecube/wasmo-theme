<?php
/**
 * Template part for displaying posts on blog home
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0
 */

?>

<?php if ( 'post' === get_post_type() ) { ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php
			the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );
		?>
	</header><!-- .entry-header -->

	<?php twentynineteen_post_thumbnail(); ?>

	<div class="entry-content">
		<?php
			the_excerpt();
		?>
	</div><!-- .entry-content -->

</article><!-- #post-${ID} -->
<?php } else if ( 'attachment' === get_post_type() ) { ?>
	<?php
		// Get image alt text
		$image_alt = trim( strip_tags( get_post_meta( get_the_ID(), '_wp_attachment_image_alt', true) ) );
		if ( empty( $image_alt )) {
			$image_alt = get_the_title();
		}
		// Get image caption
		$image_cap = get_the_excerpt();
		if ( empty( $image_cap )) {
			$image_cap = $image_alt;
		}

		// $current_attachment = get_queried_object();
		// Get the permalink of the parent
		$permalink = get_permalink( get_post_parent() );
		// Get the parent title
		$parent_title = get_post( get_post_parent() )->post_title;

	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'media post' ); ?>>
		<div class="entry-content">
			<div class="wp-block-image">
				<figure class="aligncenter size-large">
					<a href="<?php echo esc_url( $permalink ); ?>"  title="<?php echo esc_attr( $parent_title ); ?>">
						<?php 
							echo wp_get_attachment_image( 
								get_the_ID(), 
								'large',
								false,
								array( 
									'alt' => $image_alt,
								)
							);
						?>
					</a>
					<figcaption class="wp-element-caption">	
						<?php echo esc_html( $image_cap ); ?>
					</figcaption>
				</figure>
			</div>
						</div>
		<footer class="entry-footer">
			<p class="link-more screen-reader-text">
				<a class="button more-link" href="<?php echo esc_url( $permalink ); ?>" title="<?php echo esc_attr( $parent_title ); ?>">
					Read Post
					<span class="screen-reader-text"> - <?php echo esc_html( $parent_title ); ?></span>
				</a>
			</p>
		</footer>
	</article>
<?php } ?>