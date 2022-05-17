<?php
namespace SIM\CONTENTFILTER;
use SIM;

//add public/private radio buttons to attachment page
add_filter( 'attachment_fields_to_edit', function($form_fields, $post ){
    $field_value = get_post_meta( $post->ID, 'visibility', true );

    if($field_value == 'public' or empty($field_value)) $checked    = 'checked';
    $html    = "<div>";
        $html   .= "<input $checked style='width: initial' type='radio' name='attachments[{$post->ID}][visibility]' value='public'>";
        $html   .= " Public";
    $html   .= "</div>";

    $checked    = '';
    if($field_value == 'private') $checked    = 'checked';
    $html   .= "<div>";
        $html   .= "<input $checked style='width: initial' type='radio' name='attachments[{$post->ID}][visibility]' value='private'>";
        $html   .= " Private";
    $html   .= "</div>";

    $form_fields['visibility'] = array(
        'value' => $field_value ? $field_value : '',
        'label' => __( 'Visibility' ),
        'input' => 'html',
        'html'  =>  $html
      );  
    return $form_fields;
},10,2);

//change visibility of an attachment
add_action( 'edit_attachment', function($attachment_id){
    if ( isset( $_REQUEST['attachments'][$attachment_id]['visibility'] ) ) {
        $visibility = $_REQUEST['attachments'][$attachment_id]['visibility'];

        //check if changed
        $prev_vis   = get_post_meta( $attachment_id, 'visibility',true);

        if($prev_vis != $visibility){
            do_action('before_visibility_change', $attachment_id, $visibility);

            //update post meta
            update_metadata( 'post', $attachment_id, 'visibility', $visibility );

            //Check if moving to public or to private
            if($visibility == 'public'){
                $target_path = '';
            }else{
                $target_path = 'private';
            }
            move_attachment($attachment_id, $target_path);
        }
    }
} );

//Move picture to other folder
function move_attachment($post_id, $sub_dir){
    if(empty($sub_dir)){
        $new_path   = wp_upload_dir()['path'];
    }else{
        $new_path   = wp_upload_dir()['path']."/$sub_dir";
        $sub_dir    = rtrim($sub_dir,"/").'/';
    }

	//get the file location of the attachment
	$old_path   = get_attached_file($post_id);
    if(!file_exists($old_path)) return false;

    $filename   = basename ($old_path);
    $extension  = '.'.pathinfo($old_path, PATHINFO_EXTENSION);
    $base_dir   = dirname($old_path);
    $base_name  = str_replace(["-scaled", $extension], "", $filename);

	//Update the location in the db
	update_attached_file($post_id, "$new_path/$filename");

    //update main path
    update_metadata( 'post', $post_id, '_wp_attached_file', $sub_dir.$filename);
	
	//Open up the wp filesystem
	WP_Filesystem();
	global $wp_filesystem;
	
	//Move all the files to the private folder
	$files = glob("$base_dir/$base_name*");
	foreach($files as $file){
        $wp_filesystem->move($file, "$new_path/".basename ($file), true);
	}

    wp_generate_attachment_metadata($post_id, "$new_path/$filename");

    //replace any url with new urls for this attachment
    $old_url    = SIM\pathToUrl(str_replace($filename, $base_name, $old_path));
    $new_url    = SIM\pathToUrl("$new_path/$base_name");

    // Search for any post with the old url
    $query = new \WP_Query( array( 's' => $old_url ) );

    foreach($query->posts as $post){        
        //if old url is found in the content of this post
        if(strpos($post->post_content, $old_url) !== false){
            //replace with new url
            $post->post_content = str_replace($old_url, $new_url, $post->post_content);

            $args = array(
                'ID'           => $post->ID,
                'post_content' => $post->post_content,
            );
           
            // Update the post into the database
            wp_update_post( $args );
        }
    }
}

// filter library if needed
add_filter( 'ajax_query_attachments_args', function($query){
    if(!empty($_REQUEST['query']['visibility'])){
        $visibility = $_REQUEST['query']['visibility'];
        $query['meta_query'] = [
            [
                'key'     => 'visibility',
                'value'   => $visibility,
                'compare' => '==',
            ]
        ];

        if($visibility == 'public'){
            $query['meta_query']['relation'] = 'OR';
            $query['meta_query'][] = [
                'key'     => 'visibility',
                'compare' => 'NOT EXISTS'
            ];
        }
    }

    return $query;
} );

// Make private by default if enabled
add_action( 'add_attachment', function ( $postId) { 
    $default    = SIM\get_module_option('content_filter', 'default_status');
    $path       = get_attached_file($postId);
    
    if($default == 'private' or strpos($path, '/private/') !== false ){
        update_metadata( 'post',  $postId, 'visibility', 'private' );
        
        // Move if not already in the private folder
        if(strpos($path, '/private/') === false ){
            move_attachment($postId, 'private');
        }
    }
});