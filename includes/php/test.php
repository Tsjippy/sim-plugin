<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $Modules;


	return 'e-mail send';
});

add_action( 'rest_api_init', function () {
	// add element to form
	register_rest_route( 
		'si/v2', 
		'/sendtest', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> 	function(){wp_mail('enharmsen@gmail.com' , 'test e-mail', 'test');},
			'permission_callback' 	=> '__return_true',
		)
	);
});