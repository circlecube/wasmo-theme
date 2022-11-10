<?php
/**
 * Template part for displaying social media share links
 *
 */

 // is it the current user? if so update the messaging about their own profile.
$user_id = get_query_var( 'user_id' );
$is_this_user = get_query_var( 'is_this_user' );
$name = get_query_var( 'name' );
$link = get_query_var( 'link' );

$_facebook = 'https://www.facebook.com/sharer.php?u={link}&t=Read this wasmormon profile from {name}';
$_tweet    = 'https://twitter.com/intent/tweet?via=wasmormon&text=Great wasmormon profile, {name}!&url={link}';
$_toot     = 'Great wasmormon profile, {name}!&url={link} @wasmormon@mas.to';
$_reddit   = 'https://www.reddit.com/submit?url={link}&title=Read this wasmormon profile from {name}';
$_email    = 'mailto:?subject=Read this wasmormon profile from {name}&body=Read this wasmormon profile from {name}: {link}';

// links for when it is users own profile
if ( $is_this_user ) {

$_facebook = 'https://www.facebook.com/sharer.php?u={link}&t=Read my wasmormon profile';
$_tweet    = 'https://twitter.com/intent/tweet?via=wasmormon&text=Read my wasmormon profile!&url={link}';
$_toot     = 'Check out my wasmormon profile, {name}!&url={link} @wasmormon@mas.to';
$_reddit   = 'https://www.reddit.com/submit?url={link}&title=Read my wasmormon profile';
$_email    = 'mailto:?subject=Read my wasmormon profile&body=Read my wasmormon profile ({name}): {link}';

}

// find and replace placeholders with content in each link
$_facebook = str_replace(['{link}', '{name}'], [$link, $name], $_facebook );
$_tweet    = str_replace(['{link}', '{name}'], [$link, $name], $_tweet );
$_toot     = str_replace(['{link}', '{name}'], [$link, $name], $_toot );
$_reddit   = str_replace(['{link}', '{name}'], [$link, $name], $_reddit );
$_email    = str_replace(['{link}', '{name}'], [$link, $name], $_email );

?>

<ul class="social-links social-share-links">

    <li>
        <h4 style="margin: 0 0 0.5rem;">
            <?php if ( $is_this_user ) { ?>
                Share your profile
            <?php } else { ?>
                Share this profile
            <?php } ?>
        </h4>
    </li>

    <li class="facebook">
        <a
            target="_blank"
            rel="noopener noreferrer" 
            href="<?php echo esc_url( $_facebook ); ?>"
        >
            <span class="screen-reader-text">Share on Facebook</span>
            <?php echo twentynineteen_get_social_link_svg( 'facebook.com', 36 ); ?>
        </a>
    </li>
    
    <li class="twitter">
        <a
            target="_blank"
            rel="noopener noreferrer"
            href="<?php echo esc_url( $_tweet ); ?>"
        >
            <span class="screen-reader-text">Share on twitter</span>
            <?php echo twentynineteen_get_social_link_svg( 'twitter.com', 36 ); ?>
        </a>
    </li>
    
    <li class="reddit">
        <a
            target="_blank"
            rel="noopener noreferrer"
            href="<?php echo esc_url( $_reddit ); ?>"
        >
            <span class="screen-reader-text">Share on reddit</span>
            <?php echo twentynineteen_get_social_link_svg( 'reddit.com', 36 ); ?>
        </a>
    </li>
    
    <li class="mail">
        <a
            target="_blank"
            rel="noopener noreferrer"
            href="<?php echo esc_url( $_email ); ?>"
        >
            <span class="screen-reader-text">Share via email</span>
            <?php echo twentynineteen_get_social_link_svg( 'mailto:', 36 ); ?>
        </a>
    </li>
    
    <!-- <script src="https://unpkg.com/mastodon-share-button@latest/dist/mastodon-share-button.js"></script>
    <li class="mastodon">
        <mastodon-share-button
            instances='["https://mas.to", "https://mastodon.social"]'
            share_message="<?php esc_attr( $_toot ); ?>"
            share_button_text=""
            icon_url="https://upload.wikimedia.org/wikipedia/commons/4/48/Mastodon_Logotype_%28Simple%29.svg"
            class="mastodon-share"
        >
        </mastodon-share-button>
    </li> -->

</ul>