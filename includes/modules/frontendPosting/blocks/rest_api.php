<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

add_action( 'rest_api_init', function () {
	// show post list
	register_rest_route( 
		RESTAPIPREFIX.'/frontendposting',  
		'/your_posts', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\yourPosts',
			'permission_callback' 	=> '__return_true',
		)
	);

	// show pening pages
	register_rest_route( 
		RESTAPIPREFIX.'/frontendposting',  
		'/pending_pages', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\pendingPages',
			'permission_callback' 	=> '__return_true',
		)
	);
} );