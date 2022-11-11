<?php
/**
* @var $userid, $curauth
*/
?>
<?php
$spotlight_id = get_user_meta( $userid, 'spotlight_post', true );

if ( $spotlight_id ) { ?>
    <aside class="widget-area" style="margin: 1rem 0 -2.5rem; ">
        <section class="widget widget_posts_widget">
            <h4><a href="<?php echo get_permalink( $spotlight_id ); ?>">See the Spotlight on this profile</a></h4>
            <?php /* <ul class="recent_posts entry">
            $images = get_children( array (
                    'post_parent' => $post->ID,
                    'post_type' => 'attachment',
                    'post_mime_type' => 'image'
                ));

                if ( empty($images) ) {
                    // no attachments here
                } else {
                    foreach ( $images as $attachment_id => $attachment ) {
                        echo wp_get_attachment_image( $attachment_id, 'thumbnail' );
                    }
                }
                <li>
                    <figure class="post-thumbnail">
                        <a class="post-thumbnail-inner" href="<?php echo get_permalink( $spotlight_id ); ?>" aria-hidden="true" tabindex="-1">
                            <img width="300" height="300" src="<?php echo get_the_post_thumbnail_url( $spotlight_id ); ?>" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="<?php echo get_the_title( $spotlight_id ); ?> image" decoding="async" loading="lazy">
                        </a>
                    </figure>
                </li>
            </ul> */ ?>
        </section>
    </aside>
<?php } ?>