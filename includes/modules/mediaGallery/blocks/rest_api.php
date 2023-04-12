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

function displayMediaGallery($wpRestRequest) {

	$args = wp_parse_args($wpRestRequest->get_params(), array(
		'categories'	=> []
	));

	$mediaGallery   = new MediaGallery(['image'], 20, $args['categories'], false, 1, '', $args['color']);

    return $mediaGallery->filterableMediaGallery();
}