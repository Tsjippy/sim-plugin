<?php
namespace SIM\MEDIAGALLERY;
use SIM;

add_action( 'rest_api_init', function () {	
	//Route for first names
	register_rest_route( 
		'sim/v1/media_gallery', 
		'/load_more_media', 
		array(
			'methods'				=> 'POST',
			'callback'				=> function(\WP_REST_Request $request){
				$param	= $request->get_params();
				return loadMedia($param['amount'], $param['page'], $param['skipAmount'], explode(',', $param['types']), $param['startIndex']);
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'amount'		=> array('required'	=> true),
				'page'			=> array('required'	=> true),
			)
		)
	);

	register_rest_route( 
		'sim/v1/media_gallery', 
		'/media_search', 
		array(
			'methods'				=> 'POST',
			'callback'				=> function(\WP_REST_Request $request){
				$param	= $request->get_params();
				return loadMedia($param['amount'], 1, false, explode(',', $param['types']), 0, $param['search']);
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'amount'		=> array('required'	=> true),
				'search'		=> array('required'	=> true),
			)
		)
	);
} );