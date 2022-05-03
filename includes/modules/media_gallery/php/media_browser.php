<?php
namespace SIM\MEDIAGALLERY;
use SIM;

//add public/private radio buttons to attachment page
add_filter( 'attachment_fields_to_edit', function($formFields, $post ){
    $fieldValue = get_post_meta( $post->ID, 'gallery_visibility', true );

    ob_start();
    ?>
    <input type='radio' name='attachments[<?php echo $post->ID;?>][gallery_visibility]' value='show' <?php if($fieldValue == 'show') echo 'checked';?>> Show
    <input type='radio' name='attachments[<?php echo $post->ID;?>][gallery_visibility]' value='hide' <?php if($fieldValue != 'show') echo 'checked';?>> Hide
    <?php

    $formFields['visibility'] = array(
        'value' => $fieldValue,
        'label' => __( 'Gallery visibility' ),
        'input' => 'html',
        'html'  =>  ob_get_clean()
      );  
    return $formFields;
},10,2);

add_action( 'edit_attachment', function($attachment_id){
    if ( isset( $_REQUEST['attachments'][$attachment_id]['gallery_visibility'] ) ) {
        $visibility = $_REQUEST['attachments'][$attachment_id]['gallery_visibility'];

        //check if changed
        $prev_vis   = get_post_meta( $attachment_id, 'gallery_visibility',true);

        if($prev_vis != $visibility){
            //update post meta
            update_post_meta( $attachment_id, 'gallery_visibility', $visibility );
        }
    }
} );