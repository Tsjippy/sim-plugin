<?php
namespace SIM\FILEUPLOAD;
use SIM;

add_action( 'rest_api_init', __NAMESPACE__.'\uploadRestApiInit');
function uploadRestApiInit(){
	//Route for first names
	register_rest_route(
		RESTAPIPREFIX,
		'/remove-document',
		array(
			'methods'				=> 'POST',
			'callback'				=> __NAMESPACE__.'\removeDocument',
			'permission_callback' 	=> '__return_true',
            'args'					=> array(
				'url'		=> array(
					'required'	=> true,
                    'validate_callback' => __NAMESPACE__.'\validateUrl'
				)
			)
		)
	);
}

function validateUrl($param){
    // File should be in the uploads folder or a sub folder
    return str_contains($param, 'wp-content/uploads');
}

function removeDocument(){

    if(!empty($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'file-delete')){
        return new \WP_Error('file uploader', 'Please reload the page and try again');
    }

    if(empty($url)){
        return false;
    }

    $path = ABSPATH.wp_unslash($_POST['url']);

    if(isset($_POST['user-id'])){
        $userId = sanitize_text_field($_POST["user-id"]);
    }

    if(isset($_POST['metakey'])){
        $metaKey        = sanitize_text_field($_POST['metakey']);
        $metaKeys 		= str_replace(']', '', explode('[', $metaKey));
        $baseMetaKey 	= $metaKeys[0];
        unset($metaKeys[0]);
    }
    
    //remove the file
    if(isset($_POST['libraryid']) && is_numeric($_POST['libraryid'])){
        wp_delete_attachment($_POST['libraryid']);
    }else{
        wp_delete_file($path);
    }
    
    //Remove the path from db 
    if(is_numeric($userId)){
        //Get document array from db
        $documentsArray = get_user_meta( $userId, $baseMetaKey,true);
    //Generic document
    }else{
        //get documents array from db
        $documentsArray = get_option($baseMetaKey);
    }
    
    //remove from array
    if(is_array($metaKeys) && !empty($metaKeys)){
        SIM\removeFromNestedArray($documentsArray, $metaKeys);
    }else{
        $documentsArray = '';
    }
        
    //Personnal document
    if(is_numeric($userId)){
        //Store the array in db
        update_user_meta( $userId, $baseMetaKey, $documentsArray);
    //Generic document
    }else{
        //Save it in db
        update_option($baseMetaKey, $documentsArray);
    }
    
    return "File successfully removed";
}