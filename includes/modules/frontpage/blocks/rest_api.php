<?php
namespace SIM\FRONTPAGE;
use SIM;

add_action( 'rest_api_init', function () {
	// show displayname
	register_rest_route( 
		RESTAPIPREFIX.'/frontpage', 
		'/show_display_name', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\displayName',
			'permission_callback' 	=> '__return_true',
		)
	);

	// show login count
	register_rest_route( 
		RESTAPIPREFIX.'/frontpage', 
		'/show_login_count', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\loginCount',
			'permission_callback' 	=> '__return_true',
		)
	);

	// show welcome_message
	register_rest_route( 
		RESTAPIPREFIX.'/frontpage', 
		'/show_welcome_message', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\welcomeMessage',
			'permission_callback' 	=> '__return_true',
		)
	);
} );