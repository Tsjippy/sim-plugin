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
				'name'		=> array(
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
				'id'		=> array(
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

	// get next or prev page
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/get_page',
		array(
			'methods' 				=> \WP_REST_Server::EDITABLE,
			'callback' 				=> __NAMESPACE__.'\getPage',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
				),
				'pagenumber'		=> array(
					'required'	=> true,
					'validate_callback' => function($submissionId){
						return is_numeric($submissionId);
					}
				)
			)
		)
	);
} );

function getPage(){
	$displayFormResults		= new DisplayFormResults([
		'formid' 		=> $_POST['formid'],
		'search'		=> $_POST['search'],
		'shortcodeid'	=> $_POST['shortcode_id'],
		'onlyOwn'		=> $_POST['onlyown'],
		'archived'		=> $_POST['archived']
	]);

	$displayFormResults->loadShortcodeData();

	return $displayFormResults->renderTable($_POST['type'], true);
}

function saveTablePrefs( \WP_REST_Request $request ) {
	if (is_user_logged_in()) {
		$columnName					= $request['column_name'];

		$userId						= get_current_user_id();
		$hiddenColumns				= (array)get_user_meta($userId, 'hidden_columns_'.$request['formid'], true);

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
		if(!is_array($setting)){
			continue;
		}
		
		//if there are edit rights defined
		if(isset($setting['edit_right_roles'])){
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
	if(isset($tableSettings['filter'])){
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
		foreach($formSettings as $key=>&$value){
			if(is_array($value)){
				$value	= serialize($value);
			}
		}
		
		//save in db
		$wpdb->update($formTable->tableName,
			$formSettings,
			array(
				'id'		=> $formTable->formData->id,
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
		$subId		= $_POST['subid'];
		$message	= "Entry with id {$formTable->submissionId} and subid $subId succesfully {$action}d";
		

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
		$formTable->checkIfAllArchived();
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

	$elementId		= sanitize_text_field($_POST['id']);

	$elementName	= sanitize_text_field($_POST['name']);

	$element		= $formTable->getElementById($elementId);

	$curValue		= '';

	if(!$element){
		return new \WP_Error('No element found', "No element found with id '{$_POST['elementid']}'");
	}

	// get value

	// Check if we are dealing with an split element with form name[X]name
	preg_match('/(.*?)\[[0-9]\]\[(.*?)\]/', $element->name, $matches);

	if(isset($formTable->submission->formresults[$elementName])){
		$curValue	= $formTable->submission->formresults[$elementName];
	}elseif(isset($formTable->submission->formresults[str_replace('[]', '', $element->name)])){
		$curValue	= $formTable->submission->formresults[str_replace('[]', '', $element->name)];
	}elseif(isset($formTable->submission->formresults[$matches[1]])){
		$subId = $_POST['subid'];
		if(is_numeric($subId) && isset($formTable->submission->formresults[$matches[1]][$subId][$matches[2]])){
			$curValue	= $formTable->submission->formresults[$matches[1]][$subId][$matches[2]];
		}
	}

	// Get element html with the value allready set
	return $formTable->getElementHtml($element, $curValue, true);
}

function editValue(){
	$formTable	= new EditFormResults();
		
	$formTable->getForm($_POST['formid']);
	$formTable->submissionId	= $_POST['submissionid'];
	
	$formTable->parseSubmissions(null, $formTable->submissionId);
		
	//update an existing entry
	$elementName 	= sanitize_text_field($_POST['name']);
	$newValue 		= json_decode(sanitize_text_field(stripslashes($_POST['newvalue'])));

	if(is_array($newValue)){
		$string	= json_encode($newValue);
	}else{
		$string	= $newValue;
	}

	$transValue		= $formTable->transformInputData($string, $elementName, $formTable->submission->formresults);
	
	$subId			= $_POST['subid'];

	// If there is a sub id set and this field is not a main field
	if(!empty($subId) && is_numeric($subId)){
		$splitElements				= $formTable->formData->split;

		foreach($splitElements as $index){
			$elementName			= $formTable->getElementById($index, 'name');

			preg_match('/(.*?)\[[0-9]\]\[.*?\]/', $elementName, $matches);

			if(isset($matches[1])){
				$elementName	= $matches[1];
			}

			//check if this is a main field
			if(isset($formTable->submission->formresults[$elementName][$subId][$elementName])){
				$oldValue	= $formTable->submission->formresults[$elementName][$subId][$elementName];
				$formTable->submission->formresults[$elementName][$subId][$elementName]	= $newValue;
			}elseif(isset($formTable->submission->formresults[$elementName])){
				$oldValue	= $formTable->submission->formresults[$elementName];
				$formTable->submission->formresults[$elementName]	= $newValue;
			}
			$message = "Succesfully updated '$elementName' to $transValue";
		}
	}else{
		$oldValue	= $formTable->submission->formresults[$elementName];
		$formTable->submission->formresults[$elementName]	= $newValue;
		$message = "Succesfully updated '$elementName' to $transValue";
	}

	$message	= apply_filters('sim-forms-submission-updated', $message, $formTable, $elementName, $oldValue, $newValue);

	if(is_wp_error($message)){
		return ['message' => $message->get_error_message()];
	}

	$formTable->updateSubmission();
	
	//send email if needed
	$submitForm					= new SubmitForm();
	$submitForm->getForm($_POST['formid']);
	$submitForm->submission		= $formTable->submission;
	$submitForm->sendEmail('fieldchanged');
	$submitForm->sendEmail('fieldschanged');
	
	//send message back to js
	return [
		'message'			=> $message,
		'newvalue'			=> $transValue,
	];
}