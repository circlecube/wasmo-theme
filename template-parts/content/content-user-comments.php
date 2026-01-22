<?php
/**
 * Template part for displaying profile comments
 * 
 * @var int $userid The profile user ID (passed via set_query_var)
 */

$userid = get_query_var( 'userid' );
if ( ! $userid ) {
	return;
}

// Check if profile allows contact
if ( ! wasmo_user_allows_contact( $userid ) ) {
	return;
}

// Get the shadow post for this profile
$comment_post_id = wasmo_get_profile_comment_post( $userid );
if ( ! $comment_post_id ) {
	return;
}

// Get comments for this profile's shadow post
$comments = get_comments( array(
	'post_id' => $comment_post_id,
	'status'  => 'approve',
	'orderby' => 'comment_date_gmt',
	'order'   => 'ASC',
) );

$comment_count = count( $comments );
$profile_owner = get_userdata( $userid );
?>

<div id="profile-comments" class="profile-comments-section">
	<h3>
		Comments
		<?php if ( $comment_count > 0 ) : ?>
			<span class="comment-count">(<?php echo $comment_count; ?>)</span>
		<?php endif; ?>
	</h3>

	<?php if ( ! empty( $comments ) ) : ?>
		<ul class="profile-comment-list">
			<?php foreach ( $comments as $comment ) : 
				$commenter = get_user_by( 'email', $comment->comment_author_email );
				$commenter_name = $comment->comment_author;
				$commenter_link = '#';
				$commenter_avatar = get_avatar( $comment->comment_author_email, 48, '', $commenter_name );
				
				// If commenter is a registered user, link to their profile
				if ( $commenter ) {
					$commenter_name = $commenter->display_name;
					$commenter_link = get_author_posts_url( $commenter->ID );
				}
			?>
				<li class="profile-comment" id="comment-<?php echo $comment->comment_ID; ?>">
					<div class="comment-avatar">
						<?php if ( $commenter ) : ?>
							<a href="<?php echo esc_url( $commenter_link ); ?>" title="View <?php echo esc_attr( $commenter_name ); ?>'s profile">
								<?php echo $commenter_avatar; ?>
							</a>
						<?php else : ?>
							<?php echo $commenter_avatar; ?>
						<?php endif; ?>
					</div>
					<div class="comment-content">
						<div class="comment-meta">
							<?php if ( $commenter ) : ?>
								<a href="<?php echo esc_url( $commenter_link ); ?>" class="comment-author">
									<?php echo esc_html( $commenter_name ); ?>
								</a>
							<?php else : ?>
								<span class="comment-author"><?php echo esc_html( $commenter_name ); ?></span>
							<?php endif; ?>
							<span class="comment-date" title="<?php echo $comment->comment_date; ?>">
								<?php echo human_time_diff( strtotime( $comment->comment_date_gmt ), current_time( 'timestamp', true ) ); ?> ago
							</span>
						</div>
						<div class="comment-text">
							<?php echo wpautop( wp_kses_post( $comment->comment_content ) ); ?>
						</div>
						<?php 
						// Show moderation options to admins or profile owner
						if ( current_user_can( 'moderate_comments' ) || ( is_user_logged_in() && get_current_user_id() === $userid ) ) : 
						?>
							<div class="comment-actions">
								<?php if ( current_user_can( 'moderate_comments' ) ) : ?>
									<a href="<?php echo admin_url( 'comment.php?action=editcomment&c=' . $comment->comment_ID ); ?>" class="comment-edit-link">
										Edit
									</a>
								<?php endif; ?>
								<a href="<?php echo esc_url( wasmo_get_comment_delete_url( $comment->comment_ID ) ); ?>" class="comment-delete-link" onclick="return confirm('Delete this comment?');">
									Delete
								</a>
							</div>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p class="no-comments">No comments yet. Be the first to leave a supportive message!</p>
	<?php endif; ?>

	<?php if ( is_user_logged_in() ) : 
		// Don't allow users to comment on their own profile
		if ( get_current_user_id() !== $userid ) :
	?>
		<div class="profile-comment-form">
			<h4>Leave a Comment</h4>
			<form id="profile-comment-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'profile_comment_' . $userid, 'profile_comment_nonce' ); ?>
				<input type="hidden" name="action" value="wasmo_submit_profile_comment" />
				<input type="hidden" name="profile_user_id" value="<?php echo esc_attr( $userid ); ?>" />
				<input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( $comment_post_id ); ?>" />
				
				<div class="comment-form-field">
					<label for="profile-comment-content" class="screen-reader-text">Your comment</label>
					<textarea 
						id="profile-comment-content" 
						name="comment" 
						rows="4" 
						placeholder="Share a supportive message..."
						required
					></textarea>
				</div>
				
				<div class="comment-form-submit">
					<button type="submit" class="wp-block-button__link wp-element-button">
						Post Comment
					</button>
				</div>
			</form>
		</div>
	<?php 
		else : 
			// User is viewing their own profile
	?>
		<p class="own-profile-message">
			<em>This is your profile. You'll receive comments from other members here.</em>
		</p>
	<?php 
		endif;
	else : 
	?>
		<p class="login-to-comment">
			<a href="<?php echo esc_url( wp_login_url( get_author_posts_url( $userid ) . '#profile-comments' ) ); ?>">Log in</a> to leave a comment for <?php echo esc_html( $profile_owner->display_name ); ?>.
		</p>
	<?php endif; ?>
</div>
