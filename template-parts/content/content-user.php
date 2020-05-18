<?php
/**
* @var $userid
*/
?>

<div class="content-header">

	<div class="content-right">
		<?php if ( get_field( 'hi', 'user_' . $userid ) ) { ?>
			<h1 class="hi"><?php echo wp_kses_post( get_field( 'hi', 'user_' . $userid ) ); ?></h1>
		<?php } ?>

		<?php if ( get_field( 'tagline', 'user_' . $userid ) ) { ?>
			<h2 class="tagline"><?php echo wp_kses_post( get_field( 'tagline', 'user_' . $userid ) ); ?></h2>
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
	<div class="about_me"><?php echo wp_kses_post( get_field( 'about_me', 'user_' . $userid ) ); ?></div>
<?php } ?>

<?php 
$shelf_items = get_field( 'my_shelf', 'user_' . $userid );
if ( $shelf_items ) { ?>
	<h4>On my shelf</h4>
	<ul class="tags">
	<?php foreach( $shelf_items as $term ): ?>
		<li><span class="tag"><?php echo $term->name; ?></span></li>
		<!-- <li><a class="tag" href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a></li> -->
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
		<li><span class="tag"><?php echo $term->name; ?></span></li>
		<!-- <li><a class="tag" href="<?php echo get_term_link( $term ); ?>"><?php echo $term->name; ?></a></li> -->
	<?php endforeach; ?>
	</ul>
<?php } 
?>

<?php if ( get_field( 'why_i_left', 'user_' . $userid ) ) { ?>
	<h3>Why I left</h3>
	<div class="why_i_left"><?php echo wp_kses_post( get_field( 'why_i_left', 'user_' . $userid ) ); ?></div>
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
		if ( $termtaxid ) {
			$questionterm = get_term( $termtaxid, 'question' );
			echo '<h4 class="question">';
			echo wp_kses_post( $questionterm->name );
			echo ' <a href="' . get_term_link( $termtaxid, 'question' ) . '" class="question_link_inline">' . twentynineteen_get_icon_svg( 'link', 20 ) . '</a>';
			echo '</h4>';
			echo wp_kses_post( get_sub_field( 'answer', 'users_' . $userid ) );
		}
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
		$last_login = intval( get_user_meta( $userid, 'last_login', true ) );
		$last_login_rel = human_time_diff( $last_login );
		$last_save = intval( get_user_meta( $userid, 'last_save', true ) );
		$last_save_rel = human_time_diff( $last_save );
		$save_count = intval( get_user_meta( $userid, 'save_count', true ) );
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
	<span class="user-meta" 
		data-key="last-save" 
		data-value="<?php echo esc_attr( $last_save ); ?>"
		data-relval="<?php echo esc_attr( $last_save_rel ); ?>">
	</span>
	<span class="user-meta" 
		data-key="save-count" 
		data-value="<?php echo esc_attr( $save_count ); ?>">
	</span>


	<div class="buttons">
		<?php if ( 
				is_user_logged_in() &&
				$userid === get_current_user_id() 
			) { ?>
			<span class="edit-link">
				<a href="<?php echo home_url( '/edit/' ); ?>">Edit Your Profile</a>
			</span>
		<?php } ?>
		<span class="wp-block-button is-style-outline">
			<a class="wp-block-button__link" href="<?php echo home_url( '/directory/' ); ?>">Back to the Directory</a>
		</span>
		<span class="wp-block-button">
			<a class="wp-block-button__link" href="<?php echo home_url( '/login/' ); ?>">Contribute your own story</a>
		</span>
	</div>
</div>