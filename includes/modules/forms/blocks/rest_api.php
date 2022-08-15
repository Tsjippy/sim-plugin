<?php
namespace SIM\FORMS;
use SIM;

add_action( 'rest_api_init', function () {
	// add element to form
	register_rest_route( 
		'sim/v1/forms', 
		'/form_selector', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> 	__NAMESPACE__.'\showFormSelector',
			'permission_callback' 	=> '__return_true',
		)
	);
});