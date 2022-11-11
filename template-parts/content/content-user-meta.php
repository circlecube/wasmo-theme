<?php
/**
* @var $userid, $curauth
*/
?>
<?php
// display footer data in admin user
if ( current_user_can( 'manage_options' ) ) {
?>
<div class="profile-data" style="margin-top: 2rem;">
    <h4>Profile Data</h4>
    <dl>
    <?php 
        $registered = $curauth->user_registered;
        $registered_rel = human_time_diff( strtotime( $registered ) );
        $last_login = get_user_meta( $userid, 'last_login', true );
        $last_login_rel = human_time_diff( intval( $last_login ) );
        $last_save = intval( get_user_meta( $userid, 'last_save', true ) );
        $last_save_rel = human_time_diff( $last_save );
        $save_count = intval( get_user_meta( $userid, 'save_count', true ) );
        $in_directory = get_user_meta( $userid, 'in_directory', true );
        $i_want_to_write_posts = get_user_meta( $userid, 'i_want_to_write_posts', true );
    ?>
    <span class="user-meta" 
        data-key="member-since" 
        data-value="<?php echo esc_attr( strtotime( $registered ) ); ?>" 
        data-relval="<?php echo esc_attr( $registered_rel ); ?>"
        title="<?php echo esc_attr( $registered ); ?>"
    >
        <dt>Member since</dt>
        <dd><?php echo esc_attr( $registered_rel ); ?></dd>
    </span>
    <?php if ( $last_login ) { ?>
    <span class="user-meta" 
        data-key="last-login" 
        data-value="<?php echo esc_attr( $last_login ); ?>"
        data-relval="<?php echo esc_attr( $last_login_rel ); ?>"
        title="<?php echo esc_attr( date('Y-m-d H:i:s', $last_login ) ); ?>"
    >
        <dt>Last Login</dt>
        <dd><?php echo esc_attr( $last_login_rel ); ?></dd>
    </span>
    <?php } ?>
    <?php if ( $last_save ) { ?>
    <span class="user-meta" 
        data-key="last-save" 
        data-value="<?php echo esc_attr( $last_save ); ?>"
        data-relval="<?php echo esc_attr( $last_save_rel ); ?>"
        title="<?php echo esc_attr( date('Y-m-d H:i:s', $last_save ) ); ?>"
    >
        <dt>Last save</dt>
        <dd><?php echo esc_attr( $last_save_rel ); ?></dd>
    </span>
    <?php } ?>
    <?php if ( $save_count ) { ?>
    <span class="user-meta" 
        data-key="save-count" 
        data-value="<?php echo esc_attr( $save_count ); ?>"
    >
        <dt>Saves</dt>
        <dd><?php echo esc_attr( $save_count ); ?></dd>
    </span>
    <?php } ?>
    <?php if ( $in_directory ) { ?>
    <span class="user-meta"
        data-key="in_directory"
        data-value="<?php echo esc_attr( $in_directory ); ?>"
    >
        <dt>In Directory?</dt>
        <dd><?php echo $in_directory; ?></dd>
    </span>
    <?php } ?>
    <?php if ( $i_want_to_write_posts ) { ?>
    <span class="user-meta"
        data-key="i_want_to_write_posts"
        data-value="<?php echo esc_attr( $i_want_to_write_posts ); ?>"
    >
        <dt>I want to write posts?</dt>
        <dd><?php echo $i_want_to_write_posts; ?></dd>
    </span>
    <?php } ?>
    <span class="user-meta"
        data-key="edit"
        data-value="<?php echo $curauth->user_login; ?>"
    >
        <dt>Edit Profile</dt>
        <dd>
            <a 
                href="<?php echo esc_url( get_edit_user_link( $userid ) ); ?>"
                target="_blank"
            ><?php echo $curauth->user_login; ?></a>
        </dd>
    </span>
    </dl>
</div>
<?php } // end admin check ?>