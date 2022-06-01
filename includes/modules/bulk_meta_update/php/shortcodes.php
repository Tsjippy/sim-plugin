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
			$html = bulkChangeUpload($a['key'], $a['folder']);
		//Normal meta key
		}else{
			$html = bulkchangeMeta($a['key'], explode(',', $a['roles']), $a['family']);
		}
		
		return $html;
	}
});