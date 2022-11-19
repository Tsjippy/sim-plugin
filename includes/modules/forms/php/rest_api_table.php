<?php
namespace SIM\FORMS;
use SIM;
use stdClass;
use WP_Error;

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
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
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
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
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
					'validate_callback' => function($shortcodeId){
						return is_numeric($shortcodeId);
					}
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
					'validate_callback' => function($shortcodeId){
						return is_numeric($shortcodeId);
					}
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
					'validate_callback' => function($submissionId){
						return is_numeric($submissionId);
					}
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
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
				),
				'submissionid'		=> array(
					'required'	=> true,
					'validate_callback' => function($submissionId){
						return is_numeric($submissionId);
					}
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
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
				),
				'submissionid'		=> array(
					'required'	=> true,
					'validate_callback' => function($submissionId){
						return is_numeric($submissionId);
					}
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
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
				),
				'fieldname'		=> array(
					'required'	=> true,
				),
				'submissionid'		=> array(
					'required'	=> true,
					'validate_callback' => function($submissionId){
						return is_numeric($submissionId);
					}
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

		return 'Succesfully reset column visibility';
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
	
	$tableSettings 	= $_POST['table_settings'];

	// Check invalid filter names
	if(isset($tableSettings['filter']) && is_array($tableSettings['filter'])){
		foreach($tableSettings['filter'] as $filter){
			if(in_array($filter['name'], ['accept-charset', 'action', 'autocomplete', 'enctype', 'method', 'name', 'novalidate', 'rel', 'target'])){
				return new WP_Error('forms', "Invalid filter name '{$filter['name']}', use a different one");
			}
		}
	}

	//update table settings
	$formTable		= new DisplayFormResults();
	
	
	$wpdb->update(
		$formTable->shortcodeTable,
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

	do_action('sim-forms-entry-removed', $formTable, $_POST['submissionid']);

	return "Entry with id {$_POST['submissionid']} succesfully removed";
}

// Archive or unarchive a (sub)submission
function archiveSubmission(){
	$formTable	= new EditFormResults();
	$formTable->getForm($_POST['formid']);
	$formTable->submissionId	= $_POST['submissionid'];

	$formTable->parseSubmissions(null, $formTable->submissionId);

	$action	= $_POST['action'];

	if($action	== 'archive'){
		$archive = true;
	}else{
		$archive = false;
	}

	if(isset($_POST['subid']) && is_numeric($_POST['subid'])){
		$subId						= $_POST['subid'];
		$message					= "Entry with id {$formTable->submissionId} and subid $subId succesfully {$action}d";
		

		if($archive){
			// add
			if(empty($formTable->submission->archivedsubs)){
				$formTable->submission->archivedsubs	= [$subId];
			}elseif(!in_array($subId, $formTable->submission->archivedsubs)){
				// only add if not yet there
				$formTable->submission->archivedsubs[]	= $subId;
			}
		}else{
			// remove
			$formTable->submission->archivedsubs	= array_diff($formTable->submission->archivedsubs, [$subId]);
		}
		
		//check if all subfields are archived or empty
		//$formTable->checkIfAllArchived($formTable->submission->formresults[$splitField]);
	}else{
		$message					= "Entry with id {$formTable->submissionId} succesfully {$action}d";

		if($archive){
			$formTable->updateSubmission($archive);
		}else{
			$formTable->unArchiveAll($formTable->submissionId);
		}
	}
	
	return $message;
}

function getInputHtml(){
	$formTable		= new DisplayFormResults();

	$formTable->getForm($_POST['formid']);

	$formTable->parseSubmissions(null, $_POST['submissionid']);

	$elementName	= sanitize_text_field($_POST['fieldname']);
	$curValue		= '';
	$element		= $formTable->getElementByName($elementName);

	$subId			= '';
	if(isset($_POST['subid']) && is_numeric($_POST['subid'])){
		$subId		= $_POST['subid'];
	}

	// get value
	if($element && isset($formTable->submission->formresults[$element->name])){
		$curValue	= $formTable->submission->formresults[$element->name];

		if(is_numeric($subId) && is_array($curValue)){
			$curValue	= $curValue[subId];
		}
	}elseif(!$element && is_numeric($subId)){
		$splitElementName	= $formTable->formData->settings['split'];
		$elementIndexedName	= $splitElementName."[$subId][$elementName]";

		$element	= $formTable->getElementByName($elementIndexedName);

		if(isset($formTable->submission->formresults[$splitElementName][$subId][$elementName])){
			$curValue	= $formTable->submission->formresults[$splitElementName][$subId][$elementName];
		}
	}

	if(!$element){
		return new \WP_Error('No element found', "No element found with id '{$_POST['fieldname']}'");
	}

	// Get element html with the value allready set
	return $formTable->getElementHtml($element, $curValue);
}

function editValue(){
	$formTable	= new EditFormResults();
		
	$formTable->getForm($_POST['formid']);
	$formTable->submissionId	= $_POST['submissionid'];
	
	$formTable->parseSubmissions(null, $formTable->submissionId);
		
	//update an existing entry
	$fieldName 		= sanitize_text_field($_POST['fieldname']);
	$newValue 		= json_decode(sanitize_text_field(stripslashes($_POST['newvalue'])));

	$transValue		= $formTable->transformInputData($newValue, $fieldName);
	
	$subId			= $_POST['subid'];

	// If there is a sub id set and this field is not a main field
	if(is_numeric($subId)){
		$splitElementName	= [];
		preg_match('/(.*?)\[[0-9]\]\[.*?\]/', $fieldName, $splitElementName);

		//check if this is a main field
		if(isset($formTable->submission->formresults[$splitElementName][$subId][$fieldName])){
			$formTable->submission->formresults[$splitElementName][$subId][$fieldName]	= $newValue;
		}else{
			$formTable->submission->formresults[$fieldName]= $newValue;
		}
		$message = "Succesfully updated '$fieldName' to $transValue";
	}else{
		$formTable->submission->formresults[$fieldName]	= $newValue;
		$message = "Succesfully updated '$fieldName' to $transValue";
	}

	$message	= apply_filters('sim-forms-submission-updated', $message, $formTable, $fieldName, $newValue);

	if(is_wp_error($message)){
		return ['message' => $message->get_error_message()];
	}

	$formTable->updateSubmission();
	
	//send email if needed
	$submitForm					= new SubmitForm();
	$submitForm->getForm($_POST['formid']);
	$submitForm->submission		= new stdClass();
	$submitForm->sendEmail('fieldchanged');
	
	//send message back to js
	return [
		'message'			=> $message,
		'newvalue'			=> $transValue,
	];
}