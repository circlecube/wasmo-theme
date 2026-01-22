<?php
/**
 * Profile Interactions: Comments and Reactions
 *
 * Handles user profile comments (via shadow post pattern) and reactions (custom table).
 *
 * @package Wasmo_Theme
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Shadow Post CPT for Profile Comments
|--------------------------------------------------------------------------
*/

/**
 * Register the profile_comment custom post type (shadow posts for comments)
 */
function wasmo_register_profile_comment_cpt() {
    register_post_type( 'profile_comment', array(
        'labels' => array(
            'name'          => __( 'Profile Comments', 'wasmo-theme' ),
            'singular_name' => __( 'Profile Comment', 'wasmo-theme' ),
        ),
        'public'              => false,
        'show_ui'             => false,
        'show_in_menu'        => false,
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'supports'            => array( 'comments' ),
        'has_archive'         => false,
        'rewrite'             => false,
        'query_var'           => false,
    ) );
}
add_action( 'init', 'wasmo_register_profile_comment_cpt' );

/**
 * Get or create the shadow post for a user's profile comments
 *
 * @param int $user_id The user ID
 * @return int|false The post ID or false on failure
 */
function wasmo_get_profile_comment_post( $user_id ) {
    $user_id = absint( $user_id );
    if ( ! $user_id ) {
        return false;
    }

    // Check for existing shadow post
    $existing = get_posts( array(
        'post_type'      => 'profile_comment',
        'meta_key'       => '_profile_user_id',
        'meta_value'     => $user_id,
        'posts_per_page' => 1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    ) );

    if ( ! empty( $existing ) ) {
        return $existing[0];
    }

    // Create new shadow post
    $user = get_user_by( 'ID', $user_id );
    if ( ! $user ) {
        return false;
    }

    $post_id = wp_insert_post( array(
        'post_type'    => 'profile_comment',
        'post_status'  => 'publish',
        'post_title'   => sprintf( 'Profile Comments: %s', $user->display_name ),
        'post_author'  => $user_id,
        'post_content' => '',
    ) );

    if ( is_wp_error( $post_id ) ) {
        return false;
    }

    update_post_meta( $post_id, '_profile_user_id', $user_id );
    return $post_id;
}

/**
 * Get the user ID from a profile_comment post
 *
 * @param int $post_id The post ID
 * @return int|false The user ID or false
 */
function wasmo_get_user_from_comment_post( $post_id ) {
    $user_id = get_post_meta( $post_id, '_profile_user_id', true );
    return $user_id ? absint( $user_id ) : false;
}

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
*/

/**
 * Check if a user allows contact/interactions on their profile
 *
 * @param int $user_id The profile owner's user ID
 * @return bool
 */
function wasmo_user_allows_contact( $user_id ) {
    $allow_contact = get_field( 'allow_user_contact', 'user_' . $user_id );
    return ( $allow_contact === true || $allow_contact === 'yes' || $allow_contact === '1' || $allow_contact === 1 );
}

/**
 * Filter to allow profile owners to moderate comments on their profile
 */
function wasmo_profile_owner_can_moderate( $allcaps, $caps, $args, $user ) {
    // Only process edit_comment and moderate_comments capabilities
    if ( empty( $args[0] ) || ! in_array( $args[0], array( 'edit_comment', 'moderate_comments' ), true ) ) {
        return $allcaps;
    }

    // Need a comment ID for edit_comment
    if ( $args[0] === 'edit_comment' && ! empty( $args[2] ) ) {
        $comment = get_comment( $args[2] );
        if ( ! $comment ) {
            return $allcaps;
        }

        $post = get_post( $comment->comment_post_ID );
        if ( ! $post || $post->post_type !== 'profile_comment' ) {
            return $allcaps;
        }

        // Check if current user owns this profile
        $profile_user_id = wasmo_get_user_from_comment_post( $post->ID );
        if ( $profile_user_id && $profile_user_id === $user->ID ) {
            $allcaps['edit_comment'] = true;
            $allcaps['moderate_comments'] = true;
        }
    }

    return $allcaps;
}
add_filter( 'user_has_cap', 'wasmo_profile_owner_can_moderate', 10, 4 );

