<?php
/**
 * Template Name: Profile
 * 
 * The template for displaying all profiles in a directory
 *
 * @subpackage wasmo
 * @since 1.0.0
 */

get_header();
?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">

			<article class="entry">
				<div class="entry-content">

					<?php
						$curauth = (get_query_var('author_name')) ? get_user_by('slug', get_query_var('author_name')) : get_userdata(get_query_var('author'));
						$userid = $curauth->ID;
					?>

					<?php set_query_var( 'userid', $userid ); ?>
					<?php get_template_part( 'template-parts/content/content', 'user' ); ?>
					
				</div>
			</article>
		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();