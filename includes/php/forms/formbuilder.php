<?php
namespace SIM;

class Formbuilder{
	function __construct(){
		global $wpdb;
		$this->isformstep				= false;
		$this->formstepcounter			= 0;
		$this->submissiontable_prefix	= $wpdb->prefix . 'simnigeria_form_submissions';
		$this->table_name				= $wpdb->prefix . 'simnigeria_forms';
		$this->create_db_table();
		$this->non_inputs				= ['label','button','datalist','formstep','info','p','php','multi_start','multi_end'];
		$this->multi_inputs_html		= [];

		$this->user 		= wp_get_current_user();
		$this->user_roles	= $this->user->roles;
		$this->user_id		= $this->user->ID;

		//calculate full form rights
		if(array_intersect(['contentmanager'],$this->user_roles) != false){
			$this->editrights		= true;
		}else{
			$this->editrights		= false;
		}
	}

	function register_buttons($buttons) {
		array_push($buttons, 'insert_form_shortcode');
		return $buttons;
	}
	
	function add_tinymce_plugin($plugins) {
		//Add extra variables to the main.js script
		wp_localize_script( 'simnigeria_script', 
			'tinymce_data', 
			array( 
				'user_select' 		=> user_select("Select a person to show the link to",true),
				'post_type'			=> $this->post_type,
				'form_select'		=> $this->form_select()
			) 
		);
		
		global $StyleVersion;
		$plugins['insert_form_shortcode']		= plugins_url("../js/tiny_mce_action.js?ver=$StyleVersion", __DIR__);
		
		return $plugins;
	}
	
	function form_select(){
		$this->get_forms();
		
		$this->form_names				= [];
		foreach($this->forms as $form){
			$this->form_names[]			= $form->form_name;
		}
		
		$html = "<select name='form_selector'>";
		$html .= "<option value=''>---</option>";
		foreach ($this->form_names as $form_name){
			$html .= "<option value='$form_name'>$form_name</option>";
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
		
		//only create db if it does not exist
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  form_name tinytext NOT NULL,
		  form_elements longtext NOT NULL,
		  form_conditions longtext NOT NULL,
		  form_version text NOT NULL,
		  settings text NOT NULL,
		  emails text NOT NULL,
		  element_counter int NOT NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;";

		maybe_create_table($this->table_name, $sql );
		
		add_option( "forms_db_version", "1.0" );
	}
	
	function create_db_submissions_table(){
		if ( !function_exists( 'maybe_create_table' ) ) { 
			require_once ABSPATH . '/wp-admin/install-helper.php'; 
		} 

		$this->submissiontable_name = $this->submissiontable_prefix."_".$this->datatype;
		//create table for this form
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->submissiontable_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			timecreated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			timelastedited datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			userid mediumint(9) NOT NULL,
			formresults longtext NOT NULL,
			archived BIT,
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
			$this->submissiontable_name = $this->submissiontable_prefix."_".$this->datatype;
			$this->shortcode_id	= $atts['id'];
			
			if(is_numeric($atts['userid'])){
				$this->user_id	= $atts['userid'];
			}
			
			$this->submissiontable_name = $this->submissiontable_prefix."_".$this->datatype;
		}
	}
	
	function getelementbyid($id,$key=''){
		//load if needed
		if(empty($this->formdata->element_mapping)) $this->loadformdata();
		
		$elementindex	= $this->formdata->element_mapping[$id];
					
		$element		= $this->formdata->form_elements[$elementindex];
		
		if(empty($key)){
			return $element;
		}else{
			return $element[$key];
		}
	}
		