/**
 * Filter to allow comments on profile_comment posts
 */
function wasmo_allow_profile_comments( $open, $post_id ) {
    $post = get_post( $post_id );
    if ( $post && $post->post_type === 'profile_comment' ) {
        $profile_user_id = wasmo_get_user_from_comment_post( $post_id );
        if ( $profile_user_id && wasmo_user_allows_contact( $profile_user_id ) ) {
            return true;
        }
    }
    return $open;
}
add_filter( 'comments_open', 'wasmo_allow_profile_comments', 10, 2 );

/*
|--------------------------------------------------------------------------
| Reactions Table Creation
|--------------------------------------------------------------------------
*/

/**
 * Create the profile reactions custom table
 */
function wasmo_create_reactions_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'profile_reactions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        profile_user_id bigint(20) unsigned NOT NULL,
        reactor_user_id bigint(20) unsigned NOT NULL,
        section varchar(100) NOT NULL,
        reaction_type varchar(50) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_reaction (profile_user_id, reactor_user_id, section),
        KEY profile_section (profile_user_id, section),
        KEY reactor (reactor_user_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

/**
 * Check if reactions table exists and create if needed
 */
function wasmo_maybe_create_reactions_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'profile_reactions';
    
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
        wasmo_create_reactions_table();
    }
}
add_action( 'after_switch_theme', 'wasmo_create_reactions_table' );
add_action( 'admin_init', 'wasmo_maybe_create_reactions_table' );

/*
|--------------------------------------------------------------------------
| Reaction Types Management
|--------------------------------------------------------------------------
*/

/**
 * Get default reaction types (used as fallback if ACF options not set)
 *
 * @return array
 */
function wasmo_get_default_reaction_types() {
    return array(
        'like' => array(
            'emoji'       => '👍',
            'label'       => 'Like',
            'description' => 'Simple acknowledgment',
        ),
        'heart' => array(
            'emoji'       => '❤️',
            'label'       => 'Love',
            'description' => 'I appreciate you sharing',
        ),
        // 'hug' => array(
        //     'emoji'       => '🤗',
        //     'label'       => 'Hug',
        //     'description' => 'Sending support',
        // ),
        'wow' => array(
            'emoji'       => '😮',
            'label'       => 'Wow',
            'description' => "That's incredible",
        ),
        'crying' => array(
            'emoji'       => '😢',
            'label'       => 'Sad',
            'description' => 'This touched me',
        ),
        'clap' => array(
            'emoji'       => '👏',
            'label'       => 'Applause',
            'description' => 'Well said',
        ),
        'hundred' => array(
            'emoji'       => '💯',
            'label'       => 'Agree!',
            'description' => 'I feel this too',
        ),
        // 'raised_hands' => array(
        //     'emoji'       => '🙌',
        //     'label'       => 'Celebrate',
        //     'description' => 'Proud of you',
        // ),
    );
}

/**
 * Get active reaction types
 *
 * @return array
 */
function wasmo_get_reaction_types() {
    return wasmo_get_default_reaction_types();
}

/*
|--------------------------------------------------------------------------
| Reaction CRUD Functions
|--------------------------------------------------------------------------
*/

/**
 * Add or update a reaction
 *
 * @param int    $profile_user_id The profile owner's user ID
 * @param string $section         The section being reacted to
 * @param string $reaction_type   The reaction type slug
 * @return bool Success
 */
