<?php
namespace SIM;

add_action( 'rest_api_init',  __NAMESPACE__.'\blockRestApiInit');
function blockRestApiInit() {
	// show post children
	register_rest_route( 
		RESTAPIPREFIX, 
		'/show_children', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\displayChildren',
			'permission_callback' 	=> '__return_true',
		)
	);
}

function displayChildren($WP_REST_Request){
	return displayChildren($WP_REST_Request->get_params());
}