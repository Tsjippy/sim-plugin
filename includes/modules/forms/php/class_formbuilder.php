<?php
namespace SIM\FORMS;
use SIM;
use WP_Embed;
use WP_Error;

if(!trait_exists('SIM\FORMS\create_js')){
	require_once(__DIR__.'/js_builder.php');
}

class Formbuilder{
	use create_js;

	function __construct(){
		global $wpdb;
		$this->isformstep				= false;
		$this->is_multi_step_form		= '';
		$this->formstepcounter			= 0;
		$this->submissiontable_name		= $wpdb->prefix . 'sim_form_submissions';
		$this->table_name				= $wpdb->prefix . 'sim_forms';
		$this->el_table_name			= $wpdb->prefix . 'sim_form_elements';
		$this->non_inputs				= ['label','button','datalist','formstep','info','p','php','multi_start','multi_end'];
		$this->multi_inputs_html		= [];

		$this->user 		= wp_get_current_user();
		$this->user_roles	= $this->user->roles;
		$this->user_id		= $this->user->ID;

		$this->create_db_table();

		//calculate full form rights
		if(array_intersect(['editor'],$this->user_roles) != false){
			$this->editrights		= true;
		}else{
			$this->editrights		= false;
		}
	}
	
	function form_select(){
		$this->get_forms();
		
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
		
	function get_forms(){
		global $wpdb;
		
		$query							= "SELECT * FROM {$this->table_name} WHERE 1";
		
		$this->forms					= $wpdb->get_results($query);
	}
	
	function create_db_table(){
		if ( !function_exists( 'maybe_create_table' ) ) { 
			require_once ABSPATH . '/wp-admin/install-helper.php'; 
		} 

		add_option( "forms_db_version", "1.0" );
		
		//only create db if it does not exist
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		//Main table
		$sql = "CREATE TABLE {$this->table_name} (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  name tinytext NOT NULL,
		  version text NOT NULL,
		  settings text,
		  emails text,
		  PRIMARY KEY  (id)
		) $charset_collate;";

		maybe_create_table($this->table_name, $sql );

		// Form element table
		$sql = "CREATE TABLE {$this->el_table_name} (
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
		  ) $charset_collate;";
  
		maybe_create_table($this->el_table_name, $sql );

