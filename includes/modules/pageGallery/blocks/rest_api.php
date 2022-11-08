<?php
namespace SIM\PAGEGALLERY;
use SIM;

add_action( 'rest_api_init', function () {

	// show page gallery
	register_rest_route(
		RESTAPIPREFIX.'/pagegallery',
		'/show_page_gallery',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function($wpRestRequest){
				$params			= $wpRestRequest->get_params();

				$categories		= json_decode($params['categories'], true);
				if(empty($categories)){
					$categories	= $params['categories'];
				}

				$postTypes		= json_decode($params['postTypes']);
				if(empty($postTypes)){
					$postTypes	= $params['postTypes'];
				}

				return pageGallery($params['title'], $postTypes, $params['amount'], $categories, $params['speed']);
			},
			'permission_callback' 	=> '__return_true',
		)
	);
} );

// Allow non-logged in use
add_filter('sim_allowed_rest_api_urls', function($urls){
	$urls[]		= RESTAPIPREFIX.'/pagegallery/show_page_gallery';

	return $urls;
});