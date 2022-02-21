<?php
namespace SIM;

//Shortcode for bulk updaitng meta fields
add_shortcode('bulk_update_meta', function ($atts){
	$a = shortcode_atts( array(
        'inversed' 	=> false,
		'roles' 	=> "All",
		'key'		=> '',
		'folder'	=> '',
		'family'	=> false,
    ), $atts );
	
	if($a['key'] != ''){
		//Document
		if($a['folder'] != ''){				
			if (strpos($a['key'], '#') !== false){
				$meta_key_base = explode('#',$a['key'])[0];
				$meta_key_name = explode('#',$a['key'])[1];
				
			}else{
				$meta_key_base = $a['key'];
				$meta_key_name = $a['key'];
			}
			$users = get_user_accounts();
			$html = '';
			foreach($users as $user){
				$value 	= get_user_meta( $user->ID, $meta_key_base, true );
				
				//Only show if value not set
				if(!is_array($value) or !isset($value[$meta_key_name]) or $value[$meta_key_name] == '' or count($value[$meta_key_name])==0){
					$html .= "<div style='margin-top:50px;'><strong>{$user->display_name}</strong>";
					$html .= document_upload($user->ID, $meta_key_name,$a['folder'],$meta_key_base).'</div>';
				}
			}
		//Normal meta key
		}else{
			$html = bulkchange_meta($a['key'],explode(',',$a['roles']),$a['family']);
		}
		
		return $html;
	}
});

function bulkchange_meta($meta_key, $allowed_roles, $return_family=false){
	//Get the current user
	$user = wp_get_current_user();
	
	if (strpos($meta_key, '#') !== false){
		$meta_key_base = explode('#',$meta_key)[0];
		$meta_key_name = explode('#',$meta_key)[1];
		
	}else{
		$meta_key_base = $meta_key;
		$meta_key_name = $meta_key;
	}
			
	$display_name = ucfirst(str_replace('_',' ',$meta_key_name));
	
	//User is logged in and has the correct role
	if($user->ID != 0 and array_intersect($allowed_roles, $user->roles ) ) {
		//Load js
		wp_enqueue_script('simnigeria_table_script');
		
		$html = "
			<h2 id='{$meta_key}_table_title' class='table_title'>$display_name</h2>
			<table class='table' data-action='bulk_change_meta'>
				<thead class='table-head'>
					<tr>
						<th class='dsc'>Name</th>
						<th id='$meta_key'>$display_name</th>
					</tr>
				</thead>";
		
		//Get all users who are non-local nigerias, sort by last name
		foreach(get_user_accounts($return_family) as $user){
			$value 	= get_user_meta( $user->ID, $meta_key_base, true );
			
			//Check if the value is an array
			if(is_array($value))	$value 	= $value[$meta_key_name];
			
			//Now the value should not be an array
			if(is_array($value)) return 'please provide single value not an array';
				
			if($value == "") $value = "Click to update";			
			
			//Add html
			$html .= "<tr class='table-row' data-meta_key='$meta_key'>
						<td>$user->display_name</td>
						<td class='edit' data-user_id='$user->ID'>$value</td>
					  </tr>";
		}
		$html .= '</table>';
		return $html;	
	}else{
		return '<p>You do not have permission to view this</p>';
	}
}

//Save a meta key via ajax
add_action ( 'wp_ajax_bulk_change_meta', function(){
	if(!is_numeric($_POST['user_id'])) wp_die('Invalid user id', 500);
	if(!isset($_POST['meta_key'])) wp_die('No meta_key given', 500);
	
	$user_id 	= $_POST['user_id'];
	$meta_key 	= sanitize_text_field($_POST['meta_key']);
	$meta_value	= sanitize_text_field($_POST['value']);
	
	// Check if permissions to edit
	$user	= wp_get_current_user();
	//if($user->ID != $user_id and !in_array('user_management',$user->roles)) wp_die('No permission', 500);

	if (strpos($meta_key, '#') !== false){
		$meta_key_base = explode('#',$meta_key)[0];
		$meta_key_name = explode('#',$meta_key)[1];
		
		$array = (array)get_user_meta($user_id,$meta_key_base,true);
		
		$array[$meta_key_name] = $meta_value;
		
		//Save in db
		update_user_meta($user_id, $meta_key_base, $array);
		
		$meta_key = $meta_key_name;
	}else{
		//Save in db
		update_user_meta($user_id, $meta_key, $meta_value);
	}

	$meta_key = ucfirst(str_replace('_',' ',$meta_key));
	wp_die(json_encode(
		[
			'message'	=> "Saved $meta_key succesfully",
			'callback'	=> 'updated_table_value',
			'new_value'	=> $meta_value
			]
	));
});
