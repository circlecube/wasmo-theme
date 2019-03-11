<?php
/**
* @var $userid
*/
?>

<div class="user_photo"><?php 
$userimg = get_field( 'photo', 'user_' . $userid );
if ( $userimg ) {
	echo wp_get_attachment_image( $userimg, 'medium' );
}
?></div>
<div class="hi"><?php echo get_field( 'hi', 'user_' . $userid ); ?></div>
<div class="tagline"><?php echo get_field( 'tagline', 'user_' . $userid ); ?></div>
<h3>Why I left</h3>
<div class="why_i_left"><?php echo get_field( 'why_i_left', 'user_' . $userid ); ?></div>

<?php
//questions repeater
if( have_rows( 'questions', 'user_' . $userid ) ):
	?>

	<h3>Questions</h3>
	
	<?php
 	// loop through the rows of data
	while ( have_rows( 'questions', 'user_' . $userid ) ) : 
		the_row();

		echo '<h4 class="question">';
		$termtaxid = get_sub_field( 'question', 'users_' . $userid );
		$questionterm = get_term( $termtaxid, 'question' );
		echo $questionterm->name;
		echo '</h4>';
		echo get_sub_field( 'answer', 'users_' . $userid );

    endwhile;

else :
    // no questions found
endif;
?>