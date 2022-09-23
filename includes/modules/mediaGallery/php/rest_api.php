<?php
namespace SIM\MEDIAGALLERY;
use SIM;

add_action( 'rest_api_init', function () {	
	//load more media
	register_rest_route( 
		RESTAPIPREFIX.'/media_gallery', 
		'/load_more_media', 
		array(
			'methods'				=> 'POST',
			'callback'				=> function(\WP_REST_Request $request){
				$param	= $request->get_params();

				if(empty($param['types'])){
					$types	= ['image', 'video', 'audio'];
				}else{
					$types	= explode(',', $param['types']);
				}

				if(empty($param['categories'])){
					$categories	= [];
				}elseif(!is_array($param['categories'])){
					$categories	= explode(',', $param['categories']);
				}
				return loadMedia($param['amount'], $param['page'], $param['skipAmount'], $types, $categories, $param['startIndex']);
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'amount'		=> array('required'	=> true),
				'page'			=> array('required'	=> true),
			)
		)
	);

	//media search
	register_rest_route( 
		RESTAPIPREFIX.'/media_gallery', 
		'/media_search', 
		array(
			'methods'				=> 'POST',
			'callback'				=> function(\WP_REST_Request $request){
				$param	= $request->get_params();
				if(!is_array($param['categories'])){
					$param['categories']	= explode(',', $param['categories']);
				}
				return loadMedia($param['amount'], 1, false, explode(',', $param['types']), $param['categories'], 0, $param['search']);
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'amount'		=> array('required'	=> true),
				'search'		=> array('required'	=> true),
			)
		)
	);

	register_rest_route( 
		RESTAPIPREFIX.'/media_gallery', 
		'/change_cats', 
		array(
			'methods'				=> 'POST',
			'callback'				=> function(\WP_REST_Request $request){
				$param	= $request->get_params();

				if(empty($param['categories'])){
					$categories	= [];
				}elseif(!is_array($param['categories'])){
					$categories	= explode(',', $param['categories']);
				}

				$html=loadMedia($param['amount'], 1, false, explode(',', $param['types']), $categories);
				if(!$html){
					return "No media found";
				}
				return $html;
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'amount'		=> array('required'	=> true)
			)
		)
	);
} );