function wasmo_add_reaction( $profile_user_id, $section, $reaction_type ) {
    global $wpdb;
    
    if ( ! is_user_logged_in() ) {
        return false;
    }

    $reactor_user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'profile_reactions';

    // Validate reaction type
    $valid_types = wasmo_get_reaction_types();
    if ( ! isset( $valid_types[ $reaction_type ] ) ) {
        return false;
    }

    // Use REPLACE to insert or update
    $result = $wpdb->replace(
        $table_name,
        array(
            'profile_user_id' => absint( $profile_user_id ),
            'reactor_user_id' => absint( $reactor_user_id ),
            'section'         => sanitize_text_field( $section ),
            'reaction_type'   => sanitize_text_field( $reaction_type ),
            'created_at'      => current_time( 'mysql' ),
        ),
        array( '%d', '%d', '%s', '%s', '%s' )
    );

    return $result !== false;
}

/**
 * Remove a user's reaction from a section
 *
 * @param int    $profile_user_id The profile owner's user ID
 * @param string $section         The section
 * @return bool Success
 */
function wasmo_remove_reaction( $profile_user_id, $section ) {
    global $wpdb;
    
    if ( ! is_user_logged_in() ) {
        return false;
    }

    $reactor_user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'profile_reactions';

    $result = $wpdb->delete(
        $table_name,
        array(
            'profile_user_id' => absint( $profile_user_id ),
            'reactor_user_id' => absint( $reactor_user_id ),
            'section'         => sanitize_text_field( $section ),
        ),
        array( '%d', '%d', '%s' )
    );

    return $result !== false;
}

/**
 * Get the current user's reaction for a section
 *
 * @param int    $profile_user_id The profile owner's user ID
 * @param string $section         The section
 * @return string|null The reaction type or null
 */
function wasmo_get_user_reaction( $profile_user_id, $section ) {
    global $wpdb;
    
    if ( ! is_user_logged_in() ) {
        return null;
    }

    $reactor_user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'profile_reactions';

    $reaction = $wpdb->get_var( $wpdb->prepare(
        "SELECT reaction_type FROM $table_name 
         WHERE profile_user_id = %d AND reactor_user_id = %d AND section = %s",
        absint( $profile_user_id ),
        absint( $reactor_user_id ),
        sanitize_text_field( $section )
    ) );

    return $reaction;
}

/**
 * Get all reactions for a section with counts and user info
 *
 * @param int    $profile_user_id The profile owner's user ID
 * @param string $section         The section
 * @return array Reactions grouped by type with counts and users
 */
function wasmo_get_section_reactions( $profile_user_id, $section ) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'profile_reactions';
    $reactions = array();

    // Get counts per reaction type
    $counts = $wpdb->get_results( $wpdb->prepare(
        "SELECT reaction_type, COUNT(*) as count 
         FROM $table_name 
         WHERE profile_user_id = %d AND section = %s 
         GROUP BY reaction_type",
        absint( $profile_user_id ),
        sanitize_text_field( $section )
    ) );

    foreach ( $counts as $row ) {
        $reactions[ $row->reaction_type ] = array(
            'count' => (int) $row->count,
            'users' => array(),
        );
    }

    // Get user details for each reaction
    $user_reactions = $wpdb->get_results( $wpdb->prepare(
        "SELECT reactor_user_id, reaction_type 
         FROM $table_name 
         WHERE profile_user_id = %d AND section = %s 
         ORDER BY created_at DESC",
        absint( $profile_user_id ),
        sanitize_text_field( $section )
    ) );

    foreach ( $user_reactions as $row ) {
        $user = get_user_by( 'ID', $row->reactor_user_id );
        if ( $user && isset( $reactions[ $row->reaction_type ] ) ) {
            $reactions[ $row->reaction_type ]['users'][] = array(
                'id'           => $user->ID,
                'display_name' => $user->display_name,
                'profile_url'  => get_author_posts_url( $user->ID ),
                'avatar_url'   => wasmo_get_user_image_url( $user->ID ),
            );
        }
    }

    return $reactions;
}

/**
 * Get total reaction count for a section
 *
 * @param int    $profile_user_id The profile owner's user ID
 * @param string $section         The section
 * @return int Total count
 */
function wasmo_get_section_reaction_count( $profile_user_id, $section ) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'profile_reactions';

    $count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
         WHERE profile_user_id = %d AND section = %s",
        absint( $profile_user_id ),
        sanitize_text_field( $section )
    ) );

    return (int) $count;
}

