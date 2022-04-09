<?php
namespace SIM\MEDIAGALLERY;
use SIM;

//add public/private radio buttons to attachment page
add_filter( 'attachment_fields_to_edit', function($form_fields, $post ){
    $field_value = get_post_meta( $post->ID, 'gallery_visibility', true );

    if($field_value == 'hide') $checked    = 'checked';
    $html    = "<div>";
        $html   .= "<input type='checkbox' $checked name='attachments[{$post->ID}][gallery_visibility]' value='hide'>";
        $html   .= " Do not show in media gallery";
    $html   .= "</div>";

    $form_fields['visibility'] = array(
        'value' => $field_value ? $field_value : '',
        'label' => __( 'Gallery visibility' ),
        'input' => 'html',
        'html'  =>  $html
      );  
    return $form_fields;
},10,2);

//change visibility of an attachment
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