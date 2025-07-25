<?php
/**
 * Section to display consistent attribution for imported profiles.
 * @var $userid, $curauth
 */
?>
<?php
$import_source = get_field( 'import_source', 'user_' . $userid );
$import_text   = get_field( 'import_text', 'user_' . $userid );
$default_text  = 'Story originally posted at';

if ( $import_source || $import_text ) { 
    $import_text = $import_text ? $import_text : $default_text;    
?>
    <div class="user-attribution">
        <h4>Attribution</h4>
        <p><?php echo $import_text; ?> <?php echo wasmo_auto_link_text( $import_source ); ?></p>
    </div>
<?php } ?>
