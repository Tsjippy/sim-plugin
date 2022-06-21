<?php
namespace SIM\FORMS;
use SIM;
use WP_Embed;
use WP_Error;

if(!trait_exists(__NAMESPACE__.'\CreateJs')){
	require_once(__DIR__.'/js_builder.php');
}

class Formbuilder{
	use CreateJs;

	function __construct(){
		global $wpdb;
		$this->isFormStep				= false;
		$this->isMultiStepForm			= '';
		$this->formStepCounter			= 0;
		$this->submissionTableName		= $wpdb->prefix . 'sim_form_submissions';
		$this->tableName				= $wpdb->prefix . 'sim_forms';
		$this->elTableName				= $wpdb->prefix . 'sim_form_elements';
		$this->nonInputs				= ['label','button','datalist','formstep','info','p','php','multi_start','multi_end'];
		$this->multiInputsHtml			= [];
		$this->user 					= wp_get_current_user();
		$this->userRoles				= $this->user->roles;
		$this->userId					= $this->user->ID;
		$this->pageSize					= 100;

		//calculate full form rights
		if(array_intersect(['editor'], $this->userRoles)){
			$this->editRights		= true;
		}else{
			$this->editRights		= false;
		}
	}
	
	/**
	 * Creates a dropdown with all the forms
	 * 
	 * @return	string	the select html
	 */
	function formSelect(){
		$this->getForms();
		
		$this->names				= [];
		foreach($this->forms as $form){
			$this->names[]			= $form->name;
		}
		
		$html = "<select name='form_selector'>";
			$html .= "<option value=''>---</option>";
		foreach ($this->names as $name){
			$html .= "<option value='$name'>$name</option>";
		}
		$html .= "</select>";
		
		return $html;
	}
	
	/**
	 * Gets all forms from the db
	 */
	function getForms(){
		global $wpdb;
		
		$query							= "SELECT * FROM {$this->tableName} WHERE 1";
		
		$this->forms					= $wpdb->get_results($query);
	}
	
	/**
	 * Creates the tables for this module
	 */
	function createDbTable(){
		if ( !function_exists( 'maybe_create_table' ) ) { 
			require_once ABSPATH . '/wp-admin/install-helper.php'; 
		} 

		add_option( "forms_db_version", "1.0" );
		
		//only create db if it does not exist
		global $wpdb;
		$charsetCollate = $wpdb->get_charset_collate();

		//Main table
		$sql = "CREATE TABLE {$this->tableName} (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  name tinytext NOT NULL,
		  version text NOT NULL,
		  settings text,
		  emails text,
		  PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->tableName, $sql );

