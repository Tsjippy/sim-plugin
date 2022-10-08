<?php
namespace SIM\FORMS;
use SIM;

add_action( 'rest_api_init', function () {
	//save_table_prefs
	register_rest_route( 
		RESTAPIPREFIX.'/forms', 
		'/save_table_prefs', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\saveTablePrefs',
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
		RESTAPIPREFIX.'/forms', 
		'/delete_table_prefs', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\deleteTablePrefs',
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
		RESTAPIPREFIX.'/forms', 
		'/save_column_settings', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\saveColumnSettings',
			'permission_callback' 	=> function(){
				$formsTable		= new DisplayFormResults();
				return $formsTable->editRights;
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

	// save_table_prefs
	register_rest_route( 
		RESTAPIPREFIX.'/forms', 
		'/save_table_settings', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\saveTableSettings',
			'permission_callback' 	=> function(){
				$formsTable		= new DisplayFormResults(array( 
					'id'			=> $_POST['shortcode_id'],
					'formid'		=> $_POST['formid']
				));
				return $formsTable->editRights;
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
		RESTAPIPREFIX.'/forms', 
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

	//archive submission
	register_rest_route( 
		RESTAPIPREFIX.'/forms', 
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
		RESTAPIPREFIX.'/forms', 
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
		RESTAPIPREFIX.'/forms', 
		'/get_input_html', 
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\getInputHtml',
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

function saveTablePrefs( \WP_REST_Request $request ) {
	if (is_user_logged_in()) {
		$columnName		= $request['column_name'];

		$userId		= get_current_user_id();
		$hiddenColumns	= (array)get_user_meta($userId, 'hidden_columns_'.$request['formid'], true);	

		$hiddenColumns[$columnName]	= 'hidden';

		update_user_meta($userId, 'hidden_columns_'.$request['formid'], $hiddenColumns);	

		return 'Succesfully updated column settings';
	}else{
		return new \WP_Error('Error','You are not logged in');
	}
}

function deleteTablePrefs( \WP_REST_Request $request ) {
	if (is_user_logged_in()) {
		$userId		= get_current_user_id();
		delete_user_meta($userId, 'hidden_columns_'.$request['formid']);	

		return 'Succesfully deleted column settings';
	}else{
		return new \WP_Error('error','You are not logged in');
	}
}

function saveColumnSettings($settings='', $shortcodeId=''){
	global $wpdb;

	if($settings instanceof \WP_REST_Request){
		$params			= $settings->get_params();

		$settings 		= $params['column_settings'];
		$shortcodeId 	= $params['shortcode_id'];
	}
	
	//copy edit right roles to view right roles
	if(empty($settings)){
		return new \WP_Error('forms', 'No settings provided');
	}

	if(empty($shortcodeId)){
		return new \WP_Error('forms', 'No shortcode id provided');
	}

	foreach($settings as $key=>$setting){
		//if there are edit rights defined
		if(isset($setting['edit_right_roles']) && is_array($setting['edit_right_roles'])){
			//create view array if it does not exist
			if(!is_array($setting['view_right_roles'])){
				$setting['view_right_roles'] = [];
			}
			
			//merge and save
			$settings[$key]['view_right_roles'] = array_merge($setting['view_right_roles'], $setting['edit_right_roles']);
		}
	}
	
	$formTable	= new DisplayFormResults();
	$wpdb->update($formTable->shortcodeTable, 
		array(
			'column_settings'	=> maybe_serialize($settings)
		), 
		array(
			'id'				=> $shortcodeId,
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
	$tableSettings 	= $_POST['table_settings'];

	$formTable		= new DisplayFormResults();
	
	$wpdb->update($formTable->shortcodeTable, 
		array(
			'table_settings'=> maybe_serialize($tableSettings)
		), 
		array(
			'id'			=> $_POST['shortcode_id'],
		),
	);
	
	//also update form setings if needed
	$formSettings = $_POST['form_settings'];
	if(is_array($formSettings) && is_numeric($_POST['formid'])){
		$formTable->getForm($_POST['formid']);
		
		//update existing
		foreach($formSettings as $key=>$setting){
			$formTable->formData->settings[$key] = $setting;
		}
		
		//save in db
		$wpdb->update($formTable->tableName, 
			array(
				'settings' 	=> maybe_serialize($formTable->formData->settings)
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
	$formTable	= new EditFormResults();

	$result		= $formTable->deleteSubmission($_POST['submissionid']);
	
	if(is_wp_error($result)){
		return $result;
	}
	return "Entry with id {$_POST['submissionid']} succesfully removed";
}

// Archive or unarchive a (sub)submission
function archiveSubmission(){
	$formTable	= new EditFormResults();
	$formTable->getForm($_POST['formid']);
	$formTable->submissionId	= $_POST['submissionid'];

	$formTable->getSubmissionData(null, $formTable->submissionId);

	$action	= $_POST['action'];

	if($action	== 'archive'){
		$archive = true;
	}else{
		$archive = false;
	}

	if(is_numeric($_POST['subid'])){
		$subId						= $_POST['subid'];
		$message					= "Entry with id {$formTable->submissionId} and subid $subId succesfully {$action}d";
		$splitField					= $formTable->formData->settings['split'];
		
		$formTable->formResults[$splitField][$subId]['archived'] = $archive;
		
		//check if all subfields are archived or empty
		$formTable->checkIfAllArchived($formTable->formResults[$splitField]);
	}else{
		$message					= "Entry with id {$formTable->submissionId} succesfully {$action}d";
		$formTable->updateSubmissionData($archive);
	}
	
	return $message;
}

function getInputHtml(){
	$formTable	= new DisplayFormResults();
	$formTable->getForm($_POST['formid']);

	$elementName	= sanitize_text_field($_POST['fieldname']);
	if(isset($_POST['subid']) && is_numeric($_POST['subid'])){
		$elementIndexedName	= $formTable->formData->settings['split']."[{$_POST['subid']}][$elementName]";
	}
	
	foreach ($formTable->formElements as $element){
		$name	= str_replace('[]', '', $element->name);
		if($name == $elementName || $name == $elementIndexedName){
			$formTable->getSubmissionData(null, $_POST['submissionid']);

			$curValue	= '';
			if(isset($formTable->formResults[$element->name])){
				$curValue	= $formTable->formResults[$element->name];
			}

			//Load default values for this element
			return $formTable->getElementHtml($element, $curValue);
		}
	}
	
	return new \WP_Error('No element found', "No element found with id '{$_POST['fieldname']}'");
}

function editValue(){
	$formTable	= new EditFormResults();
		
	$formTable->getForm($_POST['formid']);
	$formTable->submissionId		= $_POST['submissionid'];
	
	$formTable->getSubmissionData(null, $formTable->submissionId);
		
	//update an existing entry
	$fieldName 		= sanitize_text_field($_POST['fieldname']);
	$newValue 		= json_decode(sanitize_text_field(stripslashes($_POST['newvalue'])));

	$transValue		= $formTable->transformInputData($newValue, $fieldName);
	
	$subId			= $_POST['subid'];

	// If there is a sub id set and this field is not a main field
	if(is_numeric($subId)){
		$fieldMainName	= $formTable->formData->settings['split'];
		//check if this is a main field
		if(isset($formTable->formResults[$fieldMainName][$subId][$fieldName])){
			$formTable->formResults[$fieldMainName][$subId][$fieldName]	= $newValue;
		}else{
			$formTable->formResults[$fieldName]= $newValue;
		}
		$message = "Succesfully updated '$fieldName' to $transValue";
	}else{
		$formTable->formResults[$fieldName]	= $newValue;
		$message = "Succesfully updated '$fieldName' to $transValue";
	}

	$message	= apply_filters('sim-forms-submission-updated', $message, $formTable, $fieldName, $newValue);

	if(is_wp_error($message)){
		return ['message' => $message->get_error_message()];
	}

	$formTable->updateSubmissionData();
	
	//send email if needed
	$submitForm	= new SubmitForm();
	$submitForm->getForm($_POST['formid']);
	$submitForm->sendEmail('fieldchanged');
	
	//send message back to js
	return [
		'message'			=> $message,
		'newvalue'			=> $transValue,
	];
}