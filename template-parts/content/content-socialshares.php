<?php
/**
 * Template part for displaying social media share links
 *
 */

 // is it the current user? if so update the messaging about their own profile.
$userid = get_query_var( 'userid' );
$is_this_user = get_query_var( 'is_this_user' );
$name = get_query_var( 'name' );
$link = get_query_var( 'link' );

// true = public
// private = only to a logged in user
// website = show on web but not on social
// false = don't show anywhere
$in_directory = get_field( 'in_directory', 'user_' . $userid );

// if user indicates they don't want to be on social or in the directory, bail now
if ( $in_directory === 'website' || $in_directory === false ) {
    return;
}

$_facebook = 'https://www.facebook.com/sharer.php?u={link}&t=Read this wasmormon profile from {name}';
$_tweet    = 'https://twitter.com/intent/tweet?via=wasmormon&text=Great wasmormon profile, {name}!&url={link}';
// $_toot     = 'Great wasmormon profile, {name}!&url={link} @wasmormon@mas.to';
$_reddit   = 'https://www.reddit.com/submit?url={link}&title=Read this wasmormon profile from {name}';
$_email    = 'mailto:?subject=Read this wasmormon profile from {name}&body=Read this wasmormon profile from {name}: {link}';
$_email2   = '';

// links for when it is users own profile
if ( $is_this_user ) {

$_facebook = 'https://www.facebook.com/sharer.php?u={link}&t=Read my wasmormon profile';
$_tweet    = 'https://twitter.com/intent/tweet?via=wasmormon&text=Read my wasmormon profile!&url={link}';
// $_toot     = 'Check out my wasmormon profile, {name}!&url={link} @wasmormon@mas.to';
$_reddit   = 'https://www.reddit.com/submit?url={link}&title=Read my wasmormon profile';
$_email    = 'mailto:?subject=Read my wasmormon profile&body=Read my wasmormon profile ({name}): {link}';

}

// find and replace placeholders with content in each link
$_facebook = str_replace(['{link}', '{name}'], [$link, $name], $_facebook );
$_tweet    = str_replace(['{link}', '{name}'], [$link, $name], $_tweet );
// $_toot     = str_replace(['{link}', '{name}'], [$link, $name], $_toot );
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
            href="<?php echo esc_url( $_facebook ); ?>"
            rel="noopener noreferrer"
            target="_blank"
            title="Share a link on facebook" 
        >
            <span class="screen-reader-text">Share link on Facebook</span>
            <?php echo twentynineteen_get_social_link_svg( 'facebook.com', 36 ); ?>
        </a>
    </li>
    
    <li class="twitter">
        <a
            href="<?php echo esc_url( $_tweet ); ?>"
            rel="noopener noreferrer"
            target="_blank"
            title="Share a link on twitter"
        >
            <span class="screen-reader-text">Share link on twitter</span>
            <?php echo twentynineteen_get_social_link_svg( 'twitter.com', 36 ); ?>
        </a>
    </li>
    
    <li class="reddit">
        <a
            href="<?php echo esc_url( $_reddit ); ?>"
            rel="noopener noreferrer"
            target="_blank"
            title="Share link on reddit"
        >
            <span class="screen-reader-text">Share link on reddit</span>
            <?php echo twentynineteen_get_social_link_svg( 'reddit.com', 36 ); ?>
        </a>
    </li>
    
    <li class="mail">
        <a
            href="<?php echo esc_url( $_email ); ?>"
            rel="noopener noreferrer"
            target="_blank"
            title="Share link via email"
        >
            <span class="screen-reader-text">Share link via email</span>
            <?php echo twentynineteen_get_social_link_svg( 'mailto:', 36 ); ?>
        </a>
    </li>

</ul>