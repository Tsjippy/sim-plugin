<?php
namespace SIM\BULKCHANGE;
use SIM;

//Save a meta key via ajax
add_action ( 'wp_ajax_bulk_change_meta', function(){
	if(!is_numeric($_POST['user_id'])) wp_die('Invalid user id', 500);
	if(!isset($_POST['meta_key'])) wp_die('No meta_key given', 500);
	
	$userId 	= $_POST['user_id'];
	$metaKey 	= sanitize_text_field($_POST['meta_key']);
	$metaValue	= sanitize_text_field($_POST['value']);
	
	// Check if permissions to edit
	$user	= wp_get_current_user();
	//if($user->ID != $user_id and !in_array('user_management',$user->roles)) wp_die('No permission', 500);

	if (strpos($metaKey, '#') !== false){
		$metaKeyBase = explode('#',$metaKey)[0];
		$metaKeyName = explode('#',$metaKey)[1];
		
		$array = (array)get_user_meta($userId,$metaKeyBase,true);
		
		$array[$metaKeyName] = $metaValue;
		
		//Save in db
		update_user_meta($userId, $metaKeyBase, $array);
		
		$metaKey = $metaKeyName;
	}else{
		//Save in db
		update_user_meta($userId, $metaKey, $metaValue);
	}

	$metaKey = ucfirst(str_replace('_',' ',$metaKey));
	wp_die(json_encode(
		[
			'message'	=> "Saved $metaKey succesfully",
			'callback'	=> 'updated_table_value',
			'new_value'	=> $metaValue
			]
	));
});