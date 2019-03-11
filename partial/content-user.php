<?php
/**
* @var $userid
*/
?>
<div class="name"><?php echo esc_html( $user->display_name ); ?></div>
<div class="user_photo"><?php the_field( 'photo', 'user' . $userid ); ?></div>
<div class="hi"><?php the_field( 'hi', 'user_' . $userid ); ?></div>
<div class="tagline"><?php the_field( 'tagline', 'user_' . $userid ); ?></div>
<h3>Why I left</h3>
<div class="why_i_left"><?php the_field( 'why_i_left', 'user_' . $userid ); ?></div>
<h3>Questions</h3>
<?php
//questions repeater
if( have_rows( 'questions', 'user_' . $userid ) ):

 	// loop through the rows of data
	while ( have_rows( 'questions', 'user_' . $userid ) ) : 
		the_row();

        echo '<h4 class="question">';
        the_sub_field( 'question', 'users_' . $userid );
		echo '</h4>';
		the_sub_field( 'answer', 'users_' . $userid );

    endwhile;

else :
    // no questions found
endif;
?>