<?php
/**
* @var $userid
*/
?>
<?php 
$curauth = (get_query_var('author_name')) ? get_user_by('slug', get_query_var('author_name')) : get_userdata(get_query_var('author'));
?>

<?php set_query_var( 'curauth', $curauth ); ?>
<?php set_query_var( 'userid', $userid ); ?>
<?php get_template_part( 'template-parts/content/content', 'user-header' ); ?>

<?php if ( get_field( 'about_me', 'user_' . $userid ) ) { ?>
	<div class="profile-section" id="about-me">
		<h3>About me</h3>
		<div class="about_me"><?php 
			echo wasmo_auto_htmlize_text(
				wasmo_auto_link_text( 
					wp_kses_post( 
						get_field( 'about_me', 'user_' . $userid )
					)
				)
			); 
		?></div>
		<?php // wasmo_render_reaction_buttons( $userid, 'about_me' ); ?>
	</div>
<?php } ?>

<?php if ( get_field( 'video', 'user_' . $userid ) ) { ?>
	<div class="profile-video"><?php 
		// Load value.
		$iframe = get_field('video', 'user_' . $userid );

		// Use preg_match to find iframe src.
		preg_match('/src="(.+?)"/', $iframe, $matches);
		$src = $matches[1];

		// Add extra parameters to src and replace HTML.
		$params = array(
			'controls'  => 0,
			'hd'        => 1,
			'autohide'  => 1,
			'autoplay'  => 0,
			'loop'      => 0,
			'rel'       => 0,
		);
		$new_src = add_query_arg($params, $src);
		$iframe = str_replace($src, $new_src, $iframe);

		// Add extra attributes to iframe HTML.
		$attributes = 'frameborder="0"';
		$iframe = str_replace('></iframe>', ' ' . $attributes . '></iframe>', $iframe);

		// Display customized HTML.
		echo $iframe;
	?></div>
<?php } ?>
<div class="profile-section content-full-width" id="my-shelf">
<?php 
$shelf_items = get_field( 'my_shelf', 'user_' . $userid );
if ( $shelf_items ) { ?>
	<h4>	
		<?php echo wasmo_get_icon_svg( 'shelf', 20 ); ?>
		On my shelf
	</h4>
	<ul class="tags">
	<?php foreach( $shelf_items as $term ): ?>
		<!-- <li><span class="tag"><?php echo $term->name; ?></span></li> -->
		<li><a class="tag" href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a></li>
	<?php endforeach; ?>
	</ul>
<?php } 
?>
</div>
<div class="profile-section content-full-width" id="mormon-spectrum">
<?php 
$spectrum_terms = get_field( 'mormon_spectrum', 'user_' . $userid );
if ( $spectrum_terms ) { ?>
	<h4>
		<?php echo wasmo_get_icon_svg( 'spectrum', 20 ); ?>
		On the Mormon Spectrum
	</h4>
	<ul class="tags">
	<?php foreach( $spectrum_terms as $term ): ?>
		<!-- <li><span class="tag"><?php echo $term->name; ?></span></li> -->
		<li><a class="tag" href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a></li>
	<?php endforeach; ?>
	</ul>
<?php } 
?>
</div>
<?php if ( get_field( 'why_i_left', 'user_' . $userid ) ) { ?>
	<?php 
		$anchor_desc = "Link to 'Why I left the Mormon church' by " . wp_kses_post( $curauth->display_name );
		$more_desc   = "More stories of 'Why I left' the Mormon church";
	?>
	<div id="why-i-left" class="profile-section">
		<h3>
			<a href="#why-i-left" class="question_link_inline question_anchor" title="<?php echo esc_attr( $anchor_desc ); ?>">
				<sup>#</sup>
				<span class="screen-reader-text"><?php esc_html($anchor_desc); ?></span>
			</a>
			Why I left
			<a href="/why-i-left/" class="question_link_inline" title="<?php echo esc_attr( $more_desc ); ?>">
				<?php echo wasmo_get_icon_svg( 'link', 20 ); ?>
				<span class="screen-reader-text"><?php echo esc_html( $more_desc ); ?></span>
			</a>
		</h3>
		<div class="why_i_left">
			<?php
				echo wasmo_auto_htmlize_text(
					wasmo_auto_link_text( 
						wp_kses_post( 
							get_field( 'why_i_left', 'user_' . $userid )
						)
					)
				);
			?>
		</div>
		<?php // wasmo_render_reaction_buttons( $userid, 'why_i_left' ); ?>
	</div>
<?php } ?>

