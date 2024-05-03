<?php
namespace SIM\FILEUPLOAD;
use SIM;

add_action( 'rest_api_init', function () {
	//Route for first names
	register_rest_route(
		RESTAPIPREFIX,
		'/remove_document',
		array(
			'methods'				=> 'POST',
			'callback'				=> __NAMESPACE__.'\removeDocument',
			'permission_callback' 	=> '__return_true',
            'args'					=> array(
				'url'		=> array(
					'required'	=> true,
                    'validate_callback' => function($param){
                        // File should be in the uploads folder or a sub folder
                        return str_contains($param, 'wp-content/uploads');
                    }
				)
			)
		)
	);
});

function removeDocument(){
    $path = ABSPATH.$_POST['url'];

    if(isset($_POST['userid'])){
        $userId = sanitize_text_field($_POST["userid"]);
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
        unlink($path);
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