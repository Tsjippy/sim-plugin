<?php
namespace SIM\FORMS;
use SIM;
use stdClass;
use WP_Error;

function checkPermissions(){
	$user	= wp_get_current_user();
	if(in_array('editor', $user->roles)){
		return true;
	}

	return false;
}

add_action( 'rest_api_init', function () {
	// add element to form
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/add_form_element',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\addFormElement',
			'permission_callback' 	=> __NAMESPACE__.'\checkPermissions',
			'args'					=> array(
				'formfield'		=> array(
					'required'	=> true
				),
				'element_id'		=> array(
					'required'	=> true
				)
			)
		)
	);

	// delete element
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/remove_element',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\removeElement',
			'permission_callback' 	=> __NAMESPACE__.'\checkPermissions',
			'args'					=> array(
				'elementindex'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// request form element
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/request_form_element',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\requestFormElement',
			'permission_callback' 	=> __NAMESPACE__.'\checkPermissions',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'elementid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// reorder form elements
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/reorder_form_elements',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\reorderFormElements',
			'permission_callback' 	=> __NAMESPACE__.'\checkPermissions',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'old_index'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'new_index'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// edit formfield width
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/edit_formfield_width',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\editFormfieldWidth',
			'permission_callback' 	=> __NAMESPACE__.'\checkPermissions',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'elementid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'new_width'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// form conditions html
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/request_form_conditions_html',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\requestFormConditionsHtml',
			'permission_callback' 	=> __NAMESPACE__.'\checkPermissions',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'elementid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// save_element_conditions
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/save_element_conditions',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\saveElementConditions',
			'permission_callback' 	=> __NAMESPACE__.'\checkPermissions',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'elementid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// save_form_settings
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/save_form_settings',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\saveFormSettings',
			'permission_callback' 	=> __NAMESPACE__.'\checkPermissions',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'settings'		=> array(
					'required'	=> true
				)
			)
		)
	);

	// save_form_input
	register_rest_route(
		'sim/v2/forms',
		'/save_form_input',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\saveFormInput',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// save_form_emails
	register_rest_route(
		RESTAPIPREFIX.'/forms',
		'/save_form_emails',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\saveFormEmails',
			'permission_callback' 	=> __NAMESPACE__.'\checkPermissions',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				),
				'emails'		=> array(
					'required'	=> true
				),
				
			)
		)
	);
});

