<?php
namespace SIM\CONTENTFILTER;
use SIM;

//add public/private radio buttons to attachment page
add_filter( 'attachment_fields_to_edit', function($formFields, $post ){
    $fieldValue = get_post_meta( $post->ID, 'visibility', true );

    if($fieldValue == 'public' || empty($fieldValue)){
        $checked    = 'checked';
    }

    $html    = "<div>";
        $html   .= "<input $checked style='width: initial' type='radio' name='attachments[{$post->ID}][visibility]' value='public'>";
        $html   .= " Public";
    $html   .= "</div>";

    $checked    = '';
    if($fieldValue == 'private'){
        $checked    = 'checked';
    }

    $html   .= "<div>";
        $html   .= "<input $checked style='width: initial' type='radio' name='attachments[{$post->ID}][visibility]' value='private'>";
        $html   .= " Private";
    $html   .= "</div>";

    $formFields['visibility'] = array(
        'value' => $fieldValue ? $fieldValue : '',
        'label' => __( 'Visibility' ),
        'input' => 'html',
        'html'  =>  $html
    );

    return $formFields;
},10,2);

//change visibility of an attachment
add_action( 'edit_attachment', function($attachmentId){
    if ( isset( $_REQUEST['attachments'][$attachmentId]['visibility'] ) ) {
        $visibility     = $_REQUEST['attachments'][$attachmentId]['visibility'];

        //check if changed
        $prevVis        = get_post_meta( $attachmentId, 'visibility',true);

        if($prevVis != $visibility){
            do_action('sim_before_visibility_change', $attachmentId, $visibility);

            //update post meta
            update_metadata( 'post', $attachmentId, 'visibility', $visibility );

            //Check if moving to public or to private
            if($visibility == 'public'){
                $targetPath = '';
            }else{
                $targetPath = 'private';
            }
            moveAttachment($attachmentId, $targetPath);
        }
    }
} );

/**
 * Move attachment to other folder
 * @param  	int 	$postId		    WP_Post id
 * @param  	string	$subDir   	    The sub folder where the files should be uploaded
*/
function moveAttachment($postId, $subDir, $generate=true){
    if(empty($subDir)){
        $newPath   = wp_upload_dir()['basedir'];
    }else{
        $newPath   = wp_upload_dir()['basedir']."/$subDir";
        $subDir     = rtrim($subDir,"/").'/';
    }

	//get the file location of the attachment
	$oldPath   = get_attached_file($postId);
    if(!file_exists($oldPath)){
        return false;
    }

    $filename   = basename ($oldPath);
    $extension  = '.'.pathinfo($oldPath, PATHINFO_EXTENSION);
    $baseDir    = dirname($oldPath);
    $baseName   = str_replace(["-scaled", $extension], "", $filename);

	//Update the location in the db
	update_attached_file($postId, "$newPath/$filename");

    //update main path
    update_metadata( 'post', $postId, '_wp_attached_file', $subDir.$filename);
	
	//Move all the files to the private folder
	$files = glob("$baseDir/$baseName*");
	foreach($files as $file){
        rename($file, "$newPath/".basename ($file));
	}

    if($generate){
        if(!function_exists('wp_generate_attachment_metadata')){
            require_once(ABSPATH.'/wp-admin/includes/image.php');
        }
        wp_generate_attachment_metadata($postId, "$newPath/$filename");
    }

    SIM\urlUpdate(str_replace($filename, $baseName, $oldPath), "$newPath/$baseName");
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

/**
 * Make private by default if enabled
 */

// Move the file to the private dir
add_filter('wp_handle_upload', function($file){
    $default    = SIM\getModuleOption(MODULE_SLUG, 'default_status');

    if($default == 'private' && strpos($file['file'], 'private') === false){
        $newPath    = wp_upload_dir()['basedir'].'/private/'.basename($file['file']);
        $newUrl     = SIM\pathToUrl($newPath);

        rename($file['file'], $newPath);

        $file['file']   = $newPath;
        $file['url']    = $newUrl;
    }

    return $file;
});

// Set the visibility key
add_action( 'add_attachment', function ( $postId) {
    $default    = SIM\getModuleOption(MODULE_SLUG, 'default_status');

    if($default == 'private'){
        update_metadata( 'post',  $postId, 'visibility', 'private' );
    }
});