/*
|--------------------------------------------------------------------------
| Render Functions
|--------------------------------------------------------------------------
*/

/**
 * Render reaction buttons for a section
 *
 * @param int    $profile_user_id The profile owner's user ID
 * @param string $section         The section identifier
 */
function wasmo_render_reaction_buttons( $profile_user_id, $section ) {
    if ( ! wasmo_user_allows_contact( $profile_user_id ) ) {
        return;
    }

    $reactions = wasmo_get_section_reactions( $profile_user_id, $section );
    $user_reaction = is_user_logged_in() ? wasmo_get_user_reaction( $profile_user_id, $section ) : null;
    $reaction_types = wasmo_get_reaction_types();
    $total_count = array_sum( array_column( $reactions, 'count' ) );

    // Build summary of reactions with counts for display
    $reaction_summary = array();
    foreach ( $reactions as $type_slug => $data ) {
        if ( ! empty( $data['count'] ) && isset( $reaction_types[ $type_slug ] ) ) {
            $reaction_summary[ $type_slug ] = $data['count'];
        }
    }

    ?>
    <div class="profile-reactions" 
         data-profile="<?php echo esc_attr( $profile_user_id ); ?>" 
         data-section="<?php echo esc_attr( $section ); ?>"
         data-nonce="<?php echo esc_attr( wp_create_nonce( 'wasmo_reactions' ) ); ?>">
        
        <div class="reaction-picker-wrapper">
            <?php // Trigger button - shows heart or user's current reaction ?>
            <button type="button" class="reaction-trigger <?php echo $user_reaction ? 'has-reaction' : ''; ?>" 
                    <?php echo ! is_user_logged_in() ? 'disabled' : ''; ?>
                    title="<?php echo is_user_logged_in() ? 'React' : 'Log in to react'; ?>">
                <?php 
                $trigger_icon = $user_reaction ? 'reaction-' . $user_reaction : 'reaction-like';
                $trigger_label = $user_reaction && isset( $reaction_types[ $user_reaction ] ) 
                    ? $reaction_types[ $user_reaction ]['label'] 
                    : 'React';
                ?>
                <span class="reaction-trigger-icon"><?php echo wasmo_get_icon_svg( $trigger_icon, 20 ); ?></span>
                <span class="reaction-trigger-label"><?php echo esc_html( $trigger_label ); ?></span>
            </button>

            <?php // Expandable picker with all reactions ?>
            <div class="reaction-picker">
                <?php foreach ( $reaction_types as $slug => $type ) : 
                    $is_active = ( $user_reaction === $slug );
                    $icon_key = 'reaction-' . $slug;
                ?>
                    <button 
                        type="button"
                        class="reaction-btn <?php echo $is_active ? 'active' : ''; ?>" 
                        data-type="<?php echo esc_attr( $slug ); ?>"
                        title="<?php echo esc_attr( $type['label'] ); ?>"
                        <?php echo ! is_user_logged_in() ? 'disabled' : ''; ?>
                    >
                        <span class="reaction-icon"><?php echo wasmo_get_icon_svg( $icon_key, 24 ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ( ! is_user_logged_in() ) : ?>
            <p class="reactions-login-prompt">
                <em>
                <a class="register" href="<?php echo esc_url( home_url('/login/') ); ?>">Join</a> or
                    <a class="nav-login" href="<?php echo esc_url( home_url('/login/') ); ?>">log in</a> to react or share your own story.</em>
            </p>
        <?php endif; ?>

        <?php // Reaction summary - shows icons with counts ?>
        <?php if ( $total_count > 0 ) : ?>
            <div class="reaction-summary">
                <span class="reaction-summary-icons">
                    <?php foreach ( $reaction_summary as $type_slug => $count ) : ?>
                        <span class="reaction-summary-icon" title="<?php echo esc_attr( $reaction_types[ $type_slug ]['label'] . ': ' . $count ); ?>">
                            <?php echo wasmo_get_icon_svg( 'reaction-' . $type_slug, 16 ); ?>
                        </span>
                    <?php endforeach; ?>
                </span>
                <button type="button" class="reaction-details-toggle">
                    <?php echo esc_html( $total_count ); ?>
                </button>
            </div>
            <div class="reaction-details" style="display: none;">
                <?php foreach ( $reactions as $type_slug => $data ) : 
                    if ( empty( $data['users'] ) ) continue;
                    $type_info = $reaction_types[ $type_slug ] ?? null;
                    if ( ! $type_info ) continue;
                ?>
                    <div class="reaction-detail-group">
                        <span class="reaction-detail-icon"><?php echo wasmo_get_icon_svg( 'reaction-' . $type_slug, 16 ); ?></span>
                        <span class="reaction-detail-label"><?php echo esc_html( $type_info['label'] ); ?></span>
                        <span class="reaction-detail-users">
                            <?php foreach ( $data['users'] as $user_info ) : ?>
                                <a href="<?php echo esc_url( $user_info['profile_url'] ); ?>" 
                                   class="reaction-user-avatar"
                                   title="<?php echo esc_attr( $user_info['display_name'] ); ?>">
                                    <img src="<?php echo esc_url( $user_info['avatar_url'] ); ?>" 
                                         alt="<?php echo esc_attr( $user_info['display_name'] ); ?>"
                                         loading="lazy" />
                                </a>
                            <?php endforeach; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/*
|--------------------------------------------------------------------------
| AJAX Handlers
|--------------------------------------------------------------------------
*/

/**
 * AJAX handler for toggling reactions
 */
function wasmo_ajax_toggle_reaction() {
    check_ajax_referer( 'wasmo_reactions', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in to react.' ) );
    }

    $profile_user_id = isset( $_POST['profile_user_id'] ) ? absint( $_POST['profile_user_id'] ) : 0;
    $section = isset( $_POST['section'] ) ? sanitize_text_field( $_POST['section'] ) : '';
    $reaction_type = isset( $_POST['reaction_type'] ) ? sanitize_text_field( $_POST['reaction_type'] ) : '';

    if ( ! $profile_user_id || ! $section || ! $reaction_type ) {
        wp_send_json_error( array( 'message' => 'Missing required fields.' ) );
    }

    // Verify profile allows contact
    if ( ! wasmo_user_allows_contact( $profile_user_id ) ) {
        wp_send_json_error( array( 'message' => 'This profile does not allow interactions.' ) );
    }

    // Get current reaction
    $current = wasmo_get_user_reaction( $profile_user_id, $section );
    $is_new_reaction = empty( $current );

    if ( $current === $reaction_type ) {
        // Remove if clicking same reaction
        wasmo_remove_reaction( $profile_user_id, $section );
        $action = 'removed';
    } else {
        // Add or change reaction
        wasmo_add_reaction( $profile_user_id, $section, $reaction_type );
        $action = 'added';
        
        // Send email notification only for new reactions (not changes)
        if ( $is_new_reaction ) {
            wasmo_notify_profile_reaction( $profile_user_id, $section, $reaction_type );
        }
    }

    // Return updated data
    $reactions = wasmo_get_section_reactions( $profile_user_id, $section );
    $reaction_types = wasmo_get_reaction_types();
    
    // Format response
    $response_reactions = array();
    foreach ( $reaction_types as $slug => $type ) {
        $response_reactions[ $slug ] = array(
            'count' => $reactions[ $slug ]['count'] ?? 0,
            'users' => $reactions[ $slug ]['users'] ?? array(),
        );
    }

    wp_send_json_success( array(
        'action'        => $action,
        'reactions'     => $response_reactions,
        'user_reaction' => ( $action === 'added' ) ? $reaction_type : null,
        'total_count'   => array_sum( array_column( $response_reactions, 'count' ) ),
    ) );
}
add_action( 'wp_ajax_wasmo_toggle_reaction', 'wasmo_ajax_toggle_reaction' );

/**
 * Handle profile comment form submission
 */
function wasmo_handle_profile_comment_submission() {
    // Verify nonce
    $profile_user_id = isset( $_POST['profile_user_id'] ) ? absint( $_POST['profile_user_id'] ) : 0;
    
    if ( ! wp_verify_nonce( $_POST['profile_comment_nonce'] ?? '', 'profile_comment_' . $profile_user_id ) ) {
        wp_die( 'Security check failed.', 'Error', array( 'back_link' => true ) );
    }

    // Must be logged in
    if ( ! is_user_logged_in() ) {
        wp_die( 'You must be logged in to comment.', 'Error', array( 'back_link' => true ) );
    }

    // Can't comment on own profile
    if ( get_current_user_id() === $profile_user_id ) {
        wp_die( 'You cannot comment on your own profile.', 'Error', array( 'back_link' => true ) );
    }

    // Check profile allows contact
    if ( ! wasmo_user_allows_contact( $profile_user_id ) ) {
        wp_die( 'This profile does not allow comments.', 'Error', array( 'back_link' => true ) );
    }

    $comment_post_id = isset( $_POST['comment_post_ID'] ) ? absint( $_POST['comment_post_ID'] ) : 0;
    $comment_content = isset( $_POST['comment'] ) ? sanitize_textarea_field( $_POST['comment'] ) : '';

    if ( ! $comment_post_id || empty( $comment_content ) ) {
        wp_die( 'Please enter a comment.', 'Error', array( 'back_link' => true ) );
    }

    // Verify the comment post belongs to this profile
    $post_profile_user_id = wasmo_get_user_from_comment_post( $comment_post_id );
    if ( $post_profile_user_id !== $profile_user_id ) {
        wp_die( 'Invalid comment post.', 'Error', array( 'back_link' => true ) );
    }

    // Get current user info
    $current_user = wp_get_current_user();

    // Insert comment
    $comment_data = array(
        'comment_post_ID'      => $comment_post_id,
        'comment_author'       => $current_user->display_name,
        'comment_author_email' => $current_user->user_email,
        'comment_author_url'   => get_author_posts_url( $current_user->ID ),
        'comment_content'      => $comment_content,
        'user_id'              => $current_user->ID,
        'comment_approved'     => 1, // Auto-approve for logged in users
    );

    $comment_id = wp_insert_comment( $comment_data );

    if ( ! $comment_id ) {
        wp_die( 'Failed to post comment. Please try again.', 'Error', array( 'back_link' => true ) );
    }

    // Send email notification to profile owner
    wasmo_notify_profile_comment( $profile_user_id, $current_user->ID, $comment_id );

    // Redirect back to profile
    $redirect_url = get_author_posts_url( $profile_user_id ) . '#comment-' . $comment_id;
    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_wasmo_submit_profile_comment', 'wasmo_handle_profile_comment_submission' );

/**
 * Handle profile comment deletion (frontend)
 */
function wasmo_handle_profile_comment_deletion() {
    $comment_id = isset( $_GET['comment_id'] ) ? absint( $_GET['comment_id'] ) : 0;
    
    if ( ! $comment_id ) {
        wp_die( 'Invalid comment.', 'Error', array( 'back_link' => true ) );
    }

    // Verify nonce
    if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'delete_profile_comment_' . $comment_id ) ) {
        wp_die( 'Security check failed.', 'Error', array( 'back_link' => true ) );
    }

    // Must be logged in
    if ( ! is_user_logged_in() ) {
        wp_die( 'You must be logged in to delete comments.', 'Error', array( 'back_link' => true ) );
    }

    $comment = get_comment( $comment_id );
    if ( ! $comment ) {
        wp_die( 'Comment not found.', 'Error', array( 'back_link' => true ) );
    }

    // Get the profile user ID from the shadow post
    $profile_user_id = wasmo_get_user_from_comment_post( $comment->comment_post_ID );
    if ( ! $profile_user_id ) {
        wp_die( 'Invalid comment post.', 'Error', array( 'back_link' => true ) );
    }

    $current_user_id = get_current_user_id();
    
    // Only profile owner or admins can delete
    if ( $current_user_id !== $profile_user_id && ! current_user_can( 'moderate_comments' ) ) {
        wp_die( 'You do not have permission to delete this comment.', 'Error', array( 'back_link' => true ) );
    }

    // Delete the comment
    $deleted = wp_delete_comment( $comment_id, true );

    if ( ! $deleted ) {
        wp_die( 'Failed to delete comment.', 'Error', array( 'back_link' => true ) );
    }

    // Redirect back to profile
    $redirect_url = get_author_posts_url( $profile_user_id ) . '#profile-comments';
    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_wasmo_delete_profile_comment', 'wasmo_handle_profile_comment_deletion' );

/**
 * Generate a frontend delete URL for a profile comment
 *
 * @param int $comment_id The comment ID
 * @return string The delete URL
 */
function wasmo_get_comment_delete_url( $comment_id ) {
    return wp_nonce_url(
        admin_url( 'admin-post.php?action=wasmo_delete_profile_comment&comment_id=' . $comment_id ),
        'delete_profile_comment_' . $comment_id
    );
}

/*
|--------------------------------------------------------------------------
| Migration Tool
|--------------------------------------------------------------------------
*/

/**
 * Migrate reactions from one type to another
 *
 * @param string $from_type Source reaction type
 * @param string $to_type   Target reaction type
 * @return int|false Number of rows updated or false on error
 */
function wasmo_migrate_reactions( $from_type, $to_type ) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'profile_reactions';

    $updated = $wpdb->update(
        $table_name,
        array( 'reaction_type' => sanitize_text_field( $to_type ) ),
        array( 'reaction_type' => sanitize_text_field( $from_type ) ),
        array( '%s' ),
        array( '%s' )
    );

    return $updated;
}

/**
 * Get reaction statistics
 *
 * @return array Stats by reaction type
 */
function wasmo_get_reaction_stats() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'profile_reactions';

    $stats = $wpdb->get_results(
        "SELECT reaction_type, COUNT(*) as count 
         FROM $table_name 
         GROUP BY reaction_type 
         ORDER BY count DESC"
    );

    $result = array();
    foreach ( $stats as $row ) {
        $result[ $row->reaction_type ] = (int) $row->count;
    }

    return $result;
}

