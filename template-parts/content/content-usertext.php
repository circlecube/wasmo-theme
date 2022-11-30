<?php
/**
* @var $userid
*/

$userid = get_query_var( 'userid' );
?>

<?php if ( get_field( 'hi', 'user_' . $userid ) ) { ?>
<?php echo wp_kses_post( get_field( 'hi', 'user_' . $userid ) ); ?>


<?php } ?>
<?php if ( get_field( 'tagline', 'user_' . $userid ) ) { ?>
<?php echo wp_kses_post( get_field( 'tagline', 'user_' . $userid ) ); ?>


<?php } ?>
<?php if ( get_field( 'location', 'user_' . $userid ) ) { ?>
<?php echo wp_kses_post( get_field( 'location', 'user_' . $userid ) ); ?>


<?php } ?>
<?php if ( get_field( 'about_me', 'user_' . $userid ) ) { ?>
About me
<?php echo wp_strip_all_tags( get_field( 'about_me', 'user_' . $userid ) ); ?>


<?php } ?>
<?php $shelf_items = get_field( 'my_shelf', 'user_' . $userid ); ?>
<?php if ( $shelf_items ) { ?>
On my shelf
<?php foreach( $shelf_items as $term ): ?>
<?php echo $term->name; ?>, <?php endforeach; ?>


<?php } ?>
<?php $spectrum_terms = get_field( 'mormon_spectrum', 'user_' . $userid ); ?>
<?php if ( $spectrum_terms ) { ?>
On the Mormon Spectrum
<?php foreach( $spectrum_terms as $term ): ?>
<?php echo $term->name; ?>, <?php endforeach; ?>


<?php } ?>
<?php if ( get_field( 'why_i_left', 'user_' . $userid ) ) { ?>
Why I left
<?php echo wp_strip_all_tags( get_field( 'why_i_left', 'user_' . $userid ) ); ?>


<?php } ?>
<?php if( have_rows( 'questions', 'user_' . $userid ) ): ?>
Questions
<?php while ( have_rows( 'questions', 'user_' . $userid ) ) : 
    the_row();
    $termtaxid = get_sub_field( 'question', 'users_' . $userid );
    if ( $termtaxid ) :
        $questionterm = get_term( $termtaxid, 'question' );
        echo wp_kses_post( $questionterm->name ); ?>

<?php
        echo wp_strip_all_tags( get_sub_field( 'answer', 'users_' . $userid ) ); ?>


<?php
    endif;
    endwhile; 
endif;
?>