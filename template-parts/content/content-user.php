<?php
/**
* @var $userid
*/
?>

<div class="content-header">
	<div class="content-left">
		<div class="user_photo"><?php 
		$userimg = get_field( 'photo', 'user_' . $userid );
		if ( $userimg ) {
			echo wp_get_attachment_image( $userimg, 'medium' );
		} else {
			echo '<img src="' . get_stylesheet_directory_uri() . '/img/default.svg">';
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

	<div class="content-right">
		<?php if ( get_field( 'hi', 'user_' . $userid ) ) { ?>
			<h1 class="hi"><?php echo esc_textarea( get_field( 'hi', 'user_' . $userid ) ); ?></h1>
		<?php } ?>

		<?php if ( get_field( 'tagline', 'user_' . $userid ) ) { ?>
			<h2 class="tagline"><?php echo esc_textarea( get_field( 'tagline', 'user_' . $userid ) ); ?></h2>
		<?php } ?>
	</div>
</div>

<?php if ( get_field( 'about_me', 'user_' . $userid ) ) { ?>
	<h3>About me</h3>
	<div class="about_me"><?php echo esc_textarea( get_field( 'about_me', 'user_' . $userid ) ); ?></div>
<?php } ?>

<?php if ( get_field( 'location', 'user_' . $userid ) ) { ?>
	<div class="location"><?php echo esc_textarea( get_field( 'location', 'user_' . $userid ) ); ?></div>
<?php } ?>


<?php if ( get_field( 'why_i_left', 'user_' . $userid ) ) { ?>
	<h3>Why I left</h3>
	<div class="why_i_left"><?php echo esc_textarea( get_field( 'why_i_left', 'user_' . $userid ) ); ?></div>
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

		echo '<h4 class="question">';
		$termtaxid = get_sub_field( 'question', 'users_' . $userid );
		$questionterm = get_term( $termtaxid, 'question' );
		echo esc_textarea( $questionterm->name );
		echo '</h4>';
		echo esc_textarea( get_sub_field( 'answer', 'users_' . $userid ) );

    endwhile;

else :
    // no questions found
endif;
?>
<div class="content-footer">
	<?php 
		$curauth = (get_query_var('author_name')) ? get_user_by('slug', get_query_var('author_name')) : get_userdata(get_query_var('author'));
		$registered = $curauth->user_registered;
		$registered_rel = human_time_diff( strtotime( $registered ) );
		$last_login = get_user_meta( $userid, 'last_login', true );
		$last_login_rel = human_time_diff( $last_login );
	?>
	<span class="user-meta" 
		data-key="member-since" 
		data-value="<?php echo esc_attr( strtotime( $registered ) ); ?>" 
		data-relval="<?php echo esc_attr( $registered_rel ); ?>">
	</span>
	<span class="user-meta" 
		data-key="last-login" 
		data-value="<?php echo esc_attr( $last_login ); ?>"
		data-relval="<?php echo esc_attr( $last_login_rel ); ?>">
	</span>
</div>