// DONE
function addFormElement(){
	global $wpdb;

	$simForms	= new SaveFormSettings();
	$simForms->getForm($_POST['formid']);

	$index		= 0;
	$oldElement	= new stdClass();

	//Store form results if submitted
	$element		= (object)$_POST["formfield"];
	if(is_numeric($_POST['element_id'])){
		$update		= true;

		$oldElement	= $simForms->getElementById($_POST['element_id']);

		$element->id	= $_POST['element_id'];
	}else{
		$update	= false;
	}
	
	if($element->type == 'php'){
		//we store the functionname in the html variable replace any double \ with a single \
		$functionName 			= str_replace('\\\\', '\\', $element->functionname);
		$element->name			= $functionName;
		$element->functionname	= $functionName;
		
		//only continue if the function exists
		if ( ! function_exists( $functionName ) ){
			return new WP_Error('forms', "A function with name $functionName does not exist!");
		}
	}
	
	if(in_array($element->type, ['label','button','formstep'])){
		$element->name	= $element->text;
	}elseif(empty($element->name)){
		return new \WP_Error('Error', "Please enter a formfieldname");
	}

	$element->nicename	= $element->name;
	$element->name		= str_replace(" ","_",strtolower(trim($element->name)));

	if(
		in_array($element->type, $simForms->nonInputs) 		&& 	// this is a non-input
		$element->type != 'datalist'						&& 	// but not a datalist
		strpos($element->name, $element->type) === false		// and the type is not yet added to the name
	){
		$element->name	.= '_'.$element->type;
	}
	
	//Give an unique name
	if(strpos($element->name, '[]') === false && (!$update || $oldElement->name != $element->name)){
		global $wpdb;

		$unique = false;
		
		$i = '';
		while(!$unique){
			if($i == ''){
				$elementName = $element->name;
			}else{
				$elementName = "{$element->name}_$i";
			}
			
			$unique = true;
			//loop over all elements to check if the names are equal
			foreach($simForms->formElements as $index=>$el){
				//don't compare with itself
				if($update && $index == $_POST['element_id']){
					continue;
				}

				//we found a duplicate name
				if($elementName == $el->name){
					$i++;
					$unique = false;
				}
			}
		}
		//update the name
		if($i != ''){
			$element->name .= "_$i";
		}

		if($update){
			// update splitted field
			if($simForms->formData->settings['split'] == $oldElement->name){
				// update the splitted field
				$simForms->formData->settings['split']	= $element->name;

				$result	= $simForms->updateFormSettings();
		
				if(is_wp_error($result)){
					return $result;
				}
			}

			// update js
			$simForms->createJs();

			// Update column settings
			$displayFormResults	= new DisplayFormResults();

			$query						= "SELECT * FROM {$displayFormResults->shortcodeTable} WHERE form_id= '{$simForms->formData->id}'";
			foreach($wpdb->get_results($query) as $data){
				$displayFormResults->shortcodeId	= $data->id;
				$displayFormResults->loadShortcodeData();
				$displayFormResults->addColumnSetting($element);

				saveColumnSettings($displayFormResults->columnSettings, $displayFormResults->shortcodeId);
			}

			// Update submission data
			$displayFormResults->showArchived	= true;
			$displayFormResults->getForm($simForms->formData->id);
			$displayFormResults->setSubmissionData(null, null, true);

			$submitForm	= new SubmitForm();

			foreach($displayFormResults->submissionData as $data){
				if(isset($data->formresults[$oldElement->name])){
					$data->formresults[$element->name]	= $data->formresults[$oldElement->name];
					unset($data->formresults[$oldElement->name]);
				}
				$wpdb->update(
					$submitForm->submissionTableName,
					array(
						'formresults'	=> maybe_serialize($data->formresults),
					),
					array(
						'id'			=> $data->id
					)
					);
			}
		}
	}

	//Store info text in text column
	if(in_array($element->type, ['info', 'p'])){
		$element->text 	= wp_kses_post($element->infotext);
	}
	//do not store infotext
	unset($element->infotext);

	foreach($element as &$val){
		if($val == "true"){
			$val=true;
		}

		if(is_array($val)){
			$val=serialize($val);
		}else{
			$val	= SIM\deslash($val);
		}
	}
	
	if($update){
		$message								= "Succesfully updated '{$element->name}'";
		$element->id							= $_POST['element_id'];
		$simForms->updateFormElement($element);
	}else{
		$message								= "Succesfully added '{$element->name}' to this form";
		if(!is_numeric($_POST['insertafter'])){
			$element->priority	= $wpdb->get_var( "SELECT COUNT(`id`) FROM `{$simForms->elTableName}` WHERE `form_id`={$element->form_id}") +1;
		}else{
			$element->priority	= $_POST['insertafter'] + 1;
			$simForms->reorderElements(-1, $element->priority, $element);
		}

		$element->id	= $simForms->insertElement($element);
	}
		
	$formBuilderForm	= new FormBuilderForm();
	$formBuilderForm->getForm($_POST['formid']);

	$html = $formBuilderForm->buildHtml($element, $index);
	
	return [
		'message'		=> $message,
		'html'			=> $html
	];
}

// DONE
function removeElement(){
	global $wpdb;

	$formBuilder	= new SaveFormSettings();

	$elementId		= $_POST['elementindex'];

	$wpdb->delete(
		$formBuilder->elTableName,
		['id' => $elementId],
	);

	// Fix priorities
	// Get all elements of this form
	$formBuilder->getAllFormElements('priority', $_POST['formid']);
	
	//Loop over all elements and give them the new priority
	foreach($formBuilder->formElements as $key=>$el){
		if($el->priority != $key+1){
			//Update the database
			$wpdb->update($formBuilder->elTableName,
				array(
					'priority'	=> $key+1
				),
				array(
					'id'		=> $el->id
				),
			);
			
			if(!empty($wpdb->last_error)){
				return new \WP_Error('Error', $wpdb->print_error());
			}
		}
	}

	return "Succesfully removed the element";
}

