<?php
namespace SIM\MEDIAGALLERY;
use SIM;

add_action( 'rest_api_init', function () {	
	//Route for first names
	register_rest_route( 'sim/v1', '/load_more_media', array(
		'methods'				=> 'POST',
		'callback'				=> __NAMESPACE__.'\loadMoreMedia',
		'permission_callback' 	=> '__return_true',
		'args'					=> array(
			'amount'		=> array('required'	=> true),
			'page'			=> array('required'	=> true),
		)
		)
	);

	register_rest_route( 'sim/v1', '/media_search', array(
		'methods'				=> 'POST',
		'callback'				=> __NAMESPACE__.'\mediaSearch',
		'permission_callback' 	=> '__return_true',
		'args'					=> array(
			'amount'		=> array('required'	=> true),
			'search'		=> array('required'	=> true),
		)
	));
} );

//Function to return the first name of a user with a certain phone number
function loadMoreMedia(\WP_REST_Request $request ) {
	if (is_user_logged_in()){
		$param	= $request->get_params();
		return loadMedia($param['amount'], $param['page'], $param['skipAmount'], explode(',', $param['types']), $param['startIndex']);
	}
}

function mediaSearch(\WP_REST_Request $request ) {
	if (is_user_logged_in()){
		$param	= $request->get_params();
		return loadMedia($param['amount'], 1, false, explode(',', $param['types']), 0, $param['search']);
	}
}