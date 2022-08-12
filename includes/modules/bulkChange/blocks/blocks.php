<?php
namespace SIM\BULKCHANGE;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/bulk-change/build', 
		array(
			'render_callback' => __NAMESPACE__.'\showBulkChangeForm',
		)
	);
});

function showBulkChangeForm($attributes){
	if($attributes instanceof \WP_REST_Request){
		$attributes	= $_GET;
	}

	$args = wp_parse_args($attributes, array(
		'roles' 	=> "All",
		'key'		=> '',
		'folder'	=> '',
		'family'	=> false,
	));
	
	if($args['key'] != ''){
		//Document
		if($args['folder'] != ''){				
			$html = bulkChangeUpload($args['key'], $args['folder']);
		//Normal meta key
		}else{
			$html = bulkchangeMeta($args['key'], $args['roles'], $args['family']);
		}
		
		return $html;
	}

	return 'Define which meta key you want to update items for...';
}