// DONE
function requestFormElement(){
	$formBuilderForm				= new FormBuilderForm();
	
	$formId 				= $_POST['formid'];
	$elementId 				= $_POST['elementid'];
	
	$formBuilderForm->getForm($formId);
	
	$conditionForm			= $formBuilderForm->elementConditionsForm($elementId);

	$elementForm			= $formBuilderForm->elementBuilderForm($elementId);
	
	return [
		'elementForm'		=> $elementForm,
		'conditionsHtml'	=> $conditionForm
	];
}

// DONE
function reorderFormElements(){
	$formBuilder	= new SaveFormSettings();
	
	$oldIndex 	= $_POST['old_index'];
	$newIndex	= $_POST['new_index'];
	
	$formBuilder->reorderElements($oldIndex, $newIndex, '', $_POST['formid']);
	
	return "Succesfully saved new form order";
}

// DONE
function editFormfieldWidth(){
	$formBuilder	= new SaveFormSettings();
	
	$elementId 		= $_POST['elementid'];
	$element		= $formBuilder->getElementById($elementId);
	
	$newwidth 		= $_POST['new_width'];
	$element->width = min($newwidth,100);
	
	$formBuilder->updateFormElement($element);
	
	return "Succesfully updated formelement width to $newwidth%";
}

// DONE
function requestFormConditionsHtml(){
	$formBuilder	= new FormBuilderForm();

	$elementID = $_POST['elementid'];
	
	$formBuilder->getForm($_POST['formid']);
	
	return $formBuilder->elementConditionsForm($elementID);
}

// DONE
function saveElementConditions(){
	$formBuilder	= new SaveFormSettings();

	$elementID 		= $_POST['elementid'];
	$formID			= $_POST['formid'];
	
	$formBuilder->getForm($formID);
	
	$element = $formBuilder->getElementById($elementID);
	
	$elementConditions	= $_POST['element_conditions'];
	if(empty($elementConditions)){
		$element->conditions	= '';
		
		$message = "Succesfully removed all conditions for {$element->name}";
	}else{
		$element->conditions = serialize($elementConditions);
		
		$message = "Succesfully updated conditions for {$element->name}";
	}

	$formBuilder->updateFormElement($element);
	
	//Create new js
	$errors		 = $formBuilder->createJs();

	if(!empty($errors)){
		$message	.= "\n\nThere were some errors:\n";
		$message	.= implode("\n", $errors);
	}

	return $message;
}

// DONE
function saveFormSettings(){
	$formBuilder			= new SaveFormSettings();
	
	$formSettings 			= $_POST['settings'];

	$formBuilder->formName	= $formSettings['formname'];
	
	//remove double slashes
	$formSettings['upload_path']	= str_replace('\\\\', '\\', $formSettings['upload_path']);
	
	$formBuilder->maybeInsertForm();
	
	$result	= $formBuilder->updateFormSettings($_POST['formid'], $formSettings);
	
	if(is_wp_error($result)){
		return $result;
	}
	return "Succesfully saved your form settings";
}

