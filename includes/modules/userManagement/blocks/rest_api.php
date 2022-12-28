<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action( 'rest_api_init', function () {
	// show reminders
	register_rest_route(
		RESTAPIPREFIX.'/usermanagement',
		'/show_reminders',
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\expiryWarnings',
			'permission_callback' 	=> '__return_true',
		)
	);
} );