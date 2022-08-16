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
			'methods' 				=> 'POST,GET',
			'callback' 				=> 	__NAMESPACE__.'\showFormBuilder',
			'permission_callback' 	=> '__return_true',
		)
	);

	// Get all forms
	register_rest_route( 
		'sim/v1/forms', 
		'/get_forms', 
		array(
			'methods' 				=> 'POST,GET',
			'callback' 				=> 	__NAMESPACE__.'\getAllForms',
			'permission_callback' 	=> '__return_true',
		)
	);

	 // Show form results
	register_rest_route( 
		'sim/v1/forms', 
		'/show_form_results', 
		array(
			'methods' 				=> 'POST,GET',
			'callback' 				=> 	__NAMESPACE__.'\showFormResults',
			'permission_callback' 	=> '__return_true',
		)
	);

	// Add new form table
	register_rest_route( 
		'sim/v1/forms', 
		'/add_form_table', 
		array(
			'methods' 				=> 'POST,GET',
			'callback' 				=> 	function(){
				$displayFormResults	= new DisplayFormResults();
				return $displayFormResults->insertInDb($_REQUEST['formid']);
			},
			'permission_callback' 	=> '__return_true',
		)
	);

	// Add new form table
	register_rest_route( 
		'sim/v1/forms', 
		'/missing_form_fields', 
		array(
			'methods' 				=> 'POST,GET',
			'callback' 				=> 	function($attributes){
				if($attributes instanceof \WP_REST_Request){
					$attributes	= $_REQUEST;
				}
				$result	= missingFormFields($attributes);
				if(empty($result)){
					return "No {$attributes['type']} fields found";
				}
				return $result;
			},
			'permission_callback' 	=> '__return_true',
		)
	);
});

function showFormBuilder($attributes){

	if($attributes instanceof \WP_REST_Request){
		if(!empty($_REQUEST['formname'])){
			$attributes = ['formname' => $_REQUEST['formname']];
		}elseif(!empty($_REQUEST['formid'])){
			$attributes = ['formid' => $_REQUEST['formid']];
		}else{
			return false;
		}
	}elseif(!isset($attributes['formname'])){
		return false;
	}
	
	$simForms = new SimForms();

	$html	= $simForms->determineForm($attributes);
	if(is_wp_error($html)){
		$html = $html->get_error_message();
	}

	return $html;
}

function showFormResults($attributes){

	if($attributes instanceof \WP_REST_Request){
		if(!empty($_REQUEST['formid'])){
			$attributes = [
				'formid' 	=> $_REQUEST['formid'],
				'id'		=> $_REQUEST['id']
			];

			if(isset($_REQUEST['archived'])){
				$attributes['archived']	= $_REQUEST['archived'];
			}

			if(isset($_REQUEST['onlyown'])){
				$attributes['onlyOwn']	= $_REQUEST['onlyown'];
			}
		}else{
			return false;
		}
	}elseif(!isset($attributes['formid'])){
		return false;
	}

	if(isset($attributes['tableid'])){
		$attributes['id']	= $attributes['tableid'];
	}
	
	$displayFormResults = new DisplayFormResults();
    $displayFormResults->processAtts($attributes);
	$html	= $displayFormResults->showFormresultsTable();
	
	if(is_wp_error($html)){
		$html = $html->get_error_message();
	}

	return $html;
}

function getAllForms(){
	$simForms	= new SimForms();
	$simForms->getForms();

	return $simForms->forms;
}