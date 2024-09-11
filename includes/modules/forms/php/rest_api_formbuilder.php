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
					'validate_callback' => function($elementIndex){
						return is_numeric($elementIndex);
					}
				),
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
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
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
				),
				'elementid'		=> array(
					'required'	=> true,
					'validate_callback' => function($elementId){
						return is_numeric($elementId);
					}
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
				'form_id'		=> array(
					'required'	=> true,
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
				),
				'el_id'		=> array(
					'required'	=> true,
					'validate_callback' => function($elementId){
						return is_numeric($elementId);
					}
				),
				'old_index'		=> array(
					'required'	=> true,
					'validate_callback' => function($oldIndex){
						return is_numeric($oldIndex);
					}
				),
				'new_index'		=> array(
					'required'	=> true,
					'validate_callback' => function($newIndex){
						return is_numeric($newIndex);
					}
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
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
				),
				'elementid'		=> array(
					'required'	=> true,
					'validate_callback' => function($elementId){
						return is_numeric($elementId);
					}
				),
				'new_width'		=> array(
					'required'	=> true,
					'validate_callback' => function($width){
						return is_numeric($width);
					}
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
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
				),
				'elementid'		=> array(
					'required'	=> true,
					'validate_callback' => function($elementId){
						return is_numeric($elementId);
					}
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
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
				),
				'elementid'		=> array(
					'required'	=> true,
					'validate_callback' => function($elementId){
						return is_numeric($elementId);
					}
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
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
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
			'callback' 				=> 	function(){
				$formBuilder	= new SubmitForm();
				return $formBuilder->formSubmit();
			},
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'formid'		=> array(
					'required'	=> true,
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
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
					'validate_callback' => function($formId){
						return is_numeric($formId);
					}
				),
				'emails'		=> array(
					'required'	=> true
				),
				
			)
		)
	);
});

function getUniqueName($element, $update, $oldElement, $simForms){
	$element->name	= end(explode('\\', $element->name));
	if(
		str_contains($element->name, '[]') || 
		(
			$update && $oldElement->name == $element->name && 
			count($simForms->getElementByName($element->name, '', false)) == 1)
		){
		return $element->name;
	}

	global $wpdb;

	$elementName = $element->name;
	
	$i = '';
	// getElementByName returns false when no match found
	while($simForms->getElementByName($elementName)){
		$i++;
		
		$elementName = "{$element->name}_$i";
	}
	//update the name
	if($i != ''){
		$element->name .= "_$i";
	}

	// only update previous submissions when an update of the name of existing element took place
	if(!$update){
		return $element->name;
	}

	// update js
	$simForms->createJs();

	// Update column settings
	$displayFormResults	= new DisplayFormResults(['formid'=>$simForms->formData->id]);

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
	$displayFormResults->parseSubmissions(null, null, true);

	$submitForm	= new SubmitForm();

	foreach($displayFormResults->submissions as $submission){
		if(isset($submission->formresults[$oldElement->name])){
			$submission->formresults[$element->name]	= $submission->formresults[$oldElement->name];
			unset($submission->formresults[$oldElement->name]);
		}
		$wpdb->update(
			$submitForm->submissionTableName,
			array(
				'formresults'	=> maybe_serialize($submission->formresults),
			),
			array(
				'id'			=> $submission->id
			)
			);
	}

	return $element->name;
}

// DONE
function addFormElement(){
	global $wpdb;

	$simForms	= new SaveFormSettings();
	$simForms->getForm($_POST['formid']);

	$index		= 0;
	$oldElement	= new stdClass();

	//Store form results if submitted
	$element			= (object)$_POST["formfield"];
	$element->options	= str_replace('\\\\', '\\', $element->options);
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
	
	if(in_array($element->type, ['label', 'button', 'formstep'])){
		$element->name	= $element->text;
	}elseif(empty($element->name)){
		return new \WP_Error('Error', "Please enter a formfieldname");
	}

	$element->nicename	= ucfirst(trim($element->name, '[]'));
	//$element->name		= str_replace(" ", "_", strtolower(trim($element->name)));

	if(
		in_array($element->type, $simForms->nonInputs) 		&& 	// this is a non-input
		$element->type != 'datalist'						&& 	// but not a datalist
		!str_contains($element->name, $element->type)			// and the type is not yet added to the name
	){
		$element->name	.= '_'.$element->type;
	}
	
	//Give an unique name
	$element->name		= getUniqueName($element, $update, $oldElement, $simForms);
	if(is_wp_error($element->name)){
		return $element->name;
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
		$result									= $simForms->updateFormElement($element);
		if(is_wp_error($result)){
			return $result;
		}
	}else{
		$message								= "Succesfully added '{$element->name}' to this form";
		if(!is_numeric($_POST['insertafter'])){
			$element->priority	= $wpdb->get_var( "SELECT COUNT(`id`) FROM `{$simForms->elTableName}` WHERE `form_id`={$element->form_id}") +1;
		}else{
			$element->priority	= $_POST['insertafter'] + 1;
		}

		$element->id	= $simForms->insertElement($element);
		
		$simForms->reorderElements(-1, $element->priority, $element);
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
	$formBuilder			= new SaveFormSettings();

	$formBuilder->formId	= $_POST['form_id'];
	
	$oldIndex 				= $_POST['old_index'];
	$newIndex				= $_POST['new_index'];

	$element				= $formBuilder->getElementById($_POST['el_id']);
	
	$formBuilder->reorderElements($oldIndex, $newIndex, $element);
	
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

	$elementId		= $_POST['elementid'];
	if(!$elementId){
		return new \WP_Error('forms', "First save the element before adding conditions to it");
	}
	$formId			= $_POST['formid'];
	
	$formBuilder->getForm($formId);
	
	$element = $formBuilder->getElementById($elementId);
	
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

	if(is_wp_error($errors)){
		return $errors;
	}elseif(!empty($errors)){
		$message	.= "\n\nThere were some errors:\n";
		$message	.= implode("\n", $errors);
	}

	return $message;
}

// DONE
function saveFormSettings(){
	$formBuilder			= new SaveFormSettings();
	
	$formSettings 			= $_POST;
	unset($formSettings['_wpnonce']);
	unset($formSettings['formid']);

	$formBuilder->formName	= $formSettings['form_name'];
	
	//remove double slashes
	$formSettings['upload_path']	= str_replace('\\\\', '\\', $formSettings['upload_path']);
	
	$formBuilder->maybeInsertForm($_POST['formid']);
	
	$result	= $formBuilder->updateFormSettings($_POST['formid'], $formSettings);
	
	if(is_wp_error($result)){
		return $result;
	}
	return "Succesfully saved your form settings";
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
