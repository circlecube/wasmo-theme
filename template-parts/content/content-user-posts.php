<?php
/**
* @var $userid, $curauth
*/
?>
<?php
$args = array(
    'author'        =>  $userid, 
    'orderby'       =>  'post_date',
    'order'         =>  'ASC',
    'posts_per_page' => -1 // no limit
);
$userposts = get_posts( $args );
$user = get_user_by('id', $userid);

// if has posts and is not admin
if ( $userposts && !$user->has_cap( 'manage_options' ) ) { ?>
    <aside class="widget-area">
        <section class="widget widget_posts_widget">
            <?php 
                
            ?>
            <h4>Posts by <?php echo esc_html( $user->display_name ); ?></h4>
            <ul class="user_recent_posts entry">
                <?php foreach ( $userposts as $post ) : ?>
                    <?php setup_postdata( $post ); ?>
                    <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                    <?php //echo wp_oembed_get( get_the_permalink() ); ?>
                <?php endforeach; ?>
                <?php wp_reset_postdata(); ?>
            </ul>
        </section>
    </aside>
<?php } ?>