<?php
namespace SIM\FORMS;
use SIM;
use WP_Error;

class SimForms{
	
	public $isFormStep;
	public $isMultiStepForm;
	public $formStepCounter;
	public $submissionTableName;
	public $tableName;
	public $elTableName;
	public $nonInputs;
	public $multiInputsHtml;
	public $user;
	public $userRoles;
	public $userId;
	public $pageSize;
	public $multiwrap;
	public $submitRoles;
	public $showArchived;
	public $editRights;
	public $formName;
	public $formData;
	public $forms;
	public $formId;
	public $formElements;
	public $jsFileName;
	public $names;
	public $shortcodeId;
	public $onlyOwn;
	public $all;

	public function __construct(){
		global $wpdb;

		$this->isFormStep				= false;
		$this->isMultiStepForm			= '';
		$this->formStepCounter			= 0;
		$this->submissionTableName		= $wpdb->prefix . 'sim_form_submissions';
		$this->tableName				= $wpdb->prefix . 'sim_forms';
		$this->elTableName				= $wpdb->prefix . 'sim_form_elements';
		$this->nonInputs				= ['label','button','datalist','formstep','info','p','php','multi_start','multi_end','div_start','div_end'];
		$this->multiInputsHtml			= [];
		$this->user 					= wp_get_current_user();
		$this->userRoles				= $this->user->roles;
		$this->userId					= $this->user->ID;
		$this->pageSize					= 100;
		$this->multiwrap				= '';
		$this->submitRoles				= [];
		$this->showArchived				= false;

		//calculate full form rights
		$postAuthor	= get_queried_object()->post_author;
		if(empty($postAuthor) && !empty($_REQUEST['post'])){
			$postAuthor	= get_post($_REQUEST['post'])->post_author;
		}

		if(array_intersect(['editor'], $this->userRoles) || $postAuthor == $this->user->ID){
			$this->editRights		= true;
		}else{
			$this->editRights		= false;
		}
	}

	/**
	 * Inserts a new form in the db
	 *
	 * @param	string	$name	The form name
	 *
	 * @return	int|WP_Error	The form id or error ion failure
	 */
	public function insertForm($name=''){
		global $wpdb;

		if(empty($name)){
			$name = $this->formName;
		}

		$name	= strtolower($name);

		// Check if name already exists
		$newName	= $name;
		$i			= 1;
		while(true){
			$query	= "SELECT * FROM {$this->tableName} WHERE name = '$newName'";
			$result	= $wpdb->get_results($query);

			if(empty($result)){
				break;
			}

			$newName	= "$name$i";
			$i++;
		}

		$this->formName	= $newName;

		$wpdb->insert(
			$this->tableName,
			array(
				'name'			=> $this->formName,
				'version' 		=> 1
			)
		);
		
		if(!empty($wpdb->last_error)){
			return new \WP_Error('error', $wpdb->print_error());
		}

		return $wpdb->insert_id;
	}
	
	/**
	 * Gets all forms from the db
	 */
	public function getForms(){
		global $wpdb;
		
		$query							= "SELECT * FROM {$this->tableName} WHERE 1";
		
		$this->forms					= $wpdb->get_results($query);
	}

