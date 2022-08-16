<?php
namespace SIM\BULKCHANGE;
use SIM;

//Save a meta key via rest api
add_action( 'rest_api_init', function () {
	register_rest_route( 
		RESTAPIPREFIX.'/bulkchange', 
		'/bulk_change_meta', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\bulkUpdateMeta',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'user_id'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'meta_key'		=> array(
					'required'	=> true
				),
				'value'		=> array(
					'required'	=> true
				),
			)
		)
	);
});

/**
 * Processes the meta value update
*/
function bulkUpdateMeta(){	
	$userId 	= $_POST['user_id'];
	$metaKey 	= sanitize_text_field($_POST['meta_key']);
	$metaValue	= sanitize_text_field($_POST['value']);
	
	// To do: Check if permissions to edit

	if (strpos($metaKey, '#') !== false){
		$metaKeyBase 			= explode('#', $metaKey)[0];
		$metaKeyName 			= explode('#', $metaKey)[1];
		
		$array 					= (array)get_user_meta($userId, $metaKeyBase, true);
		
		$array[$metaKeyName] 	= $metaValue;
		
		//Save in db
		update_user_meta($userId, $metaKeyBase, $array);
		
		$metaKey 				= $metaKeyName;
	}else{
		//Save in db
		update_user_meta($userId, $metaKey, $metaValue);
	}

	$metaKey = ucfirst(str_replace('_',' ',$metaKey));
	return [
		'message'	=> "Saved $metaKey succesfully",
		'callback'	=> 'updated_table_value',
		'new_value'	=> $metaValue
	];
}