/*
|--------------------------------------------------------------------------
| Email Notifications
|--------------------------------------------------------------------------
*/

/**
 * Send email notification when someone reacts to a profile section
 *
 * @param int    $profile_user_id The profile owner's user ID
 * @param string $section         The section reacted to
 * @param string $reaction_type   The reaction type slug
 */
function wasmo_notify_profile_reaction( $profile_user_id, $section, $reaction_type ) {
    // Check user's notification preference
    $notify_pref = get_field( 'notify_me_about_reactions', 'user_' . $profile_user_id );
    // Only send if preference is 'yes' (both) or 'reactions' (reactions only)
    if ( ! in_array( $notify_pref, array( 'yes', 'reactions' ), true ) ) {
        return;
    }

    // Don't notify if user reacts to their own profile
    $reactor_user_id = get_current_user_id();
    if ( $reactor_user_id === $profile_user_id ) {
        return;
    }

    $profile_owner = get_user_by( 'ID', $profile_user_id );
    $reactor = get_user_by( 'ID', $reactor_user_id );
    
    if ( ! $profile_owner || ! $reactor ) {
        return;
    }

    // Get reaction info
    $reaction_types = wasmo_get_reaction_types();
    $reaction_info = $reaction_types[ $reaction_type ] ?? null;
    if ( ! $reaction_info ) {
        return;
    }

    // Format section name for display
    $section_display = ucwords( str_replace( '_', ' ', $section ) );
    
    // Build email
    $profile_url = get_author_posts_url( $profile_user_id );
    $reactor_profile_url = get_author_posts_url( $reactor_user_id );
    
    $to = $profile_owner->user_email;
    // $reply_to = $reactor->user_email;
    $subject = sprintf( '%s reacted to your profile on wasmormon.org', $reactor->display_name );
    
    $message = sprintf(
        "Hi %s,\n\n" .
        "%s reacted to your \"%s\" section with %s %s.\n\n" .
        "View your profile: %s\n\n" .
        "See %s's profile: %s\n\n" .
        "---\n" .
        "You received this email because someone reacted to your wasmormon.org profile.\n",
        $profile_owner->display_name,
        $reactor->display_name,
        $section_display,
        $reaction_info['emoji'],
        $reaction_info['label'],
        $profile_url,
        $reactor->display_name,
        $reactor_profile_url
    );

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        // 'Reply-To: <' . $reply_to . '>'
    );
    
    wp_mail( $to, $subject, $message, $headers );
}

