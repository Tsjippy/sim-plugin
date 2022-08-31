<?php
namespace SIM\MEDIAGALLERY;
use SIM;

add_action( 'rest_api_init', function () {
	// show schedules
	register_rest_route( 
		RESTAPIPREFIX.'/mediagallery', 
		'/show', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\displayMediaGallery',
			'permission_callback' 	=> '__return_true',
		)
	);
} );

function displayMediaGallery($attributes) {

	$args = wp_parse_args($attributes->get_params(), array(
		'categories'	=> []
	));

	return showMediaGallery($args);
}