		// Form element table
		$sql = "CREATE TABLE {$this->elTableName} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			form_id int NOT NULL,
			type text NOT NULL,
			priority int,
			width int default 100,
			functionname text,
			foldername text,
			name text,
			nicename text,
			text text,
			html text,
			valuelist text,
			default_value text,
			default_array_value text,
			options text,
			required boolean default False,
			mandatory boolean default False,
			recommended boolean default False,
			wrap boolean default False,
			hidden boolean default False,
			multiple boolean default False,
			library boolean default False,
		  	conditions longtext,
			warning_conditions longtext,
			PRIMARY KEY  (id)
		  ) $charsetCollate;";
  
		maybe_create_table($this->elTableName, $sql );

		//submission table
		$sql = "CREATE TABLE {$this->submissionTableName} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			form_id	int NOT NULL,
			timecreated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			timelastedited datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			userid mediumint(9) NOT NULL,
			formresults longtext NOT NULL,
			archived BOOLEAN,
			PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->submissionTableName, $sql );
	}

	/**
	 * Parses all WP Shortcode attributes
	 */
	function processAtts($atts){
		if(!isset($this->formName)){
			shortcode_atts(
				array(
					'formname'		=> '', 
					'userid'		=> '', 
					'search'		=> '',
					'id'			=> ''
				),
				$atts
			);
			
			$this->formName 	= strtolower(sanitize_text_field($atts['formname']));
			$this->shortcodeId	= $atts['id'];
			
			if(is_numeric($atts['userid'])){
				$this->userId	= $atts['userid'];
			}
		}
	}
	
	/**
	 * Finds an element by its id
	 * 
	 * @param	int		$id		the element id
	 * @param	string	$key	A specific element attribute to return. Default empty
	 * 
	 * @return	object|array|string|false			The element or element property
	 */
	function getElementById($id, $key=''){
		//load if needed
		if(empty($this->formData->elementMapping)){
			$this->loadFormData();
		}
		
		$elementIndex	= $this->formData->elementMapping[$id];

		if(!isset($this->formData->elementMapping[$id])){
			SIM\printArray("Element with id $id not found");
			return false;
		}
					
		$element		= $this->formElements[$elementIndex];
		
		if(empty($key)){
			return $element;
		}else{
			return $element->$key;
		}
	}

	/**
	 * Get all elements belonging to the current form
	 * 
	 * @param	string	$sortCol	the column to sort on. Default empty
	 */
	function getAllFormElements($sortCol = ''){
		global $wpdb;

		if($this->formData and is_numeric($this->formData->id)){
			$formId	= $this->formData->id;
		}elseif(isset($_POST['formfield']['form_id'])){
			$formId	= $_POST['formfield']['form_id'];
		}elseif(isset($_POST['formid'])){
			$formId	= $_POST['formid'];
		}

		// Get all form elements
		$query						= "SELECT * FROM {$this->elTableName} WHERE form_id= '$formId'";

		if(!empty($sortCol)){
			$query .= " ORDER BY {$this->elTableName}.`$sortCol` ASC";
		}
		$this->formElements 		=  $wpdb->get_results($query);
	}
	
	/**
	 * Inserts a new form in the db
	 * 
	 * @param	string	$name	The form name
	 * 
	 * @return	int|WP_Error	The form id or error ion failure
	 */
	function insertForm($name=''){
		global $wpdb;

		if(empty($name)){
			$name = $this->formName;
		}

		$result = $wpdb->insert(
			$this->tableName, 
			array(
				'name'			=> $name,
				'version' 		=> 1
			)
		);
		
		if(!empty($wpdb->last_error)){
			return new \WP_Error('error', $wpdb->print_error());
		}

		return $wpdb->insert_id;
	}

	/**
	 * Load a specific form
	 * 
	 * @param	int		$formId	the form id to load. Default empty
	 */
	function loadFormData($formId=''){
		global $wpdb;

		if(is_numeric($_POST['formid'])){
			$formId	= $_POST['formid'];
		}
		
		// Get the form data
		$query				= "SELECT * FROM {$this->tableName} WHERE ";
		if(is_numeric($formId)){
			$query	.= "id= '$formId'";
		}else{
			$query	.= "name= '$this->formName'";
		}

		$result				= $wpdb->get_results($query);

		// Form does not exit yet
		if(empty($result)){
			$this->insertForm();
			$this->formData 	=  new \stdClass();
		}else{
			$this->formData 	=  (object)$result[0];
		}

		$this->getAllFormElements('priority');
		
		//used to find the index of an element based on its unique id
 		$this->formData->elementMapping	= [];
		foreach($this->formElements as $index=>$element){			
			$this->formData->elementMapping[$element->id]	= $index;
		}
		
		if(empty($this->formData->settings)){
			$settings['succesmessage']			= "";
			$settings['formname']				= "";
			$settings['roles']					= [];

			$this->formData->settings = $settings;
		}else{
			$this->formData->settings = maybe_unserialize(utf8_encode($this->formData->settings));
		}

		if(!$this->editRights){
			$editRoles	= ['editor'];
			foreach($this->formData->settings['full_right_roles'] as $key=>$role){
				if(!empty($role)){
					$editRoles[] = $key;
				}
			}
			//calculate full form rights
			if(array_intersect($editRoles, (array)$this->userRoles) != false){
				$this->editRights		= true;
			}else{
				$this->editRights		= false;
			}
		}

		$this->submitRoles	= [];
		foreach($this->formData->settings['submit_others_form'] as $key=>$role){
			if(!empty($role)){
				$this->submitRoles[] = $key;
			}
		}
		
		if(empty($this->formData->emails)){
			$emails[0]["from"]		= "";
			$emails[0]["to"]			= "";
			$emails[0]["subject"]	= "";
			$emails[0]["message"]	= "";
			$emails[0]["headers"]	= "";
			$emails[0]["files"]		= "";

			$this->formData->emails = $emails;
		}else{
			$this->formData->emails = maybe_unserialize($this->formData->emails);
		}
		
		if($wpdb->last_error !== ''){
			SIM\printArray($wpdb->print_error());
		}
		
		$this->jsFileName	= plugin_dir_path(__DIR__)."js/dynamic/{$this->formData->name}forms";
	}

	/**
	 * Change an existing form element
	 * 
	 * @param	object|array	$element	The new element data
	 * 
	 * @return	true|WP_Error				The result or error on failure
	 */
	function updateFormElement($element){
		global $wpdb;
		
		//Update element
		$result = $wpdb->update(
			$this->elTableName, 
			(array)$element, 
			array(
				'id'		=> $element->id,
			),
		);

		if($this->formData == null){
			$this->loadFormData($_POST['formid']);
		}

		//Update form version
		$result = $wpdb->update(
			$this->tableName, 
			['version'	=> $this->formData->version+1], 
			['id'		=> $this->formData->id],
		);
		
		if($wpdb->last_error !== ''){
			return new WP_Error('forms', $wpdb->print_error());
		}

		return $result;
	}

	/**
	 * Inserts a new element in the db
	 * 
	 * @param	object|array	$element	The new element to insert
	 * 
	 * @return	int							The new element id
	 */
	function insertElement($element){
		global $wpdb;
		
		$result = $wpdb->insert(
			$this->elTableName, 
			(array)$element
		);

		return $wpdb->insert_id;
	}

	/**
	 * Change the priority of an element
	 * 
	 * @param	object|array	$element	The element to change the priority of
	 * 
	 * @return	array|WP_Error				The result or error on failure
	 */
	function updatePriority($element){
		global $wpdb;

		//Update the database
		$result = $wpdb->update($this->elTableName, 
			array(
				'priority'	=> $element->priority
			), 
			array(
				'id'		=> $element->id
			),
		);
		
		if($wpdb->last_error !== ''){
			return new WP_Error('forms', $wpdb->print_error());
		}

		return $result;
	}

	/**
	 * Change the order of form elements
	 * 
	 * @param	int				$oldPriority	The old priority of the element
	 * @param	int				$newPriority	The new priority of the element
	 * @param	object|array	$element		The element to change the priority of
	 */
	function reorderElements($oldPriority, $newPriority, $element) {
		if ($oldPriority == $newPriority){
			return;
		}

		// Get all elements of this form
		$this->getAllFormElements('priority');

		if(empty($element)){
			foreach($this->formElements as $el){
				if($el->priority == $oldPriority){
					$element = $el;
					break;
				}
			}
		}

		//No need to reorder if we are adding a new element at the end
		if(count($this->formElements) == $newPriority){
			// First element is the element without priority
			$el				= $this->formElements[0];
			$el->priority	= $newPriority;
			$this->updatePriority($el);
			return;
		}
		
		//Loop over all elements and give them the new priority
		foreach($this->formElements as $el){
			if($el->name == $element->name){
				$el->priority	= $newPriority;
				$this->updatePriority($el);
			}elseif($oldPriority == -1){
				if($el->priority >= $newPriority){
					$el->priority++;
					$this->updatePriority($el);
				}
			}elseif(
				$oldPriority > $newPriority		and 	//we are moving an element upward
				$el->priority >= $newPriority	and		// current priority is bigger then the new prio
				$el->priority < $oldPriority			// current priority is smaller than the old prio
			){
				$el->priority++;
				$this->updatePriority($el);
			}elseif(
				$oldPriority < $newPriority		and 	//we are moving an element downward
				$el->priority > $oldPriority	and		// current priority is bigger then the old prio
				$el->priority < $newPriority			// current priority is smaller than the new prio
			){
				$el->priority--;
				$this->updatePriority($el);
			}
		}
	}

	/**
	 * Checks if the current form is a multi step form
	 * 
	 * @return	bool	True if multistep false otherwise
	 */
	function isMultiStep(){
		if(empty($this->isMultiStepForm)){
			foreach($this->formElements as $el){
				if($el->type == 'formstep'){
					$this->isMultiStepForm	= true;
					return true;
				}
			}

			$this->isMultiStepForm	= false;
			return false;
		}else{
			return $this->isMultiStepForm;
		}
	}
	
	/**
	 * Get formresults of the current form
	 * 
	 * @param	int		$userId			Optional the user id to get the results of. Default null
	 * @param	int		$submissionId	Optional a specific id. Default null
	 * 
	 * 
	 */
	function getSubmissionData($userId=null, $submissionId=null, $all=false){
		global $wpdb;
		
		$query				= "SELECT * FROM {$this->submissionTableName} WHERE form_id={$this->formData->id}";
		if(is_numeric($submissionId)){
			$query .= " and id='$submissionId'";
		}elseif(is_numeric($userId)){
			$query .= " and userid='$userId'";
		}
		
		if(!$this->showArchived && $submissionId == null){
			$query .= " and archived=0";
		}

		// Limit the amount to 100
		if(isset($_POST['pagenumber']) && is_numeric($_POST['pagenumber'])){
			$this->currentPage	= $_POST['pagenumber'];

			if(isset($_POST['prev'])){
				$this->currentPage--;
			}
			if(isset($_POST['next'])){
				$this->currentPage++;
			}
			$start	= $this->currentPage * $this->pageSize;
		}else{
			$start				= 0;
			$this->currentPage	= 0;
		}

		$query	= apply_filters('sim_formdata_retrieval_query', $query, $userId, $this->formName);

		// Get the total
		$result	= $wpdb->get_results(str_replace('*', 'count(*) as total', $query));
		if(empty($result)){
			$this->total	= 0;
		}else{
			$this->total	= $result[0]->total;
		}

		if(!$all){
			$query	.= " LIMIT $start, $this->pageSize";
		}

		// Get results
		$result	= $wpdb->get_results($query);
		$result	= apply_filters('sim_retrieved_formdata', $result, $userId, $this->formName);

		$this->submissionData		= $result;
		
		if(is_numeric($submissionId)){
			$this->submissionData	= $this->submissionData[0];
			$this->formResults 		= maybe_unserialize($this->submissionData->formresults);
		}else{
			// unserialize
			foreach($this->submissionData as &$data){
				$data->formresults	= unserialize($data->formresults);
			}

			$this->processSplittedData();
		}
		
		if($wpdb->last_error !== ''){
			SIM\printArray($wpdb->print_error());
		}
	}

	/**
	 * This function creates seperated entries from entries with an splitted value
	 */
	function processSplittedData(){
		if(!empty($this->formData->settings['split'])){
			$fieldMainName	= $this->formData->settings['split'];
			
			//loop over all submissions
			foreach($this->submissionData as $key=>$entry){
				// loop over all entries of the split key
				foreach($entry->formresults[$fieldMainName] as $subKey=>$array){
					// Should always be an array
					if(is_array($array)){
						// Check if it has data
						$hasData	= false;
						foreach($array as $value){
							if(!empty($value)){
								$hasData = true;
								break;
							}
						}

						// If it has data add as a seperate item to the submission data
						if($hasData){
							$newSubmission	= clone $entry;
							// Mark this submission as archived if needed
							if(isset($array['archived'])){
								if($this->showArchived){
									$newSubmission->archived	= true;
									unset($array['archived']);
								}else{
									continue;
								}
							}
							// Add the array to the formresults array
							$newSubmission->formresults = array_merge($entry->formresults, $array);

							// remove the index value from the copy
							unset($newSubmission->formresults[$fieldMainName]);

							// Add the subkey
							$newSubmission->sub_id	= $subKey;

							// Copy the entry
							$this->submissionData[]	= $newSubmission;
						}
					}
				}

				// remove the original entry
				unset($this->submissionData[$key]);
			}
		}
	}
	
	/**
	 * Builds the array with default values for the current user
	 */
	function buildDefaultsArray(){
		//Only create one time
		if(empty($this->defaultValues)){
			if(empty($this->formName)){
				$this->formName	= $this->formData->name;
			}

			$this->defaultValues		= (array)$this->user->data;
			//Change ID to userid because its a confusing name
			$this->defaultValues['user_id']	= $this->defaultValues['ID'];
			unset($this->defaultValues['ID']);
			
			$this->defaultArrayValues	= [];
			
			foreach(['user_pass', 'user_activation_key', 'user_status', 'user_level'] as $field){
				unset($this->defaultValues[$field]);
			}
			
			//get defaults from filters
			$this->defaultValues		= apply_filters('sim_add_form_defaults', $this->defaultValues, $this->userId, $this->formName);
			$this->defaultArrayValues	= apply_filters('sim_add_form_multi_defaults', $this->defaultArrayValues, $this->userId, $this->formName);
			
			ksort($this->defaultValues);
			ksort($this->defaultArrayValues);
		}
	}
	
	/**
	 * Gets the html of form element
	 * 
	 * @param	object	$element	The element
	 * @param	int		$width		The width of the element. Default 100
	 * 
	 * @return	string				The html
	 */
	function getElementHtml($element, $width=100){
		$html		= '';
		$elClass	= '';
		$elOptions	= '';

		/*
			ELEMENT OPTIONS
		*/
		if(!empty($element->options)){
			//Store options in an array
			$options	= explode("\n", $element->options);
			
			//Loop over the options array
			foreach($options as $option_key=>$option){
				//Remove starting or ending spaces and make it lowercase
				$option 		= trim($option);

				$optionType		= explode('=',$option)[0];
				$optionValue	= str_replace('\\\\', '\\', explode('=',$option)[1]);

				if($optionType == 'class'){
					$elClass .= " $optionValue";
				}else{
					if(!in_array($optionType, ['pattern', 'title', 'accept'])){
						$optionValue = str_replace([' ', ',', '(', ')'],['_', '', '', ''], $optionValue);
					}

					//remove any leading "
					$optionValue	= trim($optionValue, '\'"');

					//Write the corrected option as html
					$elOptions	.= " $optionType='$optionValue'";
				}
			}
		}
		
		if($element->type == 'p'){
			$html = wp_kses_post($element->text);
			$html = "<div name='{$element->name}'>".$this->deslash($html)."</div>";
		}elseif($element->type == 'php'){
			//we store the functionname in the html variable replace any double \ with a single \
			$functionName 						= str_replace('\\\\','\\',$element->functionname);
			//only continue if the function exists
			if (function_exists( $functionName ) ){
				$html	= $functionName($this->userId);
			}else{
				$html	= "php function '$functionName' not found";
			}
		}elseif(in_array($element->type, ['multi_start','multi_end'])){
			$html 		= "";
		}elseif(in_array($element->type, ['info'])){
			//remove any paragraphs
			$content = str_replace('<p>','',$element->text);
			$content = str_replace('</p>','',$content);
			$content = $this->deslash($content);
			
			$html = "<div class='infobox' name='{$element->name}'>";
				$html .= '<div style="float:right">';
					$html .= '<p class="info_icon"><img draggable="false" role="img" class="emoji" alt="ℹ" src="'.PICTURESURL.'/info.png"></p>';
				$html .= '</div>';
				$html .= "<span class='info_text'>$content</span>";
			$html .= '</div>';
		}elseif(in_array($element->type, ['file','image'])){
			$name		= $element->name;
			
			// Element setting
			if(!empty($element->foldername)){
				if(strpos($element->foldername, "private/") !== false){
					$targetDir	= $element->foldername;
				}else{
					$targetDir	= "private/".$element->foldername;
				}
			} 
			// Form setting
			if(empty($targetDir)){
				$targetDir = $this->formData->settings['upload_path'];
			}
			// Default setting
			if(empty($targetDir)){
				$targetDir = 'form_uploads/'.$this->formData->settings['formname'];
			}

			if(empty($this->formData->settings['save_in_meta'])){
				$library	= false;
				$metakey	= '';
				$userId		= '';
			}else{
				$library	= $element->library;
				$metakey	= $name;
				$userId		= $this->userId;
			}
			//Load js			
			$uploader = new SIM\Fileupload($userId, $name, $targetDir, $element->multiple, $metakey, $library, '', false);
			
			$html = $uploader->getUploadHtml($elOptions);
		}else{
			/*
				ELEMENT TAG NAME
			*/
			if(in_array($element->type, ['formstep','info'])){
				$elType	= "div";
			}elseif(in_array($element->type,array_merge($this->nonInputs,['select','textarea']))){
				$elType	= $element->type;
			}else{
				$elType	= "input type='{$element->type}'";
			}
			
			/* 				
				ELEMENT NAME
			 */
			//Get the field name, make it lowercase and replace any spaces with an underscore
			$elName	= $element->name;
			// [] not yet added to name
			if(in_array($element->type,['radio','checkbox']) and strpos($elName, '[]') === false) {
				$elName .= '[]';
			}
			
			/*
				ELEMENT ID
			*/
			//datalist needs an id to work as well as mandatory elements for use in anchor links
			if($element->type == 'datalist' or $element->mandatory or $element->recommended){
				$elId	= "id='$elName'";
			}
			
			/*
				ELEMENT CLASS
			*/
			$elClass = " formfield";
			switch($element->type){
				case 'label':
					$elClass .= " formfieldlabel";
					break;
				case 'button':
					$elClass .= " button";
					break;
				case 'formstep':
					$elClass .= " formstep stephidden";
					break;
				default:
					$elClass .= " formfieldinput";
			}
			
			/*
				BUTTON TYPE
			*/
			if($element->type == 'button'){
				$elOptions	.= " type='button'";
			}
			/*
				ELEMENT VALUE
			*/
			$values	= $this->getFieldValues($element);
			if($this->multiwrap || !empty($element->multiple)){
				if(strpos($elType,'input') !== false){
					$elValue	= "value='%value%'";
				}
			}elseif(!empty($values)){				
				//this is an input and there is a value for it
				if(empty($this->formData->settings['save_in_meta'])){
					$val		= array_values((array)$values['defaults'])[0];
				}else{
					$val		= array_values((array)$values['metavalue'])[0];
				}
				
				if(strpos($elType,'input') !== false && !empty($val)){
					$elValue	= "value='$val'";
				}else{
					$elValue	= "";
				}
			}
			
			/*
				ELEMENT TAG CONTENTS
			*/
			$elContent = "";
			if($element->type == 'textarea'){
				$elContent = $values['metavalue'][0];
			}elseif(!empty($element->text)){
				switch($element->type){
					case 'formstep':
						$elContent = "<h3>{$element->text}</h3>";
						break;
					case 'label':
						$elContent = "<h4 class='labeltext'>{$element->text}</h4>";;
						break;
					case 'button':
						$elContent = $element->text;
						break;
					default:
						$elContent = "<label class='labeltext'>{$element->text}</label>";
				}
			}
			
			switch($element->type){
				case 'select':
					$elContent .= "<option value=''>---</option>";
					$options	= $this->getFieldOptions($element);

					$selValues	= '';
					if(!empty($values['metavalue'])){
						$selValues	= array_map('strtolower', (array)$values['metavalue']);
					}
		
					foreach($options as $key=>$option){
						if(in_array(strtolower($option), $selValues) || in_array(strtolower($key), $selValues)){
							$selected	= 'selected';
						}else{
							$selected	= '';
						}
						$elContent .= "<option value='$key' $selected>$option</option>";
					}
					break;
				case 'datalist':
					$options	= $this->getFieldOptions($element);
					foreach($options as $key=>$option){
						$elContent .= "<option data-value='$key' value='$option'>";
					}
					break;
				case 'radio':
				case 'checkbox':
					$options	= $this->getFieldOptions($element);
					$html		= "<div class='checkbox_options_group formfield'>";
					
					$lowValues	= [];
					
					$default_key				= $element->default_value;
					if(!empty($default_key)){
						$lowValues[]	= strtolower($this->defaultValues[$default_key]);
					}
					foreach((array)$values['metavalue'] as $key=>$val){
						if(is_array($val)){
							foreach($val as $v){
								$lowValues[] = strtolower($v);
							}
						}else{
							$lowValues[] = strtolower($val);
						}
					}

					foreach($options as $key=>$option){
						if($this->multiwrap){
							$checked	= '%checked%';
						}elseif(in_array(strtolower($option), $lowValues) or in_array(strtolower($key), $lowValues)){
							$checked	= 'checked';
						}else{
							$checked	= '';
						}
						
						$html .= "<label>";
							$html .= "<$elType name='$elName' class='$elClass' $elOptions value='$key' $checked>";
							$html .= "<span class='optionlabel'>$option</span>";
						$html .= "</label><br>";
					}

					$html .= "</div>";
					break;
				default:
					$elContent .= "";
			}
			
			//close the field
			if(in_array($element->type,['select','textarea','button','datalist'])){
				$elClose	= "</{$element->type}>";
			}else{
				$elClose	= "";
			}
			
			//only close a label field if it is not wrapped
			if($element->type == 'label' and empty($element->wrap)){
				$elClose	= "</label>";
			}
			
			/*
				ELEMENT MULTIPLE
			*/
			if(!empty($element->multiple)){
				if($element->type == 'select'){
					$html	= "<$elType  name='$elName' $elId class='$elClass' $elOptions>";
				}else{
					$html	= "<$elType  name='$elName' $elId class='$elClass' $elOptions value='%value%'>$elContent$elClose";
				}
					
				$this->processMultiFields($element, 'multifield', $width, $values, $html);
				$html	= "<div class='clone_divs_wrapper'>";
				foreach($this->multiInputsHtml as $h){
					$html	.= $h;
				}
				$html	.= '</div>';
			}elseif(empty($html)){
				$html	= "<$elType  name='$elName' $elId class='$elClass' $elOptions $elValue>$elContent$elClose";
			}
		}
		
		//remove unnessary whitespaces
		$html = preg_replace('/\s+/', ' ', $html);
		
		//check if we need to transform a keyword to date 
		preg_match_all('/%([^%;]*)%/i', $html,$matches);
		foreach($matches[1] as $key=>$keyword){
			$keyword = str_replace('_',' ',$keyword);
			
			//If the keyword is a valid date keyword
			if(strtotime($keyword) != ''){
				//convert to date
				$dateString = date("Y-m-d", strtotime($keyword));
				
				//update form element
				$html = str_replace($matches[0][$key], $dateString, $html);
			}
		}

		return $html;
	}
	
	/**
	 * Get the values of an element
	 * 
	 * @param	object	$element		The element
	 * 
	 * @return	array					The array of values
	 */
	function getFieldValues($element){
		$values	= [];

		// Do not return default values when requesting the html over rest api
		if(defined('REST_REQUEST')){
			return $values;
		}
		
		if(in_array($element->type,$this->nonInputs)){
			return [];
		}

		$this->buildDefaultsArray();

		//get the fieldname, remove [] and split on remaining [
		$fieldName	= str_replace(']', '', explode('[', str_replace('[]', '', $element->name)));
		
		//retrieve meta values if needed
		if(!empty($this->formData->settings['save_in_meta'])){
			//only load usermeta once
			if(!is_array($this->usermeta)){
				//usermeta comes as arrays, only keep the first
				$this->usermeta	= [];
				foreach(get_user_meta($this->userId) as $key=>$meta){
					$this->usermeta[$key]	= $meta[0];
				}
				$this->usermeta	= apply_filters('sim_forms_load_userdata', $this->usermeta, $this->userId);
			}
		
			if(count($fieldName) == 1){
				//non array name
				$fieldName					= $fieldName[0];
				$values['metavalue']		= (array)maybe_unserialize($this->usermeta[$fieldName]);
			}else{
				//an array of values, we only want a specific one
				$values['metavalue']		= (array)maybe_unserialize($this->usermeta[$fieldName[0]]);
				unset($fieldName[0]);
				//loop over all the subkeys, and store the value until we have our final result
				foreach($fieldName as $v){
					$values['metavalue'] = (array)$values['metavalue'][$v];
				}
			}
		}
		
		//add default values
		if(empty($element->multiple) or in_array($element->type, ['select','checkbox'])){
			$defaultKey					= $element->default_value;
			if(!empty($defaultKey)){
				$values['defaults']		= $this->defaultValues[$defaultKey];
			}
		}else{
			$defaultKey					= $element->defaultArrayValue;
			if(!empty($defaultKey)){
				$values['defaults']		= $this->defaultArrayValues[$defaultKey];
			}
		}

		return $values;
	}
	
	/**
	 * Gets the options of an element like styling etc.
	 * 
	 * @param	object	$element		The element
	 * 
	 * @return	array					Array of options
	 */
	function getFieldOptions($element){
		$options	= [];
		//add element values
		$elValues	= explode("\n", $element->valuelist);
		
		if(!empty($elValues[0])){
			foreach($elValues as $value){
				$split = explode('|', $value);
				
				//Remove starting or ending spaces and make it lowercase
				$value 			= trim($split[0]);
				$escapedValue 	= str_replace([' ', ',', '(', ')'], ['_', '', '', ''], $value);
				
				if(!empty($split[1])){
					$displayName	= $split[1];
				}else{
					$displayName	= $value;
				}
				
				$options[$escapedValue]	= $displayName;
			}

			asort($options);
		}
		
		$defaultArrayKey				= $element->default_array_value;
		if(!empty($defaultArrayKey)){
			$this->buildDefaultsArray();
			$options	= (array)$this->defaultArrayValues[$defaultArrayKey]+$options;
		}
		
		return $options;
	}
	
	/**
	 * Renders the html for element who can have multiple inputs
	 * 
	 * @param	object	$element		The element
	 * @param	int		$key			The index of the element list
	 * @param	int		$width			The width of the elements
	 * @param	array	$values			The values of the inputs. Default null
	 * @param	string	$elementHtml	Existing element html. Default empty
	 */
	function processMultiFields($element, $key, $width, $values=null, $elementHtml=''){
		if($this->multiwrap or !is_numeric($key)){
			if(is_numeric($key)){
				//Check if element needs to be hidden
				if(!empty($element->hidden)){
					$hidden = ' hidden';
				}else{
					$hidden = '';
				}
				
				//if the current element is required or this is a label and the next element is required
				if(
					!empty($element->required)	or
					$element->type == 'label'		and
					$this->nextElement->required
				){
					$hidden .= ' required';
				}
		
				$elementHtml = $this->getElementHtml($element);
				
				//close the label element after the field element
				if($this->wrap and !isset($element->wrap)){
					$elementHtml .= "</div>";
					if($this->wrap == 'label'){
						$elementHtml .= "</label>";
					}
					$this->wrap = false;
				}elseif(!$this->wrap){
					if($element->type == 'info'){
						$elementHtml .= "<div class='inputwrapper$hidden info'>";
					}else{
						if(!empty($element->wrap)){
							$class	= 'flex';
						}else{
							$class	= '';
						}
						$elementHtml .= "<div class='inputwrapper$hidden $class' style='width:$width%;'>";
					}
				}
				
				if(in_array($element->type,$this->nonInputs)){	
					//Get the field values of the next element as this does not have any
					$values		= $this->getFieldValues($this->nextElement);
				}else{
					$values		= $this->getFieldValues($element);
				}

				if(empty($this->formData->settings['save_in_meta'])){
					$values		= array_values((array)$values['defaults']);
				}else{
					$values		= array_values((array)$values['metavalue']);
				}
				
				if(
					$this->wrap == false								and
					isset($element->wrap)								and 			//we should wrap around next element
					is_object($this->nextElement)						and 			// and there is a next element
					!in_array($this->nextElement->type, ['select','php','formstep'])	// and the next element type is not a select or php element
				){
					$this->wrap = $element->type;
				}
			//key is not numeric so we are dealing with a multi input field
			}else{
				//add label to each entry if prev element is a label and wrapped with this one
				if($this->prevElement->type	== 'label' and !empty($this->prevElement->wrap)){
					$this->prevElement->text = $this->prevElement->text.' %key%';
					$prevLabel = $this->getElementHtml($this->prevElement).'</label>';
				}else{
					$prevLabel	= '';
				}

				if(empty($this->formData->settings['save_in_meta'])){
					$values		= array_values((array)$values['defaults']);
				}else{
					$values		= array_values((array)$values['metavalue']);
				}

				//check how many elements we should render
				$this->multiWrapValueCount	= max(1,count((array)$values));
			}

			//create as many inputs as the maximum value found
			for ($index = 0; $index < $this->multiWrapValueCount; $index++) {
				$val	= $values[$index];
				//Add the key to the fields name
				$html	= preg_replace("/(name='.*?)'/i", "\${1}[$index]'", $elementHtml);
				
				//we are dealing with a select, add options
				if($element->type == 'select'){
					$html .= "<option value=''>---</option>";
					$options	= $this->getFieldOptions($element);
					foreach($options as $option_key=>$option){
						if(strtolower($val) == strtolower($option_key) or strtolower($val) == strtolower($option)){
							$selected	= 'selected';
						}else{
							$selected	= '';
						}
						$html .= "<option value='$option_key' $selected>$option</option>";
					}

					$html  .= "</select>";
				}elseif(in_array($element->type, ['radio','checkbox'])){
					$options	= $this->getFieldOptions($element);

					//make key and value lowercase
					array_walk_recursive($options, function(&$item, &$key){
						$item 	= strtolower($item);
						$key 	= strtolower($key);
					});
					foreach($options as $option_key=>$option){
						$found = false;

						if(is_array($val)){
							foreach($val as $v){
								if(strtolower($v) == $option_key or strtolower($v) == $option){
									$found 	= true;
								}
							}
						}elseif(strtolower($val) == $option_key or strtolower($val) == $option){
							$found 	= true;
						}
						
						if($found){
							//remove the % to leave the selected
							$html	= str_replace('%','',$html);
						}else{
							//remove the %checked% keyword
							$html	= str_replace('%checked%','',$html);
						}
					}
				}else{
					if(is_array($val)){
						$html	= str_replace('%value%',$val[$index],$html);
					}else{
						$html	= str_replace('%value%',$val,$html);
					}
				}
				
				//open the clone div
				if(!is_numeric($key)){
					$this->multiInputsHtml[$index] .= "<div class='clone_div' data-divid='$index'>";
				}

				if($this->prevElement->type == 'multi_start'){
					$this->multiInputsHtml[$index] .= "<div class='clone_div' data-divid='$index' style='display:flex'>";
					$this->multiInputsHtml[$index] .= "<div class='multi_input_wrapper'>";
				}
				
				//add flex to single multi items, re-add the label for each value
				if(!is_numeric($key)){
					$this->multiInputsHtml[$index] .= str_replace('%key%', $index+1, $prevLabel);
					//wrap input AND buttons in a flex div
					$this->multiInputsHtml[$index] .= "<div class='buttonwrapper' style='width:100%; display: flex;'>";
				}
				
				if($element->type == 'label'){
					$nr		= $index+1;
					$html	= str_replace('</h4>'," $nr</h4>", $html);
				}
				//write the element
				$this->multiInputsHtml[$index] .= $html;
				
				//end, write the buttons and closing div
				if(!is_numeric($key) or $this->nextElement->type == 'multi_end'){
					//close any label first before adding the buttons
					if($this->wrap == 'label'){
						$this->multiInputsHtml[$index] .= "</label>";
					}
					
					//close select
					if($element->type == 'select'){
						$this->multiInputsHtml[$index] .= "</select>";
					}
					
					//open the buttons div in case we have not written the flex div
					if(is_numeric($key)){
						$this->multiInputsHtml[$index] .= "</div>";//close multi_input_wrapper div
						$this->multiInputsHtml[$index] .= "<div class='buttonwrapper' style='margin: auto;'>";
					}
							$this->multiInputsHtml[$index] .= "<button type='button' class='add button' style='flex: 1;'>+</button>";
							$this->multiInputsHtml[$index] .= "<button type='button' class='remove button' style='flex: 1;'>-</button>";
						$this->multiInputsHtml[$index] .= "</div>";
					$this->multiInputsHtml[$index] .= "</div>";//close clone_div
				}
			}
		}
	}
	
	/**
	 * Build all html for a particular elemnt including edit controls.
	 * 
	 * @param	object	$element		The element
	 * @param	int		$key			The key in case of a multi element. Default 0
	 * 
	 * @return	string					The html
	 */
	function buildHtml($element, $key=0){
		if(empty($this->formData)){
			$this->loadFormData();
		}
		if(empty($this->formElements)){
			$this->getAllFormElements();
		}
		$this->prevElement		= $this->formElements[$key-1];
		$this->nextElement		= $this->formElements[$key+1];

		if(
			!empty($this->nextElement->multiple) 	and // if next element is a multiple
			$this->nextElement->type != 'file' 	and // next element is not a file
			$element->type == 'label'				and // and this is a label
			!empty($element->wrap)					and // and the label is wrapped around the next element
			!isset($_GET['formbuilder']) 			and	// and we are not building the form
			!defined('REST_REQUEST')				// and we are not doing a REST call
		){
			return;
		}
		//store the prev rendered element before updating the current element
		$prevRenderedElement	= $this->currentElement;
		$this->currentElement	= $element;
				
		//Set the element width to 85 percent so that the info icon floats next to it
		if($key != 0 and $prevRenderedElement->type == 'info'){
			$width = 85;
		//We are dealing with a label which is wrapped around the next element
		}elseif($element->type == 'label'	and !isset($element->wrap) and is_numeric($this->nextElement->width)){
			$width = $this->nextElement->width;
		}elseif(is_numeric($element->width)){
			$width = $element->width;
		}else{
			$width = 100;
		}

		//Load default values for this element
		$elementHtml = $this->getElementHtml($element, $width);
		
		//Check if element needs to be hidden
		if(!empty($element->hidden)){
			$hidden = ' hidden';
		}else{
			$hidden = '';
		}
		
		//if the current element is required or this is a label and the next element is required
		if(
			!empty($element->required)	or
			$element->type == 'label'		and
			$this->nextElement->required
		){
			$hidden .= ' required';
		}
		
		//Add form edit controls if needed
		if($this->editRights and (isset($_GET['formbuilder']) or defined('REST_REQUEST'))){
			$html = " <div class='form_element_wrapper' data-id='{$element->id}' data-formid='{$this->formData->id}' data-priority='{$element->priority}' style='display: flex;'>";
				$html .= "<span class='movecontrol formfieldbutton' aria-hidden='true'>:::</span>";
				$html .= "<div class='resizer_wrapper'>";
					if($element->type == 'info'){
						$html .= "<div class='show inputwrapper$hidden'>";
					}else{
						$html .= "<div class='resizer show inputwrapper$hidden' data-widthpercentage='$width' style='width:$width%;'>";
					}
					
					if($element->type == 'formstep'){
						$html .= ' ***formstep element***</div>';
					}elseif($element->type == 'datalist'){
						$html .= ' ***datalist element***';
					}elseif($element->type == 'multi_start'){
						$html .= ' ***multi answer start***';
						$elementHtml	= '';
					}elseif($element->type == 'multi_end'){
						$html .= ' ***multi answer end***';
						$elementHtml	= '';
					}
					
					
					$html .= $elementHtml;
						//Add a star if this field has conditions
						if(!empty($element->conditions)){
							$html .= "<div class='infobox'>";
								$html .= "<span class='conditions_info formfieldbutton'>*</span>";
								$html .= "<span class='info_text conditions' style='margin:-20px 10px;'>This element has conditions</span>";
							$html .= "</div>";
						}
						$html .= "<span class='widthpercentage formfieldbutton'></span>";
					$html .= "</div>";
				$html .= "</div>";
				$html .= "<button type='button' class='add_form_element button formfieldbutton' 	title='Add an element after this one'>+</button>";
				$html .= "<button type='button' class='remove_form_element button formfieldbutton'	title='Remove this element'>-</button>";
				$html .= "<button type='button' class='edit_form_element button formfieldbutton'	title='Change this element'>Edit</button>";
			$html .= "</div>";
		//just the form
		}else{
			$html	= '';
			//write a formstep div
			if($element->type == 'formstep'){
				//if there is already a formstep written close that one first
				if($this->isFormStep){
					$html .= "</div>";
				//first part of the form, don't hide
				}else{
					$this->isFormStep	= true;
					$html .= "<img class='formsteploader' src='".LOADERIMAGEURL."'>";
				}
				
				$this->formStepCounter	+= 1;
				$html .= $elementHtml;
			}elseif($element->type == 'multi_end'){
				$this->multiwrap	= false;
				//write down all the multi html
				$name	= str_replace('end','start',$element->name);
				$html  .= "<div class='clone_divs_wrapper' name='$name'>";
				foreach($this->multiInputsHtml as $multihtml){
					$html .= $multihtml;
				}
				$html .= '</div>';//close clone_divs_wrapper
				$html .= '</div>';//close inputwrapper

			//just calculate the multi html
			}elseif($this->multiwrap){
				$this->processMultiFields($element, $key, $width);
			//write other elements
			}else{
				//wrap an element and a folowing field in the same wrapper
				// so only write a div if the wrap property is not set
				if(!$this->wrap){
					if($element->type == 'info'){
						$html = "<div class='inputwrapper$hidden info'>";
					}else{
						if(!empty($element->wrap)){
							$class	= 'flex';
						}else{
							$class	= '';
						}
						$html = "<div class='inputwrapper$hidden $class' style='width:$width%;'>";
					}
				}
				
				$html .= $elementHtml;
				if($element->type == 'multi_start'){
					$this->multiwrap				= true;
					
					//we are wrapping so we need to find the max amount of filled in fields
					$i								= $key+1;
					$this->multiWrapValueCount	= 1;
					//loop over all consequent wrapped elements
					while(true){
						$type	= $this->formElements[$i]->type;
						if($type != 'multi_end'){
							if(!in_array($type, $this->nonInputs)){
								//Get the field values and count
								$valueCount		= count($this->getFieldValues($this->formElements[$i]));
								
								if($valueCount > $this->multiWrapValueCount){
									$this->multiWrapValueCount = $valueCount;
								}
							}
							$i++;
						}else{
							break;
						}
					}
				}else{
					//do not close the div if wrap is turned on
					if(
						!$this->wrap									and
						!empty($element->wrap)							and				//we should wrap around next element
						is_object($this->nextElement)					and 			// and there is a next element
						!in_array($this->nextElement->type, ['php','formstep'])	// and the next element type is not a select or php element
					){
						//end a label if the next element is a select
						if($element->type == 'label' and in_array($this->nextElement->type, ['php','select'])){
							$html .= "</label></div>";
						}else{
							$this->wrap = $element->type;
						}
					//we have not started a wrap
					}else{		
						//only close wrap if the current element is not wrapped or it is the last
						if(empty($element->wrap) or !is_array($this->nextElement)){
							//close the label element after the field element or if this the last element of the form and a label
							if(
								$this->wrap == 'label' or 
								($element->type == 'label' and !is_array($this->nextElement))
							){
								$html .= "</label>";
							}
							$this->wrap = false;
							$html .= "</div>";
						}
					}
				}
			}
		}
		return $html;
	}

	/**
	 * Removes any unneeded slashes
	 * 
	 * @param	string	$content	The string to deslash
	 * 
	 * @return	string				The cleaned string
	 */
	function deslash( $content ) {
		$content = preg_replace( "/\\\+'/", "'", $content );
		$content = preg_replace( '/\\\+"/', '"', $content );
		$content = preg_replace( '/https?:\/\/https?:\/\//i', 'https://', $content );
	
		return $content;
	}

	/**
	 * Update an existing form submission
	 * 
	 * @param	bool	$archive	Whether we should archive the submission. Default false
	 * 
	 *  @return	true|WP_Error		The result or error on failure
	 */
	function updateSubmissionData($archive=false){
		global $wpdb;

		$submissionId	= $this->formResults['id'];
		if(!is_numeric($submissionId)){
			if(is_numeric($this->submissionId)){
				$submissionId	= $this->submissionId;
			}elseif(is_numeric($_POST['submissionid'])){
				$submissionId	= $_POST['submissionid'];
			}else{
				SIM\printArray('No submission id found');
				return false;
			}
		}	

		$this->formResults['edittime']	= date("Y-m-d H:i:s");

		//Update the database
		$result = $wpdb->update(
			$this->submissionTableName, 
			array(
				'timelastedited'	=> date("Y-m-d H:i:s"),
				'formresults'		=> maybe_serialize($this->formResults),
				'archived'			=> $archive
			),
			array(
				'id'				=> $submissionId,
			)
		);
		
		if($wpdb->last_error !== ''){
			$message	= $wpdb->print_error();
			if(defined('REST_REQUEST')){
				return new WP_Error('form error', $message);
			}else{
				SIM\printArray($message);
			}
		}elseif($result == false){
			$message	= "No row with id $submissionId found";
			if(defined('REST_REQUEST')){
				return new WP_Error('form error', $message);
			}else{
				SIM\printArray($message);
				SIM\printArray($this->formResults);
			}
		}
		
		return $result;
	}
	
	/**
	 * Returns conditional e-mails with a valid condition
	 * 
	 * @param	array	$conditions		The conditions of a conditional e-mail
	 * 
	 * @return	string|false			The e-mail adres or false if none found
	 */
	function findConditionalEmail($conditions){
		//loop over all conditions
		foreach($conditions as $condition){
			//loop over all elements to find the element with the id specified
			foreach($this->formElements as $element){
				//we found the element with the correct id
				if($element->id == $condition['fieldid']){
					//get the submitted form value
					$formValue = $this->formResults[$element->name];
					
					//if the value matches the conditional value
					if(strtolower($formValue) == strtolower($condition['value'])){
						return $condition['email'];
					}
				}
			}
		}

		return false;
	}

	/**
	 * Filters the e-mail footer url and text
	 * 
	 * @param	array	$footer		The footer array
	 * 
	 * @return	array				The filtered footer array
	 */
	function emailFooter($footer){
		$footer['url']		= $_POST['formurl'];
		$footer['text']		= $_POST['formurl'];
		return $footer;
	}

	/**
	 * Send an e-mail
	 * 
	 * @param	string	$trigger	One of 'submitted' or 'fieldchanged'. Default submitted
	 */
	function sendEmail($trigger='submitted'){
		$emails = $this->formData->emails;
		
		foreach($emails as $key=>$email){
			if($email['emailtrigger'] == $trigger){
				if($trigger == 'fieldchanged'){
					$formValue	= '';
					//loop over all elements to find the element with the id specified
					foreach($this->formElements as $element){
						//we found the element with the correct id
						if($element->id == $email['conditionalfield']){
							//get the submitted form value
							$formValue = $this->formResults[$element->name];
							
							break;
						}
					}

					//do not proceed if there is no match
					if(strtolower($formValue) != strtolower($email['conditionalvalue'])){
						continue;
					}
				}
				
				$from	= '';
				//Send e-mail from conditional e-mail adress
				if($email['fromemail'] == 'conditional'){
					$from 	= $this->findConditionalEmail($email['conditionalfromemail']);
				}elseif($email['fromemail'] == 'fixed'){
					$from	= $this->processPlaceholders($email['from']);
				}

				if(empty($from)){
					SIM\printArray("No from email found for email $key");
				}
								
				$to		= '';
				if($email['emailto'] == 'conditional'){
					$to = $this->findConditionalEmail($email['conditionalemailto']);
				}elseif($email['emailto'] == 'fixed'){
					$to		= $this->processPlaceholders($email['to']);
				}
				
				if(empty($to)){
					SIM\printArray("No to email found for email $key");
					continue;
				}

				$subject	= $this->processPlaceholders($email['subject']);
				$message	= $this->processPlaceholders($email['message']);
				$headers	= explode("\n",$email['headers']);
				if(!empty($from)){$headers[]	= "Reply-To: $from";}
				
				$files		= $this->processPlaceholders($email['files']);
				
				//Send the mail
				if($_SERVER['HTTP_HOST'] != 'localhost'){
					add_filter('sim_email_footer_url', [$this, 'emailFooter']);

					SIM\printArray("Sending e-mail to $to");
					$result = wp_mail($to , $subject, $message, $headers, $files);
					if($result === false){
						SIM\printArray("Sending the e-mail failed");
					}
					remove_filter('sim_email_footer_url', [$this, 'emailFooter']);
				}
			}
		}
	}
	
	/**
	 * Auto archive form submission based on the form settings
	 */
	function autoArchive(){
		//get all the forms
		$this->getForms();
		
		//loop over all the forms
		foreach($this->forms as $form){
			$settings = maybe_unserialize($form->settings);
			
			//check if auto archive is turned on for this form
			if($settings['autoarchive'] == 'true'){
				$this->formName				= $form->name;
				$this->formData				= $form;
				$this->formData->settings 	= maybe_unserialize(utf8_encode($this->formData->settings));

				$fieldMainName		= $settings['split'];
				
				//Get all submissions of this form
				$this->getSubmissionData(null, null, true);
				
				$triggerName	= $this->getElementById($settings['autoarchivefield'], 'name');
				$triggerValue	= $settings['autoarchivevalue'];
				$pattern		= '/'.$fieldMainName."\[[0-9]+\]\[([^\]]+)\]/i";
				if(preg_match($pattern, $triggerName,$matches)){
					$triggerName	= $matches[1];
				}
				
				//check if we need to transform a keyword to a date 
				$pattern = '/%([^%;]*)%/i';
				//Execute the regex
				preg_match_all($pattern, $triggerValue,$matches);
				if(!is_array($matches[1])){
					SIM\printArray($matches[1]);
				}else{
					foreach((array)$matches[1] as $keyword){
						//If the keyword is a valid date keyword
						if(strtotime($keyword)){
							//convert to date
							$triggerValue = date("Y-m-d", strtotime(str_replace('%', '', $triggerValue)));
						}
					}
				}
				
				//loop over all submissions to see if we need to archive
				foreach($this->submissionData as &$subData){
					$this->formResults		= $subData->formresults;
					
					$this->submissionId		= $subData->id;

					//there is no trigger value found in the results, check multi value array
					if(empty($this->formResults[$triggerName])){
						//loop over all multi values
						foreach($this->formResults[$fieldMainName] as $subId=>$sub){
							//we found a match for a sub entry, archive it
							$val	= $sub[$triggerName];						
							if(
								$val == $triggerValue || 						//value is the same as the trigger value or
								(
									strtotime($triggerValue) && 				// trigger value is a date
									strtotime($val) &&							// this value is a date
									strtotime($val) < strtotime($triggerValue)	// value is smaller than the trigger value
								)
							){
								$this->formResults[$fieldMainName][$subId]['archived'] = true;
								
								//update in db
								$this->checkIfAllArchived($this->formResults[$fieldMainName]);
							}
						}
					}else{
						//if the form value is equal to the trigger value it needs to be to be archived
						if($this->formResults[$triggerName] == $triggerValue){
							//Check if we are dealing with an subvalue
							if($subData->sub_id){
								$subData->archived	= true;
								$this->checkIfAllArchived($subData);
							}else{
								$this->updateSubmissionData(true);
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * Checks if all sub entries are archived, if so archives the whole
	 * 
	 * @param	object	$data	the data to check
	 */
	function checkIfAllArchived($data){
		//check if all subfields are archived or empty
		$allArchived = true;

		// check if we have a subsubmission who is not yet archived
		foreach($this->submissionData as $submission){
			// If this submission belomgs to the same as the given submission and it is not archived
			if($submission->id == $data->id && !$submission->archived){
				$allArchived = false;
				break;
			}
		}

		// Get the original submission
		$this->getSubmissionData(null, $submission->id);

		// Update the original
		$this->formResults[$this->formData->settings['split']][$data->sub_id]['archived']	= true;
		
		//update and mark as archived if all entries are empty or archived
		$this->updateSubmissionData($allArchived);
	}
	
	/**
	 * Creates a dropdown with all form elements
	 * 
	 * @param	int		$selectedId	The id of the current selected element in the dropdown. Default empty
	 * @param	int		$elementId	the id of the element
	 * 
	 * @return	string				The dropdown html
	 */
	function inputDropdown($selectedId, $elementId=''){
		if($selectedId == ''){
			$html = "<option value='' selected>---</option>";
		}else{
			$html = "<option value=''>---</option>";
		}
		
		foreach($this->formElements as $key=>$element){
			//do not include the element itself do not include non-input types
			if($element->id != $elementId and !in_array($element->type, ['label','info','datalist','formstep'])){
				$name = ucfirst(str_replace('_',' ',$element->name));
				
				//Check which option is the selected one
				if($selectedId != '' and $selectedId == $element->id){
					$selected = 'selected';
				}else{
					$selected = '';
				}
				$html .= "<option value='{$element->id}' $selected>$name</option>";
			}
		}
		
		return $html;
	}
	
	/**
	 * Replaces placeholder with the value
	 * 
	 * @param	string	$string		THe string to check for placeholders
	 * 
	 * @return	string				The filtered string
	 */
	function processPlaceholders($string){
		if(empty($string)){
			return $string;
		}

		$this->formResults['submissiondate']	= date('d F y', strtotime($this->formResults['submissiontime']));
		$this->formResults['editdate']			= date('d F y', strtotime($this->formResults['edittime']));

		$pattern = '/%([^%;]*)%/i';
		//Execute the regex
		preg_match_all($pattern, $string, $matches);
		
		//loop over the results
		foreach($matches[1] as $key=>$match){
			if(empty($this->formResults[$match])){
				//remove the placeholder, there is no value
				$string = str_replace("%$match%", '', $string);
			}elseif(is_array($this->formResults[$match])){
				$files	= $this->formResults[$match];
				$string = array_map(function($value){
					return ABSPATH.$value;
				}, $files);
			}else{
				//replace the placeholder with the value
				$replaceValue	= str_replace('_', ' ', $this->formResults[$match]);
				$string 		= str_replace("%$match%", $replaceValue, $string);
			}
		}
		
		return $string;
	}

	/**
	 * Main function to show all
	 * 
	 * @param	array	$atts	The attribute array of the WP Shortcode
	 */
	function formBuilder($atts){
		$this->processAtts($atts);
				
		$this->loadFormData();
		
		// We cannot use the minified version as the dynamic js files depend on the function names
		wp_enqueue_script('sim_forms_script');

		if(
			array_intersect($this->userRoles, $this->submitRoles) != false	and	// we have the permission to submit on behalf on someone else
			is_numeric($_GET['userid'])										and // and the userid parameter is set in the url
			empty($atts['userid'])												// and the user id is not given in the shortcode 
		){
			$this->userId	= $_GET['userid'];
		}

		ob_start();

		//add formbuilder.js
		if($this->editRights and isset($_GET['formbuilder'])){
			//Formbuilder js
			wp_enqueue_script( 'sim_formbuilderjs');
		}
		
		//Load conditional js if available and needed
		if(!isset($_GET['formbuilder'])){
			if($_SERVER['HTTP_HOST'] == 'localhost'){
				$jsPath		= $this->jsFileName.'.js';
			}else{
				$jsPath		= $this->jsFileName.'.min.js';
			}

			if(!file_exists($jsPath)){
				SIM\printArray("$jsPath does not exist!\nBuilding it now");
				
				$path	= plugin_dir_path(__DIR__)."js/dynamic";
				if (!is_dir($path)) {
					mkdir($path, 0777, true);
				}
				
				//build initial js if it does not exist
				$this->createJs();
			}

			//Only enqueue if there is content in the file
			if(file_exists($jsPath) and filesize($jsPath) > 0){
				wp_enqueue_script( "dynamic_{$this->formName}forms", SIM\pathToUrl($jsPath), array('sim_forms_script'), $this->formData->version, true);
			}
		}
		
		?>
		<div class="sim_form wrapper">
			<?php
			
			if($this->editRights){
				//redirect  to formbuilder if we have rights and no formfields defined yet
				if(empty($this->formElements) and !isset($_GET['formbuilder'])){
					$url = SIM\currentUrl();
					if(strpos($url,'?') === false){
						$url .= '/?';
					}else{
						$url .= '&';
					}
					header("Location: {$url}formbuilder=yes");
				}
				
				if(isset($_GET['formbuilder'])){
					$this->addElementModal();

					?>
					<button class="button tablink formbuilderform<?php if(!empty($this->formElements)){echo ' active';}?>"	id="show_element_form" data-target="element_form">Form elements</button>
					<button class="button tablink formbuilderform<?php if(empty($this->formElements)){echo ' active';}?>"	id="show_form_settings" data-target="form_settings">Form settings</button>
					<button class="button tablink formbuilderform"															id="show_form_emails" data-target="form_emails">Form emails</button>
					<?php 
				}else{
					//button
					echo "<a href='?formbuilder=yes' class='button sim'>Show formbuilder</a>";
				}
				?>
				
				<div class="tabcontent<?php if(empty($this->formElements)){echo ' hidden';}?>" id="element_form">
					<?php
					if(isset($_GET['formbuilder'])){
						if(empty($this->formElements)){
							?>
							<div name="formbuildbutton">
								<p>No formfield defined yet.</p>
								<button name='createform' class='button' data-formname='<?php echo $this->formName;?>'>Add fields to this form</button>
							</div>
							<?php
						}else{
							?>
							<div class="formeditbuttons_wrapper">
								<button name='editform' class='button' data-action='hide' style='padding-top:0px;padding-bottom:0px;'>Hide form edit controls</button>
								<a href="." class="button sim">Show enduser form</a>
							</div>
							<?php
						}
					}
			}
			
			$this->showForm();
			if($this->editRights and isset($_GET['formbuilder'])){
				?>
				</div>
				
				<div class="tabcontent<?php if(!empty($this->formElements)){echo ' hidden';}?>" id="form_settings">
					<?php $this->formSettingsForm();?>
				</div>
				
				<div class="tabcontent hidden" id="form_emails">
					<?php $this->formEmailsForm();?>
				</div>
			<?php
			}
			?>
		</div>
		<?php
		
		//needed for force_balance_tags
		include_once( ABSPATH . 'wp-includes/formatting.php');

		$html	= ob_get_clean();
		//close any open html tags
		return force_balance_tags($html);
	}
	
	/**
	 * The modal to add an element to the form
	 */
	function addElementModal(){
		?>
		<div class="modal add_form_element_modal hidden">
			<!-- Modal content -->
			<div class="modal-content" style='max-width:90%;'>
				<span id="modal_close" class="close">&times;</span>
				
				<button class="button tablink formbuilderform active"	id="show_element_builder" data-target="element_builder">Form element</button>
				<button class="button tablink formbuilderform"			id="show_element_conditions" data-target="element_conditions">Element conditions</button>
				
				<div class="tabcontent" id="element_builder">
					<?php echo $this->elementBuilderForm();?>
				</div>
				
				<div class="tabcontent hidden" id="element_conditions">
					<div class="element_conditions_wrapper"></div>
				</div>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Show the form
	 */
	function showForm(){
		$formName	= $this->formData->settings['formname'];
		$buttonText	= $this->formData->settings['buttontext'];
		if(empty($buttonText)){
			$buttonText = 'Submit the form';
		}
		
		if(!empty($formName)){
			echo "<h3>$formName</h3>";
		}	

		if(array_intersect($this->userRoles, $this->submitRoles) != false and !empty($this->formData->settings['save_in_meta'])){
			echo SIM\userSelect("Select an user to show the data of:");
		}
		echo do_action('sim_before_form', $this->formName);

		$formClass	= "sim_form";
		if(isset($_GET['formbuilder'])){
			$formClass	.= ' builder';
		}

		$dataset	= "data-formid=".$this->formData->id;
		if(!empty($this->formData->settings['formreset'])){
			$dataset .= " data-reset='true'";
		}
		if(!empty($this->formData->settings['save_in_meta'])){
			$dataset .= " data-addempty='true'";
		}

		?>
		<form action='' method='post' class='<?php echo $formClass;?>' <?php echo $dataset;?>>
			<div class='form_elements'>
				<input type='hidden' name='formid'		value='<?php echo $this->formData->id;?>'>
				<input type='hidden' name='formurl'		value='<?php echo SIM\currentUrl();?>'>
				<input type='hidden' name='userid'		value='<?php echo $this->userId;?>'>
		<?php		
			foreach($this->formElements as $key=>$element){
				echo $this->buildHtml($element, $key);
			}
			
		//close the last formstep if needed
		if($this->isFormStep){
			?>
			</div>
			<div class="multistepcontrols hidden">
				<div class="multistepcontrols_wrapper">
					<div style="flex:1;">
						<button type="button" class="button" name="prevBtn">Previous</button>
					</div>
					
					<!-- Circles which indicates the steps of the form: -->
					<div style="flex:1;text-align:center;margin:auto;">
					<?php
					for ($x = 1; $x <= $this->formStepCounter; $x++) {
						echo '<span class="step"></span>';
					}
					?>
					</div>
				
					<div style="flex:1;">
						<button type="button" class="button nextBtn" name="nextBtn">Next</button>
						<?php echo SIM\addSaveButton('submit_form', $buttonText, 'hidden form_submit');?>
					</div>
				</div>
			</div>
			<?php
		}
			//only show submit button when not editing the form
			if($this->editRights and isset($_GET['formbuilder'])){
				$hidden = 'hidden';
			}else{
				$hidden = '';
			}
			if(!$this->isFormStep and !empty($this->formElements)){
				echo SIM\addSaveButton('submit_form', $buttonText, "$hidden form_submit");
			}
			?>
			</div>
		</form>
		<?php
	}
	
	/**
	 * Form to change form settings
	 */
	function formSettingsForm(){
		global $wp_roles;
		
		$settings = $this->formData->settings;
		
		//Get all available roles
		$userRoles = $wp_roles->role_names;
		
		//Sort the roles
		asort($userRoles);
		
		?>
		<div class="element_settings_wrapper">
			<form action='' method='post' class='sim_form builder'>
				<div class='form_elements'>
					<input type='hidden' class='formbuilder' name='formid'	value='<?php echo $this->formData->id;?>'>
					
					<label class="block">
						<h4>Submit button text</h4>
						<input type='text' class='formbuilder formfieldsetting' name='settings[buttontext]' value="<?php echo $settings['buttontext']?>">
					</label>
					
					<label class="block">
						<h4>Succes message</h4>
						<input type='text' class='formbuilder formfieldsetting' name='settings[succesmessage]' value="<?php echo $settings['succesmessage']?>">
					</label>
					
					<label class="block">
						<h4>Form name</h4>
						<input type='text' class='formbuilder formfieldsetting' name='settings[formname]' value="<?php echo $settings['formname']?>">
					</label>
					<br>
					
					<label class='block'>
						<?php
						if(!empty($settings['save_in_meta'])){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						?>
						<input type='checkbox' class='formbuilder formfieldsetting' name='settings[save_in_meta]' value='save_in_meta' <?php echo $checked;?>>
						Save submissiondata in usermeta table
					</label>
					<br>

					<label class="block">
						<h4>Form url</h4>
						<?php
						if(!empty($settings['formnurl'])){
							$url	= $settings['formnurl'];
						}else{
							$url	= str_replace(['?formbuilder=yes', '&formbuilder=yes'], '', SIM\currentUrl());
						}

						?>
						<input type='url' class='formbuilder formfieldsetting' name='settings[formnurl]' value="<?php echo $url?>">
					</label>
					<br>
					
					<?php
					//check if we have any upload fields in this form
					$hideUploadEl	= true;
					foreach($this->formElements as $el){
						if($el->type == 'file' or $el->type == 'image'){
							$hideUploadEl	= false;
							break;
						}
					}
					?>
					<label class='block <?php if($hideUploadEl){echo 'hidden';}?>'>
						<h4>Save form uploads in this subfolder of the uploads folder:<br>
						If you leave it empty the default form_uploads will be used</h4>
						<input type='text' class='formbuilder formfieldsetting' name='settings[upload_path]' value='<?php echo $settings['upload_path'];?>'>
					</label>
					<br>
					
					<label>
						<?php
						if(!empty($settings['formreset'])){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						?>
						<input type='checkbox' class='formbuilder formfieldsetting' name='settings[formreset]' value='formreset' <?php echo $checked;?>>
						Reset form after succesfull submission
					</label>
					<br>
						
					<h4>Select available actions for formdata</h4>
					<?php
					
					$actions = ['archive','delete'];
					foreach($actions as $action){
						if(!empty($settings['actions'][$action])){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						?>
						<label class='option-label'>
							<input type='checkbox' class='formbuilder formfieldsetting' name='settings[actions][<?php echo $action;?>]' value='<?php echo $action;?>' <?php echo $checked;?>>
							<?php echo ucfirst($action);?>
						</label><br>
						<?php
					}
					?>
					
					<h4>Select a field with multiple answers where you want to create seperate rows for</h4>
					<select name="settings[split]">
					<?php
					if($settings['split'] == ''){
						?><option value='' selected>---</option><?php
					}else{
						?><option value=''>---</option><?php
					}
					
					$foundElements = [];
					foreach($this->formElements as $key=>$element){
						$pattern = "/([^\[]+)\[[0-9]+\]/i";
						
						if(preg_match($pattern, $element->name, $matches)){
							//Only add if not found before
							if(!in_array($matches[1], $foundElements)){
								$foundElements[]	= $matches[1];
								$value 				= strtolower(str_replace('_', ' ', $matches[1]));
								$name				= ucfirst($value);
								
								//Check which option is the selected one
								if($settings['split'] == $value){
									$selected = 'selected';
								}else{
									$selected = '';
								}
								echo "<option value='$value' $selected>$name</option>";
							}
						}
					}
					?>
					</select>
					<div class="formsettings_wrapper">
						<label class="block">
							<h4>Select if you want to auto archive results</h4>
							<br>
							<?php
							if($settings['autoarchive'] == 'true'){
								$checked1	= 'checked';
								$checked2	= '';
							}else{
								$checked1	= '';
								$checked2	= 'checked';
							}
							?>
							<label>
								<input type="radio" name="settings[autoarchive]" value="true" <?php echo $checked1;?>>
								Yes
							</label>
							<label>
								<input type="radio" name="settings[autoarchive]" value="false" <?php echo $checked2;?>>
								No
							</label>
						</label>
						<br>
						<div class='autoarchivelogic <?php if($checked1 == '') echo 'hidden';?>' style="display: flex;width: 100%;">
							Auto archive a (sub) entry when field
							<select name="settings[autoarchivefield]" style="margin-right:10px;">
							<?php
							if($settings['autoarchivefield'] == ''){
								?><option value='' selected>---</option><?php
							}else{
								?><option value=''>---</option><?php
							}
							
							$processed = [];
							foreach($this->formElements as $key=>$element){
								if(in_array($element->type, $this->nonInputs)) continue;
								
								$pattern			= "/\[[0-9]+\]\[([^\]]+)\]/i";
								
								$name = $element->name;
								if(preg_match($pattern, $element->name,$matches)){
									//We found a keyword, check if we already got the same one
									if(!in_array($matches[1],$processed)){
										//Add to the processed array
										$processed[]	= $matches[1];
										
										//replace the name
										$name		= $matches[1];
									}else{
										//do not show this element
										continue;
									}
								}
								
								//Check which option is the selected one
								if(!empty($settings['autoarchivefield']) and $settings['autoarchivefield'] == $element->id){
									$selected = 'selected';
								}else{
									$selected = '';
								}
								echo "<option value='{$element->id}' $selected>$name</option>";
							}
							
							?>
							</select>
							<label style="margin:0 10px;">equals</label>
							<input type='text' name="settings[autoarchivevalue]" value="<?php echo $settings['autoarchivevalue'];?>">
							<div class="infobox" name="info" style="min-width: fit-content;">
								<div style="float:right">
									<p class="info_icon">
										<img draggable="false" role="img" class="emoji" alt="ℹ" src="<?php echo PICTURESURL;?>/info.png">
									</p>
								</div>
								<span class="info_text">
									You can use placeholders like '%today%+3days' for a value
								</span>
							</div>
						</div>
					</div>
					
					<h4>Select roles with form edit rights</h4>
					<div class="role_info">
					<?php
					foreach($userRoles as $key=>$roleName){
						if(!empty($settings['full_right_roles'][$key])){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						echo "<label class='option-label'>";
							echo "<input type='checkbox' class='formbuilder formfieldsetting' name='settings[full_right_roles][$key]' value='$roleName' $checked>";
							echo $roleName;
						echo"</label><br>";
					}
					?>
					</div>
					<br>

					<div class='submit_others_form_wrapper<?php if(empty($settings['save_in_meta'])) echo 'hidden';?>'>
						<h4>Select who can submit this form on behalf of someone else</h4>
						<?php
						foreach($userRoles as $key=>$roleName){
							if(!empty($settings['submit_others_form'][$key])){
								$checked = 'checked';
							}else{
								$checked = '';
							}
							echo "<label class='option-label'>";
								echo "<input type='checkbox' class='formbuilder formfieldsetting' name='settings[submit_others_form][$key]' value='$roleName' $checked>";
								echo $roleName;
							echo"</label><br>";
						}
						?>
					</div>
				</div>
				<?php
				echo SIM\addSaveButton('submit_form_setting',  'Save form settings');
				?>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Form to setup form e-mails
	 */
	function formEmailsForm(){
		/*Array structure:
		emails			[
			0 [
				from	= ''
				to		= ''
				subject	= ''
				message	= ''
				headers	= ''
				files	= ''
			]
		]*/
		$emails = $this->formData->emails;
		?>
		<div class="emails_wrapper">
			<form action='' method='post' class='sim_form builder'>
				<div class='form_elements'>
					<input type='hidden' class='formbuilder' name='formid'	value='<?php echo $this->formData->id;?>'>
					
					<label class="formfield formfieldlabel">
						Define any e-mails you want to send.<br>
						You can use placeholders in your inputs.<br>
						These default ones are available:<br><br>
					</label>
					<span class='placeholders' title="Click to copy">%id%</span>
					<span class='placeholders' title="Click to copy">%formurl%</span>
					<span class='placeholders' title="Click to copy">%submissiondate%</span>
					<span class='placeholders' title="Click to copy">%editdate%</span>
					<span class='placeholders' title="Click to copy">%submissiontime%</span>
					<span class='placeholders' title="Click to copy">%edittime%</span>
					<br>
					All your fieldvalues are available as well:
					<select class='nonice placeholderselect'>
						<option value=''>Select to copy to clipboard</option><?php
					foreach($this->formElements as $element){
						if(!in_array($element->type, ['label','info','button','datalist','formstep'])){
							echo "<option>%{$element->name}%</option>";
						}
					}
					?>
					</select>
					
					<br>
					<div class='clone_divs_wrapper'>
						<?php
						foreach($emails as $key=>$email){
							$defaultFrom	=  get_option( 'admin_email' );
							
							?>
							<div class='clone_div' data-divid='<?php echo $key;?>'>
								<h4 class="formfield" style="margin-top:50px; display:inline-block;">E-mail <?php echo $key+1;?></h4>
								<button type='button' class='add button' style='flex: 1;'>+</button>
								<button type='button' class='remove button' style='flex: 1;'>-</button>
								<div style='width:100%;'>
									<div class="formfield formfieldlabel" style="margin-top:10px;">
										Send e-mail when:<br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='submitted' <?php if(empty($email['emailtrigger']) or $email['emailtrigger'] == 'submitted') echo 'checked';?>>
											The form is submitted
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='fieldchanged' <?php if($email['emailtrigger'] == 'fieldchanged') echo 'checked';?>>
											A field has changed to a value
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailtrigger]' class='emailtrigger' value='disabled' <?php if($email['emailtrigger'] == 'disabled') echo 'checked';?>>
											Do not send this e-mail
										</label><br>
									</div>
									
									<div class='emailfieldcondition <?php if($email['emailtrigger'] != 'fieldchanged') echo 'hidden';?>'>
										<label class="formfield formfieldlabel">Field</label>
										<select name='emails[<?php echo $key;?>][conditionalfield]'>
											<?php
											echo $this->inputDropdown($email['conditionalfield']);
											?>
										</select>
										
										<label class="formfield formfieldlabel">
											Value
											<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalvalue]' value="<?php echo $email['conditionalvalue']; ?>">
										</label>
									</div>
									
									<br>
									<div class="formfield formfieldlabel">
										Sender e-mail should be:<br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][fromemail]' class='fromemail' value='fixed' <?php if(empty($email['fromemail']) or $email['fromemail'] == 'fixed') echo 'checked';?>>
											Fixed e-mail adress
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][fromemail]' class='fromemail' value='conditional' <?php if($email['fromemail'] == 'conditional') echo 'checked';?>>
											Conditional e-mail adress
										</label><br>
									</div>
									
									<div class='emailfromfixed <?php if(!empty($email['fromemail']) and $email['fromemail'] != 'fixed') echo 'hidden';?>'>
										<label class="formfield formfieldlabel">
											From e-mail
											<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][from]' value="<?php if(empty($email['from'])){echo $defaultFrom;} else{echo $email['from'];} ?>">
										</label>
									</div>
									
									<div class='emailfromconditional <?php if($email['fromemail'] != 'conditional') echo 'hidden';?>'>
										<div class='clone_divs_wrapper'>
											<?php
											if(!is_array($email['conditionalfromemail'])) $email['conditionalfromemail'] = [['fieldid'=>'','value'=>'','email'=>'']];
											foreach(array_values($email['conditionalfromemail']) as $fromKey=>$fromEmail){
											?>
											<div class='clone_div' data-divid='<?php echo $fromKey;?>'>
												<fieldset class='formemailfieldset'>
													<legend class="formfield">Condition <?php echo $fromKey+1;?>
													<button type='button' class='add button' style='flex: 1;'>+</button>
													<button type='button' class='remove button' style='flex: 1;'>-</button>
													</legend>
													If 
													<select name='emails[<?php echo $key;?>][conditionalfromemail][<?php echo $fromKey;?>][fieldid]'>
														<?php
														echo $this->inputDropdown($fromEmail['fieldid']);
														?>
													</select>
													<label class="formfield formfieldlabel">
														equals 
														<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalfromemail][<?php echo $fromKey;?>][value]' value="<?php echo $fromEmail['value'];?>">
													</label>
													<label class="formfield formfieldlabel">
														then from e-mail address should be:<br>
														<input type='email' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalfromemail][<?php echo $fromKey;?>][email]' value="<?php echo $fromEmail['email'];?>">
													</label>
												</fieldset>
											</div>
											<?php
											}
											?>
										</div>
									</div>
									
									<br>
									<div class="formfield tofieldlabel">
										Recipient e-mail should be:<br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailto]' class='emailto' value='fixed' <?php if(empty($email['emailto']) or $email['emailto'] == 'fixed') echo 'checked';?>>
											Fixed e-mail adress
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailto]' class='emailto' value='conditional' <?php if($email['emailto'] == 'conditional') echo 'checked';?>>
											Conditional e-mail adress
										</label><br>
									</div>
									<br>
									<div class='emailtofixed <?php if(!empty($email['emailto']) and $email['emailto'] != 'fixed') echo 'hidden';?>'>
										<label class="formfield formfieldlabel">
											To e-mail
											<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][to]' value="<?php if(empty($email['to'])){echo '%email%';}else{echo $email['to'];} ?>">
										</label>
									</div>
									<div class='emailtoconditional <?php if($email['emailto'] != 'conditional') echo 'hidden';?>'>
										<div class='clone_divs_wrapper'>
											<?php
											if(!is_array($email['conditionalemailto'])) $email['conditionalemailto'] = [['fieldid'=>'','value'=>'','email'=>'']];
											foreach($email['conditionalemailto'] as $toKey=>$toEmail){
											?>
											<div class='clone_div' data-divid='<?php echo $toKey;?>'>
												<fieldset class='formemailfieldset'>
													<legend class="formfield">Condition <?php echo $toKey+1;?>
													<button type='button' class='add button' style='flex: 1;'>+</button>
													<button type='button' class='remove button' style='flex: 1;'>-</button>
													</legend>
													If 
													<select name='emails[<?php echo $key;?>][conditionalemailto][<?php echo $toKey;?>][fieldid]'>
														<?php
														echo $this->inputDropdown($toEmail['fieldid']);
														?>
													</select>
													<label class="formfield formfieldlabel">
														equals 
														<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalemailto][<?php echo $toKey;?>][value]' value="<?php echo $toEmail['value'];?>">
													</label>
													<label class="formfield formfieldlabel">
														then from e-mail address should be:<br>
														<input type='email' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalemailto][<?php echo $toKey;?>][email]' value="<?php echo $toEmail['email'];?>">
													</label>
												</fieldset>
											</div>
											<?php
											}
											?>
										</div>
									</div>
									<br>
									<div class="formfield formfieldlabel">
										Subject
										<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][subject]' value="<?php echo $email['subject']?>">
									</div>
									
									<br>
									<div class="formfield formfieldlabel">
										E-mail content
										<?php
										$settings = array(
											'wpautop' => false,
											'media_buttons' => false,
											'forced_root_block' => true,
											'convert_newlines_to_brs'=> true,
											'textarea_name' => "emails[$key][message]",
											'textarea_rows' => 10
										);
									
										echo wp_editor(
											$email['message'],
											"{$this->formName}_email_message_$key",
											$settings
										);
										?>
									</div>
									
									<br>
									<div class="formfield formfieldlabel">
										Additional headers like 'Reply-To'
										<textarea class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][headers]'><?php
											echo $email['headers']?>
										</textarea>
									</div>
									
									<br>
									<div class="formfield formfieldlabel">
										Form values that should be attached to the e-mail
										<textarea class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][files]'><?php
											echo $email['files']?>
										</textarea>
									</div>
								</div>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<?php
				echo SIM\addSaveButton('submit_form_emails','Save form email configuration');
				?>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Form to add or edit a new form element
	 */
	function elementBuilderForm($element=null){
		ob_start();

		if(is_numeric($element)){
			$element = $this->getElementById($element);
		}
		?>
		<form action="" method="post" name="add_form_element_form" class="form_element_form sim_form" data-addempty=true>
			<div style="display: none;" class="error"></div>
			<h4>Please fill in the form to add a new form element</h4><br>

			<input type="hidden" name="formid" value="<?php echo $this->formData->id;?>">

			<input type="hidden" name="formfield[form_id]" value="<?php echo $this->formData->id;?>">
			
			<input type="hidden" name="element_id" value="<?php if( $element != null) echo $element->id;?>">
			
			<input type="hidden" name="insertafter">
			
			<input type="hidden" name="formfield[width]" value="100">
			
			<label>Select which kind of form element you want to add</label>
			<select class="formbuilder elementtype" name="formfield[type]" required>
				<optgroup label="Normal elements">
					<?php
					$options=[
						"button"	=> "Button",
						"checkbox"	=> "Checkbox",
						"color"		=> "Color",
						"date"		=> "Date",
						"select"	=> "Dropdown",
						"email"		=> "E-mail",
						"file"		=> "File upload",
						"image"		=> "Image upload",
						"label"		=> "Label",
						"month"		=> "Month",
						"number"	=> "Number",
						"password"	=> "Password",
						"tel"		=> "Phonenumber",
						"radio"		=> "Radio",
						"range"		=> "Range",
						"text"		=> "Text",
						"textarea"	=> "Text (multiline)",
						"time"		=> "Time",
						"url"		=> "Url",
						"week"		=> "Week"
					];

					foreach($options as $key=>$option){
						if($element != null && $element->type == $key){
							$selected = 'selected';
						}else{
							$selected = '';
						}
						echo "<option value='$key' $selected>$option</option>";
					}
					?>
					
				</optgroup>
				<optgroup label="Special elements">
					<?php
					$options=[
						"captcha"		=> "Captcha",
						"php"			=> "Custom code",
						"datalist"		=> "Datalist",
						"info"			=> "Infobox",
						"multi_start"	=> "Multi-answer - start",
						"multi_end"		=> "Multi-answer - end",
						"formstep"		=> "Multistep",
						"p"				=> "Paragraph"
					];

					foreach($options as $key=>$option){
						if($element != null && $element->type == $key){
							$selected = 'selected';
						}else{
							$selected = '';
						}
						echo "<option value='$key' $selected>$option</option>";
					}
					?>
				</optgroup>
			</select>
			<br>
			
			<div name='elementname' class='labelhide hide'>
				<label>
					<div>Specify a name for the element</div>
					<input type="text" class="formbuilder" name="formfield[name]" value="<?php if($element != null) echo $element->name;?>">
				</label>
				<br><br>
			</div>
			
			<div name='functionname' class='hidden'>
				<label>
					Specify the functionname
					<input type="text" class="formbuilder" name="formfield[functionname]" value="<?php if($element != null) echo $element->functionname;?>">
				</label>
				<br><br>
			</div>
			
			<div name='labeltext' class='hidden'>
				<label>
					<div>Specify the <span class='elementtype'>label</span> text</div>
					<input type="text" class="formbuilder" name="formfield[text]" value="<?php if($element != null) echo $element->text;?>">
				</label>
				<br><br>
			</div>

			<div name='upload-options' class='hidden'>
				<label>
					<input type="checkbox" class="formbuilder" name="formfield[library]" value="true" <?php if($element != null and $element->library) echo 'checkec';?>>
					Add the <span class='filetype'>file</span> to the library
				</label>
				<br><br>

				<label>
					Name of the folder the <span class='filetype'>file</span> should be uploaded to.<br>
					<input type="text" class="formbuilder" name="formfield[foldername]" value="<?php if($element != null) echo $element->foldername;?>">
				</label>
			</div>
			
			<div name='wrap'>
				<label>
					<input type="checkbox" class="formbuilder" name="formfield[wrap]" value="true" <?php if($element != null and $element->wrap) echo 'checked';?>>
					Group together with next element
				</label>
				<br><br>
			</div>
			
			<div name='infotext' class='hidden'>
				<label>
					Specify the text for the <span class='type'>infobox</span>
					<?php
					$settings = array(
						'wpautop'					=> false,
						'media_buttons'				=> false,
						'forced_root_block'			=> true,
						'convert_newlines_to_brs'	=> true,
						'textarea_name'				=> "formfield[infotext]",
						'textarea_rows'				=> 10,
						'editor_class'				=> 'formbuilder'
					);
				
					if(empty($element->text)){
						$content	= '';
					}else{
						$content	= $element->text;
					}
					echo wp_editor(
						$content,
						$this->formData->name."_infotext",	//editor should always have an unique id
						$settings
					);
					?>
					
				</label>
				<br>
			</div>
			
			<div name='multiple' class='labelhide hide'>
				<label>
					<input type="checkbox" class="formbuilder" name="formfield[multiple]" value="true" <?php if($element != null and $element->multiple) echo 'checked';?>>
					Allow multiple answers
				</label>
				<br>
				<br>
			</div>
			
			<div name='valuelist' class='hidden'>
				<label>
					Specify the values, one per line
					<textarea class="formbuilder" name="formfield[valuelist]"><?php if($element != null) echo trim($element->valuelist);?></textarea>
				</label>
				<br>
			</div>

			<div name='select_options' class='hidden'>
				<label class='block'>Specify an options group if desired</label>
				<select class="formbuilder" name="formfield[default_array_value]">
					<option value="">---</option>
					<?php
					$this->buildDefaultsArray();
					foreach($this->defaultArrayValues as $key=>$field){
						if($element != null and $element->default_array_value == $key){
							$selected = 'selected';
						}else{
							$selected = '';
						}
						$optionName	= ucfirst(str_replace('_',' ',$key));
						echo "<option value='$key' $selected>$optionName</option>";
					}
					?>
				</select>
			</div>

			<div name='defaults' class='labelhide hide'>
				<label class='block'>Specify a default value if desired</label>
				<select class="formbuilder" name="formfield[default_value]">
					<option value="">---</option>
					<?php
					foreach($this->defaultValues as $key=>$field){
						if($element != null && $element->default_value == $key){
							$selected = 'selected';
						}else{
							$selected = '';
						}
						$optionName	= ucfirst(str_replace('_',' ',$key));
						echo "<option value='$key' $selected>$optionName</option>";
					}
					?>
				</select>
			</div>
			<br>
			<div name='elementoptions' class='labelhide hide'>
				<label>
					Specify any options like styling
					<textarea class="formbuilder" name="formfield[options]"><?php if($element != null) echo trim($element->options);?></textarea>
				</label><br>
				<br>
				
				<?php
				$meta	= false;
				if(!empty($this->formData->settings['save_in_meta'])){
					$meta	= true;
				}

				if($meta){
					?>
					<h3>Warning conditions</h3>
					<label class="option-label">
						<input type="checkbox" class="formbuilder" name="formfield[mandatory]" value="true" <?php if($element != null and $element->mandatory) echo 'checked';?>>
						Check if people should be warned by e-mail/signal if they have not filled in this field.
					</label><br>
					<br>

					<label class="option-label">
						<input type="checkbox" class="formbuilder" name="formfield[recommended]" value="true" <?php if($element != null and $element->recommended) echo 'checked';?>>
						Check if people should be notified on their homepage if they have not filled in this field.
					</label><br>
					<br>

					<div <?php if($element == null or (!$element->mandatory and !$element->recommended)) echo "class='hidden'";?>>
						<?php
						if($element == null){
							$elementId	= -1;
						}else{
							$elementId	= $element->id;
						}
						echo $this->warningConditionsForm($elementId);
						?>
					</div>
					<?php
				}else{
					?>
					<label class="option-label">
						<input type="checkbox" class="formbuilder" name="formfield[required]" value="true" <?php if($element != null and $element->required) echo 'checked';?>>
						Check if this should be a required field
					</label><br>
					<br>
					<?php
				}
				?>
			</div>
			<br>
			<label class="option-label">
				<input type="checkbox" class="formbuilder" name="formfield[hidden]" value="true" <?php if($element != null and $element->hidden) echo 'checked';?>>
				Check if this should be a hidden field
			</label><br>

			<?php 
			if($element == null){
				$text	= "Add";
			}else{
				$text	= "Change";
			}	
			echo SIM\addSaveButton('submit_form_element',"$text form element"); ?>
		</form>
		<?php

		return ob_get_clean();
	}	

	/**
	 * Form to add conditions to an element
	 * 
	 * @param int	$elementId	The id of the element. Default -1 for empty
	 */
	function elementConditionsForm($elementId = -1){
		/* 		
		A field can have one or more conditions applied to it like:
			1) hide when field X is Y
			2) Show when field X is Z
		Each condition can have multiple rules like:
			Hide when field X is Y and field A is B 
			
		The array structure is therefore:
			[
				[0][
					[rules]	
							[0]
							[1]
					[action]
				[1]
					[rules]	
							[0]
					[action]
			]
		
		It is also stored at the conditional fields to be able to create efficient JavaScript
		*/

		$element	= $this->getElementById($elementId);

		if($elementId == -1 or empty($element->conditions)){
			$dummyFieldCondition['rules'][0]["conditional_field"]	= "";
			$dummyFieldCondition['rules'][0]["equation"]				= "";
			$dummyFieldCondition['rules'][0]["conditional_field_2"]	= "";
			$dummyFieldCondition['rules'][0]["equation_2"]			= "";
			$dummyFieldCondition['rules'][0]["conditional_value"]	= "";
			$dummyFieldCondition["action"]					= "";
			$dummyFieldCondition["target_field"]			= "";
			
			if($elementId == -1) $elementId			= 0;
			$element->conditions = [$dummyFieldCondition];
		}
		
		$conditions = maybe_unserialize($element->conditions);
		
		ob_start();
		$counter = 0;
		foreach($this->formElements as $el){
			if(in_array($elementId, (array)unserialize($el->conditions)['copyto'])){
				$counter++;
				?>
				<div class="form_element_wrapper" data-id="<?php echo $el->id;?>" data-formid="<?php echo $this->formData->id;?>">
					<button type="button" class="edit_form_element button" title="Jump to conditions element">View conditions of '<?php echo $el->name;?>'</button>
				</div>
				<?php
			}
		}
		if($counter>0){
			$jumbButtonHtml =  ob_get_clean();
			if($counter==1){
				$counter	= 'another element';
				$any 		= 'the';
			}else{
				$counter	= "$counter other elements";
				$any 		= 'any';
			}
			
			ob_start();
			?>
			<div>
				This element has some conditions defined by <?php echo $counter;?>.<br>
				Click on <?php echo $any;?> button below to view.
				<?php echo $jumbButtonHtml;?>
			</div><br><br>
			<?php
		}
		?>
		
		<form action='' method='post' name='add_form_element_conditions_form'>
			<h3>Form element conditions</h3>
			<input type='hidden' class='element_condition' name='formid' value='<?php echo $this->formData->id;?>'>
			
			<input type='hidden' class='element_condition' name='elementid' value='<?php echo $elementId;?>'>

			<?php
			$lastCondtionKey = array_key_last($conditions);
			foreach($conditions as $conditionIndex=>$condition){
				if(!is_numeric($conditionIndex)) continue;
			?>
			<div class='condition_row' data-condition_index='<?php echo $conditionIndex;?>'>
				<span style='font-weight: 600;'>If</span>
				<br>
				<?php
				$lastRuleKey = array_key_last($condition['rules']);
				foreach($condition['rules'] as $ruleIndex=>$rule){
					?>
					<div class='rule_row' data-rule_index='<?php echo $ruleIndex;?>'>
						<input type='hidden' class='element_condition combinator' name='element_conditions[<?php echo $conditionIndex;?>][rules][<?php echo $ruleIndex;?>][combinator]' value='<?php echo $rule['combinator']; ?>'>
					
						<select class='element_condition condition_select conditional_field' name='element_conditions[<?php echo $conditionIndex;?>][rules][<?php echo $ruleIndex;?>][conditional_field]' required>
						<?php
							echo $this->inputDropdown($rule['conditional_field'], $elementId);
						?>
						</select>

						<select class='element_condition condition_select equation' name='element_conditions[<?php echo $conditionIndex;?>][rules][<?php echo $ruleIndex;?>][equation]' required>
							<option value=''		<?php if($rule['equation'] == '')			echo 'selected';?>>---</option>";
							<option value='changed'	<?php if($rule['equation'] == 'changed')	echo 'selected';?>>has changed</option>
							<option value='clicked'	<?php if($rule['equation'] == 'clicked')	echo 'selected';?>>is clicked</option>
							<option value='=='		<?php if($rule['equation'] == '==')			echo 'selected';?>>equals</option>
							<option value='!='		<?php if($rule['equation'] == '!=')			echo 'selected';?>>is not</option>
							<option value='>'		<?php if($rule['equation'] == '>')			echo 'selected';?>>greather than</option>
							<option value='<'		<?php if($rule['equation'] == '<')			echo 'selected';?>>smaller than</option>
							<option value='checked'	<?php if($rule['equation'] == 'checked')	echo 'selected';?>>is checked</option>
							<option value='!checked'<?php if($rule['equation'] == '!checked')	echo 'selected';?>>is not checked</option>
							<option value='== value'<?php if($rule['equation'] == '== value')	echo 'selected';?>>equals the value of</option>
							<option value='!= value'<?php if($rule['equation'] == '!= value')	echo 'selected';?>>is not the value of</option>
							<option value='> value'	<?php if($rule['equation'] == '> value')	echo 'selected';?>>greather than the value of</option>
							<option value='< value'	<?php if($rule['equation'] == '< value')	echo 'selected';?>>smaller than the value of</option>
							<option value='-'		<?php if($rule['equation'] == '-')			echo 'selected';?>>minus the value of</option>
							<option value='+'		<?php if($rule['equation'] == '+')			echo 'selected';?>>plus the value of</option>
						</select>

						<?php
						//show if -, + or value field istarget value
						if($rule['equation'] == '-' or $rule['equation'] == '+' or strpos($rule['equation'], 'value') !== false){
							$hidden = '';
						}else{
							$hidden = 'hidden';
						}
						?>
						
						<span class='<?php echo $hidden;?> condition_form conditional_field_2'>
							<select class='element_condition condition_select' name='element_conditions[<?php echo $conditionIndex;?>][rules][<?php echo $ruleIndex;?>][conditional_field_2]'>
							<?php
								echo $this->inputDropdown($rule['conditional_field_2'], $elementId);
							?>
							</select>
						</span>
						
						<?php
						if($rule['equation'] == '-' or $rule['equation'] == '+'){
							$hidden = '';
						}else{
							$hidden = 'hidden';
						}
						?>

						<span class='<?php echo $hidden;?> condition_form equation_2'>
							<select class='element_condition condition_select' name='element_conditions[<?php echo $conditionIndex;?>][rules][<?php echo $ruleIndex;?>][equation_2]'>
								<option value=''	<?php if($rule['equation_2'] == '')		echo 'selected';?>>---</option>";
								<option value='=='	<?php if($rule['equation_2'] == '==')	echo 'selected';?>>equals</option>
								<option value='!='	<?php if($rule['equation_2'] == '!=')	echo 'selected';?>>is not</option>
								<option value='>'	<?php if($rule['equation_2'] == '>')	echo 'selected';?>>greather than</option>
								<option value='<'	<?php if($rule['equation_2'] == '<')	echo 'selected';?>>smaller than</option>
							</select>
						</span>
						<?php 
						if(strpos($rule['equation'], 'value') !== false or in_array($rule['equation'], ['changed','checked','!checked'])){
							$hidden = 'hidden';
						}else{
							$hidden = '';
						}
						?>
						<input  type='text'   class='<?php echo $hidden;?> element_condition condition_form' name='element_conditions[<?php echo $conditionIndex;?>][rules][<?php echo $ruleIndex;?>][conditional_value]' value="<?php echo $rule['conditional_value'];?>">
						
						<button type='button' class='element_condition and_rule condition_form button <?php if(!empty($rule['combinator']) and $rule['combinator'] == 'AND') echo 'active';?>'	title='Add a new "AND" rule to this condition'>AND</button>
						<button type='button' class='element_condition or_rule condition_form button  <?php if(!empty($rule['combinator']) and $rule['combinator'] == 'OR') echo 'active';?>'	title='Add a new "OR"  rule to this condition'>OR</button>
						<button type='button' class='remove_condition condition_form button' title='Remove rule or condition'>-</button>
						<?php
						if($conditionIndex == $lastCondtionKey and $ruleIndex == $lastRuleKey){
							?>
							<button type='button' class='add_condition condition_form button' title='Add a new condition'>+</button>
							<button type='button' class='add_condition opposite condition_form button' title='Add a new condition, opposite to to the previous one'>Add opposite</button>
						<?php
						}
						?>
					</div>
				<?php 
				}
				?>
				<br>
				<span style='font-weight: 600;'>then</span><br>
				
				<div class='action_row'>
					<div class='radio_wrapper condition_form'>
						<label>
							<input type='radio' name='element_conditions[<?php echo $conditionIndex;?>][action]' class='element_condition' value='show' <?php if($condition['action'] == 'show') echo 'checked';?> required>
							Show this element
						</label><br>
						
						<label>
							<input type='radio' name='element_conditions[<?php echo $conditionIndex;?>][action]' class='element_condition' value='hide' <?php if($condition['action'] == 'hide') echo 'checked';?> required>
							Hide this element
						</label><br>
						
						<label>
							<input type='radio' name='element_conditions[<?php echo $conditionIndex;?>][action]' class='element_condition' value='toggle' <?php if($condition['action'] == 'toggle') echo 'checked';?> required>
							Toggle this element
						</label><br>
						
						<label>
							<input type='radio' name='element_conditions[<?php echo $conditionIndex;?>][action]' class='element_condition' value='value' <?php if($condition['action'] == 'value') echo 'checked';?> required>
							Set property
						</label>
						<input type="text" name="element_conditions[<?php echo $conditionIndex;?>][propertyname1]" class='element_condition' placeholder="property name" value="<?php echo $condition['propertyname1'];?>">
						<label> to:</label>
						<input type="text" name="element_conditions[<?php echo $conditionIndex;?>][action_value]" class='element_condition' value="<?php echo $condition['action_value'];?>">
						<br>
						
						<input type='radio' name='element_conditions[<?php echo $conditionIndex;?>][action]' class='element_condition' value='property' <?php if($condition['action'] == 'property') echo 'checked';?> required>
						<label for='property'>Set the</label>
						
						<input type="text" list="propertylist" name="element_conditions[<?php echo $conditionIndex;?>][propertyname]" class='element_condition' placeholder="property name" value="<?php echo $condition['propertyname'];?>">
						<datalist id="propertylist">
							<option value="value">
							<option value="min">
							<option value="max">
						</datalist>
						<label for='property'> property to the value of </label>
						
						<select class='element_condition condition_select' name='element_conditions[<?php echo $conditionIndex;?>][property_value]'>
						<?php echo $this->inputDropdown($condition['property_value'], $elementId);?>
						</select><br>
					</div>
				</div>
			</div>
			<?php 
			}
			?>
			<br>
			<div class="copyfieldswrapper">
				<label>
					<input type='checkbox' class="showcopyfields" <?php if(!empty($conditions['copyto'])) echo 'checked';?>>
					Apply visibility conditions to other fields
				</label><br><br>
				
				<div class='copyfields <?php if(empty($conditions['copyto'])) echo 'hidden';?>'>
					Check the fields these conditions should apply to as well,<br>
					This holds only for visibility conditions (show, hide or toggle).<br><br>
					<?php
					foreach($this->formElements as $key=>$element){
						//do not show the current element itself or wrapped labels
						if($element->id != $elementId and empty($element->wrap)){
							if(!empty($conditions['copyto'][$element->id])){
								$checked = 'checked';
							}else{
								$checked = '';
							}
							
							echo "<label>";
								echo "<input type='checkbox' name='element_conditions[copyto][{$element->id}]' value='{$element->id}' $checked>";
								echo ucfirst(str_replace('_',' ',$element->name));
							echo "</label><br>";
						}
					}
					?>
				</div>
			</div>
			<?php
			echo SIM\addSaveButton('submit_form_condition','Save conditions'); ?>
		</form>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	/**
	 * Form to add warning conditions to an element
	 * 
	 * @param	int		$elementId		The id to add warning conditions for. Default -1 for empty
	 */
	function warningConditionsForm($elementId = -1){
		$element	= $this->getElementById($elementId);

		if($elementId == -1 or empty($element->warning_conditions)){
			$dummy[0]["user_meta_key"]	= "";
			$dummy[0]["equation"]		= "";
			
			if($elementId == -1) $elementId			= 0;
			$element->warning_conditions = $dummy;
		}
		
		$conditions 	= maybe_unserialize($element->warning_conditions);
		
		$userMetaKeys	= get_user_meta($this->userId);
		ksort($userMetaKeys);

		ob_start();
		?>
			
		<label>Do not warn if usermeta with the key</label>
		<br>

		<div class="conditions_wrapper">
			<?php
			foreach($conditions as $conditionIndex=>$condition){
				if(!is_numeric($conditionIndex)) continue;
				?>
				<div class='warning_conditions element_conditions' data-index='<?php echo $conditionIndex;?>'>
					<input type="hidden" class='warning_condition combinator' name="formfield[warning_conditions][<?php echo $conditionIndex;?>][combinator]" value="<?php echo $condition['combinator'];?>">

					<input type="text" class="warning_condition meta_key" name="formfield[warning_conditions][<?php echo $conditionIndex;?>][meta_key]" value="<?php echo $condition['meta_key'];?>" list="meta_key" style="width: fit-content;">
					<datalist id="meta_key">
						<?php
						foreach($userMetaKeys as $key=>$value){
							// Check if array, store array keys
							$value 	= maybe_unserialize($value[0]);
							$data	= '';
							if(is_array($value)){
								$keys	= implode(',', array_keys($value));
								$data	= "data-keys=$keys";
							}
							echo esc_html("<option value='$key' $data>");
						}

						?>
					</datalist>

					<?php
						$arrayKeys	= maybe_unserialize($userMetaKeys[$condition['meta_key']][0]);
					?>
					<span class="index_wrapper <?php if(!is_array($arrayKeys)) echo 'hidden';?>">
						<span>and index</span>
						<input type="text" class="warning_condition meta_key_index" name='formfield[warning_conditions][<?php echo $conditionIndex;?>][meta_key_index]' value="<?php echo $condition['meta_key_index'];?>" list="meta_key_index[<?php echo $conditionIndex;?>]" style="width: fit-content;">
						<datalist class="meta_key_index_list warning_condition" id="meta_key_index[<?php echo $conditionIndex;?>]">
							<?php
							if(is_array($arrayKeys)){
								foreach(array_keys($arrayKeys) as $key){
									echo esc_html("<option value='$key'>");
								}
							}
							?>
						</datalist>
					</span>
					
					<select class="warning_condition" name='formfield[warning_conditions][<?php echo $conditionIndex;?>][equation]'>
						<option value=''		<?php if($condition['equation'] == '')			echo 'selected';?>>---</option>";
						<option value='=='		<?php if($condition['equation'] == '==')		echo 'selected';?>>equals</option>
						<option value='!='		<?php if($condition['equation'] == '!=')		echo 'selected';?>>is not</option>
						<option value='>'		<?php if($condition['equation'] == '>')			echo 'selected';?>>greather than</option>
						<option value='<'		<?php if($condition['equation'] == '<')			echo 'selected';?>>smaller than</option>
					</select>
					<input  type='text'   class='warning_condition' name='formfield[warning_conditions][<?php echo $conditionIndex;?>][conditional_value]' value="<?php echo $condition['conditional_value'];?>" style="width: fit-content;">
					
					<button type='button' class='warn_cond button <?php if(!empty($condition['combinator']) and $condition['combinator'] == 'and') echo 'active';?>'	title='Add a new "AND" rule' value="and">AND</button>
					<button type='button' class='warn_cond button  <?php if(!empty($condition['combinator']) and $condition['combinator'] == 'or') echo 'active';?>'	title='Add a new "OR"  rule' value="or">OR</button>
					<button type='button' class='remove_warn_cond  button' title='Remove rule'>-</button>

					<br>
				</div>
				<?php 
			}
			?>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	/**
	 * Checks if the current form exists in the db. If not, inserts it
	 */
	function maybeInsertForm(){
		global $wpdb;
		
		//check if form row already exists
		if($wpdb->get_var("SELECT * FROM {$this->tableName} WHERE `name` = '{$this->formName}'") != true){
			//Create a new form row
			SIM\printArray("Inserting new form in db");
			
			$this->insertForm();
		}
	}

	/**
	 * Rename any existing files to include the form id.
	 */
	function processFiles($uploadedFiles, $inputName){
		//loop over all files uploaded in this fileinput
		foreach ($uploadedFiles as $key => $url){
			$urlParts 	= explode('/',$url);
			$fileName	= end($urlParts);
			$path		= SIM\urlToPath($url);
			$targetDir	= str_replace($fileName,'',$path);
			
			//add input name to filename
			$fileName	= "{$inputName}_$fileName";
			
			//also add submission id if not saving to meta
			if(empty($this->formData->settings['save_in_meta'])){
				$fileName	= $this->formResults['id']."_$fileName";
			}
			
			//Create the filename
			$i = 0;
			$targetFile = $targetDir.$fileName;
			//add a number if the file already exists
			while (file_exists($targetFile)) {
				$i++;
				$targetFile = "$targetDir.$fileName($i)";
			}
	
			//if rename is succesfull
			if (rename($path, $targetFile)) {
				//update in formdata
				$this->formResults[$inputName][$key]	= str_replace(ABSPATH, '', $targetFile);
			}else {
				//update in formdata
				$this->formResults[$inputName][$key]	= str_replace(ABSPATH,'',$path);
			}
		}
	}
}