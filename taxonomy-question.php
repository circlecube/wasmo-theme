<?php
/**
 * The template for displaying archive pages for questions
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0
 */

get_header();

$termid = get_queried_object()->term_id;
$term = get_term_by( 'id', $termid, 'question' );
?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main">
			<article class="entry">
				<header class="entry-header">
					<h1 class="entry-title">
						<?php echo wasmo_get_icon_svg( 'question', 36 ); ?>
						<?php echo wp_kses_post( $term->name ); ?>
					</h1>
					<h2 class="entry-description has-regular-font-size"><?php echo wp_kses_post( $term->description ); ?></h2>
				</header><!-- .page-header -->

<?php

//define transient name - taxid + user state.
$transient_name = 'answers-tax-question-' . $termid . '-' . is_user_logged_in();
if ( current_user_can('administrator') ) {
	$transient_name = time();
}
//use transient to cache data
if ( false === ( $the_answers = get_transient( $transient_name ) ) ) {

	//get users
	$args = array(
		'orderby'      => 'meta_value',
		'meta_key'     => 'last_save',
		'order'        => 'DESC',
	); 
	$users = get_users( $args );
	//user loop
	foreach ( $users as $user ) { 
		$userid = $user->ID;

		//questions loop
		if( have_rows( 'questions', 'user_' . $userid ) ) {
			
			// loop through the rows of data
			while ( have_rows( 'questions', 'user_' . $userid ) ) {
				the_row();

				$termtaxid = get_sub_field( 'question', 'users_' . $userid );
				$answer = get_sub_field( 'answer', 'user_' . $userid );

				//check if they answered this question
				if ( $termtaxid === $termid && $answer ) {

					// answer
					$the_answers .= '<div class="answer answer-' . $userid . '">';
					$the_answers .= '<blockquote>';
					$the_answers .= auto_link_text( wp_kses_post( $answer ) );
					$the_answers .= '</blockquote>';

					// user attribution - photo and name and link (only if they want to be listed in directory)
					$in_directory = get_field( 'in_directory', 'user_' . $userid );
					if ( 
						'true' === $in_directory ||
						'website' === $in_directory ||
						( 'private' === $in_directory && is_user_logged_in() )
					) {
						$username = esc_html( $user->nickname );
						$the_answers .= '<cite>';
						$the_answers .= '<a class="person person-' . esc_attr( $userid ) . '" href="' . get_author_posts_url( $userid ) . '">';
						$the_answers .= '<span class="directory-img">';
						$the_answers .= wasmo_get_user_image( $userid );
						$the_answers .= '</span>';
						$the_answers .= '<span class="directory-name">' . $username . '</span>';
						$the_answers .= '</a>';
						$the_answers .= '</cite>';
					}

					$the_answers .= '</div>';
				}
			}
		}
	}
	set_transient( $transient_name, $the_answers, 24 * HOUR_IN_SECONDS );
}

?>
					<div class="entry-content answers">
						<?php echo $the_answers; ?>

						<?php if ( '' === $the_answers ) { ?>
							<h3>There are no currently available answers for this question, add your own and be the first!</h3>
						<?php } ?>

						<hr />

						<div class="is-layout-flex wp-block-buttons">
							<div class="wp-block-button has-custom-font-size" style="font-size:20px">
								<a class="wp-block-button__link wp-element-button" href="<?php echo home_url( '/login/' ); ?>" style="border-radius:100px">Create a Profile</a>
							</div>
							<div class="wp-block-button has-custom-font-size is-style-outline" style="font-size:20px">
								<a class="wp-block-button__link wp-element-button" href="<?php echo home_url( '/profiles/' ); ?>" style="border-radius:100px">See All Profiles</a>
							</div>
							<div class="wp-block-button has-custom-font-size is-style-outline" style="font-size:20px">
								<a class="wp-block-button__link wp-element-button" href="<?php echo home_url( '/questions/' ); ?>" style="border-radius:100px">See Questions</a>
							</div>
						</div>

					</div>
					
					<footer class="entry-footer">
					<?php
						// load related posts for this question
						set_query_var( 'tax', 'question' );
						set_query_var( 'termid', $termid );						
						get_template_part( 'template-parts/content/taxonomy', 'relatedposts' );
					?>
					</footer>

			</article>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();