<?php
namespace SIM\USERPAGE;
use SIM;

add_action( 'rest_api_init', function () {
	// show schedules
	register_rest_route( 
		RESTAPIPREFIX.'/userpage', 
		'/linked_user_description', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> function(){
				return linkedUserDescription($_REQUEST);
			},
			'permission_callback' 	=> '__return_true',
		)
	);
} );