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
					<h1 class="entry-title"><?php echo wp_kses_post( $term->name ); ?></h1>
				</header><!-- .page-header -->

<?php

//define transient name - taxid + user state.
$transient_name = 'answers-tax-question-' . $termid . '-' . is_user_logged_in();
if ( current_user_can('administrator') && WP_DEBUG ) {
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

				//check if they answered this question
				if ( $termtaxid === $termid && '' != get_sub_field( 'answer', 'user_' . $userid ) ) {

					// answer
					$the_answers .= '<div class="answer answer-' . $userid . '">';
					$the_answers .= '<blockquote>';
					$the_answers .= wp_kses_post( get_sub_field( 'answer', 'user_' . $userid ) );
					$the_answers .= '</blockquote>';

					// user attribution - photo and name and link (only if they want to be listed in directory)
					$in_directory = get_field( 'in_directory', 'user_' . $userid );
					if ( 
						'true' === $in_directory ||
						'website' === $in_directory ||
						( 'private' === $in_directory && is_user_logged_in() )
					) {
						
						$userimg = get_field( 'photo', 'user_' . $userid );
						$username = esc_html( $user->nickname );

						$the_answers .= '<cite>';
						$the_answers .= '<a class="person person-' . esc_attr( $userid ) . '" href="' . get_author_posts_url( $userid ) . '">';
						$the_answers .= '<span class="directory-img">';
						if ( $userimg ) {
							$the_answers .= wp_get_attachment_image( $userimg, 'medium' );
						} else {
							$the_answers .= '<img src="' . get_stylesheet_directory_uri() . '/img/default.svg">';
						}
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
					</div>

					<footer class="entry-footer">
					</footer>

			</article>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();