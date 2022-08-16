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

	// form builder
	register_rest_route( 
		'sim/v1/forms', 
		'/form_builder', 
		array(
			'methods' 				=> 'GET,POST',
			'callback' 				=> 	__NAMESPACE__.'\showFormBuilder',
			'permission_callback' 	=> '__return_true',
		)
	);

	
});

function showFormBuilder($attributes){

	if($attributes instanceof \WP_REST_Request){
		if(!empty($_REQUEST['name'])){
			$attributes = ['formname' => $_REQUEST['name']];
		}elseif(!empty($_REQUEST['formid'])){
			$attributes = ['formid' => $_REQUEST['formid']];
		}else{
			return false;
		}
	}elseif(!isset($attributes['name'])){
		return false;
	}
	
	$simForms = new SimForms();

	return $simForms->determineForm($attributes);
}