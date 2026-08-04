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

$_facebook = 'https://www.facebook.com/sharer.php?u={link}&t=Read {name}\'s Mormon faith journey story on wasmormon.org';
$_tweet    = 'https://twitter.com/intent/tweet?via=wasmormon&text={name} shares their Mormon faith transition story on wasmormon.org&url={link}';
// $_toot     = '{name} shares their Mormon faith transition story on wasmormon.org {link} @wasmormon@mas.to';
$_reddit   = 'https://www.reddit.com/submit?url={link}&title={name}\'s Mormon faith journey story - wasmormon.org';
$_email    = 'mailto:?subject=Mormon faith journey story from {name}&body=Check out {name}\'s Mormon faith journey story on wasmormon.org: {link}';

// links for when it is users own profile
if ( $is_this_user ) {

$_facebook = 'https://www.facebook.com/sharer.php?u={link}&t=My Mormon faith journey story on wasmormon.org';
$_tweet    = 'https://twitter.com/intent/tweet?via=wasmormon&text=I shared my Mormon faith transition story on wasmormon.org&url={link}';
// $_toot     = 'I shared my Mormon faith transition story on wasmormon.org {link} @wasmormon@mas.to';
$_reddit   = 'https://www.reddit.com/submit?url={link}&title=My Mormon faith journey story - wasmormon.org';
$_email    = 'mailto:?subject=My Mormon faith journey story&body=I shared my Mormon faith journey story on wasmormon.org: {link}';

}

// find and replace placeholders with content in each link
$_facebook = str_replace(['{link}', '{name}'], [$link, $name], $_facebook );
$_tweet    = str_replace(['{link}', '{name}'], [$link, $name], $_tweet );
// $_toot     = str_replace(['{link}', '{name}'], [$link, $name], $_toot );
$_reddit   = str_replace(['{link}', '{name}'], [$link, $name], $_reddit );
$_email    = str_replace(['{link}', '{name}'], [$link, $name], $_email );

?>

<ul id="story-share" class="social-links social-share-links">

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