		//submission table
		$sql = "CREATE TABLE {$this->submissiontable_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			form_id	int NOT NULL,
			timecreated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			timelastedited datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			userid mediumint(9) NOT NULL,
			formresults longtext NOT NULL,
			archived BOOLEAN,
			PRIMARY KEY  (id)
		) $charset_collate;";

		maybe_create_table($this->submissiontable_name, $sql );
	}

	function process_atts($atts){
		if(!isset($this->datatype)){
			shortcode_atts(
				array(
					'datatype'		=> '', 
					'userid'		=> '', 
					'search'		=> '',
					'id'			=> ''
				),
				$atts
			);
			
			$this->datatype 	= strtolower(sanitize_text_field($atts['datatype']));
			$this->shortcode_id	= $atts['id'];
			
			if(is_numeric($atts['userid'])){
				$this->user_id	= $atts['userid'];
			}
		}
	}
	
	function getElementById($id, $key=''){
		//load if needed
		if(empty($this->formdata->element_mapping)) $this->loadformdata();
		
		$elementindex	= $this->formdata->element_mapping[$id];

		if(!isset($this->formdata->element_mapping[$id])){
			SIM\printArray("Element with id $id not found");
			return false;
		}
					
		$element		= $this->form_elements[$elementindex];
		
		if(empty($key)){
			return $element;
		}else{
			return $element->$key;
		}
	}

	function get_all_form_elements($sort_col = ''){
		global $wpdb;

		if($this->formdata and is_numeric($this->formdata->id)){
			$form_id	= $this->formdata->id;
		}elseif(isset($_POST['formfield']['form_id'])){
			$form_id	= $_POST['formfield']['form_id'];
		}elseif(isset($_POST['formid'])){
			$form_id	= $_POST['formid'];
		}

		// Get all form elements
		$query						= "SELECT * FROM {$this->el_table_name} WHERE form_id= '$form_id'";

		if(!empty($sort_col))	$query .= " ORDER BY {$this->el_table_name}.`$sort_col` ASC";
		$this->form_elements 		=  $wpdb->get_results($query);
	}
	
	function insert_form($name=''){
		global $wpdb;

		if(empty($name))	$name = $this->datatype;

		$result = $wpdb->insert(
			$this->table_name, 
			array(
				'name'			=> $name,
				'version' 		=> 1
			)
		);
		
		if(!empty($wpdb->last_error)){
			return new \WP_Error('error', $wpdb->print_error());
		}
	}

	function loadformdata($form_id=''){
		global $wpdb;

		if(is_numeric($_POST['formid'])) $form_id	= $_POST['formid'];
		
		// Get the form data
		$query				= "SELECT * FROM {$this->table_name} WHERE ";
		if(is_numeric($form_id)){
			$query	.= "id= '$form_id'";
		}else{
			$query	.= "name= '$this->datatype'";
		}

		$result				= $wpdb->get_results($query);

		// Form does not exit yet
		if(empty($result)){
			$this->insert_form();
			$this->formdata 	=  new \stdClass();
		}else{
			$this->formdata 	=  (object)$result[0];
		}

		$this->get_all_form_elements('priority');
		
		//used to find the index of an element based on its unique id
 		$this->formdata->element_mapping	= [];
		foreach($this->form_elements as $index=>$element){			
			$this->formdata->element_mapping[$element->id]	= $index;
		}
		
		if(empty($this->formdata->settings)){
			$settings['succesmessage']			= "";
			$settings['formname']				= "";
			$settings['roles']					= [];

			$this->formdata->settings = $settings;
		}else{
			$this->formdata->settings = maybe_unserialize(utf8_encode($this->formdata->settings));
		}

		if(!$this->editrights){
			$edit_roles	= ['editor'];
			foreach($this->formdata->settings['full_right_roles'] as $key=>$role){
				if(!empty($role)){
					$edit_roles[] = $key;
				}
			}
			//calculate full form rights
			if(array_intersect($edit_roles,(array)$this->user_roles) != false){
				$this->editrights		= true;
			}else{
				$this->editrights		= false;
			}
		}

		$this->submit_roles	= [];
		foreach($this->formdata->settings['submit_others_form'] as $key=>$role){
			if(!empty($role)){
				$this->submit_roles[] = $key;
			}
		}
		
		if(empty($this->formdata->emails)){
			$emails[0]["from"]		= "";
			$emails[0]["to"]			= "";
			$emails[0]["subject"]	= "";
			$emails[0]["message"]	= "";
			$emails[0]["headers"]	= "";
			$emails[0]["files"]		= "";

			$this->formdata->emails = $emails;
		}else{
			$this->formdata->emails = maybe_unserialize($this->formdata->emails);
		}
		
		if($wpdb->last_error !== '')		SIM\printArray($wpdb->print_error());
		
		$this->jsfilename	= plugin_dir_path(__DIR__)."js/dynamic/{$this->formdata->name}forms";
	}

	function update_form_element($element){
		global $wpdb;
		
		//Update element
		$result = $wpdb->update(
			$this->el_table_name, 
			(array)$element, 
			array(
				'id'		=> $element->id,
			),
		);

		if($this->formdata == null){
			$this->loadformdata($_POST['formid']);
		}

		//Update form version
		$result = $wpdb->update(
			$this->table_name, 
			['version'	=> $this->formdata->version+1], 
			['id'		=> $this->formdata->id],
		);
		
		if($wpdb->last_error !== ''){
			return new WP_Error('forms', $wpdb->print_error());
		}
	}

	function insert_element($element){
		global $wpdb;
		
		$result = $wpdb->insert(
			$this->el_table_name, 
			(array)$element
		);

		return $wpdb->insert_id;
	}

	function update_priority($element){
		global $wpdb;

		//Update the database
		$result = $wpdb->update($this->el_table_name, 
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
	}

	function reorder_elements($old_priority, $new_priority, $element) {
		if ($old_priority == $new_priority) return;

		// Get all elements of this form
		$this->get_all_form_elements('priority');

		if(empty($element)){
			foreach($this->form_elements as $el){
				if($el->priority == $old_priority){
					$element = $el;
					break;
				}
			}
		}

		//No need to reorder if we are adding a new element at the end
		if(count($this->form_elements) == $new_priority){
			// First element is the element without priority
			$el				= $this->form_elements[0];
			$el->priority	= $new_priority;
			$this->update_priority($el);
			return;
		}
		
		//Loop over all elements and give them the new priority
		foreach($this->form_elements as $el){
			if($el->name == $element->name){
				$el->priority	= $new_priority;
				$this->update_priority($el);
			}elseif($old_priority == -1){
				if($el->priority >= $new_priority){
					$el->priority++;
					$this->update_priority($el);
				}
			}elseif(
				$old_priority > $new_priority	and 	//we are moving an element upward
				$el->priority >= $new_priority	and		// current priority is bigger then the new prio
				$el->priority < $old_priority			// current priority is smaller than the old prio
			){
				$el->priority++;
				$this->update_priority($el);
			}elseif(
				$old_priority < $new_priority	and 	//we are moving an element downward
				$el->priority > $old_priority	and		// current priority is bigger then the old prio
				$el->priority < $new_priority			// current priority is smaller than the new prio
			){
				$el->priority--;
				$this->update_priority($el);
			}
		}
	}

	function is_multi_step(){
		if(empty($this->is_multi_step_form)){
			foreach($this->form_elements as $el){
				if($el->type == 'formstep'){
					$this->is_multi_step_form	= true;
					return true;
				}
			}

			$this->is_multi_step_form	= false;
			return false;
		}else{
			return $this->is_multi_step_form;
		}
	}
	
	function get_submission_data($userId=null, $submission_id=null){
		global $wpdb;
		
		$query				= "SELECT * FROM {$this->submissiontable_name} WHERE form_id={$this->formdata->id}";
		if(is_numeric($submission_id)){
			$query .= " and id='$submission_id'";
		}elseif(is_numeric($userId)){
			$query .= " and userid='$userId'";
		}
		
		if(!$this->show_archived and $submission_id == null){
			$query .= " and archived=0";
		}

		$query	= apply_filters('formdata_retrieval_query', $query, $userId, $this->datatype);

		$result	= $wpdb->get_results($query);
		$result	= apply_filters('retrieved_formdata', $result, $userId, $this->datatype);

		$this->submission_data		= $result;
		
		if(is_numeric($submission_id))	$this->submission_data = $this->submission_data[0];

		$this->formresults 			= maybe_unserialize($this->submission_data->formresults);
		
		if($wpdb->last_error !== '')		SIM\printArray($wpdb->print_error());
	}
	
	function build_defaults_array(){
		//Only create one time
		if(empty($this->default_values)){
			$this->default_values		= (array)get_userdata($this->user_id)->data;
			//Change ID to userid because its a confusing name
			$this->default_values['user_id']	= $this->default_values['ID'];
			unset($this->default_values['ID']);
			
			$this->default_array_values	= [];
			
			foreach(['user_pass','user_activation_key','user_status','user_level'] as $field){
				unset($this->default_values[$field]);
			}
			
			//get defaults from filters
			$this->default_values		= apply_filters('add_form_defaults', $this->default_values,$this->user_id,$this->datatype);
			$this->default_array_values	= apply_filters('add_form_multi_defaults', $this->default_array_values,$this->user_id,$this->datatype);
			
			ksort($this->default_values);
			ksort($this->default_array_values);
		}
	}
	
	function get_element_html($element, $width=100){
		$html		= '';
		$el_class	= '';
		$el_options	= '';

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

				$option_type	= explode('=',$option)[0];
				$option_value	= str_replace('\\\\', '\\', explode('=',$option)[1]);

				if($option_type == 'class'){
					$el_class .= " $option_value";
				}else{
					if(!in_array($option_type, ['pattern', 'title', 'accept'])){
						$option_value = str_replace([' ', ',', '(', ')'],['_', '', '', ''], $option_value);
					}

					//remove any leading "
					$option_value	= trim($option_value, '\'"');

					//Write the corrected option as html
					$el_options	.= " $option_type='$option_value'";
				}
			}
		}
		
		if($element->type == 'p'){
			$html = wp_kses_post($element->text);
			$html = "<div name='{$element->name}'>".$this->deslash($html)."</div>";
		}elseif($element->type == 'php'){
			//we store the functionname in the html variable replace any double \ with a single \
			$functionname 						= str_replace('\\\\','\\',$element->functionname);
			//only continue if the function exists
			if (function_exists( $functionname ) ){
				$html	= $functionname($this->user_id);
			}else{
				$html	= "php function '$functionname' not found";
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
					$targetdir	= $element->foldername;
				}else{
					$targetdir	= "private/".$element->foldername;
				}
			} 
			// Form setting
			if(empty($targetdir)) $targetdir = $this->formdata->settings['upload_path'];
			// Default setting
			if(empty($targetdir)) $targetdir = 'form_uploads/'.$this->formdata->settings['formname'];

			if(empty($this->formdata->settings['save_in_meta'])){
				$library	= false;
				$metakey	= '';
				$userId	= '';
			}else{
				$library	= $element->library;
				$metakey	= $name;
				$userId	= $this->user_id;
			}
			//Load js			
			$uploader = new SIM\Fileupload($userId, $name, $targetdir, $element->multiple, $metakey, $library, '', false);
			
			$html = $uploader->getUploadHtml($el_options);
		}else{
			/*
				ELEMENT TAG NAME
			*/
			if(in_array($element->type,['formstep','info'])){
				$el_type	= "div";
			}elseif(in_array($element->type,array_merge($this->non_inputs,['select','textarea']))){
				$el_type	= $element->type;
			}else{
				$el_type	= "input type='{$element->type}'";
			}
			
			/* 				
				ELEMENT NAME
			 */
			//Get the field name, make it lowercase and replace any spaces with an underscore
			$el_name	= $element->name;
			// [] not yet added to name
			if(in_array($element->type,['radio','checkbox']) and strpos($el_name, '[]') === false) {
				$el_name .= '[]';
			}
			
			/*
				ELEMENT ID
			*/
			//datalist needs an id to work as well as mandatory elements for use in anchor links
			if($element->type == 'datalist' or $element->mandatory or $element->recommended){
				$el_id	= "id='$el_name'";
			}
			
			/*
				ELEMENT CLASS
			*/
			$el_class = " formfield";
			switch($element->type){
				case 'label':
					$el_class .= " formfieldlabel";
					break;
				case 'button':
					$el_class .= " button";
					break;
				case 'formstep':
					$el_class .= " formstep stephidden";
					break;
				default:
					$el_class .= " formfieldinput";
			}
			
			/*
				BUTTON TYPE
			*/
			if($element->type == 'button'){
				$el_options	.= " type='button'";
			}
			/*
				ELEMENT VALUE
			*/
			$values	= $this->get_field_values($element);
			if($this->multiwrap or !empty($element->multiple)){
				if(strpos($el_type,'input') !== false){
					$el_value	= "value='%value%'";
				}
			}elseif(!empty($values)){				
				//this is an input and there is a value for it
				if(empty($this->formdata->settings['save_in_meta'])){
					$val		= array_values((array)$values['defaults'])[0];
				}else{
					$val		= array_values((array)$values['metavalue'])[0];
				}
				
				if(strpos($el_type,'input') !== false and !empty($val)){
					$el_value	= "value='$val'";
				}else{
					$el_value	= "";
				}
			}
			
			/*
				ELEMENT TAG CONTENTS
			*/
			$el_content = "";
			if($element->type == 'textarea'){
				$el_content = $values['metavalue'][0];
			}elseif(!empty($element->text)){
				switch($element->type){
					case 'formstep':
						$el_content = "<h3>{$element->text}</h3>";
						break;
					case 'label':
						$el_content = "<h4 class='labeltext'>{$element->text}</h4>";;
						break;
					case 'button':
						$el_content = $element->text;
						break;
					default:
						$el_content = "<label class='labeltext'>{$element->text}</label>";
				}
			}
			
			switch($element->type){
				case 'select':
					$el_content .= "<option value=''>---</option>";
					$options	= $this->get_field_options($element);
					$sel_values		= array_map('strtolower', (array)$values['metavalue']);
		
					foreach($options as $key=>$option){
						if(in_array(strtolower($option),$sel_values) or in_array(strtolower($key),$sel_values)){
							$selected	= 'selected';
						}else{
							$selected	= '';
						}
						$el_content .= "<option value='$key' $selected>$option</option>";
					}
					break;
				case 'datalist':
					$options	= $this->get_field_options($element);
					foreach($options as $key=>$option){
						$el_content .= "<option data-value='$key' value='$option'>";
					}
					break;
				case 'radio':
				case 'checkbox':
					$options	= $this->get_field_options($element);
					$html		= "<div class='checkbox_options_group formfield'>";
					
					$low_values	= [];
					
					$default_key				= $element->default_value;
					if(!empty($default_key)){
						$low_values[]	= strtolower($this->default_values[$default_key]);
					}
					foreach((array)$values['metavalue'] as $key=>$val){
						if(is_array($val)){
							foreach($val as $v){
								$low_values[] = strtolower($v);
							}
						}else{
							$low_values[] = strtolower($val);
						}
					}

					foreach($options as $key=>$option){
						if($this->multiwrap){
							$checked	= '%checked%';
						}elseif(in_array(strtolower($option),$low_values) or in_array(strtolower($key),$low_values)){
							$checked	= 'checked';
						}else{
							$checked	= '';
						}
						
						$html .= "<label>";
							$html .= "<$el_type name='$el_name' class='$el_class' $el_options value='$key' $checked>";
							$html .= "<span class='optionlabel'>$option</span>";
						$html .= "</label><br>";
					}

					$html .= "</div>";
					break;
				default:
					$el_content .= "";
			}
			
			//close the field
			if(in_array($element->type,['select','textarea','button','datalist'])){
				$el_close	= "</{$element->type}>";
			}else{
				$el_close	= "";
			}
			
			//only close a label field if it is not wrapped
			if($element->type == 'label' and empty($element->wrap)){
				$el_close	= "</label>";
			}
			
			/*
				ELEMENT MULTIPLE
			*/
			if(!empty($element->multiple)){
				if($element->type == 'select'){
					$html	= "<$el_type  name='$el_name' $el_id class='$el_class' $el_options>";
				}else{
					$html	= "<$el_type  name='$el_name' $el_id class='$el_class' $el_options value='%value%'>$el_content$el_close";
				}
					
				$this->process_multi_fields($element, 'multifield', $width, $values, $html);
				$html	= "<div class='clone_divs_wrapper'>";
				foreach($this->multi_inputs_html as $h){
					$html	.= $h;
				}
				$html	.= '</div>';
			}elseif(empty($html)){
				$html	= "<$el_type  name='$el_name' $el_id class='$el_class' $el_options $el_value>$el_content$el_close";
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
				$date_string = date("Y-m-d", strtotime($keyword));
				
				//update form element
				$html = str_replace($matches[0][$key],$date_string,$html);
			}
		}

		return $html;
	}
	
	function get_field_values($element){
		$values	= [];

		// Do not return default values when requesting the html over rest api
		if(defined('REST_REQUEST')){
			return $values;
		}
		
		if(in_array($element->type,$this->non_inputs)) return [];

		$this->build_defaults_array();

		//get the fieldname, remove [] and split on remaining [
		$fieldname	= str_replace(']','',explode('[',str_replace('[]','',$element->name)));
		
		//retrieve meta values if needed
		if(!empty($this->formdata->settings['save_in_meta'])){
			//only load usermeta once
			if(!is_array($this->usermeta)){
				//usermeta comes as arrays, only keep the first
				$this->usermeta	= [];
				foreach(get_user_meta($this->user_id) as $key=>$meta){
					$this->usermeta[$key]	= $meta[0];
				}
				$this->usermeta	= apply_filters('forms_load_userdata',$this->usermeta,$this->user_id);
			}
		
			if(count($fieldname) == 1){
				//non array name
				$fieldname	= $fieldname[0];
				$values['metavalue']		= (array)maybe_unserialize($this->usermeta[$fieldname]);
			}else{
				//an array of values, we only want a specific one
				$values['metavalue']		= (array)maybe_unserialize($this->usermeta[$fieldname[0]]);
				unset($fieldname[0]);
				//loop over all the subkeys, and store the value until we have our final result
				foreach($fieldname as $v){
					$values['metavalue'] = (array)$values['metavalue'][$v];
				}
			}
		}
		
		//add default values
		if(empty($element->multiple) or in_array($element->type, ['select','checkbox'])){
			$default_key				= $element->default_value;
			if(!empty($default_key)){
				$values['defaults']		= $this->default_values[$default_key];
			}
		}else{
			$default_key				= $element->default_array_value;
			if(!empty($default_key)){
				$values['defaults']		= $this->default_array_values[$default_key];
			}
		}

		return $values;
	}
	
	function get_field_options($element){
		$options	= [];
		//add element values
		$el_values	= explode("\n",$element->valuelist);
		
		if(!empty($el_values[0])){
			foreach($el_values as $key=>$value){
				$split = explode('|',$value);
				
				//Remove starting or ending spaces and make it lowercase
				$value = trim($split[0]);
				$escaped_value = str_replace(' ','_',$value);
				$escaped_value = str_replace(',','',$escaped_value);
				$escaped_value = str_replace('(','',$escaped_value);
				$escaped_value = str_replace(')','',$escaped_value);
				
				if(!empty($split[1])){
					$display_name	= $split[1];
				}else{
					$display_name	= $value;
				}
				
				$options[$escaped_value]	= $display_name;
			}

			asort($options);
		}
		
		$default_array_key				= $element->default_array_value;
		if(!empty($default_array_key)){
			$options	= (array)$this->default_array_values[$default_array_key]+$options;
		}
		
		return $options;
	}
	
	function process_multi_fields($element, $key, $width, $values=null, $element_html=''){
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
					$this->next_element->required
				){
					$hidden .= ' required';
				}
		
				$element_html = $this->get_element_html($element);
				
				//close the label element after the field element
				if($this->wrap and !isset($element->wrap)){
					$element_html .= "</div>";
					if($this->wrap == 'label'){
						$element_html .= "</label>";
					}
					$this->wrap = false;
				}elseif(!$this->wrap){
					if($element->type == 'info'){
						$element_html .= "<div class='inputwrapper$hidden info'>";
					}else{
						if(!empty($element->wrap)){
							$class	= 'flex';
						}else{
							$class	= '';
						}
						$element_html .= "<div class='inputwrapper$hidden $class' style='width:$width%;'>";
					}
				}
				
				if(in_array($element->type,$this->non_inputs)){	
					//Get the field values of the next element as this does not have any
					$values		= $this->get_field_values($this->next_element);
				}else{
					$values		= $this->get_field_values($element);
				}

				if(empty($this->formdata->settings['save_in_meta'])){
					$values		= array_values((array)$values['defaults']);
				}else{
					$values		= array_values((array)$values['metavalue']);
				}
				
				if(
					$this->wrap == false								and
					isset($element->wrap)								and 			//we should wrap around next element
					is_object($this->next_element)						and 			// and there is a next element
					!in_array($this->next_element->type, ['select','php','formstep'])	// and the next element type is not a select or php element
				){
					$this->wrap = $element->type;
				}
			//key is not numeric so we are dealing with a multi input field
			}else{
				//add label to each entry if prev element is a label and wrapped with this one
				if($this->prev_element->type	== 'label' and !empty($this->prev_element->wrap)){
					$this->prev_element->text = $this->prev_element->text.' %key%';
					$prev_label = $this->get_element_html($this->prev_element).'</label>';
				}else{
					$prev_label	= '';
				}

				if(empty($this->formdata->settings['save_in_meta'])){
					$values		= array_values((array)$values['defaults']);
				}else{
					$values		= array_values((array)$values['metavalue']);
				}

				//check how many elements we should render
				$this->multi_wrap_value_count	= max(1,count((array)$values));
			}

			//create as many inputs as the maximum value found
			for ($index = 0; $index < $this->multi_wrap_value_count; $index++) {
				$val	= $values[$index];
				//Add the key to the fields name
				$html	= preg_replace("/(name='.*?)'/i", "\${1}[$index]'", $element_html);
				
				//we are dealing with a select, add options
				if($element->type == 'select'){
					$html .= "<option value=''>---</option>";
					$options	= $this->get_field_options($element);
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
					$options	= $this->get_field_options($element);

					//make key and value lowercase
					array_walk_recursive($options, function(&$item, &$key){
						$item = strtolower($item);
						$key = strtolower($key);
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
					$this->multi_inputs_html[$index] .= "<div class='clone_div' data-divid='$index'>";
				}
				if($this->prev_element->type == 'multi_start'){
					$this->multi_inputs_html[$index] .= "<div class='clone_div' data-divid='$index' style='display:flex'>";
					$this->multi_inputs_html[$index] .= "<div class='multi_input_wrapper'>";
				}
				
				//add flex to single multi items, re-add the label for each value
				if(!is_numeric($key)){
					$this->multi_inputs_html[$index] .= str_replace('%key%',$index+1,$prev_label);
					//wrap input AND buttons in a flex div
					$this->multi_inputs_html[$index] .= "<div class='buttonwrapper' style='width:100%; display: flex;'>";
				}
				
				if($element->type == 'label'){
					$nr		= $index+1;
					$html	= str_replace('</h4>'," $nr</h4>",$html);
				}
				//write the element
				$this->multi_inputs_html[$index] .= $html;
				
				//end, write the buttons and closing div
				if(!is_numeric($key) or $this->next_element->type == 'multi_end'){
					//close any label first before adding the buttons
					if($this->wrap == 'label'){
						$this->multi_inputs_html[$index] .= "</label>";
					}
					
					//close select
					if($element->type == 'select'){
						$this->multi_inputs_html[$index] .= "</select>";
					}
					
					//open the buttons div in case we have not written the flex div
					if(is_numeric($key)){
						$this->multi_inputs_html[$index] .= "</div>";//close multi_input_wrapper div
						$this->multi_inputs_html[$index] .= "<div class='buttonwrapper' style='margin: auto;'>";
					}
							$this->multi_inputs_html[$index] .= "<button type='button' class='add button' style='flex: 1;'>+</button>";
							$this->multi_inputs_html[$index] .= "<button type='button' class='remove button' style='flex: 1;'>-</button>";
						$this->multi_inputs_html[$index] .= "</div>";
					$this->multi_inputs_html[$index] .= "</div>";//close clone_div
				}
			}
		}
	}
	
	function buildhtml($element, $key=0){
		if(empty($this->formdata))		$this->loadformdata();
		if(empty($this->form_elements)) $this->get_all_form_elements();
		$this->prev_element		= $this->form_elements[$key-1];
		$this->next_element		= $this->form_elements[$key+1];

		if(
			!empty($this->next_element->multiple) 	and // if next element is a multiple
			$this->next_element->type != 'file' 	and // next element is not a file
			$element->type == 'label'				and // and this is a label
			!empty($element->wrap)					and // and the label is wrapped around the next element
			!isset($_GET['formbuilder']) 			and	// and we are not building the form
			!defined('REST_REQUEST')				// and we are not doing a REST call
		){
			return;
		}
		//store the prev rendered element before updating the current element
		$this->prev_rendered_element	= $this->current_element;
		$this->current_element			= $element;
				
		//Set the element width to 85 percent so that the info icon floats next to it
		if($key != 0 and $this->prev_rendered_element->type == 'info'){
			$width = 85;
		//We are dealing with a label which is wrapped around the next element
		}elseif($element->type == 'label'	and !isset($element->wrap) and is_numeric($this->next_element->width)){
			$width = $this->next_element->width;
		}elseif(is_numeric($element->width)){
			$width = $element->width;
		}else{
			$width = 100;
		}

		//Load default values for this element
		$element_html = $this->get_element_html($element,$width);
		
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
			$this->next_element->required
		){
			$hidden .= ' required';
		}
		
		//Add form edit controls if needed
		if($this->editrights == true and (isset($_GET['formbuilder']) or defined('REST_REQUEST'))){
			$html = " <div class='form_element_wrapper' data-id='{$element->id}' data-formid='{$this->formdata->id}' data-priority='{$element->priority}' style='display: flex;'>";
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
						$element_html	= '';
					}elseif($element->type == 'multi_end'){
						$html .= ' ***multi answer end***';
						$element_html	= '';
					}
					
					
					$html .= $element_html;
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
				//if there is already a formstep wrtten close that one first
				if($this->isformstep == true){
					$html .= "</div>";
				//first part of the form, don't hide
				}else{
					$this->isformstep = true;
					$html .= "<img class='formsteploader' src='".LOADERIMAGEURL."'>";
				}
				
				$this->formstepcounter	+= 1;
				$html .= $element_html;
			}elseif($element->type == 'multi_end'){
				$this->multiwrap	= false;
				//write down all the multi html
				$name	= str_replace('end','start',$element->name);
				$html  .= "<div class='clone_divs_wrapper' name='$name'>";
				foreach($this->multi_inputs_html as $multihtml){
					$html .= $multihtml;
				}
				$html .= '</div>';//close clone_divs_wrapper
				$html .= '</div>';//close inputwrapper

			//just calculate the multi html
			}elseif($this->multiwrap){
				$this->process_multi_fields($element, $key, $width);
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
				
				$html .= $element_html;
				if($element->type == 'multi_start'){
					$this->multiwrap				= true;
					
					//we are wrapping so we need to find the max amount of filled in fields
					$i								= $key+1;
					$this->multi_wrap_value_count	= 1;
					//loop over all consequent wrapped elements
					while(true){
						$type	= $this->form_elements[$i]->type;
						if($type != 'multi_end'){
							if(!in_array($type,$this->non_inputs)){
								//Get the field values and count
								$value_count		= count($this->get_field_values($this->form_elements[$i]));
								
								if($value_count > $this->multi_wrap_value_count){
									$this->multi_wrap_value_count = $value_count;
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
						is_object($this->next_element)					and 			// and there is a next element
						!in_array($this->next_element->type, ['php','formstep'])	// and the next element type is not a select or php element
					){
						//end a label if the next element is a select
						if($element->type == 'label' and in_array($this->next_element->type, ['php','select'])){
							$html .= "</label></div>";
						}else{
							$this->wrap = $element->type;
						}
					//we have not started a wrap
					}else{		
						//only close wrap if the current element is not wrapped or it is the last
						if(empty($element->wrap) or !is_array($this->next_element)){
							//close the label element after the field element or if this the last element of the form and a label
							if(
								$this->wrap == 'label' or 
								($element->type == 'label' and !is_array($this->next_element))
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

	function deslash( $content ) {
		$content = preg_replace( "/\\\+'/", "'", $content );
		$content = preg_replace( '/\\\+"/', '"', $content );
		$content = preg_replace( '/https?:\/\/https?:\/\//i', 'https://', $content );
	
		return $content;
	}

	function update_submission_data($archive=false){
		global $wpdb;

		$submission_id	= $this->formresults['id'];
		if(!is_numeric($submission_id)){
			if(is_numeric($this->submission_id)){
				$submission_id	= $this->submission_id;
			}elseif(is_numeric($_POST['submissionid'])){
				$submission_id	= $_POST['submissionid'];
			}else{
				SIM\printArray('No submission id found');
				return false;
			}
		}	

		$this->formresults['edittime']	= date("Y-m-d H:i:s");
		//Update the database
		$result = $wpdb->update(
			$this->submissiontable_name, 
			array(
				'timelastedited'	=> date("Y-m-d H:i:s"),
				'formresults'		=> maybe_serialize($this->formresults),
				'archived'			=> $archive
			),
			array(
				'id'				=> $submission_id,
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
			$message	= "No row with id $submission_id found";
			if(defined('REST_REQUEST')){
				return new WP_Error('form error', $message);
			}else{
				SIM\printArray($message);
				SIM\printArray($this->formresults);
			}
		}
		
		return;
	}
	
	function find_conditional_email($conditions){
		//loop over all conditions
		foreach($conditions as $condition){
			//loop over all elements to find the element with the id specified
			foreach($this->form_elements as $element){
				//we found the element with the correct id
				if($element->id == $condition['fieldid']){
					//get the submitted form value
					$form_value = $this->formresults[$element->name];
					
					//if the value matches the conditional value
					if(strtolower($form_value) == strtolower($condition['value'])){
						return $condition['email'];
					}
				}
			}
		}
	}

	function email_footer($footer){
		$footer['url']		= $_POST['formurl'];
		$footer['text']		= $_POST['formurl'];
		return $footer;
	}

	function send_email($trigger='submitted'){
		$emails = $this->formdata->emails;
		
		foreach($emails as $key=>$email){
			if($email['emailtrigger'] == $trigger){
				if($trigger == 'fieldchanged'){
					$form_value='';
					//loop over all elements to find the element with the id specified
					foreach($this->form_elements as $element){
						//we found the element with the correct id
						if($element->id == $email['conditionalfield']){
							//get the submitted form value
							$form_value = $this->formresults[$element->name];
							
							break;
						}
					}
					//do not proceed if there is no match
					if(strtolower($form_value) != strtolower($email['conditionalvalue'])) continue;
				}
				
				$from	= '';
				//Send e-mail from conditional e-mail adress
				if($email['fromemail'] == 'conditional'){
					$from = $this->find_conditional_email($email['conditionalfromemail']);
				}elseif($email['fromemail'] == 'fixed'){
					$from		= $this->process_placeholders($email['from']);
				}
				if($from == ''){
					SIM\printArray("No from email found for email $key");
				}
								
				$to		= '';
				if($email['emailto'] == 'conditional'){
					$to = $this->find_conditional_email($email['conditionalemailto']);
				}elseif($email['emailto'] == 'fixed'){
					$to		= $this->process_placeholders($email['to']);
				}
				
				if(empty($to)){
					SIM\printArray("No to email found for email $key");
					continue;
				}

				$subject	= $this->process_placeholders($email['subject']);
				$message	= $this->process_placeholders($email['message']);
				$headers	= explode("\n",$email['headers']);
				if(!empty($from)){$headers[]	= "Reply-To: $from";}
				
				$files		= $this->process_placeholders($email['files']);
				
				//Send the mail
				if($_SERVER['HTTP_HOST'] != 'localhost'){
					add_filter('sim_email_footer_url', [$this, 'email_footer']);

					SIM\printArray("Sending e-mail to $to");
					$result = wp_mail($to , $subject, $message, $headers, $files);
					if($result === false){
						SIM\printArray("Sending the e-mail failed");
					}
					remove_filter('sim_email_footer_url', [$this, 'email_footer']);
				}
			}
		}
	}
	
	function autoarchive(){
		//get all the forms
		$this->get_forms();
		
		//loop over all the forms
		foreach($this->forms as $form){
			$settings = maybe_unserialize($form->settings);
			
			//check if auto archive is turned on for this form
			if($settings['autoarchive'] == 'true'){
				$this->datatype		= $form->name;
				$field_main_name	= $settings['split'];
				
				//Get all submissions of this form
				$this->get_submission_data();
				
				$trigger_name	= $this->getElementById($settings['autoarchivefield'],'name');
				$trigger_value	= $settings['autoarchivevalue'];
				$pattern		= '/'.$field_main_name."\[[0-9]+\]\[([^\]]+)\]/i";
				if(preg_match($pattern, $trigger_name,$matches)){
					$trigger_name	= $matches[1];
				}
				
				//check if we need to transform a keyword to a date 
				$pattern = '/%([^%;]*)%/i';
				//Execute the regex
				preg_match_all($pattern, $trigger_value,$matches);
				if(!is_array($matches[1])){
					SIM\printArray($matches[1]);
				}else{
					foreach((array)$matches[1] as $key=>$keyword){
						//If the keyword is a valid date keyword
						if(strtotime($keyword)){
							//convert to date
							$trigger_value = date("Y-m-d", strtotime(str_replace('%','',$trigger_value)));
						}
					}
				}
				
				//loop over all submissions to see if we need to archive
				foreach($this->submission_data as $id=>$sub_data){
					$this->formresults		= maybe_unserialize($sub_data->formresults);
					
					$this->submission_id	= $sub_data->id;
					//there is no trigger value found in the results, check multi value array
					if(empty($this->formresults[$trigger_name])){
						//loop over all multi values
						foreach($this->formresults[$field_main_name] as $sub_id=>$sub){
							//we found a match for a sub entry, archive it
							$val	= $sub[$trigger_name];						
							if(
								$val == $trigger_value or 						//value is the same as the trigger value or
								(
									strtotime($trigger_value) and 				// trigger value is a date
									strtotime($val) and							// this value is a date
									strtotime($val)<strtotime($trigger_value)	// value is smaller than the trigger value
								)
							){
								$this->formresults[$field_main_name][$sub_id]['archived'] = true;
								
								//update in db
								$this->check_if_all_archived($this->formresults[$field_main_name]);
							}
						}
					}else{
						//if the form value is equal to the trigger value it needs to be to be archived
						if($this->formresults[$trigger_name] == $trigger_value){
							$this->update_submission_data(true);
						}
					}
				}
			}
		}
	}
	
	function check_if_all_archived($data){
		//check if all subfields are archived or empty
		$all_archived = true;
		//loop over all keys
		foreach($data as $subfieldsarray){
			//loop over all fields of this key
			$empty = true;
			foreach($subfieldsarray as $field){
				if(!empty($field)) $empty = false;
				break;
			}
			
			//there are values and the sub entry is not archived
			if($empty == false and empty($subfieldsarray['archived'])){
				$all_archived = false;
				break;
			}
		}
		
		//update and mark as archived if all entries are empty or archived
		$this->update_submission_data($all_archived);
	}
	
	function input_dropdown($field_id, $element_id=-1){
		if($field_id == ''){
			$html = "<option value='' selected>---</option>";
		}else{
			$html = "<option value=''>---</option>";
		}
		
		foreach($this->form_elements as $key=>$element){
			//do not include the element itself do not include non-input types
			if($element->id != $element_id and !in_array($element->type,['label','info','datalist','formstep'])){
				$name = ucfirst(str_replace('_',' ',$element->name));
				
				//Check which option is the selected one
				if($field_id != '' and $field_id == $element->id){
					$selected = 'selected';
				}else{
					$selected = '';
				}
				$html .= "<option value='{$element->id}' $selected>$name</option>";
			}
		}
		
		return $html;
	}
	
	function process_placeholders($string){
		if(empty($string)) return $string;

		$this->formresults['submissiondate']	= date('d F y',strtotime($this->formresults['submissiontime']));
		$this->formresults['editdate']			= date('d F y',strtotime($this->formresults['edittime']));

		$pattern = '/%([^%;]*)%/i';
		//Execute the regex
		preg_match_all($pattern, $string,$matches);
		
		//loop over the results
		foreach($matches[1] as $key=>$match){
			if(empty($this->formresults[$match])){
				//remove the placeholder, there is no value
				$string = str_replace("%$match%",'',$string);
			}elseif(is_array($this->formresults[$match])){
				$files	= $this->formresults[$match];
				$string = array_map(function($value){
					return ABSPATH.$value;
				},$files);
			}else{
				//replace the placeholder with the value
				$replaceValue	= str_replace('_', ' ', $this->formresults[$match]);
				$string 		= str_replace("%$match%", $replaceValue, $string);
			}
		}
		
		return $string;
	}

	function formbuilder($atts){
		$this->process_atts($atts);
				
		$this->loadformdata();
		
		// We cannot use the minified version as the dynamic js files depend on the function names
		wp_enqueue_script('sim_forms_script');

		if(
			array_intersect($this->user_roles,$this->submit_roles) != false	and	// we have the permission to submit on behalf on someone else
			is_numeric($_GET['userid'])																				and // and the userid parameter is set in the url
			empty($atts['userid'])																						// and the user id is not given in the shortcode 
		){
			$this->user_id	= $_GET['userid'];
		}
		ob_start();

		//add formbuilder.js
		if($this->editrights == true and isset($_GET['formbuilder'])){
			//Formbuilder js
			wp_enqueue_script( 'sim_formbuilderjs');
		}
		
		//Load conditional js if available and needed
		if(!isset($_GET['formbuilder'])){
			if($_SERVER['HTTP_HOST'] == 'localhost'){
				$js_path		= $this->jsfilename.'.js';
			}else{
				$js_path		= $this->jsfilename.'.min.js';
			}

			if(!file_exists($js_path)){
				SIM\printArray("$js_path does not exist!\nBuilding it now");
				
				$path	= plugin_dir_path(__DIR__)."js/dynamic";
				if (!is_dir($path)) {
					mkdir($path, 0777, true);
				}
				
				//build initial js if it does not exist
				$this->create_js();
			}

			//Only enqueue if there is content in the file
			if(file_exists($js_path) and filesize($js_path) > 0){
				wp_enqueue_script( "dynamic_{$this->datatype}forms", SIM\pathToUrl($js_path), array('sim_forms_script'), $this->formdata->version, true);
			}
		}
		
		?>
		<div class="sim_form wrapper">
			<?php
			
			if($this->editrights == true){
				//redirect  to formbuilder if we have rights and no formfields defined yet
				if(empty($this->form_elements) and !isset($_GET['formbuilder'])){
					$url = SIM\currentUrl();
					if(strpos($url,'?') === false){
						$url .= '/?';
					}else{
						$url .= '&';
					}
					header("Location: {$url}formbuilder=yes");
				}
				
				if(isset($_GET['formbuilder'])){
					$this->add_element_modal();

					?>
					<button class="button tablink formbuilderform<?php if(!empty($this->form_elements)) echo ' active';?>"	id="show_element_form" data-target="element_form">Form elements</button>
					<button class="button tablink formbuilderform<?php if(empty($this->form_elements)) echo ' active';?>"	id="show_form_settings" data-target="form_settings">Form settings</button>
					<button class="button tablink formbuilderform"															id="show_form_emails" data-target="form_emails">Form emails</button>
					<?php 
				}else{
					//button
					echo "<a href='?formbuilder=yes' class='button sim'>Show formbuilder</a>";
				}
				?>
				
				<div class="tabcontent<?php if(empty($this->form_elements)) echo ' hidden';?>" id="element_form">
					<?php
					if(isset($_GET['formbuilder'])){
						if(empty($this->form_elements)){
							?>
							<div name="formbuildbutton">
								<p>No formfield defined yet.</p>
								<button name='createform' class='button' data-formname='<?php echo $this->datatype;?>'>Add fields to this form</button>
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
			
			$this->showform();
			if($this->editrights == true and isset($_GET['formbuilder'])){
				?>
				</div>
				
				<div class="tabcontent<?php if(!empty($this->form_elements)) echo ' hidden';?>" id="form_settings">
					<?php $this->form_settings_form();?>
				</div>
				
				<div class="tabcontent hidden" id="form_emails">
					<?php $this->form_emails_form();?>
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
	
	function add_element_modal(){
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
	
	function showform(){
		$formname	= $this->formdata->settings['formname'];
		$buttontext	= $this->formdata->settings['buttontext'];
		if(empty($buttontext)) $buttontext = 'Submit the form';
		
		if(!empty($formname)) echo "<h3>$formname</h3>";	

		if(array_intersect($this->user_roles,$this->submit_roles) != false and !empty($this->formdata->settings['save_in_meta'])){
			echo SIM\userSelect("Select an user to show the data of:");
		}
		echo do_action('before_form',$this->datatype);

		$form_class	= "sim_form";
		if(isset($_GET['formbuilder'])) $form_class	.= ' builder';

		$dataset	= "data-formid=".$this->formdata->id;
		if(!empty($this->formdata->settings['formreset']))		$dataset .= " data-reset='true'";
		if(!empty($this->formdata->settings['save_in_meta']))	$dataset .= " data-addempty='true'";

		?>
		<form action='' method='post' class='<?php echo $form_class;?>' <?php echo $dataset;?>>
			<div class='form_elements'>
				<input type='hidden' name='formid'		value='<?php echo $this->formdata->id;?>'>
				<input type='hidden' name='formurl'		value='<?php echo SIM\currentUrl();?>'>
				<input type='hidden' name='userid'		value='<?php echo $this->user_id;?>'>
		<?php		
			$this->labelwritten = false;
			foreach($this->form_elements as $key=>$element){
				echo $this->buildhtml($element,$key);
			}
			
		//close the last formstep if needed
		if($this->isformstep == true){
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
					for ($x = 1; $x <= $this->formstepcounter; $x++) {
						echo '<span class="step"></span>';
					}
					?>
					</div>
				
					<div style="flex:1;">
						<button type="button" class="button nextBtn" name="nextBtn">Next</button>
						<?php echo SIM\add_save_button('submit_form',$buttontext,'hidden form_submit');?>
					</div>
				</div>
			</div>
		<?php
		}//close $this->formstepcounter
			//only show submit button when not editing the form
			if($this->editrights == true and isset($_GET['formbuilder'])){
				$hidden = 'hidden';
			}else{
				$hidden = '';
			}
			if($this->isformstep == false and !empty($this->form_elements)){
				echo SIM\add_save_button('submit_form',$buttontext,"$hidden form_submit");
			}
			?>
			</div>
		</form>
		<?php
	}
	
	function form_settings_form(){
		global $wp_roles;
		
		$settings = $this->formdata->settings;
		
		//Get all available roles
		$user_roles = $wp_roles->role_names;
		
		//Sort the roles
		asort($user_roles);
		
		?>
		<div class="element_settings_wrapper">
			<form action='' method='post' class='sim_form builder'>
				<div class='form_elements'>
					<input type='hidden' class='formbuilder' name='formid'	value='<?php echo $this->formdata->id;?>'>
					
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
					$hide_upload_el	= true;
					foreach($this->form_elements as $el){
						if($el->type == 'file' or $el->type == 'image'){
							$hide_upload_el	= false;
							break;
						}
					}
					?>
					<label class='block <?php if($hide_upload_el) echo 'hidden';?>'>
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
					
					$found_elements = [];
					foreach($this->form_elements as $key=>$element){
						$pattern = "/([^\[]+)\[[0-9]+\]/i";
						
						if(preg_match($pattern, $element->name, $matches)){
							//Only add if not found before
							if(!in_array($matches[1],$found_elements)){
								$found_elements[]	= $matches[1];
								$value 				= strtolower(str_replace('_',' ',$matches[1]));
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
							foreach($this->form_elements as $key=>$element){
								if(in_array($element->type,$this->non_inputs)) continue;
								
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
					foreach($user_roles as $key=>$role_name){
						if(!empty($settings['full_right_roles'][$key])){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						echo "<label class='option-label'>";
							echo "<input type='checkbox' class='formbuilder formfieldsetting' name='settings[full_right_roles][$key]' value='$role_name' $checked>";
							echo $role_name;
						echo"</label><br>";
					}
					?>
					</div>
					<br>

					<div class='submit_others_form_wrapper<?php if(empty($settings['save_in_meta'])) echo 'hidden';?>'>
						<h4>Select who can submit this form on behalf of someone else</h4>
						<?php
						foreach($user_roles as $key=>$role_name){
							if(!empty($settings['submit_others_form'][$key])){
								$checked = 'checked';
							}else{
								$checked = '';
							}
							echo "<label class='option-label'>";
								echo "<input type='checkbox' class='formbuilder formfieldsetting' name='settings[submit_others_form][$key]' value='$role_name' $checked>";
								echo $role_name;
							echo"</label><br>";
						}
						?>
					</div>
				</div>
				<?php
				echo SIM\add_save_button('submit_form_setting','Save form settings');
				?>
			</form>
		</div>
		<?php
	}
	
	function form_emails_form(){
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
		$emails = $this->formdata->emails;
		?>
		<div class="emails_wrapper">
			<form action='' method='post' class='sim_form builder'>
				<div class='form_elements'>
					<input type='hidden' class='formbuilder' name='formid'	value='<?php echo $this->formdata->id;?>'>
					
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
					foreach($this->form_elements as $element){
						if(!in_array($element->type,['label','info','button','datalist','formstep'])){
							echo "<option>%{$element->name}%</option>";
						}
					}
					?>
					</select>
					
					<br>
					<div class='clone_divs_wrapper'>
						<?php
						foreach($emails as $key=>$email){
							$default_from	=  get_option( 'admin_email' );
							
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
											echo $this->input_dropdown($email['conditionalfield']);
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
											<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][from]' value="<?php if(empty($email['from'])){echo $default_from;} else{echo $email['from'];} ?>">
										</label>
									</div>
									
									<div class='emailfromconditional <?php if($email['fromemail'] != 'conditional') echo 'hidden';?>'>
										<div class='clone_divs_wrapper'>
											<?php
											if(!is_array($email['conditionalfromemail'])) $email['conditionalfromemail'] = [['fieldid'=>'','value'=>'','email'=>'']];
											foreach(array_values($email['conditionalfromemail']) as $from_key=>$from_email){
											?>
											<div class='clone_div' data-divid='<?php echo $from_key;?>'>
												<fieldset class='formemailfieldset'>
													<legend class="formfield">Condition <?php echo $from_key+1;?>
													<button type='button' class='add button' style='flex: 1;'>+</button>
													<button type='button' class='remove button' style='flex: 1;'>-</button>
													</legend>
													If 
													<select name='emails[<?php echo $key;?>][conditionalfromemail][<?php echo $from_key;?>][fieldid]'>
														<?php
														echo $this->input_dropdown($from_email['fieldid']);
														?>
													</select>
													<label class="formfield formfieldlabel">
														equals 
														<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalfromemail][<?php echo $from_key;?>][value]' value="<?php echo $from_email['value'];?>">
													</label>
													<label class="formfield formfieldlabel">
														then from e-mail address should be:<br>
														<input type='email' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalfromemail][<?php echo $from_key;?>][email]' value="<?php echo $from_email['email'];?>">
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
											foreach($email['conditionalemailto'] as $to_key=>$to_email){
											?>
											<div class='clone_div' data-divid='<?php echo $to_key;?>'>
												<fieldset class='formemailfieldset'>
													<legend class="formfield">Condition <?php echo $to_key+1;?>
													<button type='button' class='add button' style='flex: 1;'>+</button>
													<button type='button' class='remove button' style='flex: 1;'>-</button>
													</legend>
													If 
													<select name='emails[<?php echo $key;?>][conditionalemailto][<?php echo $to_key;?>][fieldid]'>
														<?php
														echo $this->input_dropdown($to_email['fieldid']);
														?>
													</select>
													<label class="formfield formfieldlabel">
														equals 
														<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalemailto][<?php echo $to_key;?>][value]' value="<?php echo $to_email['value'];?>">
													</label>
													<label class="formfield formfieldlabel">
														then from e-mail address should be:<br>
														<input type='email' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][conditionalemailto][<?php echo $to_key;?>][email]' value="<?php echo $to_email['email'];?>">
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
											"{$this->datatype}_email_message_$key",
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
				echo SIM\add_save_button('submit_form_emails','Save form email configuration');
				?>
			</form>
		</div>
		<?php
	}
	
	function elementBuilderForm($element=null){
		ob_start();

		if(is_numeric($element)){
			$element = $this->getElementById($element);
		}
		?>
		<form action="" method="post" name="add_form_element_form" class="form_element_form sim_form" data-addempty=true>
			<div style="display: none;" class="error"></div>
			<h4>Please fill in the form to add a new form element</h4><br>

			<input type="hidden" name="formid" value="<?php echo $this->formdata->id;?>">

			<input type="hidden" name="formfield[form_id]" value="<?php echo $this->formdata->id;?>">
			
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
						if($element != null and $element->type == $key){
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
						if($element != null and $element->type == $key){
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
				
					if(empty($element->infotext)){
						$content	= '';
					}else{
						$content	= $element->infotext;
					}
					echo wp_editor(
						$content,
						"{$this->datatype}_infotext",	//editor should always have an unique id
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
					$this->build_defaults_array();
					foreach($this->default_array_values as $key=>$field){
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
					foreach($this->default_values as $key=>$field){
						if($element != null and $element->default_value == $key){
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
				if(!empty($this->formdata->settings['save_in_meta'])){
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
			echo SIM\add_save_button('submit_form_element',"$text form element"); ?>
		</form>
		<?php

		return ob_get_clean();
	}	

	function element_conditions_form($element_id = -1){
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

		$element	= $this->getElementById($element_id);

		if($element_id == -1 or empty($element->conditions)){
			$dummyfieldcondition['rules'][0]["conditional_field"]	= "";
			$dummyfieldcondition['rules'][0]["equation"]				= "";
			$dummyfieldcondition['rules'][0]["conditional_field_2"]	= "";
			$dummyfieldcondition['rules'][0]["equation_2"]			= "";
			$dummyfieldcondition['rules'][0]["conditional_value"]	= "";
			$dummyfieldcondition["action"]					= "";
			$dummyfieldcondition["target_field"]			= "";
			
			if($element_id == -1) $element_id			= 0;
			$element->conditions = [$dummyfieldcondition];
		}
		
		$conditions = maybe_unserialize($element->conditions);
		
		ob_start();
		$counter = 0;
		foreach($this->form_elements as $el){
			if(in_array($element_id, (array)unserialize($el->conditions)['copyto'])){
				$counter++;
				?>
				<div class="form_element_wrapper" data-id="<?php echo $el->id;?>" data-formid="<?php echo $this->formdata->id;?>">
					<button type="button" class="edit_form_element button" title="Jump to conditions element">View conditions of '<?php echo $el->name;?>'</button>
				</div>
				<?php
			}
		}
		if($counter>0){
			$jumbbuttonhtml =  ob_get_clean();
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
				<?php echo $jumbbuttonhtml;?>
			</div><br><br>
			<?php
		}
		?>
		
		<form action='' method='post' name='add_form_element_conditions_form'>
			<h3>Form element conditions</h3>
			<input type='hidden' class='element_condition' name='formid' value='<?php echo $this->formdata->id;?>'>
			
			<input type='hidden' class='element_condition' name='elementid' value='<?php echo $element_id;?>'>

			<?php
			$lastcondtion_key = array_key_last($conditions);
			foreach($conditions as $condition_index=>$condition){
				if(!is_numeric($condition_index)) continue;
			?>
			<div class='condition_row' data-condition_index='<?php echo $condition_index;?>'>
				<span style='font-weight: 600;'>If</span>
				<br>
				<?php
				$lastrule_key = array_key_last($condition['rules']);
				foreach($condition['rules'] as $rule_index=>$rule){
					?>
					<div class='rule_row' data-rule_index='<?php echo $rule_index;?>'>
						<input type='hidden' class='element_condition combinator' name='element_conditions[<?php echo $condition_index;?>][rules][<?php echo $rule_index;?>][combinator]' value='<?php echo $rule['combinator']; ?>'>
					
						<select class='element_condition condition_select conditional_field' name='element_conditions[<?php echo $condition_index;?>][rules][<?php echo $rule_index;?>][conditional_field]' required>
						<?php
							echo $this->input_dropdown($rule['conditional_field'], $element_id);
						?>
						</select>

						<select class='element_condition condition_select equation' name='element_conditions[<?php echo $condition_index;?>][rules][<?php echo $rule_index;?>][equation]' required>
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
							<select class='element_condition condition_select' name='element_conditions[<?php echo $condition_index;?>][rules][<?php echo $rule_index;?>][conditional_field_2]'>
							<?php
								echo $this->input_dropdown($rule['conditional_field_2'],$element_id);
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
							<select class='element_condition condition_select' name='element_conditions[<?php echo $condition_index;?>][rules][<?php echo $rule_index;?>][equation_2]'>
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
						<input  type='text'   class='<?php echo $hidden;?> element_condition condition_form' name='element_conditions[<?php echo $condition_index;?>][rules][<?php echo $rule_index;?>][conditional_value]' value="<?php echo $rule['conditional_value'];?>">
						
						<button type='button' class='element_condition and_rule condition_form button <?php if(!empty($rule['combinator']) and $rule['combinator'] == 'AND') echo 'active';?>'	title='Add a new "AND" rule to this condition'>AND</button>
						<button type='button' class='element_condition or_rule condition_form button  <?php if(!empty($rule['combinator']) and $rule['combinator'] == 'OR') echo 'active';?>'	title='Add a new "OR"  rule to this condition'>OR</button>
						<button type='button' class='remove_condition condition_form button' title='Remove rule or condition'>-</button>
						<?php
						if($condition_index == $lastcondtion_key and $rule_index == $lastrule_key){
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
							<input type='radio' name='element_conditions[<?php echo $condition_index;?>][action]' class='element_condition' value='show' <?php if($condition['action'] == 'show') echo 'checked';?> required>
							Show this element
						</label><br>
						
						<label>
							<input type='radio' name='element_conditions[<?php echo $condition_index;?>][action]' class='element_condition' value='hide' <?php if($condition['action'] == 'hide') echo 'checked';?> required>
							Hide this element
						</label><br>
						
						<label>
							<input type='radio' name='element_conditions[<?php echo $condition_index;?>][action]' class='element_condition' value='toggle' <?php if($condition['action'] == 'toggle') echo 'checked';?> required>
							Toggle this element
						</label><br>
						
						<label>
							<input type='radio' name='element_conditions[<?php echo $condition_index;?>][action]' class='element_condition' value='value' <?php if($condition['action'] == 'value') echo 'checked';?> required>
							Set property
						</label>
						<input type="text" name="element_conditions[<?php echo $condition_index;?>][propertyname1]" class='element_condition' placeholder="property name" value="<?php echo $condition['propertyname1'];?>">
						<label> to:</label>
						<input type="text" name="element_conditions[<?php echo $condition_index;?>][action_value]" class='element_condition' value="<?php echo $condition['action_value'];?>">
						<br>
						
						<input type='radio' name='element_conditions[<?php echo $condition_index;?>][action]' class='element_condition' value='property' <?php if($condition['action'] == 'property') echo 'checked';?> required>
						<label for='property'>Set the</label>
						
						<input type="text" list="propertylist" name="element_conditions[<?php echo $condition_index;?>][propertyname]" class='element_condition' placeholder="property name" value="<?php echo $condition['propertyname'];?>">
						<datalist id="propertylist">
							<option value="value">
							<option value="min">
							<option value="max">
						</datalist>
						<label for='property'> property to the value of </label>
						
						<select class='element_condition condition_select' name='element_conditions[<?php echo $condition_index;?>][property_value]'>
						<?php echo $this->input_dropdown($condition['property_value'], $element_id);?>
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
					foreach($this->form_elements as $key=>$element){
						//do not show the current element itself or wrapped labels
						if($element->id != $element_id and empty($element->wrap)){
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
			echo SIM\add_save_button('submit_form_condition','Save conditions'); ?>
		</form>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	function warningConditionsForm($element_id = -1){
		$element	= $this->getElementById($element_id);

		if($element_id == -1 or empty($element->warning_conditions)){
			$dummy[0]["user_meta_key"]	= "";
			$dummy[0]["equation"]		= "";
			
			if($element_id == -1) $element_id			= 0;
			$element->warning_conditions = $dummy;
		}
		
		$conditions 	= maybe_unserialize($element->warning_conditions);
		
		$usermeta_keys	= get_user_meta($this->user_id);
		ksort($usermeta_keys);

		ob_start();
		?>
			
		<label>Do not warn if usermeta with the key</label>
		<br>

		<div class="conditions_wrapper">
			<?php
			foreach($conditions as $condition_index=>$condition){
				if(!is_numeric($condition_index)) continue;
				?>
				<div class='warning_conditions element_conditions' data-index='<?php echo $condition_index;?>'>
					<input type="hidden" class='warning_condition combinator' name="formfield[warning_conditions][<?php echo $condition_index;?>][combinator]" value="<?php echo $condition['combinator'];?>">

					<input type="text" class="warning_condition meta_key" name="formfield[warning_conditions][<?php echo $condition_index;?>][meta_key]" value="<?php echo $condition['meta_key'];?>" list="meta_key" style="width: fit-content;">
					<datalist id="meta_key">
						<?php
						foreach($usermeta_keys as $key=>$value){
							// Check if array, store array keys
							$value 	= maybe_unserialize($value[0]);
							$data	= '';
							if(is_array($value)){
								$keys	= implode(',', array_keys($value));
								$data	= "data-keys=$keys";
							}
							echo "<option value='$key' $data>";
						}

						?>
					</datalist>

					<?php
						$array_keys	= maybe_unserialize($usermeta_keys[$condition['meta_key']][0]);
					?>
					<span class="index_wrapper <?php if(!is_array($array_keys)) echo 'hidden';?>">
						<span>and index</span>
						<input type="text" class="warning_condition meta_key_index" name='formfield[warning_conditions][<?php echo $condition_index;?>][meta_key_index]' value="<?php echo $condition['meta_key_index'];?>" list="meta_key_index[<?php echo $condition_index;?>]" style="width: fit-content;">
						<datalist class="meta_key_index_list warning_condition" id="meta_key_index[<?php echo $condition_index;?>]">
							<?php
							if(is_array($array_keys)){
								foreach(array_keys($array_keys) as $key){
									echo "<option value='$key'>";
								}
							}
							?>
						</datalist>
					</span>
					
					<select class="warning_condition" name='formfield[warning_conditions][<?php echo $condition_index;?>][equation]'>
						<option value=''		<?php if($condition['equation'] == '')			echo 'selected';?>>---</option>";
						<option value='=='		<?php if($condition['equation'] == '==')		echo 'selected';?>>equals</option>
						<option value='!='		<?php if($condition['equation'] == '!=')		echo 'selected';?>>is not</option>
						<option value='>'		<?php if($condition['equation'] == '>')			echo 'selected';?>>greather than</option>
						<option value='<'		<?php if($condition['equation'] == '<')			echo 'selected';?>>smaller than</option>
					</select>
					<input  type='text'   class='warning_condition' name='formfield[warning_conditions][<?php echo $condition_index;?>][conditional_value]' value="<?php echo $condition['conditional_value'];?>" style="width: fit-content;">
					
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

	function maybe_insert_form(){
		global $wpdb;
		
		//check if form row already exists
		if($wpdb->get_var("SELECT * FROM {$this->table_name} WHERE `name` = '{$this->datatype}'") != true){
			//Create a new form row
			SIM\printArray("Inserting new form in db");
			
			$this->insert_form();
		}
	}

	function process_files($uploaded_files, $inputname){
		//loop over all files uploaded in this fileinput
		foreach ($uploaded_files as $key => $url){
			$urlparts 	= explode('/',$url);
			$filename	= end($urlparts);
			$path		= SIM\urlToPath($url);
			$targetdir	= str_replace($filename,'',$path);
			
			//add input name to filename
			$filename	= "{$inputname}_$filename";
			
			//also add submission id if not saving to meta
			if(empty($this->formdata->settings['save_in_meta'])){
				$filename	= $this->formresults['id']."_$filename";
			}
			
			//Create the filename
			$i = 0;
			$target_file = $targetdir.$filename;
			//add a number if the file already exists
			while (file_exists($target_file)) {
				$i++;
				$target_file = "$targetdir.$filename($i)";
			}
	
			//if rename is succesfull
			if (rename($path, $target_file)) {
				//update in formdata
				$this->formresults[$inputname][$key]	= str_replace(ABSPATH,'',$target_file);
			}else {
				//update in formdata
				$this->formresults[$inputname][$key]	= str_replace(ABSPATH,'',$path);
			}
		}
	}
}