	/**
	 * Load a specific form or creates it if it does not exist
	 *
	 * @param	int		$formId	the form id to load. Default empty
	 */
	public function getForm($formId=''){
		global $wpdb;

		// first check if needed
		if(empty($this->formData) || (
			(
				!empty($this->formId)		&&
				$this->formData->id	!= $this->formId
			)	||
			(
				!empty($this->formName)		&&
				$this->formData->name	!= $this->formName
			)
		)){
			// Get the form data
			$query				= "SELECT * FROM {$this->tableName} WHERE ";
			if(is_numeric($formId)){
				$query	.= "id= '$formId'";
			}elseif(is_numeric($this->formId)){
				$query	.= "id= '$this->formId'";
			}elseif(!empty($this->formName)){
				$query	.= "name= '$this->formName'";
			}else{
				return new \WP_Error('forms', 'No form name or id given');
			}

			$result				= $wpdb->get_results($query);

			// Form does not exist yet
			if(empty($result)){
				$this->insertForm();
				$this->formData 	=  new \stdClass();
			}else{
				$this->formData 	=  (object)$result[0];
				$formId				= $this->formData->id;
			}
		}

		$this->getAllFormElements('priority', $formId);
		
		//used to find the index of an element based on its unique id
 		$this->formData->elementMapping									= [];
		$this->formData->elementMapping['type']							= [];
		$this->formData->elementMapping['name']							= [];
		foreach($this->formElements as $index=>$element){
			$this->formData->elementMapping['id'][$element->id]			= $index;
			$this->formData->elementMapping['name'][$element->name][] 	= $index;
			$this->formData->elementMapping['type'][$element->type][] 	= $index;
		}
		
		if(empty($this->formData->settings)){
			$settings['succesmessage']			= "";
			$settings['formname']				= "";
			$settings['roles']					= [];

			$this->formData->settings = $settings;
		}elseif(!is_array($this->formData->settings)){
			$this->formData->settings = maybe_unserialize($this->formData->settings);
		}

		if(!$this->editRights){
			$editRoles	= ['editor'];
			foreach($this->formData->settings['full_right_roles'] as $key=>$role){
				if(!empty($role)){
					$editRoles[] = $key;
				}
			}
			//calculate full form rights
			if(array_intersect($editRoles, (array)$this->userRoles) || get_queried_object()->post_author == $this->user->ID){
				$this->editRights		= true;
			}else{
				$this->editRights		= false;
			}
		}

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
		
		$this->jsFileName	= plugin_dir_path(__DIR__)."../js/dynamic/{$this->formData->name}forms";

		return true;
	}

	/**
	 * Creates a dropdown with all the forms
	 *
	 * @return	string	the select html
	 */
	public function formSelect(){
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
	 * Creates the tables for this module
	 */
	public function createDbTable(){
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
			timecreated datetime DEFAULT NULL,
			timelastedited datetime DEFAULT NULL,
			userid mediumint(9) NOT NULL,
			formresults longtext NOT NULL,
			archived BOOLEAN,
			archivedsubs tinytext,
			PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->submissionTableName, $sql );
	}
	
	/**
	 * Finds an element by its id
	 *
	 * @param	int		$id		the element id
	 * @param	string	$key	A specific element attribute to return. Default empty
	 *
	 * @return	object|array|string|false			The element or element property
	 */
	public function getElementById($id, $key=''){
		if(empty($id) || $id < 0){
			return false;
		}
		
		//load if needed
		if(empty($this->formData->elementMapping)){
			$this->getForm();
		}
		
		if(!isset($this->formData->elementMapping['id'][$id])){
			SIM\printArray("Element with id $id not found");
			return false;
		}
		$elementIndex	= $this->formData->elementMapping['id'][$id];
					
		$element		= $this->formElements[$elementIndex];
		
		if(empty($key)){
			return $element;
		}else{
			return $element->$key;
		}
	}

	/**
	 * Finds an element by its name
	 *
	 * @param	string	$name	The element name
	 * @param	string	$key	A specific element attribute to return. Default empty
	 *
	 * @return	object|array|string|false			The element or element property
	 */
	public function getElementByName($name, $key='', $single=true){
		if(empty($name)){
			return false;
		}
		
		//load if needed
		if(empty($this->formData->elementMapping)){
			$this->getForm();
		}

		if(!isset($this->formData->elementMapping['name'][$name])){
			$nameNew	= explode('[', $name)[0];

			if(isset($this->formData->elementMapping['name'][$nameNew])){
				$name	.= $nameNew;
			}elseif(isset($this->formData->elementMapping['name'][$name.'[]'])){
				$name	.= '[]';
			}else{
				SIM\printArray("Element with name $name not found");
				return false;
			}
		}
		$elementIndexes	= $this->formData->elementMapping['name'][$name];
	
		$elements		= [];
		
		foreach($elementIndexes as $index){
			$elements[]	= $this->formElements[$index];
		}

		if(!$single){
			return $elements;
		}

		$element	= $elements[0];
		
		if(empty($key)){
			return $element;
		}else{
			return $element->$key;
		}
	}