<?php
//questions repeater
if( have_rows( 'questions', 'user_' . $userid ) ):
	$description = "Questions about Mormons";
	?>

	<h3>
		<a 
			href="/questions/"
			class="question_link_inline"
			title="<?php echo $description; ?>"
		><?php echo wasmo_get_icon_svg( 'question', 26 );
		?><span class="screen-reader-text"><?php echo $description; ?></span></a>
		My Answers to Questions about Mormonism
	</h3>
	
	<?php
 	// loop through the rows of data
	while ( have_rows( 'questions', 'user_' . $userid ) ) : 
		the_row();
		$termtaxid = get_sub_field( 'question', 'users_' . $userid );
		$answer = get_sub_field( 'answer', 'users_' . $userid );
		if ( $termtaxid && $answer ) {
			$questionterm = get_term( $termtaxid, 'question' );
			$anchor = "Link to this answer of '" . wp_kses_post( $questionterm->name ) . "' by " . $curauth->display_name;
			$description = "See more answers about '" . wp_kses_post( $questionterm->name ) . "'";
			echo '<div class="profile-section question-section" id="' . esc_attr( $questionterm->slug ) . '">';
			echo '<h4 class="question">';
			echo '<a href="#' . esc_attr( $questionterm->slug ) . '" class="question_link_inline question_anchor" title="' . $anchor . '">';
			echo '<sup>#</sup><span class="screen-reader-text">' . $anchor . '</span></a> ';
			echo wp_kses_post( $questionterm->name );
			echo ' <a href="' . get_term_link( $termtaxid, 'question' ) . '" class="question_link_inline" title="' . $description . '">';
			echo wasmo_get_icon_svg( 'link', 20 );
			echo '<span class="screen-reader-text">' . $description . '</span></a>';
			echo '</h4>';
			echo '<div class="answer">';
			echo wasmo_auto_htmlize_text( wasmo_auto_link_text( wp_kses_post( $answer ) ) );
			echo '</div>';
			wasmo_render_reaction_buttons( $userid, 'question_' . $questionterm->slug );
			echo '</div>';
		}
    endwhile;

else :
    // no questions found
endif;

$is_this_user = false;
if ( 
	is_user_logged_in() &&
	$userid === get_current_user_id() 
) {
	$is_this_user = true;
}
?>

<?php 
// Profile comments section
set_query_var( 'userid', $userid );
get_template_part( 'template-parts/content/content', 'user-comments' ); 
?>

<div class="content-footer">
	
	<?php set_query_var( 'userid', $userid ); ?>
	<?php set_query_var( 'is_this_user', $is_this_user ); ?>
	<?php set_query_var( 'name', $curauth->user_login ); ?>
	<?php set_query_var( 'link', get_author_posts_url( $userid ) ); ?>
	<?php get_template_part( 'template-parts/content/content', 'user-spotlight' ); ?>
	<?php get_template_part( 'template-parts/content/content', 'user-posts' ); ?>
	<?php get_template_part( 'template-parts/content/content', 'user-attribution' ); ?>
	<?php get_template_part( 'template-parts/content/content', 'socialshares' ); ?>
	
	<!-- <p>
		Joined <?php echo human_time_diff( strtotime( $curauth->user_registered ) ); ?> ago.<br />
		Last updated <?php echo human_time_diff( get_user_meta( $userid, 'last_save', true ) ); ?> ago.
	</p> -->

	<div class="is-layout-flex wp-block-buttons">
		<div class="wp-block-button has-custom-font-size" style="font-size:20px">
			<?php if ( is_user_logged_in() ) { ?>
				<a class="wp-block-button__link wp-element-button" style="border-radius:100px" href="<?php echo home_url( '/edit/' ); ?>">
					Edit Your <?php echo $is_this_user ? '' : 'Own'; ?> Profile
				</a>
			<?php } else { ?>
				<a class="wp-block-button__link wp-element-button" style="border-radius:100px" href="<?php echo home_url( '/login/' ); ?>">
					Contribute your own story
				</a>
			<?php } ?>
		</div>
	</div>

	<div class="is-layout-flex wp-block-buttons">
		<div class="wp-block-button has-custom-font-size is-style-outline" style="font-size:20px">
			<a class="wp-block-button__link wp-element-button" href="<?php echo home_url( '/profiles/' ); ?>" style="border-radius:100px">Back to the Directory</a>
		</div>
		<div class="wp-block-button has-custom-font-size is-style-outline" style="font-size:20px">
			<a class="wp-block-button__link wp-element-button" href="<?php echo wasmo_get_random_profile_url(); ?>" style="border-radius:100px">Random Profile</a>
		</div>
	</div>

	<?php set_query_var( 'curauth', $curauth ); ?>
	<?php get_template_part( 'template-parts/content/content', 'user-meta' ); ?>

</div>