<?php
namespace SIM\BULKCHANGE;
use SIM;

//Save a meta key via rest api
add_action( 'rest_api_init', function () {
	register_rest_route( 
		RESTAPIPREFIX.'/bulkchange', 
		'/bulk_change_meta_html', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\showBulkChangeForm',
			'permission_callback' 	=> '__return_true',
		)
	);

});