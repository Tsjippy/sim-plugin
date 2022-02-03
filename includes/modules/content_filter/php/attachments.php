<?php
namespace SIM;

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
            update_post_meta( $attachment_id, 'visibility', $visibility );

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
    update_post_meta($post_id, '_wp_attached_file', $sub_dir.$filename);
	
	//Open up the wp filesystem
	WP_Filesystem();
	global $wp_filesystem;
	
	//Move all the files to the private folder
	$files = glob("$base_dir/$base_name*");
	foreach($files as $file){
        $wp_filesystem->move($file, "$new_path/".basename ($file), true);
	}

    wp_generate_attachment_metadata($post_id, "$new_path/$filename");

    //update all posts where this is attached
    $posts = get_posts( [
        'post_type'     => get_post_types(),
        'post_status'   => 'publish',
        'numberposts'   => -1,
    ] );
    foreach($posts as $post){
        //replace any url with new urls for this attachment

        $old_url    = 'src="'.path_to_url(str_replace($filename, $base_name, $old_path));
        $new_url    = 'src="'.path_to_url("$new_path/$base_name");
        
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

//filter library if needed
add_filter( 'ajax_query_attachments_args', function($query){
    if(!empty($_REQUEST['query']['visibility'])){
        $query['meta_key']     = 'visibility';
        $query['meta_value']   = $_REQUEST['query']['visibility'];
        $query['meta_compare'] = '==';
    }

    return $query;
} );

if(!empty($GLOBALS['Modules']['private_content']['enable'])){
	//load js script to change media screen
	add_action( 'wp_enqueue_media', function(){
        global $StyleVersion;
		wp_enqueue_script('sim_library_script', IncludesUrl.'/modules/private_content/js/library.js', [], $StyleVersion);
	});
}