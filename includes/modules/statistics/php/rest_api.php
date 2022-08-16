<?php
namespace SIM\STATISTICS;
use SIM;

// Allow rest api urls for non-logged in users
add_filter('sim_allowed_rest_api_urls', function($urls){
    $urls[]	= RESTAPIPREFIX.'/statistics/add_page_view';

    return $urls;
});

add_action( 'rest_api_init', function () {
	// Check for existing travel request
	register_rest_route( 
		RESTAPIPREFIX.'/statistics', 
		'/add_page_view', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> function(){
				$statistics	= new Statistics();
				$statistics->addPageView();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'url'		=> array('required'	=> true),
			)
		)
	);
} );