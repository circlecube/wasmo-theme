<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0
 */

get_header();
?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">

			<article class="error-404 not-found entry">
				<header class="entry-header">
					<h1 class="entry-title">Oops!</h1>
					<h3 class="entry-subtitle">That can't be displayed right now.</h3>
					<h3 class="entry-subtitle">Looking for one of these profiles?</h3>
				</header><!-- .page-header -->

				<?php get_template_part( 'template-parts/content/content', 'directory' ); ?>

			</article><!-- .error-404 -->

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
