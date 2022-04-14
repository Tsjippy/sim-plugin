<?php
namespace SIM\FORMS;
use SIM;

add_action( 'rest_api_init', function () {
	//save_table_prefs
	register_rest_route( 
		'sim/v1/forms', 
		'/save_table_prefs', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\save_table_prefs',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'column_name'	=> array('required'	=> true),
			)
		)
	);

	//delete_table_prefs
	register_rest_route( 
		'sim/v1/forms', 
		'/delete_table_prefs', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\delete_table_prefs',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
			)
		)
	);

	//save_column_settings
	register_rest_route( 
		'sim/v1/forms', 
		'/save_column_settings', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\saveColumnSettings',
			'permission_callback' 	=> function(){
				$formsTable		= new FormTable();
				return $formsTable->editrights;
			},
			'args'					=> array(
				'shortcode_id'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'column_settings'		=> array(
					'required'	=> true,
				),
			)
		)
	);

	//delete_table_prefs
	register_rest_route( 
		'sim/v1/forms', 
		'/save_table_settings', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\saveTableSettings',
			'permission_callback' 	=> function(){
				$formsTable		= new FormTable();
				return $formsTable->editrights;
			},
			'args'					=> array(
				'shortcode_id'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'table_settings'		=> array(
					'required'	=> true,
				),
			)
		)
	);

	//remove submission
	register_rest_route( 
		'sim/v1/forms', 
		'/remove_submission', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\removeSubmission',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'submissionid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	//remove submission
	register_rest_route( 
		'sim/v1/forms', 
		'/archive_submission', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\archiveSubmission',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'submissionid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// edit value
	register_rest_route( 
		'sim/v1/forms', 
		'/edit_value', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\editValue',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'submissionid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'fieldname'		=> array(
					'required'	=> true,
				),
				'newvalue'		=> array(
					'required'	=> true,
				),
			)
		)
	);

	//get_input_html
	register_rest_route( 
		'sim/v1/forms', 
		'/get_input_html', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\get_input_html',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'fieldname'		=> array(
					'required'	=> true,
				),
				'submissionid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
			)
		)
	);
} );

function save_table_prefs( \WP_REST_Request $request ) {
	if (is_user_logged_in()) {
		$columnName		= $request['column_name'];

		$user_id		= get_current_user_id();
		$hidden_columns	= (array)get_user_meta($user_id, 'hidden_columns_'.$request['formid'], true);	

		$hidden_columns[$columnName]	= 'hidden';

		update_user_meta($user_id, 'hidden_columns_'.$request['formid'], $hidden_columns);	

		return 'Succesfully updated column settings';
	}else{
		return new \WP_Error('Error','You are not logged in');
	}
}

function delete_table_prefs( \WP_REST_Request $request ) {
	if (is_user_logged_in()) {
		$user_id		= get_current_user_id();
		delete_user_meta($user_id, 'hidden_columns_'.$request['formid']);	

		return 'Succesfully deleted column settings';
	}else{
		return new \WP_Error('error','You are not logged in');
	}
}

function saveColumnSettings(){
	global $wpdb;
	
	//copy edit right roles to view right roles
	$settings = $_POST['column_settings'];
	foreach($settings as $key=>$setting){
		//if there are edit rights defined
		if(is_array($setting['edit_right_roles'])){
			//create view array if it does not exist
			if(!is_array($setting['view_right_roles'])) $setting['view_right_roles'] = [];
			
			//merge and save
			$settings[$key]['view_right_roles'] = array_merge($setting['view_right_roles'],$setting['edit_right_roles']);
		}
	}
	
	$formTable	= new FormTable();
	$wpdb->update($formTable->shortcodetable, 
		array(
			'column_settings'	 	=> maybe_serialize($settings)
		), 
		array(
			'id'		=> $_POST['shortcode_id'],
		),
	);
	
	if($wpdb->last_error !== ''){
		return new \WP_Error('db error', $wpdb->print_error());
	}

	return "Succesfully saved your column settings";	
}

