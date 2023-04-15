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

				$mediaGallery	= new MediaGallery($types, $param['amount'], $categories, false, $param['page']);
				return $mediaGallery->loadMediaHTML($param['skipAmount'], $param['startIndex']);
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

				$categories	= $param['categories'];
				if(!empty($categories) && !is_array($categories)){
					$categories	= explode(',', $categories);
				}else{
					$categories	= [];
				}

				$mediaGallery	= new MediaGallery(explode(',', $param['types']), $param['amount'], $categories, false, 1, $param['search'], $param['color']);
				return $mediaGallery->loadMediaHTML();
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

				$mediaGallery	= new MediaGallery(explode(',', $param['types']), $param['amount'], $categories, false);
				$html			= $mediaGallery->loadMediaHTML();
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

	register_rest_route(
		RESTAPIPREFIX.'/media_gallery',
		'/show_media_gallery',
		array(
			'methods'				=> 'POST',
			'callback'				=> function(\WP_REST_Request $request){
				$param	= $request->get_params();

				if(empty($param['categories'])){
					$categories	= [];
				}elseif(!is_array($param['categories'])){
					$categories	= json_decode($param['categories']);
				}

				if(empty($param['types'])){
					$types	= [];
				}elseif(!is_array($param['types'])){
					$types	= json_decode($param['types']);
				}

				$mediaGallery	= new MediaGallery($types, $param['amount'], $categories, true, 1, '', $param['color']);
				return $mediaGallery->mediaGallery(trim($param['title']), $param['speed'], $param['desc']);
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'amount'		=> array('required'	=> true)
			)
		)
	);
} );