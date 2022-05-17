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
				$meta_key		= $meta_key_base.'['.$meta_key_name.']';				
			}else{
				$meta_key_base 	= $a['key'];
				$meta_key_name 	= $a['key'];
				$meta_key		= "";
			}
			$users = SIM\getUserAccounts();
			$html = '';
			foreach($users as $user){
				$value 	= get_user_meta( $user->ID, $meta_key_base, true );

				if (strpos($a['key'], '#') !== false){
					$value			= $value[$meta_key_name];
				}
				
				//Only show if value not set
				if(empty($value)){
					$html .= "<div style='margin-top:50px;'>";
						$html .= "<strong>{$user->display_name}</strong>";
						$uploader = new SIM\Fileupload($user->ID, $meta_key, $a['folder'], true, $meta_key);
						$html .= $uploader->getUploadHtml();
					$html .= '</div>';
				}
			}
		//Normal meta key
		}else{
			$html = bulkchangeMeta($a['key'], explode(',', $a['roles']), $a['family']);
		}
		
		return $html;
	}
});