<?php
/**
* @var $userid
*/
?>
<?php 
$curauth = (get_query_var('author_name')) ? get_user_by('slug', get_query_var('author_name')) : get_userdata(get_query_var('author'));
?>
<div class="content-header">

	<div class="content-right">
		<?php if ( get_field( 'hi', 'user_' . $userid ) ) { ?>
			<h1 class="hi"><?php echo wp_kses_post( get_field( 'hi', 'user_' . $userid ) ); ?></h1>
		<?php } else { ?>
			<h1 class="hi">Hi, I'm <?php echo $curauth->user_login; ?></h1>
		<?php } ?>

		<?php if ( get_field( 'tagline', 'user_' . $userid ) ) { ?>
			<h2 class="tagline"><?php echo wp_kses_post( get_field( 'tagline', 'user_' . $userid ) ); ?></h2>
		<?php } else { ?>
			<h2 class="tagline">I was a mormon.</h2>
		<?php } ?>

		<?php if ( get_field( 'location', 'user_' . $userid ) ) { ?>
			<div class="location"><?php echo wp_kses_post( get_field( 'location', 'user_' . $userid ) ); ?></div>
		<?php } ?>
	</div>

	<div class="content-left">
		<div class="user_photo"><?php 
		$userimg = get_field( 'photo', 'user_' . $userid );
		if ( $userimg ) {
			echo wp_get_attachment_image( $userimg, 'medium' );
		} else {
			$hash = md5( strtolower( trim( $curauth->user_email ) ) );
			$default_img = urlencode( 'https://raw.githubusercontent.com/circlecube/wasmo-theme/main/img/default.png' );
			$gravatar = $hash . '?s=300&d='.$default_img;
			echo '<img src="https://www.gravatar.com/avatar/' . $gravatar . '">';
		}
		?></div>
		<?php 
		$links = get_field( 'links', 'user_' . $userid );
		if ( $links ) { ?>
			<ul class="social-links">
			<?php if ( $links['facebook'] ) { 
				$svg = twentynineteen_get_social_link_svg( $links['facebook'], 26 );
			?>
				<li class="facebook"><a target="_blank" rel="noopener noreferrer" href="<?php 
					echo esc_url( $links['facebook'] ); 
				?>"><span class="screen-reader-text">Facebook</span><?php echo $svg; ?></a></li>
			<?php } ?>
			<?php if ( $links['instagram'] ) {
				$svg = twentynineteen_get_social_link_svg( $links['instagram'], 26 );
			?>
				<li class="instagram"><a target="_blank" rel="noopener noreferrer" href="<?php 
					echo esc_url( $links['instagram'] ); 
				?>"><span class="screen-reader-text">instagram</span><?php echo $svg; ?></a></li>
			<?php } ?>
			<?php if ( $links['reddit'] ) {
				$svg = twentynineteen_get_social_link_svg( $links['reddit'], 26 );
			?>
				<li class="reddit"><a target="_blank" rel="noopener noreferrer" href="<?php 
					echo esc_url( $links['reddit'] ); 
				?>"><span class="screen-reader-text">reddit</span><?php echo $svg; ?></a></li>
			<?php } ?>
			<?php if ( $links['twitter'] ) {
				$svg = twentynineteen_get_social_link_svg( $links['twitter'], 26 );
			?>
				<li class="twitter"><a target="_blank" rel="noopener noreferrer" href="<?php 
					echo esc_url( $links['twitter'] ); 
				?>"><span class="screen-reader-text">twitter</span><?php echo $svg; ?></a></li>
			<?php } ?>
			<?php if ( $links['other'] ) {
				$svg = twentynineteen_get_social_link_svg( $links['other'], 26 );
				if ( empty( $svg ) ) {
					$svg = twentynineteen_get_icon_svg( 'link' );
				}
			?>
				<li class="other"><a target="_blank" rel="noopener noreferrer" href="<?php 
					echo esc_url( $links['other'] );
				?>"><span class="screen-reader-text">other</span><?php echo $svg; ?></a></li>
			<?php } ?>
			</ul>
		<?php } ?>
	</div>