function saveTableSettings(){
	global $wpdb;
		
	//update table settings
	$table_settings = $_POST['table_settings'];

	$formTable	= new FormTable();
	
	$wpdb->update($formTable->shortcodetable, 
		array(
			'table_settings'	 	=> maybe_serialize($table_settings)
		), 
		array(
			'id'		=> $_POST['shortcode_id'],
		),
	);
	
	//also update form setings if needed
	$form_settings = $_POST['form_settings'];
	if(is_array($form_settings) and is_numeric($_POST['formid'])){
		$formTable->loadformdata($_POST['formid']);
		
		//update existing
		foreach($form_settings as $key=>$setting){
			$formTable->formdata->settings[$key] = $setting;
		}
		
		//save in db
		$wpdb->update($formTable->table_name, 
			array(
				'settings' 	=> maybe_serialize($formTable->formdata->settings)
			), 
			array(
				'id'		=> $_POST['formid'],
			),
		);
	}
	
	if($wpdb->last_error !== ''){
		return new \WP_Error('db error', $wpdb->print_error());
	}
	
	return "Succesfully saved your table settings";
}

function removeSubmission(){
	global $wpdb;

	$formTable	= new FormTable();
	
	$result = $wpdb->delete(
		$formTable->submissiontable_name, 
		array(
			'id'		=> $_POST['submissionid']
		)
	);
	
	if($result === false){
		return new \WP_Error('db error', "Removal failed"); 
	}
	return "Entry with id {$formTable->submission_id} succesfully removed";
}

// Archive or unarchive a (sub)submission
function archiveSubmission(){
	$formTable	= new FormTable();
	$formTable->loadformdata($_POST['formid']);
	$formTable->submission_id	= $_POST['submissionid'];

	$formTable->get_submission_data(null, $formTable->submission_id);

	$action	= $_POST['action'];

	if($action	== 'archive'){
		$archive = true;
	}else{
		$archive = false;
	}

	if(is_numeric($_POST['subid'])){
		$sub_id						= $_POST['subid'];
		$message					= "Entry with id {$formTable->submission_id} and subid $sub_id succesfully {$action}d";
		$splitfield					= $formTable->formdata->settings['split'];
		
		$formTable->formresults[$splitfield][$sub_id]['archived'] = $archive;
		
		//check if all subfields are archived or empty
		$formTable->check_if_all_archived($formTable->formresults[$splitfield]);
	}else{
		$message					= "Entry with id {$formTable->submission_id} succesfully {$action}d";
		$formTable->update_submission_data($archive);
	}
	
	return $message;
}

function get_input_html(){
	$formTable	= new FormTable();
	$formTable->loadformdata($_POST['formid']);
	
	foreach ($formTable->form_elements as $key=>$element){
		if(str_replace('[]','',$element->name) == $_POST['fieldname']){
			//Load default values for this element
			$element_html = $formTable->get_element_html($element);
			
			return $element_html;
		}
	}
	
	return new \WP_Error('No element found', "No element found with id '{$_POST['fieldname']}'");
}

function editValue(){
	$formTable	= new FormTable();
		
	$formTable->loadformdata($_POST['formid']);	
	$formTable->submission_id		= $_POST['submissionid'];
	
	$formTable->get_submission_data(null, $formTable->submission_id);
		
	//update an existing entry
	$fieldName 		= sanitize_text_field($_POST['fieldname']);
	$newValue 		= sanitize_text_field($_POST['newvalue']);
	
	$sub_id			= $_POST['subid'];
	//Update the current value
	if(is_numeric($sub_id)){
		$field_main_name	= $formTable->formdata->settings['split'];
		$pattern = "/$field_main_name\[[0-9]\]\[([^\]]*)\]/i";
		if(preg_match($pattern, $fieldName,$matches)){
			$formTable->formresults[$field_main_name][$sub_id][$matches[1]]	= $newValue;
			$message = "Succesfully updated '{$matches[1]}' to $newValue";
		}else{
			return new \WP_Error('No element founc', "Could not find $fieldName");
		}
	}else{
		$formTable->formresults[$fieldName]	= $newValue;
		$message = "Succesfully updated '$fieldName' to $newValue";
	}
	
	$formTable->update_submission_data();
	
	//send email if needed
	$formTable->send_email('fieldchanged');
	
	//send message back to js
	return [
		'message'			=> $message,
		'newvalue'			=> $formTable->transform_inputdata($newValue, $fieldName),
	];
}