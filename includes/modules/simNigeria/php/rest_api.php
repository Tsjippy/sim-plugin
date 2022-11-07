<?php
namespace SIM\SIMNIGERIA;
use SIM;

add_action( 'rest_api_init', function () {
	// Check for existing travel request
	register_rest_route( 
		RESTAPIPREFIX.'/sim_nigeria', 
		'/verify_traveldate', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\verifyTraveldate',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'userid'		=> array('required'	=> true),
				'departuredate'	=> array('required'	=> true),
			)
		)
	);

	// Update quota documents
	register_rest_route( 
		RESTAPIPREFIX.'/sim_nigeria', 
		'/update_visa_documents', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\updateVisaDocuments',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'quota_documents'		=> array('required'	=> true)
			)
		)
	);
} );

function verifyTraveldate( \WP_REST_Request $request ) {
	if (is_user_logged_in()) {
		$formBuilder	= new SIM\FORMS\DisplayFormResults();

		$formBuilder->formName	= 'travel';

		$formBuilder->getForm();

		$formBuilder->parseSubmissions();

		foreach($formBuilder->submissions as $submission){
			$formResults	= $submission->formresults;
			if($formResults['departuredate1'] == $request['departuredate']){
				if($formResults['user_id'] == $request['userid']){
					return 'You already have a travel request on '.date('d F Y', strtotime($request['departuredate'])).'!';
				}elseif(in_array( $request['userid'] , $formResults['passengers'])){
					return $formResults['name'].' already submitted a travelrequest for '.date('d F Y', strtotime($request['departuredate'])).' including you as a passenger.' ;
				}
			}
		}
	}

	return false;
}

function updateVisaDocuments(){
	$quotaDocuments = get_option('quota_documents');

	if(isset($_POST['quota_documents']['quotafiles'])){
		$quotaDocuments['quotafiles']	= $_POST['quota_documents']['quotafiles'];
	}else{
		array_merge($quotaDocuments,$_POST['quota_documents']);
	}
	update_option('quota_documents', $quotaDocuments);
	
	return "Updated quota documents succesfully";
}