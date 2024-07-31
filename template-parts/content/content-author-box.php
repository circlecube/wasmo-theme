<?php
/**
* @var $user
*/
?>
<section class="author-box-section">
    <p><?php twentynineteen_get_icon_svg( 'person', 16 ); ?> This post is by <?php echo $user->display_name; ?>.</p>
    <hr />
    <div class="author-box">
        <div class="content-right">
            <?php if ( get_field( 'hi', 'user_' . $user->ID ) ) { ?>
                <h3 class="hi"><?php echo wp_kses_post( get_field( 'hi', 'user_' . $user->ID ) ); ?></h3>
            <?php } else { ?>
                <h3 class="hi">Hi, I'm <?php echo $user->user_login; ?></h3>
            <?php } ?>

            <?php if ( get_field( 'tagline', 'user_' . $user->ID ) ) { ?>
                <h4 class="tagline" itemprop="description"><?php echo wp_kses_post( get_field( 'tagline', 'user_' . $user->ID ) ); ?></h4>
            <?php } else { ?>
                <h4 class="tagline" itemprop="description">I was a mormon.</h4>
            <?php } ?>

            <?php if ( get_field( 'location', 'user_' . $user->ID ) ) { ?>
                <div class="location">
                    <?php
                        echo wasmo_get_icon_svg( 'location', 16 );
                        echo wp_kses_post( get_field( 'location', 'user_' . $user->ID ) );
                    ?>
                </div>
            <?php } ?>
        </div>
        <div class="content-left">
            <div class="user_photo"><?php echo wasmo_get_user_image( $user->ID, true ); ?></div>
            <?php 
            $links = get_field( 'links', 'user_' . $user->ID );
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
    <hr />
    <div class="wp-block-buttons is-content-justification-center is-layout-flex wp-container-core-buttons-is-layout-1 wp-block-buttons-is-layout-flex">
        <div class="wp-block-button">
            <a
                class="wp-block-button__link has-white-color has-primary-background-color has-text-color has-background wp-element-button"
                href="<?php echo get_author_posts_url( $user->ID ); ?>" 
            >Read My 'I was a Mormon' Story</a>
        </div>
        <div class="wp-block-button">
            <a
                class="wp-block-button__link has-white-color has-secondarty-background-color has-text-color has-background wp-element-button"
                href="/login/" 
            >Share Your Own Story</a>
        </div>
    </div>
</section>