<?php
namespace SIM\BANKING;
use SIM;

add_action( 'rest_api_init', function () {
	// add element to form
	register_rest_route( 
		RESTAPIPREFIX.'/banking', 
		'/get_statements', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> 	__NAMESPACE__.'\showStatements',
			'permission_callback' 	=> '__return_true',
		)
	);
});
