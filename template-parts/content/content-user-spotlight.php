<?php
/**
* @var $userid, $curauth
*/
?>
<?php
$spotlight_id = get_user_meta( $userid, 'spotlight_post', true );
$user = get_user_by('id', $userid);

if ( $spotlight_id && get_post_status( $spotlight_id ) === 'publish' ) :
    $images = get_children( array (
        'post_parent' => $spotlight_id,
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'order' => 'ASC',
        'orderby' => 'title',
    ));
    if ( !empty($images) ) : ?>
        <aside class="widget-area" style="margin: 1rem 0 -2.5rem; ">
            <section class="widget widget_spotlight_widget user-spotlight">
                <h4><a href="<?php echo get_permalink( $spotlight_id ); ?>">Spotlight on <?php echo esc_html( $user->display_name ); ?></a></h4>
                <!-- <img width="300" height="300" src="<?php echo get_the_post_thumbnail_url( $spotlight_id ); ?>" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="<?php echo get_the_title( $spotlight_id ); ?> image" decoding="async" loading="lazy"> -->
                <ul class="spotlight-thumbs entry">
                    <?php foreach ( $images as $attachment_id => $attachment ) : ?>
                        <li>
                            <figure class="spotlight-thumb">
                                <a href="<?php echo get_permalink( $spotlight_id ); ?>">
                                    <?php echo wp_get_attachment_image( $attachment_id, 'thumbnail' ); ?>
                                </a>
                            </figure>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </aside>
    <?php endif; ?>
<?php endif; ?>