	/**
	 * Finds an element by its type
	 *
	 * @param	string	$name	The element type
	 * @param	string	$key	A specific element attribute to return. Default empty
	 *
	 * @return	object|array|string|false			The element or element property
	 */
	public function getElementByType($type){
		if(empty($type)){
			return false;
		}
		
		//load if needed
		if(empty($this->formData->elementMapping)){
			$this->getForm();
		}
		

		if(!isset($this->formData->elementMapping['type'][$type])){
			//SIM\printArray("Element with id $type not found");
			return false;
		}
		
		return $this->formData->elementMapping['type'][$type];
	}

	/**
	 * Get all elements belonging to the current form
	 *
	 * @param	string	$sortCol	the column to sort on. Default empty
	 * @param	int		$formId		The id of the form to get elements for, default empty
	 * @param	bool	$force		Whether to requery, default false
	 */
	public function getAllFormElements($sortCol = '', $formId='', $force=false){
		if(isset($this->formElements) && !$force){
			return '';
		}
		
		global $wpdb;

		if(!is_numeric($formId) && $this->formData && is_numeric($this->formData->id)){
			$formId	= $this->formData->id;
		}

		if(!is_numeric($formId) && isset($this->formId) && is_numeric($this->formId)){
			$formId	= $this->formId;
		}

		if(!is_numeric($formId)){
			return new \WP_Error('forms', 'No form id given');
		}

		// Get all form elements
		$query						= "SELECT * FROM {$this->elTableName} WHERE form_id= '$formId'";

		if(!empty($sortCol)){
			$query .= " ORDER BY {$this->elTableName}.`$sortCol` ASC";
		}
		$this->formElements 		=  apply_filters('sim-forms-elements', $wpdb->get_results($query), $this, false);
	}

	/**
	 * Parses all WP Shortcode attributes
	 */
	public function processAtts($atts){
		if(!isset($this->formName)){
			$atts	= shortcode_atts(
				array(
					'formname'		=> '',
					'userid'		=> '',
					'search'		=> '',
					'id'			=> '',
					'formid'		=> '',
					'onlyOwn'		=> false,
					'archived'		=> false,
					'all'			=> false,
				),
				$atts
			);
			
			$this->formName 	= strtolower(sanitize_text_field($atts['formname']));
			$this->formId		= sanitize_text_field($atts['formid']);

			$this->getForm();

			$this->shortcodeId	= $atts['id'];
			$this->onlyOwn		= $atts['onlyOwn'];
			if(isset($_GET['onlyown'])){
				$this->onlyOwn	= $_GET['onlyown'];
			}
			$this->all			= $atts['all'];
			$this->showArchived	= $atts['archived'];
			if(isset($_GET['archived'])){
				$this->showArchived	= $_GET['archived'];
			}

			if(isset($_GET['all'])){
				$this->all	= $_GET['all'];
			}
			
			if(!empty($atts['userid']) && is_numeric($atts['userid'])){
				$this->userId	= $atts['userid'];
			}
		}
	}

	/**
	 * Check if we should show the formbuilder or the form itself
	 */
	public function determineForm($atts){
		global $wpdb;

		$this->processAtts($atts);

		$query				= "SELECT * FROM {$this->elTableName} WHERE `form_id`=";

		if(is_numeric($this->formId)){
			$query	.= $this->formId;
		}elseif(!empty($this->formName)){
			$query	.= "(SELECT `id` FROM {$this->tableName} WHERE name='$this->formName')";
		}else{
			return new WP_Error('forms', 'Which form do you have?');
		}
		
		$formElements 		=  $wpdb->get_results($query);

		if((isset($_REQUEST['formbuilder']) || empty($formElements)) && $this->editRights){
			$formBuilderForm	= new FormBuilderForm($atts);

			return $formBuilderForm->showForm();
		}elseif(empty($formElements)){
			return "<div class='warning'>This form has no elements yet</div>";
		}else{
			$displayForm	= new DisplayForm($atts);
			return $displayForm->showForm();
		}
	}
}