	function loadformdata(){
		global $wpdb;
		
		$query				= "SELECT * FROM {$this->table_name} WHERE form_name= '".$this->datatype."'";
		
		$this->formdata 	=  (object)$wpdb->get_results($query)[0];
		
		$this->formdata->form_elements		= maybe_unserialize($this->formdata->form_elements);
		//used to find the index of an element based on its unique id
		$this->formdata->element_mapping	= [];
		foreach($this->formdata->form_elements as $index=>$element){			
			$this->formdata->element_mapping[$element['id']]	= $index;
		}
		
		$this->formdata->form_conditions	= maybe_unserialize($this->formdata->form_conditions);
		
		if(empty($this->formdata->settings)){
			$settings['succesmessage']			= "";
			$settings['formname']				= "";
			$settings['roles']					= [];

			$this->formdata->settings = $settings;
		}else{
			$this->formdata->settings = maybe_unserialize(utf8_encode($this->formdata->settings));
		}

		if(!$this->editrights){
			$edit_roles	= ['contentmanager'];
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
		
		if($wpdb->last_error !== '')		print_array($wpdb->print_error());
		
		$this->jsfilename	= plugin_dir_path(__DIR__)."../js/dynamic/{$this->datatype}forms.js";
	}
	
	function get_submission_data($user_id=null, $submission_id=null){
		global $wpdb;
		
		$query				= "SELECT * FROM {$this->submissiontable_name} WHERE";
		if(is_numeric($submission_id)){
			$query .= " id='$submission_id'";
		}elseif($user_id == null){
			$query .= " 1";
		}else{
			$query .= " userid='$user_id'";
		}
		
		if(!$this->show_archived and $submission_id == null){
			$query .= " and archived=0";
		}

		$query	= apply_filters('formdata_retrieval_query', $query, $user_id, $this->datatype);

		$result	= $wpdb->get_results($query);
		$result	= apply_filters('retrieved_formdata', $result, $user_id, $this->datatype);

		$this->submission_data		= $result;
		
		if(is_numeric($submission_id))	$this->submission_data = $this->submission_data[0];
		
		if($wpdb->last_error !== '')		print_array($wpdb->print_error());
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
	
	function get_element_html($element, $replace=true, $width=100){
		$html = '';
		
		if($element['type'] == 'p'){
			$html = wp_kses_post($element['infotext']);
			$html = "<div name='{$element['name']}'>".$this->deslash($html)."</div>";
		}elseif($element['type'] == 'php'){
			//we store the functionname in the html variable replace any double \ with a single \
			$functionname 						= str_replace('\\\\','\\',$element['functionname']);
			//only continue if the function exists
			if (function_exists( $functionname ) ){
				$html	= $functionname($this->user_id);
			}else{
				$html	= "php function '$functionname' not found";
			}
		}elseif(in_array($element['type'], ['multi_start','multi_end'])){
			$html 		= "";
		}elseif(!empty($element['infotext'])){
			//remove any paragraphs
			$content = str_replace('<p>','',$element['infotext']);
			$content = str_replace('</p>','',$content);
			$content = $this->deslash($content);
			
			$html = "<div class='infobox' name='{$element['name']}'>";
				$html .= '<div style="float:right">';
					$html .= '<p class="info_icon"><img draggable="false" role="img" class="emoji" alt="ℹ" src="'. plugins_url().'/custom-simnigeria-plugin/includes/pictures/info.png"></p>';
				$html .= '</div>';
				$html .= "<span class='info_text'>$content</span>";
			$html .= '</div>';
		}elseif(in_array($element['type'], ['file','image'])){
			$name		= $element['name'];
			$targetdir	= $this->formdata->settings['upload_path'];
			if(empty($targetdir)) $targetdir = 'form_uploads/'.$this->formdata->settings['formname'];
			
			if(!empty($this->formdata->settings['save_in_meta'])){
				$metakey	= $name;
				$user_id	= $this->user_id;
			}else{
				$metakey	= '';
				$user_id	= '';
			}
			//Load js
			wp_enqueue_script('simnigeria_fileupload_script');
			
			$uploader = new Fileupload($user_id, $name,$targetdir,$element['multiple'],$metakey);
			
			$html = $uploader->get_upload_html();
		}else{
			/*
				ELEMENT TAG NAME
			*/
			if(in_array($element['type'],['formstep','info'])){
				$el_type	= "div";
			}elseif(in_array($element['type'],array_merge($this->non_inputs,['select','textarea']))){
				$el_type	= $element['type'];
			}else{
				$el_type	= "input type='{$element['type']}'";
			}
			
			/* 				
				ELEMENT NAME
			 */
			//Get the field name, make it lowercase and replace any spaces with an underscore
			$el_name	= $element['name'];
			// [] not yet added to name
			if(in_array($element['type'],['radio','checkbox']) and strpos($el_name, '[]') === false) {
				$el_name .= '[]';
			}
			
			/*
				ELEMENT ID
			*/
			//datalist needs an id to work
			if($element['type'] == 'datalist'){
				$el_id	= "id='$el_name'";
			}
			
			/*
				ELEMENT CLASS
			*/
			$el_class = " class='formfield";
			switch($element['type']){
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
				ELEMENT OPTIONS
			*/
			if(!empty($element['options'])){
				//Store options in an array
				$options	= explode("\n", $element['options']);
				$el_options	= '';
				
				//Loop over the options array
				foreach($options as $option_key=>$option){
					//Remove starting or ending spaces and make it lowercase
					$option = trim($option);
					$escaped_option = str_replace(' ','_',$option);
					$escaped_option = str_replace(',','',$escaped_option);
					$escaped_option = str_replace('(','',$escaped_option);
					$escaped_option = str_replace(')','',$escaped_option);

					if(explode('=',$option)[0] == 'class'){
						$el_class .= " ".explode('=',$option)[1];
					}else{
						//Write the corrected option as html
						$el_options	.= " $escaped_option";
					}
				}
			}
			//CLOSE THE CLASS STRING
			$el_class			.= "'";
			
			/*
				BUTTON TYPE
			*/
			if($element['type'] == 'button'){
				$el_options	.= " type='button'";
			}
			/*
				ELEMENT VALUE
			*/
			$values	= $this->get_field_values($element);
			if($this->multiwrap or !empty($element['multiple'])){
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
			if($element['type'] == 'textarea'){
				$el_content = $values['metavalue'][0];
			}elseif(!empty($element['labeltext'])){
				switch($element['type']){
					case 'formstep':
						$el_content = "<h3>{$element['labeltext']}</h3>";
						break;
					case 'label':
						$el_content = "<h4 class='labeltext'>{$element['labeltext']}</h4>";;
						break;
					case 'button':
						$el_content = $element['labeltext'];
						break;
					default:
						$el_content = "<label class='labeltext'>{$element['labeltext']}</label>";
				}
			}
			
			switch($element['type']){
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
					
					$default_key				= $element['default_value'];
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
							$html .= "<$el_type name='$el_name' $el_class $el_options value='$key' $checked>";
							$html .= "<span class='optionlabel'>$option</span>";
						$html .= "</label><br>";
					}

					$html .= "</div>";
					break;
				default:
					$el_content .= "";
			}
			
			//close the field
			if(in_array($element['type'],['select','textarea','button','datalist'])){
				$el_close	= "</{$element['type']}>";
			}else{
				$el_close	= "";
			}
			
			//only close a label field if it is not wrapped
			if($element['type'] == 'label' and empty($element['wrap'])){
				$el_close	= "</label>";
			}
			
			/*
				ELEMENT MULTIPLE
			*/
			if(!empty($element['multiple'])){
				if($element['type'] == 'select'){
					$html	= "<$el_type  name='$el_name' $el_id $el_class $el_options>";
				}else{
					$html	= "<$el_type  name='$el_name' $el_id $el_class $el_options value='%value%'>$el_content$el_close";
				}
					
				$this->process_multi_fields($element, 'multifield', $width, $values, $html);
				$html	= "<div class='clone_divs_wrapper'>";
				foreach($this->multi_inputs_html as $h){
					$html	.= $h;
				}
				$html	.= '</div>';
			}elseif(empty($html)){
				$html	= "<$el_type  name='$el_name' $el_id $el_class $el_options $el_value>$el_content$el_close";
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

		if(in_array($element['type'],$this->non_inputs)) return [];

		$this->build_defaults_array();

		//get the fieldname, remove [] and split on remaining [
		$fieldname	= str_replace(']','',explode('[',str_replace('[]','',$element['name'])));
		
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
		if(empty($element['multiple']) or in_array($element['type'], ['select','checkbox'])){
			$default_key				= $element['default_value'];
			if(!empty($default_key)){
				$values['defaults']		= $this->default_values[$default_key];
			}
		}else{
			$default_key				= $element['default_array_value'];
			if(!empty($default_key)){
				$values['defaults']		= $this->default_array_values[$default_key];
			}
		}
		
		return $values;
	}
	
	function get_field_options($element){
		$options	= [];
		//add element values
		$el_values	= explode("\n",$element['valuelist']);
		
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
		
		$default_array_key				= $element['default_array_value'];
		if(!empty($default_array_key)){
			$options	= (array)$this->default_array_values[$default_array_key]+$options;
		}
		
		return $options;
	}
	
	function process_multi_fields($element, $key, $width, $values=null, $element_html=''){
		if($this->multiwrap or !is_numeric($key)){
			if(is_numeric($key)){
				//Check if element needs to be hidden
				if(!empty($element['hidden'])){
					$hidden = ' hidden';
				}else{
					$hidden = '';
				}
				
				//if the current element is required or this is a label and the next element is required
				if(
					!empty($element['required'])	or
					$element['type'] == 'label'		and
					$this->next_element['required']
				){
					$hidden .= ' required';
				}
		
				$element_html = $this->get_element_html($element);
				
				//close the label element after the field element
				if($this->wrap and !isset($element['wrap'])){
					$element_html .= "</div>";
					if($this->wrap == 'label'){
						$element_html .= "</label>";
					}
					$this->wrap = false;
				}elseif(!$this->wrap){
					if($element['type'] == 'info'){
						$element_html .= "<div class='inputwrapper$hidden info'>";
					}else{
						if(!empty($element['wrap'])){
							$class	= 'flex';
						}else{
							$class	= '';
						}
						$element_html .= "<div class='inputwrapper$hidden $class' style='width:$width%;'>";
					}
				}
				
				if(in_array($element['type'],$this->non_inputs)){	
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
					isset($element['wrap'])								and 							//we should wrap around next element
					is_array($this->next_element)						and 							// and there is a next element
					!in_array($this->next_element['type'], ['select','php','formstep'])	// and the next element type is not a select or php element
				){
					$this->wrap = $element['type'];
				}
			//key is not numeric so we are dealing with a multi input field
			}else{
				//add label to each entry if prev element is a label and wrapped with this one
				if($this->prev_element['type']	== 'label' and !empty($this->prev_element['wrap'])){
					$this->prev_element['labeltext'] = $this->prev_element['labeltext'].' %key%';
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
				if($element['type'] == 'select'){
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
				}elseif(in_array($element['type'], ['radio','checkbox'])){
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
				if($this->prev_element['type'] == 'multi_start'){
					$this->multi_inputs_html[$index] .= "<div class='clone_div' data-divid='$index' style='display:flex'>";
					$this->multi_inputs_html[$index] .= "<div class='multi_input_wrapper'>";
				}
				
				//add flex to single multi items, re-add the label for each value
				if(!is_numeric($key)){
					$this->multi_inputs_html[$index] .= str_replace('%key%',$index+1,$prev_label);
					//wrap input AND buttons in a flex div
					$this->multi_inputs_html[$index] .= "<div class='buttonwrapper' style='width:100%; display: flex;'>";
				}
				
				if($element['type'] == 'label'){
					$nr		= $index+1;
					$html	= str_replace('</h4>'," $nr</h4>",$html);
				}
				//write the element
				$this->multi_inputs_html[$index] .= $html;
				
				//end, write the buttons and closing div
				if(!is_numeric($key) or $this->next_element['type'] == 'multi_end'){
					//close any label first before adding the buttons
					if($this->wrap == 'label'){
						$this->multi_inputs_html[$index] .= "</label>";
					}
					
					//close select
					if($element['type'] == 'select'){
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
		global $LoaderImageURL;
		$this->prev_element		= $this->formdata->form_elements[$key-1];
		$this->next_element		= $this->formdata->form_elements[$key+1];

		if(
			!empty($this->next_element['multiple']) and // if next element is a multiple
			$this->next_element['type'] != 'file' 	and // next element is not a file
			$element['type'] == 'label'				and // and this is a label
			!empty($element['wrap'])				and // and the label is wrapped around the next element
			!isset($_GET['formbuilder']) 			and	// and we are not building the form
			!wp_doing_ajax()							// and we are not doing a AJAX call
		){
			return;
		}
		//store the prev rendered element before updating the current element
		$this->prev_rendered_element	= $this->current_element;
		$this->current_element			= $element;
				
		//Set the element width to 85 percent so that the info icon floats next to it
		if($key != 0 and $this->prev_rendered_element['type'] == 'info'){
			$width = 85;
		//We are dealing with a label which is wrapped around the next element
		}elseif($element['type'] == 'label'	and !isset($element['wrap']) and is_numeric($this->next_element['width'])){
			$width = $this->next_element['width'];
		}elseif(is_numeric($element['width'])){
			$width = $element['width'];
		}else{
			$width = 100;
		}

		//Load default values for this element
		$element_html = $this->get_element_html($element,true,$width);
		
		//Check if element needs to be hidden
		if(!empty($element['hidden'])){
			$hidden = ' hidden';
		}else{
			$hidden = '';
		}
		
		//if the current element is required or this is a label and the next element is required
		if(
			!empty($element['required'])	or
			$element['type'] == 'label'		and
			$this->next_element['required']
		){
			$hidden .= ' required';
		}
		
		//Add form edit controls if needed
		if($this->editrights == true and (isset($_GET['formbuilder']) or wp_doing_ajax())){
			$html = "<div class='form_element_wrapper' data-id='$key' data-formname='{$this->datatype}' style='display: flex;'>";
				$html .= "<span class='movecontrol formfieldbutton' aria-hidden='true'>:::</span>";
				$html .= "<div class='resizer_wrapper'>";
					if($element['type'] == 'info'){
						$html .= "<div class='show inputwrapper$hidden'>";
					}else{
						$html .= "<div class='resizer show inputwrapper$hidden' data-widthpercentage='$width' style='width:$width%;'>";
					}
					
					if($element['type'] == 'formstep'){
						$html .= ' ***formstep element***</div>';
					}elseif($element['type'] == 'datalist'){
						$html .= ' ***datalist element***';
					}elseif($element['type'] == 'multi_start'){
						$html .= ' ***multi answer start***';
						$element_html	= '';
					}elseif($element['type'] == 'multi_end'){
						$html .= ' ***multi answer end***';
						$element_html	= '';
					}
					
					
					$html .= $element_html;
						//Add a star if this field has conditions
						if(is_array($this->formdata->form_conditions[$element['id']])){
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
			if($element['type'] == 'formstep'){
				//if there is already a formstep wrtten close that one first
				if($this->isformstep == true){
					$html .= "</div>";
				//first part of the form, don't hide
				}else{
					$this->isformstep = true;
					$html .= "<img class='formsteploader' src='$LoaderImageURL'>";
				}
				
				$this->formstepcounter	+= 1;
				$html .= $element_html;
			}elseif($element['type'] == 'multi_end'){
				$this->multiwrap	= false;
				//write down all the multi html
				$name	= str_replace('end','start',$element['name']);
				$html  .= "<div class='clone_divs_wrapper' name='$name'>";
				foreach($this->multi_inputs_html as $multihtml){
					$html .= $multihtml;
				}
				$html .= '</div>';//close clone_divs_wrapper
				$html .= '</div>';//close inputwrapper
				//print_array($html);
			//just calculate the multi html
			}elseif($this->multiwrap){
				$this->process_multi_fields($element, $key, $width);
			//write other elements
			}else{
				//wrap an element and a folowing field in the same wrapper
				// so only write a div if the wrap property is not set
				if(!$this->wrap){
					if($element['type'] == 'info'){
						$html = "<div class='inputwrapper$hidden info'>";
					}else{
						if(!empty($element['wrap'])){
							$class	= 'flex';
						}else{
							$class	= '';
						}
						$html = "<div class='inputwrapper$hidden $class' style='width:$width%;'>";
					}
				}
				
				$html .= $element_html;
				if($element['type'] == 'multi_start'){
					$this->multiwrap				= true;
					
					//we are wrapping so we need to find the max amount of filled in fields
					$i								= $key+1;
					$this->multi_wrap_value_count	= 1;
					//loop over all consequent wrapped elements
					while(true){
						$type	= $this->formdata->form_elements[$i]['type'];
						if($type != 'multi_end'){
							if(!in_array($type,$this->non_inputs)){
								//Get the field values and count
								$value_count		= count($this->get_field_values($this->formdata->form_elements[$i]));
								
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
						!$this->wrap										and
						!empty($element['wrap'])							and				//we should wrap around next element
						is_array($this->next_element)						and 			// and there is a next element
						!in_array($this->next_element['type'], ['php','formstep'])	// and the next element type is not a select or php element
					){
						//end a label if the next element is a select
						if($element['type'] == 'label' and in_array($this->next_element['type'], ['php','select'])){
							$html .= "</label></div>";
						}else{
							$this->wrap = $element['type'];
						}
					//we have not started a wrap
					}else{		
						//only close wrap if the current element is not wrapped or it is the last
						if(empty($element['wrap']) or !is_array($this->next_element)){
							//close the label element after the field element or if this the last element of the form and a label
							if(
								$this->wrap == 'label' or 
								($element['type'] == 'label' and !is_array($this->next_element))
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
			}elseif(is_numeric($_POST['submission_id'])){
				$submission_id	= $_POST['submission_id'];
			}elseif(wp_doing_ajax()){
				wp_die("Please submit a valid submission id",500);
			}else{
				print_array('No submission id found');
				return;
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
			wp_die($wpdb->print_error(),500);
		}elseif($result == false){
			wp_die("No row with id $submission_id found",500);
		}
		
		return;
	}
	
	function find_conditional_email($conditions){
		//loop over all conditions
		foreach($conditions as $condition){
			//loop over all elements to find the element with the id specified
			foreach($this->formdata->form_elements as $element){
				//we found the element with the correct id
				if($element['id'] == $condition['fieldid']){
					//get the submitted form value
					$form_value = $this->formresults[$element['name']];
					
					//if the value matches the conditional value
					if(strtolower($form_value) == strtolower($condition['value'])){
						return $condition['email'];
					}
				}
			}
		}
	}

	function send_email($trigger='submitted'){
		$emails = $this->formdata->emails;
		
		foreach($emails as $key=>$email){
			if($email['emailtrigger'] == $trigger){
				if($trigger == 'fieldchanged'){
					//loop over all elements to find the element with the id specified
					foreach($this->formdata->form_elements as $element){
						//we found the element with the correct id
						if($element['id'] == $email['conditionalfield']){
							//get the submitted form value
							$form_value = $this->formresults[$element['name']];
							
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
					print_array("No from email found for email $key");
				}
								
				$to		= '';
				if($email['emailto'] == 'conditional'){
					$to = $this->find_conditional_email($email['conditionalemailto']);
				}elseif($email['emailto'] == 'fixed'){
					$to		= $this->process_placeholders($email['to']);
				}
				
				if($to == ''){
					print_array("No to email found for email $key");
					continue;
				}

				$subject	= $this->process_placeholders($email['subject']);
				$message	= $this->process_placeholders($email['message']);
				$headers	= explode("\n",$email['headers']);
				$headers[]	= 'Content-Type: text/html; charset=UTF-8';
				if($from != ''){$headers[]	= "Reply-To: $from";}
				
				$files		= $this->process_placeholders($email['files']);
				
				//Send the mail
				if($_SERVER['HTTP_HOST'] != 'localhost'){
					$result = wp_mail($to , $subject, $message, $headers, $files);
					if($result === false){
						print_array("Sending the e-mail failed");
					}
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
				$this->datatype		= $form->form_name;
				$field_main_name	= $settings['split'];
				
				//Get all submissions of this form
				$this->get_submission_data();
				
				$trigger_name	= $this->getelementbyid($settings['autoarchivefield'],'name');
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
					print_array($matches[1]);
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
	
	function input_dropdown($field_id, $element_index=-1){
		if($field_id == ''){
			$html = "<option value='' selected>---</option>";
		}else{
			$html = "<option value=''>---</option>";
		}
		
		foreach($this->formdata->form_elements as $key=>$element){
			//do not include the element itself do not include non-input types
			if($key != $element_index and !in_array($element['type'],['label','info','datalist','formstep'])){
				$name = ucfirst(str_replace('_',' ',$element['name']));
				
				//Check which option is the selected one
				if($field_id != '' and $field_id == $element['id']){
					$selected = 'selected';
				}else{
					$selected = '';
				}
				$html .= "<option value='{$element['id']}' $selected>$name</option>";
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
			}else{
				//replace the placeholder with the value
				$string = str_replace("%$match%",str_replace('_',' ',$this->formresults[$match]),$string);
			}
		}
		
		return $string;
	}

	function formbuilder($atts){
		global $wpdb;
		global $StyleVersion;

		$this->process_atts($atts);
				
		$this->loadformdata();

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
			wp_enqueue_script('simnigeria_formbuilderjs');
		}
		
		//Load conditional js if available and needed
		if(!isset($_GET['formbuilder'])){
			wp_register_script( "dynamic_{$this->datatype}forms", plugins_url("../js/dynamic/{$this->datatype}forms.js", __DIR__),array('simnigeria_forms_script'),$this->formdata->form_version,true);
			if(file_exists($this->jsfilename)){
				wp_enqueue_script( "dynamic_{$this->datatype}forms");
			}else{
				print_array($this->jsfilename." does not exist!\nBuilding it now");
				
				if (!is_dir(plugin_dir_path(__DIR__)."../js/dynamic")) {
					mkdir(plugin_dir_path(__DIR__)."../js/dynamic", 0777, true);
				}
				
				//build initial js if it does not exist
				$this->create_js();
				
				//only load on real form, not on form builder form
				wp_enqueue_script( "dynamic_{$this->datatype}forms");
			}
		}
		
		?>
		<div class="simnigeria_form wrapper">
			<?php
			
			if($this->editrights == true){
				//redirect  to formbuilder if we have rights and no formfields defined yet
				if(empty($this->formdata->form_elements) and !isset($_GET['formbuilder'])){
					$url = current_url();
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
					<button class="button tablink formbuilderform active"	id="show_element_form" data-target="element_form">Form elements</button>
					<button class="button tablink formbuilderform"			id="show_form_settings" data-target="form_settings">Form settings</button>
					<button class="button tablink formbuilderform"			id="show_form_emails" data-target="form_emails">Form emails</button>
					<?php 
				}else{
					//button
					echo "<a href='?formbuilder=yes' class='button sim'>Show formbuilder</a>";
				}
				?>
				
				<div class="tabcontent" id="element_form">
					<input type="hidden" class="input-text formbuilder" name="request_form_element_nonce" value="<?php echo wp_create_nonce("request_form_element_nonce"); ?>">
					<input type="hidden" class="input-text formbuilder" name="remove_form_element_nonce" value="<?php echo wp_create_nonce("remove_form_element_nonce"); ?>">
					<?php
					if(isset($_GET['formbuilder'])){
						if(empty($this->formdata->form_elements)){
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
				
				<div class="tabcontent hidden" id="form_settings">
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
		//print_array($html);
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
					<?php echo $this->element_builder_form();?>
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
			echo user_select("Select an user to show the data of:");
		}
		echo do_action('before_form',$this->datatype);

		$form_id	= "simnigeria_form_".$this->datatype;
		$form_class	= "simnigeria_form";
		if(isset($_GET['formbuilder'])) $form_class	.= ' builder';

		$dataset	= '';
		if(!empty($this->formdata->settings['formreset']))		$dataset .= " data-reset='true'";
		if(!empty($this->formdata->settings['save_in_meta']))	$dataset .= " data-addempty='true'";

		?>
		<form action='' method='post' id='<?php echo $form_id;?>' class='<?php echo $form_class;?>' <?php echo $dataset;?>>
			<div class='form_elements'>
				<input type='hidden' name='formname'	value='<?php echo $this->datatype;?>'>
				<input type='hidden' name='action'		value='save_form_input'>
				<input type='hidden' name='formurl'		value='<?php echo current_url(true);?>'>
				<input type='hidden' name='userid'		value='<?php echo $this->user_id;?>'>
		<?php		
			$this->labelwritten = false;
			foreach($this->formdata->form_elements as $key=>$element){
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
						<?php echo add_save_button('submit_form',$buttontext,'hidden form_submit');?>
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
			if($this->isformstep == false and !empty($this->formdata->form_elements)){
				echo add_save_button('submit_form',$buttontext,"$hidden form_submit");
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
			<form action='' method='post' id='simnigeria_form_<?php echo $this->datatype;?>_settings' class='simnigeria_form builder'>
				<div class='form_elements'>
					<input type='hidden' class='formbuilder' name='formname'	value='<?php echo $this->datatype;?>'>
					<input type='hidden' class='formbuilder' name='action'		value='save_form_settings'>
					
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
					
					<?php
					//check if we have any upload fields in this form
					$hide_upload_el	= true;
					foreach($this->formdata->form_elements as $el){
						if($el['type'] == 'file' or $el['type'] == 'image'){
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
					foreach($this->formdata->form_elements as $key=>$element){
						$pattern = "/([^\[]+)\[[0-9]+\]/i";
						
						if(preg_match($pattern, $element['name'], $matches)){
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
							foreach($this->formdata->form_elements as $key=>$element){
								if(in_array($element['type'],$this->non_inputs)) continue;
								
								$pattern			= "/\[[0-9]+\]\[([^\]]+)\]/i";
								
								$name = $element['name'];
								if(preg_match($pattern, $element['name'],$matches)){
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
								if($settings['autoarchivefield'] != '' and $settings['autoarchivefield'] == $key){
									$selected = 'selected';
								}else{
									$selected = '';
								}
								echo "<option value='{$element['id']}' $selected>$name</option>";
							}
							
							?>
							</select>
							<label style="margin:0 10px;">equals</label>
							<input type='text' name="settings[autoarchivevalue]" value="<?php echo $settings['autoarchivevalue'];?>">
							<div class="infobox" name="info" style="min-width: fit-content;">
								<div style="float:right">
									<p class="info_icon">
										<img draggable="false" role="img" class="emoji" alt="ℹ" src="<?php echo plugins_url();?>/custom-simnigeria-plugin/includes/pictures/info.png">
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
				echo add_save_button('submit_form_setting','Save form settings');
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
			<form action='' method='post' id='simnigeria_form_<?php echo $this->datatype;?>_emails' class='simnigeria_form builder'>
				<div class='form_elements'>
					<input type='hidden' class='formbuilder' name='formname'	value='<?php echo $this->datatype;?>'>
					<input type='hidden' class='formbuilder' name='action'		value='save_form_emails'>
					
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
					foreach($this->formdata->form_elements as $element){
						if(!in_array($element['type'],['label','info','button','datalist','formstep'])){
							echo "<option>%{$element['name']}%</option>";
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
								<label class="formfield">E-mail <?php echo $key+1;?></label>
								<button type='button' class='add button' style='flex: 1;'>+</button>
								<button type='button' class='remove button' style='flex: 1;'>-</button>
								<div style='width:100%;'>
									<label class="formfield formfieldlabel">
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
									</label>
									
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
									<label class="formfield formfieldlabel">
										Sender e-mail should be:<br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][fromemail]' class='fromemail' value='fixed' <?php if(empty($email['fromemail']) or $email['fromemail'] == 'fixed') echo 'checked';?>>
											Fixed e-mail adress
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][fromemail]' class='fromemail' value='conditional' <?php if($email['fromemail'] == 'conditional') echo 'checked';?>>
											Conditional e-mail adress
										</label><br>
									</label>
									
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
									<label class="formfield tofieldlabel">
										Recipient e-mail should be:<br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailto]' class='emailto' value='fixed' <?php if(empty($email['emailto']) or $email['emailto'] == 'fixed') echo 'checked';?>>
											Fixed e-mail adress
										</label><br>
										<label>
											<input type='radio' name='emails[<?php echo $key;?>][emailto]' class='emailto' value='conditional' <?php if($email['emailto'] == 'conditional') echo 'checked';?>>
											Conditional e-mail adress
										</label><br>
									</label>
									
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
									
									<label class="formfield formfieldlabel">
										Subject
										<input type='text' class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][subject]' value="<?php echo $email['subject']?>">
									</label>
									
									<label class="formfield formfieldlabel">
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
									</label>
									
									<label class="formfield formfieldlabel">
										Additional headers like 'Reply-To'
										<textarea class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][headers]'><?php
											echo $email['headers']?>
										</textarea>
									</label>
									
									<label class="formfield formfieldlabel">
										Form values that should be attached to the e-mail
										<textarea class='formbuilder formfieldsetting' name='emails[<?php echo $key;?>][files]'><?php
											echo $email['files']?>
										</textarea>
									</label>
								</div>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<?php
				echo add_save_button('submit_form_emails','Save form email configuration');
				?>
			</form>
		</div>
		<?php
	}
	
	function element_builder_form(){
		?>
		<form action="" method="post" name="add_form_element_form" class="form_element_form simnigeria_form">
			<div style="display: none;" class="error"></div>
			<h4>Please fill in the form to add a new form element</h4><br>
			<input type="hidden" class="input-text formbuilder" name="userid" value="<?php echo $this->user->ID; ?>">
			
			<input type="hidden" class="input-text formbuilder" name="action" value="add_formfield">
			
			<input type="hidden" class="input-text formbuilder" name="formname" value="<?php echo $this->datatype;?>">
			
			<input type="hidden" class="input-text formbuilder" name="insertafter">
			
			<input type="hidden" class="input-text formbuilder" name="update">
			
			<input type="hidden" class="input-text formbuilder" name="formfield[width]" value="100">
			
			<input type="hidden" class="input-text formbuilder" name="add_form_element_nonce" value="<?php echo wp_create_nonce("add_form_element_nonce"); ?>">
			
			<label>Select which kind of form element you want to add</label>
			<select class="formbuilder elementtype" name="formfield[type]" required>
				<optgroup label="Normal elements">
					<option value="button">Button</option>
					<option value="checkbox">Checkbox</option>
					<option value="color">Color</option>
					<option value="date">Date</option>
					<option value="select">Dropdown</option>
					<option value="email">E-mail</option>
					<option value="file">File upload</option>
					<option value="image">Image upload</option>
					<option value="label">Label</option>
					<option value="month">Month</option>
					<option value="number">Number</option>
					<option value="password">Password</option>
					<option value="tel">Phonenumber</option>
					<option value="radio">Radio</option>
					<option value="range">Range</option>
					<option value="text">Text</option>
					<option value="textarea">Text (multiline)</option>
					<option value="time">Time</option>
					<option value="url">Url</option>
					<option value="week">Week</option>
				</optgroup>
				<optgroup label="Special elements">
					<option value="captcha">Captcha</option>
					<option value="php">Custom code</option>
					<option value="datalist">Datalist</option>
					<option value="info">Infobox</option>
					<option value="multi_start">Multi-answer - start</option>
					<option value="multi_end">Multi-answer - end</option>
					<option value="formstep">Multistep</option>
					<option value="p">Paragraph</option>
				</optgroup>
			</select>
			<br>
			
			<div name='elementname' class='labelhide hide'>
				<label>
					<div>Specify a name for the element</div>
					<input type="text" class="formbuilder" name="formfield[name]">
				</label>
				<br><br>
			</div>
			
			<div name='functionname' class='hidden'>
				<label>
					Specify the functionname
					<input type="text" class="formbuilder" name="formfield[functionname]">
				</label>
				<br><br>
			</div>
			
			<div name='labeltext' class='hidden'>
				<label>
					<div>Specify the <span class='elementtype'>label</span> text</div>
					<input type="text" class="formbuilder" name="formfield[labeltext]">
				</label>
				<br><br>
			</div>
			
			<div name='wrap'>
				<label>
					<input type="checkbox" class="formbuilder" name="formfield[wrap]" value="false">
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
				
					echo wp_editor(
						'',
						"{$this->datatype}_infotext",	//editor should always have an unique id
						$settings
					);
					?>
					
				</label>
				<br>
			</div>
			
			<div name='multiple' class='labelhide hide'>
				<label>
					<input type="checkbox" class="formbuilder" name="formfield[multiple]" value="multiple">
					Allow multiple answers
				</label>
				<br>
				<br>
			</div>
			
			<div name='valuelist' class='hidden'>
				<label>
					Specify the values, one per line
					<textarea class="formbuilder" name="formfield[valuelist]"></textarea>
				</label>
				<br>
			</div>

			<div name='select_options' class='labelhide hide'>
				<label class='block'>Specify an options group if desired</label>
				<select class="formbuilder" name="formfield[default_array_value]">
					<option value="">---</option>
					<?php
					$this->build_defaults_array();
					foreach($this->default_array_values as $key=>$field){
						echo "<option value='$key'>".ucfirst(str_replace('_',' ',$key))."</option>";
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
						echo "<option value='$key'>".ucfirst(str_replace('_',' ',$key))."</option>";
					}
					?>
				</select>
			</div>
			<br>
			<div name='elementoptions' class='labelhide hide'>
				<label>
					Specify any options like styling
					<textarea class="formbuilder" name="formfield[options]"></textarea>
				</label>
				<br><br>
				
				<label class="option-label">
					<input type="checkbox" class="formbuilder" name="formfield[required]" value="required">
					Check if this should be a required field
				</label><br>
			</div>
			
			<label class="option-label">
				<input type="checkbox" class="formbuilder" name="formfield[hidden]" value="hidden">
				Check if this should be a hidden field
			</label><br>

			<?php echo add_save_button('submit_form_element',"Add form element"); ?>
		</form>
		<?php
	}
	
	function create_js(){
		//Add function to loop over conditions on page load
		ob_start(); 
		//write external js into this file
		echo apply_filters('form_extra_js',$this->datatype);
		
		echo "\n\n";
		?>
document.addEventListener("DOMContentLoaded", function() {
	console.log("Dynamic <?php echo $this->datatype;?> forms js loaded");
	
	tidy_multi_inputs();
	
	//show first tab
	currentTab = 0; // Current tab is set to be the first tab (0)
	form = document.getElementById('simnigeria_form_<?php echo $this->datatype;?>');
	showTab(currentTab,form); // Display the current tab
<?php
if(!empty($this->formdata->settings['save_in_meta'])){
?>
	form.querySelectorAll('select, input, textarea').forEach(el=>process_<?php echo $this->datatype;?>_fields(el));
<?php
}
?>
});

window.addEventListener("click", <?php echo $this->datatype;?>_listener);
window.addEventListener("input", <?php echo $this->datatype;?>_listener);

<?php echo $this->datatype;?>_prev_el = '';
function <?php echo $this->datatype;?>_listener(event) {
	var el			= event.target;
	form			= el.closest('form');
	var name		= el.name;
	if(name == '' || name == undefined){
		if(el.closest('.nice-select-dropdown') != null){
			el.closest('.inputwrapper').querySelectorAll('select').forEach(select=>{
				if(el.dataset.value == select.value){
					el	= select;
					name = select.name;
				}
			});
		}else{
			return;
		}
	}
	
	//prevent duplicate event handling
	if(el == <?php echo $this->datatype;?>_prev_el){
		return;
	}
	<?php echo $this->datatype;?>_prev_el = el;
	//clear event prevenion after 200 ms
	setTimeout(function(){ <?php echo $this->datatype;?>_prev_el = ''; }, 200);
	
	if(name == 'nextBtn'){
		nextPrev(1);
	}else if(name == 'prevBtn'){
		nextPrev(-1);
	}

	process_<?php echo $this->datatype;?>_fields(el);
}

function process_<?php echo $this->datatype;?>_fields(el){
	var name = el.name;
<?php
		//Loop over all elements to find any conditions
		foreach($this->formdata->form_elements as $element){
			$conditions	= $this->formdata->form_conditions[$element['id']];
			//if there are conditions
			if(is_array($conditions)){
				//Loop over the conditions
				foreach($conditions as $condition_index=>$condition){
					//if there are rules build some javascript
					if(is_array($condition['rules'])){
						
						//Open the if statemenet
						$lastrule_key			= array_key_last($condition['rules']);
						$field_check_if 		= "if(";
						$condition_variables	= '';
						$condition_if			= 'if(';
						
						$check_for_change = false;
						
						//Loop over the rules
						foreach($condition['rules'] as $rule_index=>$rule){
							$fieldnumber_1	= $rule_index*2  + 1;
							$fieldnumber_2	= $fieldnumber_1 + 1;
							$equation		= str_replace(' value','',$rule['equation']);
							
							//Get field names of the fields who's value we are checking
							$conditional_element		= $this->getelementbyid($rule['conditional_field']);
							$conditional_field_name		= $conditional_element['name'];
							if(in_array($conditional_element['type'],['radio','checkbox']) and strpos($conditional_field_name, '[]') === false) {
								$conditional_field_name .= '[]';
							}
							$conditional_field_type		= $conditional_element['type'];
							$conditional_element_2		= $this->getelementbyid($rule['conditional_field_2']);
							$conditional_field_2_name	= $conditional_element_2['name'];
							if(in_array($conditional_element_2['type'],['radio','checkbox']) and strpos($conditional_field_2_name, '[]') === false) {
								$conditional_field_2_name .= '[]';
							}
							$conditional_field_2_type	= $conditional_element_2['type'];
							
							//Check if we are calculating a value based on two field values
							if(($equation == '+' or $equation == '-') and !empty($rule['conditional_field_2']) and !empty($rule['equation_2'])){
								$calc = true;
							}else{
								$calc = false;
							}
							
							//make sure we do not include other fields in changed or click rules
							if(in_array($equation, ['changed','clicked'])){
								$field_check_if		= "if(name == '$conditional_field_name'";
								$check_for_change	= true;
							}
							
							//Only allow or statements
							if($check_for_change == false or $condition['rules'][$rule_index-1]['combinator'] == 'OR'){
								//Write the if statement to check if the current clicked field belongs to this condition
								if($field_check_if != 'if(') $field_check_if .= " || ";
								$field_check_if .= "name == '$conditional_field_name'";
								
								//If there is an extra field to check
								if(!empty($rule['conditional_field_2'])){
									$field_check_if .= " || name == '$conditional_field_2_name'";
								}
							}
			
							//We calculate the sum or difference of two field values if needed.
							if($calc){
								if($conditional_field_type == 'date'){
									//Convert date strings to date values then miliseconds to days
									$condition_variables .= "\t\tvar calculated_value_$rule_index = (Date.parse(value_$fieldnumber_1) $equation Date.parse(value_$fieldnumber_2))/ (1000 * 60 * 60 * 24);\n";
								}else{
									$condition_variables .= "\t\tvar calculated_value_$rule_index = value_$fieldnumber_1 $equation value_$fieldnumber_2;\n";
								}
								$equation = $rule['equation_2'];
							}
							
							//compare with calculated value
							if($calc){
								$comparevalue1 = "calculated_value_$rule_index";
							//compare with a field value
							}else{
								$comparevalue1 = "value_$fieldnumber_1.toLowerCase()";
							}
								
							//compare with the value of another field
							if(strpos($rule['equation'], 'value') !== false){
								$comparevalue2 = "value_$fieldnumber_2";
							//compare with a number
							}elseif(is_numeric($rule['conditional_value'])){
								$comparevalue2 = trim($rule['conditional_value']);
							//compare with text
							}else{
								$comparevalue2 = "'".strtolower(trim($rule['conditional_value']))."'";
							}
							
							/* 
								NOW WE KNOW THAT THE CHANGED FIELD BELONGS TO THIS CONDITION
								LETS CHECK IF ALL THE VALUES ARE MET AS WELL 
							*/
							if(!in_array($equation, ['changed','clicked','checked','!checked'])){
								$condition_variables .= "\n\t\tvar value_$fieldnumber_1 = get_field_value('$conditional_field_name',true,$comparevalue2);\n";
								
								if(!empty($rule['conditional_field_2'])){
									$condition_variables .= "\n\t\tvar value_$fieldnumber_2 = get_field_value('$conditional_field_2_name',true,$comparevalue2);\n";
								}
							}
							
							if($equation == 'checked'){
								if(count($condition['rules'])==1){
									$condition_if .= "el.checked == true";
								}else{
									$condition_if .= "form.querySelector('[name=\"$conditional_field_name\"]').checked == true";
								}
							}elseif($equation == '!checked'){
								if(count($condition['rules'])==1){
									$condition_if .= "el.checked == false";
								}else{
									$condition_if .= "form.querySelector('[name=\"$conditional_field_name\"]').checked == false";
								}
							}elseif($equation != 'changed' and $equation != 'clicked'){
								$condition_if .= "$comparevalue1 $equation $comparevalue2";
							}
							
							//If there is another rule, add or or and
							if($lastrule_key == $rule_index){
								$condition_if .= "){\n";
							}else{
								if(
									!empty($rule['combinator'])														and //there is a next rule
									$condition_if != 'if(' 															and	//there is already preceding code
									!in_array($condition['rules'][$rule_index+1]['equation'],['changed','clicked'])		//The next element will also be included in the if statement
								){
									if($rule['combinator'] == 'AND'){
										$condition_if .= " && ";
									}else{
										$condition_if .= " || ";
									}
								}
							}
						}
						//Now we write it all out as a whole
						echo "\n\t$field_check_if){\n";
							
							//no need for variable in case of a 'changed' condition
							if(strpos($condition_if,"if()") === false){
								echo $condition_variables;
								echo "\n\t\t$condition_if";
							}
							
							$action = $condition['action'];
							
							//show, toggle or hide action for this field
							if($action == 'show' or $action == 'hide' or $action == 'toggle'){
								if($action == 'show'){
									$action = 'remove';
								}elseif($action == 'hide'){
									$action = 'add';
								}
								
								//do not write a rule for label with another element
								if($element['type'] != 'label' or empty($element['wrap'])){
									//formstep do not have an inputwrapper
									if($element['type'] == 'formstep'){
										$closest = "";
									}else{
										$closest = ".closest('.inputwrapper')";
									}
									$name	= $element['name'];
									if(in_array($element['type'],['radio','checkbox']) and strpos($name, '[]') === false) {
										$name .= '[]';
									}
									if(empty($element['multiple'])){
										echo "\n\t\t\tform.querySelector('[name=\"$name\"]')$closest.classList.$action('hidden');";
									}else{
										echo "\n\t\t\tform.querySelector('[name^=\"$name\"]')$closest.classList.$action('hidden');";
									}
								}
								foreach($conditions['copyto'] as $field_index){
									if(!is_numeric($field_index))	continue;
									//find the element with the right id
									$copy_to_element	= $this->getelementbyid($field_index);
									$name				= $copy_to_element['name'];
									if(in_array($copy_to_element['type'],['radio','checkbox']) and strpos($name, '[]') === false) {
										$name .= '[]';
									}
									
									//formstep do not have an inputwrapper
									if($element['type'] == 'formstep'){
										$closest = "";
									}else{
										$closest = ".closest('.inputwrapper')";
									}
									if(empty($copy_to_element['multiple'])){
										echo "\n\t\t\tform.querySelector('[name=\"$name\"]')$closest.classList.$action('hidden');";
									}else{
										echo "\n\t\t\tform.querySelector('[name^=\"$name\"]')$closest.classList.$action('hidden');";
									}
								}
							//set property value
							}elseif($action == 'property' or $action == 'value'){
								//set the attribute value of one field to the value of another field
								$fieldname		= $element['name'];
								
								//fixed prop value
								if($action == 'value'){
									$propertyname	= $condition['propertyname1'];
									echo "\t\t\tvar replacement_value = '{$condition['action_value']}';";
								//retrieve value from another field
								}else{
									$propertyname	= $condition['propertyname'];
								
									$copyfieldid	= $condition['property_value'];
									
									//find the element with the right id
									$copyfieldname = $this->getelementbyid($copyfieldid,'name');
									
									//if($copyfieldname == $conditional_field_name and count($condition['rules']) == 1){
									//	echo "\t\t\tvar replacement_value = value;";
									//}else{
										echo "\t\t\tvar replacement_value = get_field_value('$copyfieldname')";
									//}
								}
								
								if($propertyname == 'value'){
									echo "\n\t\t\tchange_field_value('$fieldname', replacement_value);";
								}else{
									echo "\n\t\t\tchange_field_property('$fieldname', '$propertyname', replacement_value);";
								}
							}else{
								print_array("formbuilder.php writing js: missing action: '$action' for condition $condition_index of field {$element['name']}");
							}
							
							if(strpos($condition_if,"if()") === false){
								echo "\n\t\t}";//close condition if
							}
						echo "\n\t}";//close field check if
					}
				}
			}
		}
		
		//end js listener function
		echo "\n}";

		//write it all to a file
		$js			= ob_get_clean();

		//Create js file
		file_put_contents($this->jsfilename, $js);
	}
}

add_action('init', function(){
	//shortcode to make forms
	add_shortcode( 'formbuilder', function($atts){
		$formbuilder = new Formbuilder();
		return $formbuilder->formbuilder($atts);
	});
				
	$formbuilder	= new Formbuilder();
	//Add tinymce plugin
	add_filter('mce_external_plugins', function($plugins)use($formbuilder){
		return $formbuilder->add_tinymce_plugin($plugins);
	},999);
			
	//add tinymce button
	add_filter('mce_buttons', function($buttons)use($formbuilder){
		return $formbuilder->register_buttons($buttons);
	},999);

});

function schedule_auto_archive(){
	//Not yet activated
	if (! wp_next_scheduled ( 'simnigeria_auto_archive_action' )) {
		//schedule
		if(wp_schedule_event( time(), 'daily', 'simnigeria_auto_archive_action' )){
			print_array("Succesfully scheduled auto_archive to run daily");
		}else{
			print_array("Scheduling of auto_archive unsuccesfull");
		}
	}
}

function auto_archive_action(){
	$formbuilder = new Formbuilder();
	$formbuilder->autoarchive();
}