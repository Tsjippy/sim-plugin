<?php
namespace SIM\FORMS;
use SIM;
use WP_Error;

function checkPermissions(){
	$user	= wp_get_current_user();
	if(in_array('editor', $user->roles)) return true;

	return false;
}

add_action( 'rest_api_init', function () {
	// add element to form
	register_rest_route( 
		'sim/v1/forms', 
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
		'sim/v1/forms', 
		'/remove_element', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\removeElement',
			'permission_callback' 	=> __NAMESPACE__.'\checkPermissions',
			'args'					=> array(
				'elementindex'		=> array(
					'required'	=> true,
					'validate_callback' => 'is_numeric'
				)
			)
		)
	);

	// request form element
	register_rest_route( 
		'sim/v1/forms', 
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
		'sim/v1/forms', 
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
		'sim/v1/forms', 
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
		'sim/v1/forms', 
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
		'sim/v1/forms', 
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
		'sim/v1/forms', 
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
		'sim/v1/forms', 
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
		'sim/v1/forms', 
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

	$formbuilder	= new Formbuilder();

	//Store form results if submitted
	$element		= (object)$_POST["formfield"];
	if(is_numeric($_POST['element_id'])){
		$update	= true;
	}else{
		$update	= false;
	}
	
	if($element->type == 'php'){
		//we store the functionname in the html variable replace any double \ with a single \
		$functionname 				= str_replace('\\\\','\\',$element->functionname);
		$element->name			= $functionname;
		$element->functionname	= $functionname;
		
		//only continue if the function exists
		if ( ! function_exists( $functionname ) ) return new WP_Error('forms', "A function with name $functionname does not exist!");
	}
	
	if(in_array($element->type, ['label','button'])){
		$element->name	= $element->text;
	}elseif(empty($element->name)){
		return new \WP_Error('Error', "Please enter a formfieldname");
	}

	$element->nicename	= $element->name;
	$element->name		= str_replace(" ","_",strtolower(trim($element->name)));

	if(
		in_array($element->type, $formbuilder->non_inputs) 		and // this is a non-input
		$element->type != 'datalist'							and // but not a datalist
		strpos($element->name,$element->type) === false				// and the type is not yet added to the name 
	){
		$element->name	.= '_'.$element->type;
	}
	
	//Give an unique name
	if(strpos($element->name, '[]') === false and !$update){
		$unique = false;
		
		$i = '';
		while($unique == false){
			if($i == ''){
				$fieldname = $element->name;
			}else{
				$fieldname = "{$element->name}_$i";
			}
			
			$unique = true;
			//loop over all elements to check if the names are equal
			foreach($formbuilder->formdata->elements as $index=>$field){
				//don't compare with itself
				if($update and $index == $_POST['element_id']) continue;
				//we found a duplicate name
				if($fieldname == $field['name']){
					$i++;
					$unique = false;
				}
			}
		}
		//update the name
		if($i != '') $element->name .= "_$i";
	}

	//Store info text in text column
	if(in_array($element->type, ['info', 'p'])){
		$element->text 	= wp_kses_post($element->infotext);
	}
	//do not store infotext
	unset($element->infotext);

	foreach($element as &$val){
		if($val == "true") $val=true;

		if(is_array($val)){
			$val=serialize($val);
		}else{
			$val	= $formbuilder->deslash($val);
		}
	}
	
	if($update){
		$message								= "Succesfully updated '{$element->name}'";
		$element->id							= $_POST['element_id'];
		$formbuilder->update_form_element($element);
	}else{
		$message								= "Succesfully added '{$element->name}' to this form";
		if(!is_numeric($_POST['insertafter'])){
			$element->priority	= $wpdb->get_var( "SELECT COUNT(`id`) FROM `{$formbuilder->el_table_name}` WHERE `form_id`={$element->form_id}") +1;
		}else{
			$element->priority	= $_POST['insertafter'];
			$formbuilder->reorder_elements(-1, $element->priority, $element);
		}

		$element->id	= $formbuilder->insert_element($element);
	}
		
	$html = $formbuilder->buildhtml($element, $index);
	
	return [
		'message'		=> $message,
		'html'			=> $html
	];
}

// DONE
function removeElement(){
	global $wpdb;

	$formbuilder	= new Formbuilder();

	$element_id	= $_POST['elementindex'];

	$wpdb->delete(
		$formbuilder->el_table_name,      
		['id' => $element_id],           
	);

	// Fix priorities
	// Get all elements of this form
	$formbuilder->get_all_form_elements('priority');
	
	//Loop over all elements and give them the new priority
	foreach($formbuilder->form_elements as $key=>$el){
		if($el->priority != $key+1){
			//Update the database
			$result = $wpdb->update($formbuilder->el_table_name, 
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
	$formbuilder				= new Formbuilder();
	
	$formId 				= $_POST['formid'];
	$elementId 				= $_POST['elementid'];
	
	$formbuilder->loadformdata($formId);
	
	$conditionForm			= $formbuilder->element_conditions_form($elementId);

	$elementForm			= $formbuilder->elementBuilderForm($elementId);
	
	return [
		'element_form'		=> $elementForm,
		'conditions_html'	=> $conditionForm
	];
}

// DONE
function reorderFormElements(){
	$formbuilder	= new Formbuilder();
	
	$old_index 	= $_POST['old_index'];
	$new_index	= $_POST['new_index'];
	
	$formbuilder->reorder_elements($old_index, $new_index, '');
	
	return "Succesfully saved new form order";
}

// DONE
function editFormfieldWidth(){
	$formbuilder	= new Formbuilder();
	
	$elementId 		= $_POST['elementid'];
	$element		= $formbuilder->getElementById($elementId);
	
	$newwidth 		= $_POST['new_width'];
	$element->width = min($newwidth,100);
	
	$formbuilder->update_form_element($element);
	
	return "Succesfully updated formelement width to $newwidth%";
}

// DONE
function requestFormConditionsHtml(){
	$formbuilder	= new Formbuilder();

	$elementID = $_POST['elementid'];
	
	$formbuilder->loadformdata($_POST['formid']);
	
	$condition_html = $formbuilder->element_conditions_form($elementID);
	
	return $condition_html;
}

// DONE
function saveElementConditions(){
	$formbuilder	= new Formbuilder();

	$elementID 		= $_POST['elementid'];
	$formID			= $_POST['formid'];
	
	$formbuilder->loadformdata($formID);
	
	$element = $formbuilder->getElementById($elementID);
	
	$elementConditions	= $_POST['element_conditions'];
	if(empty($elementConditions)){
		$element->conditions	= '';
		
		$message = "Succesfully removed all conditions for {$element->name}";
	}else{
		$element->conditions = serialize($elementConditions);
		
		$message = "Succesfully updated conditions for {$element->name}";
	}

	$formbuilder->update_form_element($element);
	
	//Create new js
	$errors		 = $formbuilder->create_js();

	if(!empty($errors)){
		$message	.= "\n\nThere were some errors:\n";
		$message	.= implode("\n", $errors);
	}

	return $message;
}

// DONE
function saveFormSettings(){
	$formbuilder	= new Formbuilder();

	global $wpdb;
	
	$formbuilder->loadformdata($_POST['formid']);
	
	$form_settings = $_POST['settings'];
	
	//remove double slashes
	$form_settings['upload_path']	= str_replace('\\\\','\\',$form_settings['upload_path']);
	
	$formbuilder->maybe_insert_form();
	
	$wpdb->update($formbuilder->table_name, 
		array(
			'settings' 	=> maybe_serialize($form_settings)
		), 
		array(
			'id'		=> $_POST['formid'],
		),
	);
	
	if($wpdb->last_error !== ''){
		return new \WP_Error('Error', $wpdb->print_error());
	}
	
	return "Succesfully saved your form settings";
}

function saveFormInput(){
	global $wpdb;

	$formbuilder	= new Formbuilder();

	$form_id		= $_POST['formid'];
	
	$formbuilder->loadformdata($form_id);
	
	$formbuilder->user_id	= 0;
	if(is_numeric($_POST['userid'])){
		//If we are submitting for someone else and we do not have the right to save the form for someone else
		if(
			array_intersect($formbuilder->user_roles,$formbuilder->submit_roles) === false and 
			$formbuilder->user->ID != $_POST['userid']
		){
			return new \WP_Error('Error', 'You do not have permission to save data for user with id '.$_POST['userid']);
		}else{
			$formbuilder->user_id = $_POST['userid'];
		}
	}
	
	$formbuilder->formresults = $_POST;
		
	//remove the action and the formname
	unset($formbuilder->formresults['formname']);
	unset($formbuilder->formresults['fileupload']);
	unset($formbuilder->formresults['userid']);
	
	$formbuilder->formresults = apply_filters('before_saving_formdata', $formbuilder->formresults, $formbuilder->formdata->name, $formbuilder->user_id);
	
	$message = $formbuilder->formdata->settings['succesmessage'];
	if(empty($message)) $message = 'succes';
	
	//save to submission table
	if(empty($formbuilder->formdata->settings['save_in_meta'])){			
		//Get the id from db before insert so we can use it in emails and file uploads
		$formbuilder->formresults['id']	= $wpdb->get_var( "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE (TABLE_NAME = '{$formbuilder->submissiontable_name}')");
		
		//sort arrays
		foreach($formbuilder->formresults as $key=>$result){
			if(is_array($result)){
				//check if this a aray of uploaded files
				if(!is_array(array_values($result)[0]) and strpos(array_values($result)[0],'wp-content/uploads/') !== false){
					//rename the file
					$formbuilder->process_files($result, $key);
				}else{
					//sort the array
					ksort($result);
					$formbuilder->formresults[$key] = $result;
				}
			}
		}

		$creation_date	= date("Y-m-d H:i:s");
		$form_url		= $_POST['formurl'];
		
		$formbuilder->formresults['formurl']			= $form_url;
		$formbuilder->formresults['submissiontime']		= $creation_date;
		$formbuilder->formresults['edittime']			= $creation_date;
		
		$wpdb->insert(
			$formbuilder->submissiontable_name, 
			array(
				'form_id'			=> $form_id,
				'timecreated'		=> $creation_date,
				'timelastedited'	=> $creation_date,
				'userid'			=> $formbuilder->user_id,
				'formresults'		=> maybe_serialize($formbuilder->formresults),
				'archived'			=> false
			)
		);
		
		$formbuilder->send_email();
			
		if($wpdb->last_error !== ''){
			$message	=  new \WP_Error('error', $wpdb->last_error);
		}else{
			$message	= "$message  \nYour id is {$formbuilder->formresults['id']}";
		}
	//save to user meta
	}else{
		unset($formbuilder->formresults['formurl']);
		
		//get user data as array
		$userdata		= (array)get_userdata($formbuilder->user_id)->data;
		foreach($formbuilder->formresults as $key=>$result){
			//remove empty elements from the array
			if(is_array($result)){
				SIM\clean_up_nested_array($result);
				$formbuilder->formresults[$key]	= $result;
			}
			//update in the _users table
			if(isset($userdata[$key])){
				if($userdata[$key]		!= $result){
					$userdata[$key]		= $result;
					$update_userdata	= true;
				}
			//update user meta
			}else{
				if(empty($result)){
					delete_user_meta($formbuilder->user_id,$key);
				}else{
					update_user_meta($formbuilder->user_id,$key,$result);
				}
			}
		}

		if($update_userdata)wp_update_user($userdata);
	}

	do_action('after_saving_formdata', $formbuilder->formresults, $formbuilder->datatype, $formbuilder->user_id);

	return $message;
}

// DONE
function saveFormEmails(){
	$formbuilder	= new Formbuilder();
		
	global $wpdb;
	
	$formbuilder->loadformdata($_POST['formid']);
	
	$form_emails = $_POST['emails'];
	
	foreach($form_emails as $index=>$email){
		$form_emails[$index]['message'] = $formbuilder->deslash($email['message']);
	}
	
	$formbuilder->maybe_insert_form();
	$wpdb->update($formbuilder->table_name, 
		array(
			'emails'	=> maybe_serialize($form_emails)
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