</div>

<?php if ( get_field( 'about_me', 'user_' . $userid ) ) { ?>
	<h3>About me</h3>
	<div class="about_me"><?php echo auto_link_text( wp_kses_post( get_field( 'about_me', 'user_' . $userid ) ) ); ?></div>
<?php } ?>

<?php 
$shelf_items = get_field( 'my_shelf', 'user_' . $userid );
if ( $shelf_items ) { ?>
	<h4>On my shelf</h4>
	<ul class="tags">
	<?php foreach( $shelf_items as $term ): ?>
		<!-- <li><span class="tag"><?php echo $term->name; ?></span></li> -->
		<li><a class="tag" href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a></li>
	<?php endforeach; ?>
	</ul>
<?php } 
?>

<?php 
$spectrum_terms = get_field( 'mormon_spectrum', 'user_' . $userid );
if ( $spectrum_terms ) { ?>
	<h4>On the Mormon Spectrum</h4>
	<ul class="tags">
	<?php foreach( $spectrum_terms as $term ): ?>
		<!-- <li><span class="tag"><?php echo $term->name; ?></span></li> -->
		<li><a class="tag" href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a></li>
	<?php endforeach; ?>
	</ul>
<?php } 
?>

<?php if ( get_field( 'why_i_left', 'user_' . $userid ) ) { ?>
	<h3>
		Why I left
		<a href="/why-i-left/" class="question_link_inline" title="More answers about 'Why I left' the mormon church">
			<?php echo twentynineteen_get_icon_svg( 'link', 20 ); ?>
			<span class="screen-reader-text">More answers about 'Why I left' the mormon church</span>
		</a>
	</h3>
	<div class="why_i_left"><?php echo auto_link_text( wp_kses_post( get_field( 'why_i_left', 'user_' . $userid ) ) ); ?></div>
<?php } ?>

<?php
//questions repeater
if( have_rows( 'questions', 'user_' . $userid ) ):
	?>

	<h3>Questions I've answered</h3>
	
	<?php
 	// loop through the rows of data
	while ( have_rows( 'questions', 'user_' . $userid ) ) : 
		the_row();
		$termtaxid = get_sub_field( 'question', 'users_' . $userid );
		$answer = get_sub_field( 'answer', 'users_' . $userid );
		if ( $termtaxid && $answer ) {
			$questionterm = get_term( $termtaxid, 'question' );
			$description = "More was mormon answers about '" . wp_kses_post( $questionterm->name ) . "'";
			echo '<h4 class="question">';
			echo wp_kses_post( $questionterm->name );
			echo ' <a href="' . get_term_link( $termtaxid, 'question' ) . '" class="question_link_inline" title="' . $description . '">';
			echo twentynineteen_get_icon_svg( 'link', 20 );
			echo '<span class="screen-reader-text">' . $description . '</span></a>';
			echo '</h4>';
			echo auto_link_text( wp_kses_post( $answer ) );
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
<div class="content-footer">
	
	<?php set_query_var( 'userid', $userid ); ?>
	<?php set_query_var( 'is_this_user', $is_this_user ); ?>
	<?php set_query_var( 'name', $curauth->user_login ); ?>
	<?php set_query_var( 'displayname', $curauth->user_login ); ?>
	<?php set_query_var( 'link', get_author_posts_url( $userid ) ); ?>
	<?php get_template_part( 'template-parts/content/content', 'user-spotlight' ); ?>
	<?php get_template_part( 'template-parts/content/content', 'user-posts' ); ?>
	<?php get_template_part( 'template-parts/content/content', 'socialshares' ); ?>
	
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
			<a class="wp-block-button__link wp-element-button" href="<?php echo home_url( '?randomprofile=1' ); ?>" style="border-radius:100px">Random Profile</a>
		</div>
	</div>

	<?php set_query_var( 'curauth', $curauth ); ?>
	<?php get_template_part( 'template-parts/content/content', 'user-meta' ); ?>

</div>