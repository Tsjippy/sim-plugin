<?php
namespace SIM\BULKCHANGE;
use SIM;

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
			$users = SIM\get_user_accounts();
			$html = '';
			foreach($users as $user){
				$value 	= get_user_meta( $user->ID, $meta_key_base, true );
				
				//Only show if value not set
				if(!is_array($value) or !isset($value[$meta_key_name]) or $value[$meta_key_name] == '' or count($value[$meta_key_name])==0){
					$html .= "<div style='margin-top:50px;'><strong>{$user->display_name}</strong>";
					$html .= SIM\document_upload($user->ID, $meta_key_name,$a['folder'],$meta_key_base).'</div>';
				}
			}
		//Normal meta key
		}else{
			$html = bulkchangeMeta($a['key'],explode(',',$a['roles']),$a['family']);
		}
		
		return $html;
	}
});