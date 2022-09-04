<?php
namespace SIM;

add_action( 'rest_api_init', function () {
	// show post children
	register_rest_route( 
		RESTAPIPREFIX, 
		'/show_children', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function($WP_REST_Request){
				return displayChildren($WP_REST_Request->get_params());
			},
			'permission_callback' 	=> '__return_true',
		)
	);
} );