<?php
namespace SIM\SIMNIGERIA;
use SIM;

add_action( 'rest_api_init', function () {
	//Route for notification messages
	register_rest_route( 
		'sim/v1', 
		'/verify_traveldate', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\verify_traveldate',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'userid'		=> array('required'	=> true),
				'departuredate'	=> array('required'	=> true),
			)
		)
	);
} );

function verify_traveldate( \WP_REST_Request $request ) {
	if (is_user_logged_in()) {
		$formbuilder	= new SIM\FORMS\Formbuilder();

		$formbuilder->datatype	= 'travel';

		$formbuilder->loadformdata();

		$formbuilder->get_submission_data();

		$formbuilder->submission_data;


		foreach($formbuilder->submission_data as $submission){
			$formresults	= unserialize($submission->formresults);

			if($formresults['departuredate1'] == $request['departuredate']){
				if($formresults['user_id'] == $request['userid']){
					return 'You already have a travel request on '.date('d F Y', strtotime($request['departuredate'])).'!';
				}elseif(in_array( $request['userid'] , $formresults['passengers'])){
					return $formresults['name'].' already submitted a travelrequest for '.date('d F Y', strtotime($request['departuredate'])).' including you as a passenger.' ;
				}
			}
		}
	}

	return false;
}