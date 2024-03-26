<?php
namespace SIM\FORMS;
use SIM;

add_action( 'rest_api_init', function () {
	// add element to form
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/form_selector',
		array(
			'methods' 				=> 'GET',
			'callback' 				=> 	__NAMESPACE__.'\showFormSelector',
			'permission_callback' 	=> '__return_true',
		)
	);

	// form builder
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/form_builder',
		array(
			'methods' 				=> 'POST,GET',
			'callback' 				=> 	__NAMESPACE__.'\showFormBuilder',
			'permission_callback' 	=> '__return_true',
		)
	);

	// Get all forms
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/get_forms',
		array(
			'methods' 				=> 'POST,GET',
			'callback' 				=> 	__NAMESPACE__.'\getAllForms',
			'permission_callback' 	=> '__return_true',
		)
	);

	 // Show form results
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/show_form_results',
		array(
			'methods' 				=> 'POST,GET',
			'callback' 				=> 	__NAMESPACE__.'\showFormResults',
			'permission_callback' 	=> '__return_true',
		)
	);

	// Add new form table
	register_rest_route(
		RESTAPIPREFIX.'/forms',
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
		RESTAPIPREFIX.'/forms',
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
	$isRest	= false;
	if($attributes instanceof \WP_REST_Request){
		$isRest	= true;
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
	
	if(!$isRest){
		return [
			'html'	=> $html,
		];
	}

	do_action('wp_enqueue_scripts');

	wp_enqueue_style('sim_forms_style');
	wp_enqueue_style( 'sim_formtable_style');
	wp_enqueue_script( 'sim_formbuilderjs');

	wp_enqueue_editor();

	\_WP_Editors::enqueue_scripts();
	wp_enqueue_editor();
	ob_start();
	\_WP_Editors::editor_js();
	wp_print_scripts(["sim_formbuilderjs"]);
	print_footer_scripts();
	$js	= ob_get_clean();

	do_action('wp_enqueue_style');
	ob_start();
	wp_print_styles();
	$css	= ob_get_clean();

	return [
		'html'	=> $html,
		'js'	=> $js,
		'css'	=> $css
	];
}

function showFormResults($attributes){

 	if($attributes instanceof \WP_REST_Request){
		if(!empty($_REQUEST['formid'])){
			$attributes = [
				'formid' 		=> $_REQUEST['formid'],
				'shortcodeid'	=> $_REQUEST['shortcodeid']
			];

			if(isset($_REQUEST['archived'])){
				$attributes['archived']	= $_REQUEST['archived'];
			}

			if(isset($_REQUEST['onlyown'])){
				$attributes['onlyOwn']	= $_REQUEST['onlyown'];
			}

			if(isset($_REQUEST['all'])){
				$attributes['all']	= $_REQUEST['all'];
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