/**
 * Send email notification when someone comments on a profile
 *
 * @param int $profile_user_id The profile owner's user ID
 * @param int $commenter_id    The commenter's user ID
 * @param int $comment_id      The comment ID
 */
function wasmo_notify_profile_comment( $profile_user_id, $commenter_id, $comment_id ) {
    // Check user's notification preference
    $notify_pref = get_field( 'notify_me_about_reactions', 'user_' . $profile_user_id );
    // Only send if preference is 'yes' (both) or 'comments' (comments only)
    if ( ! in_array( $notify_pref, array( 'yes', 'comments' ), true ) ) {
        return;
    }

    // Don't notify if user comments on their own profile (shouldn't happen but just in case)
    if ( $commenter_id === $profile_user_id ) {
        return;
    }

    $profile_owner = get_user_by( 'ID', $profile_user_id );
    $commenter = get_user_by( 'ID', $commenter_id );
    $comment = get_comment( $comment_id );
    
    if ( ! $profile_owner || ! $commenter || ! $comment ) {
        return;
    }

    // Build email
    $profile_url = get_author_posts_url( $profile_user_id ) . '#comment-' . $comment_id;
    $commenter_profile_url = get_author_posts_url( $commenter_id );
    
    $to = $profile_owner->user_email;
    $reply_to = $commenter->user_email;
    $subject = sprintf( '%s commented on your profile on wasmormon.org', $commenter->display_name );
    
    // Truncate comment for preview
    $comment_preview = wp_trim_words( $comment->comment_content, 50, '...' );
    
    $message = sprintf(
        "Hi %s,\n\n" .
        "%s (%s) left a comment on your profile:\n\n" .
        "---\n" .
        "%s\n" .
        "---\n\n" .
        "View the comment: %s\n\n" .
        "See %s's profile: %s\n\n" .
        "If this comment is inappropriate, you can delete it on your profile page when you are logged in.\n\n" .
        "---\n" .
        "You received this email because someone commented on your wasmormon.org profile and you've chosen to be notified. If you want to disable these notifications, you can do so in your profile settings .\n",
        $profile_owner->display_name,
        $commenter->display_name,
        $reply_to,
        $comment_preview,
        $profile_url,
        $commenter->display_name,
        $commenter_profile_url
    );

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: <' . $reply_to . '>'
    );
    
    wp_mail( $to, $subject, $message, $headers );
}

/*
|--------------------------------------------------------------------------
| Enqueue Scripts
|--------------------------------------------------------------------------
*/

/**
 * Enqueue profile interactions scripts on author pages
 */
function wasmo_enqueue_profile_interactions_scripts() {
    if ( ! is_author() ) {
        return;
    }

    $js_file = get_stylesheet_directory() . '/js/profile-interactions.js';
    
    if ( ! file_exists( $js_file ) ) {
        return;
    }

    wp_enqueue_script(
        'wasmo-profile-interactions',
        get_stylesheet_directory_uri() . '/js/profile-interactions.js',
        array( 'jquery' ),
        filemtime( $js_file ),
        true
    );

    wp_localize_script( 'wasmo-profile-interactions', 'wasmoReactions', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'wasmo_enqueue_profile_interactions_scripts' );