function saveFormInput(){
	global $wpdb;

	$formBuilder	= new SubmitForm();

	$formId			= $_POST['formid'];
	
	$formBuilder->getForm($formId);
	
	$formBuilder->userId	= 0;
	if(is_numeric($_POST['userid'])){
		//If we are submitting for someone else and we do not have the right to save the form for someone else
		if(
			array_intersect($formBuilder->userRoles, $formBuilder->submitRoles) === false &&
			$formBuilder->user->ID != $_POST['userid']
		){
			return new \WP_Error('Error', 'You do not have permission to save data for user with id '.$_POST['userid']);
		}else{
			$formBuilder->userId = $_POST['userid'];
		}
	}
	
	$formBuilder->formResults = $_POST;
		
	//remove the action and the formname
	unset($formBuilder->formResults['formname']);
	unset($formBuilder->formResults['fileupload']);
	unset($formBuilder->formResults['userid']);
	
	$formBuilder->formResults = apply_filters('sim_before_saving_formdata', $formBuilder->formResults, $formBuilder->formData->name, $formBuilder->userId);
	
	$message = $formBuilder->formData->settings['succesmessage'];
	if(empty($message)){
		$message = 'succes';
	}
	
	//save to submission table
	if(empty($formBuilder->formData->settings['save_in_meta'])){
		//Get the id from db before insert so we can use it in emails and file uploads
		$formBuilder->formResults['id']	= $wpdb->get_var( "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE (TABLE_NAME = '{$formBuilder->submissionTableName}') AND table_schema='$wpdb->dbname'");
		
		//sort arrays
		foreach($formBuilder->formResults as $key=>$result){
			if(is_array($result)){
				//check if this a aray of uploaded files
				if(!is_array(array_values($result)[0]) && strpos(array_values($result)[0],'wp-content/uploads/') !== false){
					//rename the file
					$formBuilder->processFiles($result, $key);
				}else{
					//sort the array
					ksort($result);
					$formBuilder->formResults[$key] = $result;
				}
			}
		}

		$creationDate	= date("Y-m-d H:i:s");
		$formUrl		= $_POST['formurl'];
		
		$formBuilder->formResults['formurl']			= $formUrl;
		$formBuilder->formResults['submissiontime']		= $creationDate;
		$formBuilder->formResults['edittime']			= $creationDate;
		
		$wpdb->insert(
			$formBuilder->submissionTableName,
			array(
				'form_id'			=> $formId,
				'timecreated'		=> $creationDate,
				'timelastedited'	=> $creationDate,
				'userid'			=> $formBuilder->userId,
				'formresults'		=> maybe_serialize($formBuilder->formResults),
				'archived'			=> false
			)
		);
		
		$formBuilder->sendEmail();
			
		if($wpdb->last_error !== ''){
			$message	=  new \WP_Error('error', $wpdb->last_error);
		}else{
			$message	= "$message  \nYour id is {$formBuilder->formResults['id']}";
		}
	//save to user meta
	}else{
		unset($formBuilder->formResults['formurl']);
		
		//get user data as array
		$userData		= (array)get_userdata($formBuilder->userId)->data;
		foreach($formBuilder->formResults as $key=>$result){
			//remove empty elements from the array
			if(is_array($result)){
				SIM\cleanUpNestedArray($result);
				$formBuilder->formResults[$key]	= $result;
			}
			//update in the _users table
			if(isset($userData[$key])){
				if($userData[$key]		!= $result){
					$userData[$key]		= $result;
					$updateuserData		= true;
				}
			//update user meta
			}else{
				if(empty($result)){
					delete_user_meta($formBuilder->userId, $key);
				}else{
					update_user_meta($formBuilder->userId, $key, $result);
				}
			}
		}

		if($updateuserData){
			wp_update_user($userData);
		}
	}

	$message	= apply_filters('sim_after_saving_formdata', $message, $formBuilder);

	return $message;
}

// DONE
function saveFormEmails(){
	$formBuilder	= new SaveFormSettings();
		
	global $wpdb;
	
	$formBuilder->getForm($_POST['formid']);
	
	$formEmails = $_POST['emails'];
	
	foreach($formEmails as $index=>$email){
		$formEmails[$index]['message'] = SIM\deslash($email['message']);
	}
	
	$formBuilder->maybeInsertForm();
	$wpdb->update($formBuilder->tableName,
		array(
			'emails'	=> maybe_serialize($formEmails)
		),
		array(
			'id'		=> $_POST['formid'],
		),
	);
	
	if($wpdb->last_error !== ''){
		return new \WP_Error('error',$wpdb->print_error());
	}else{
		return "Succesfully saved your form e-mail configuration";
	}
}
