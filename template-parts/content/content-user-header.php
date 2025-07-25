<?php
/**
* @var $userid, $curauth
*/
?>
<div class="content-header">

	<div class="content-right">
		<?php if ( get_field( 'hi', 'user_' . $userid ) ) { ?>
			<h1 class="hi"><?php echo wp_kses_post( get_field( 'hi', 'user_' . $userid ) ); ?></h1>
		<?php } else { ?>
			<h1 class="hi">Hi, I'm <?php echo $curauth->user_login; ?></h1>
		<?php } ?>

		<?php if ( get_field( 'tagline', 'user_' . $userid ) ) { ?>
			<h2 class="tagline" itemprop="description"><?php echo wp_kses_post( get_field( 'tagline', 'user_' . $userid ) ); ?></h2>
		<?php } else { ?>
			<h2 class="tagline" itemprop="description">I was a mormon.</h2>
		<?php } ?>

		<?php if ( get_field( 'location', 'user_' . $userid ) ) { ?>
			<div class="location">
				<?php
					echo wasmo_get_icon_svg( 'location', 16 );
					echo wp_kses_post( get_field( 'location', 'user_' . $userid ) );
				?>
			</div>
		<?php } ?>
		<meta itemprop="identifier" content="<?php echo $userid; ?>" />
		<meta itemprop="name" id="real-name" content="<?php echo $curauth->display_name; ?>" />
		<meta itemprop="alternateName" id="handle" content="<?php echo get_query_var('author_name') ?>" />
		<meta itemprop="url" content="<?php echo get_author_posts_url( $userid ); ?>" />
		<meta itemprop="image" content="<?php echo wasmo_get_user_image_url( $userid ); ?>" />
		<meta itemprop="datePublished" content="<?php echo $curauth->user_registered; ?>" />
		<meta itemprop="dateModified" content="<?php echo esc_attr( date('Y-m-d H:i:s', intval( get_user_meta( $userid, 'last_save', true ) ) ) ); ?>" />
	</div>

	<div class="content-left">
		<div class="user_photo"><?php echo wasmo_get_user_image( $userid, true ); ?></div>
		<?php 
		$links = get_field( 'links', 'user_' . $userid );
		if ( $links ) { ?>
			<ul class="social-links">
			<?php if ( $links['facebook'] ) { 
				$svg = twentynineteen_get_social_link_svg( $links['facebook'], 26 );
			?>
				<li class="facebook"><a target="_blank" itemprop="sameAs" rel="nofollow ugc noopener noreferrer" href="<?php 
					echo esc_url( $links['facebook'] ); 
				?>"><span class="screen-reader-text">Facebook</span><?php echo $svg; ?></a></li>
			<?php } ?>
			<?php if ( $links['instagram'] ) {
				$svg = twentynineteen_get_social_link_svg( $links['instagram'], 26 );
			?>
				<li class="instagram"><a target="_blank" itemprop="sameAs" rel="nofollow ugc noopener noreferrer" href="<?php 
					echo esc_url( $links['instagram'] ); 
				?>"><span class="screen-reader-text">instagram</span><?php echo $svg; ?></a></li>
			<?php } ?>
			<?php if ( $links['reddit'] ) {
				$svg = twentynineteen_get_social_link_svg( $links['reddit'], 26 );
			?>
				<li class="reddit"><a target="_blank" itemprop="sameAs" rel="nofollow ugc noopener noreferrer" href="<?php 
					echo esc_url( $links['reddit'] ); 
				?>"><span class="screen-reader-text">reddit</span><?php echo $svg; ?></a></li>
			<?php } ?>
			<?php if ( $links['twitter'] ) {
				$svg = twentynineteen_get_social_link_svg( $links['twitter'], 26 );
			?>
				<li class="twitter"><a target="_blank" itemprop="sameAs" rel="nofollow ugc noopener noreferrer" href="<?php 
					echo esc_url( $links['twitter'] ); 
				?>"><span class="screen-reader-text">twitter</span><?php echo $svg; ?></a></li>
			<?php } ?>
			<?php if ( $links['other'] ) {
				$svg = twentynineteen_get_social_link_svg( $links['other'], 26 );
				if ( empty( $svg ) ) {
					$svg = wasmo_get_icon_svg( 'link' );
				}
			?>
				<li class="other"><a target="_blank" itemprop="sameAs" rel="ugc noopener noreferrer" href="<?php 
					echo esc_url( $links['other'] );
				?>"><span class="screen-reader-text">other</span><?php echo $svg; ?></a></li>
			<?php } ?>
			</ul>
		<?php } ?>
	</div>

</div>