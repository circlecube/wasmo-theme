<?php
/**
* @var $userid
*/
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

<?php 
$links = get_field( 'links', 'user_' . $userid );
if ( $links ) { ?>
<?php if ( $links['facebook'] ) { ?>
    <?php echo esc_url( $links['facebook'] ); ?>
<?php } ?>
<?php if ( $links['instagram'] ) { ?>
    <?php echo esc_url( $links['instagram'] ); ?>
<?php } ?>
<?php if ( $links['reddit'] ) { ?>
    <?php echo esc_url( $links['reddit'] ); ?>
<?php } ?>
<?php if ( $links['twitter'] ) { ?>
    <?php echo esc_url( $links['twitter'] ); ?>
<?php } ?>
<?php if ( $links['other'] ) { ?>
    <?php echo esc_url( $links['other'] ); ?>
<?php } ?>
<?php } ?>

<?php if ( get_field( 'about_me', 'user_' . $userid ) ) { ?>
About me
<?php echo wp_strip_all_tags( get_field( 'about_me', 'user_' . $userid ) ); ?>
<?php } ?>

<?php 
$shelf_items = get_field( 'my_shelf', 'user_' . $userid );
if ( $shelf_items ) { 
?>
On my shelf
<?php foreach( $shelf_items as $term ): ?>
<?php echo $term->name; ?>,
<?php endforeach; ?>
<?php } ?>

<?php 
$spectrum_terms = get_field( 'mormon_spectrum', 'user_' . $userid );
if ( $spectrum_terms ) { ?>
On the Mormon Spectrum
<?php foreach( $spectrum_terms as $term ): ?>
<?php echo $term->name; ?>,
<?php endforeach; ?>
<?php } ?>

<?php if ( get_field( 'why_i_left', 'user_' . $userid ) ) { ?>
Why I left
<?php echo wp_strip_all_tags( get_field( 'why_i_left', 'user_' . $userid ) ); ?>
<?php } ?>

<?php
//questions repeater
if( have_rows( 'questions', 'user_' . $userid ) ):
	?>
Questions

	<?php
 	// loop through the rows of data
	while ( have_rows( 'questions', 'user_' . $userid ) ) : 
		the_row();
		$termtaxid = get_sub_field( 'question', 'users_' . $userid );
		if ( $termtaxid ) {
			$questionterm = get_term( $termtaxid, 'question' ); 
?>
<?php echo wp_kses_post( $questionterm->name ); ?>
<?php echo wp_strip_all_tags( get_sub_field( 'answer', 'users_' . $userid ) ); ?>

<?php 
        }
    endwhile;

else :
    // no questions found
endif;
?>