<?php
namespace SIM\FORMS;
use SIM;

class FormTableAjax extends FormTable {
	function __construct(){
		// call parent constructor
		parent::__construct();
		
		if(is_user_logged_in()){			
			//Only available for contentmanagers
			if($this->editrights == true){
				//Make function save_column_settings availbale for AJAX request
				add_action ( 'wp_ajax_save_column_settings', array($this,'save_column_settings'));
				
				//Make function save_table_settings availbale for AJAX request
				add_action ( 'wp_ajax_save_table_settings', array($this,'save_table_settings'));
			}
			
			//Make forms_table_update function availbale for AJAX request
			add_action ( 'wp_ajax_forms_table_update', array($this,'table_actions'));
			
			//Make get_input_html function availbale for AJAX request
			add_action ( 'wp_ajax_get_input_html', array($this,'get_input_html'));
		}
	}
	
	function save_column_settings(){
		global $wpdb;
		
		SIM\verify_nonce('save_column_settings_nonce');
		
		if(!is_numeric($_POST['shortcode_id'])){
			wp_die('Invalid shortcode id',500);
		}
		
		//copy edit right roles to view right roles
		$settings = $_POST['column_settings'];
		foreach($settings as $key=>$setting){
			//if there are edit rights defined
			if(is_array($setting['edit_right_roles'])){
				//create view array if it does not exist
				if(!is_array($setting['view_right_roles'])) $setting['view_right_roles'] = [];
				//merge and save
				$settings[$key]['view_right_roles'] = array_merge($setting['view_right_roles'],$setting['edit_right_roles']);
			}
		}
		
		$wpdb->update($this->shortcodetable, 
			array(
				'column_settings'	 	=> maybe_serialize($settings)
			), 
			array(
				'id'		=> $_POST['shortcode_id'],
			),
		);
		
		if($wpdb->last_error !== ''){
			wp_die($wpdb->print_error(),500);
		}else{
			wp_die("Succesfully saved your column settings");
		}
	}
	
	function save_table_settings(){
		global $wpdb;
		
		SIM\verify_nonce('save_table_settings_nonce');
		
		if(!is_numeric($_POST['shortcode_id'])){
			wp_die('Invalid shortcode id',500);
		}
		
		//update table settings
		$table_settings = $_POST['table_settings'];
		
		$wpdb->update($this->shortcodetable, 
			array(
				'table_settings'	 	=> maybe_serialize($table_settings)
			), 
			array(
				'id'		=> $_POST['shortcode_id'],
			),
		);
		
		//also update form setings if needed
		$form_settings = $_POST['form_settings'];
		if(is_array($form_settings) and !empty($_POST['formname'])){
			$this->datatype = $_POST['formname'];
			$this->loadformdata();
			
			//update existing
			foreach($form_settings as $key=>$setting){
				$this->formdata->settings[$key] = $setting;
			}
			
			//save in db
			$wpdb->update($this->table_name, 
				array(
					'settings' 	=> maybe_serialize($this->formdata->settings)
				), 
				array(
					'name'		=> $this->datatype,
				),
			);
		}
		
		if($wpdb->last_error !== ''){
			wp_die($wpdb->print_error(),500);
		}else{
			wp_die("Succesfully saved your table settings");
		}
	}

	function table_actions(){
		global $wpdb;
			
		if(!empty($_POST['table_id'])){
			$this->datatype = $_POST['table_id'];
			$this->loadformdata();
		}else{
			wp_die('Invalid table id',500);
		}
		
		if(!is_numeric($_POST['shortcodeid'])) wp_die('Invalid shortcode id',500);
		
		//load shortcode settings
		$this->shortcode_id			= $_POST['shortcodeid'];
		$this->loadshortcodedata();
		$this->table_settings		= maybe_unserialize($this->shortcodedata->table_settings);
		$this->column_settings		= maybe_unserialize($this->shortcodedata->column_settings);
			
		$this->submission_id	= $_POST['submission_id'];
		if(!is_numeric($this->submission_id)){
			wp_die('Invalid submission id',500);
		}
		
		$this->get_submission_data($user_id=null,$submission_id=$this->submission_id);

		//formresults
		$this->formresults 				= maybe_unserialize($this->submission_data->formresults);
		//$form_url						= $_POST['formurl'];
		//$this->formresults['formurl']	= $form_url;
			
		//update an existing entry
		if(isset($_POST['fieldname'])){
			$fieldname 		= sanitize_text_field($_POST['fieldname']);
			$new_value 		= sanitize_text_field($_POST['newvalue']);
			
			$sub_id			= $_POST['subid'];
			//Update the current value
			if(is_numeric($sub_id)){
				$field_main_name	= $this->formdata->settings['split'];
				$pattern = "/$field_main_name\[[0-9]\]\[([^\]]*)\]/i";
				if(preg_match($pattern, $fieldname,$matches)){
					$this->formresults[$field_main_name][$sub_id][$matches[1]]	= $new_value;
					$message = "Succesfully updated '{$matches[1]}' to $new_value";
				}else{
					wp_die("Could not find $fieldname",500);
				}
			}else{
				$this->formresults[$fieldname]	= $new_value;
				$message = "Succesfully updated '$fieldname' to $new_value";
			}
			
			$this->update_submission_data();
			
			//send email if needed
			$this->send_email('fieldchanged');
			
			$callback = 'changetablevalue';
		//remove or (un)archive an existing entry
		}elseif(isset($_POST['remove']) or isset($_POST['archive'])){
			$callback	= 'remove_form_table_row';
			$sub_id		= -1;
			
			if(isset($_POST['remove'])){
				$message	= "Entry with id {$this->submission_id} succesfully removed";
				
				$result = $wpdb->delete(
					$this->submissiontable_name, 
					array(
						'id'		=> $this->submission_id
					)
				);
				
				if($result === false){
					wp_die("Removal failed",500); 
				}
			}else{
				if($_POST['archive'] == 'true'){
					$action = 'archived';
					$archive = true;
				}else{
					$action		= 'unarchived';
					$archive	= false;
					$callback	= '';
				}
				
				if($this->table_settings['archived'] == 'true'){
					$callback = 'changearchivebutton';
				}
				
				if(is_numeric($_POST['subid'])){
					$sub_id						= $_POST['subid'];
					$message					= "Entry with id {$this->submission_id} and subid $sub_id succesfully $action";
					$splitfield					= $this->formdata->settings['split'];
					
					$this->formresults[$splitfield][$sub_id]['archived'] = $archive;
					
					//check if all subfields are archived or empty
					$this->check_if_all_archived($this->formresults[$splitfield]);
				}else{
					$message					= "Entry with id {$this->submission_id} succesfully $action";
					$this->update_submission_data($archive);
				}
			}
		}else{
			wp_die('I did not know what to do!',500);
		}
		
		//send message back to js
		wp_send_json(
			[
				'message'			=> $message,
				'formid'			=> $this->datatype,
				'submissionid'		=> $this->submission_id,
				'subid'				=> $sub_id,
				'callback'			=> $callback,
				'newvalue'			=> $this->transform_inputdata($new_value,$fieldname),
				'cellid'			=> $fieldname,
			]
		);
	}
	
	//function to show input to update input values
	function get_input_html(){
		SIM\verify_nonce('updateforminput');
		
		if(!empty($_POST['formname'])){
			$this->datatype = $_POST['formname'];
			$this->loadformdata();
		}else{
			wp_die('Invalid form name',500);
		}
		
		if(empty($_POST['fieldname']) or !is_numeric($_POST['submission_id']))	wp_die('Invalid submission id',500);
		
		foreach ($this->form_elements as $key=>$element){
			if(str_replace('[]','',$element->name) == $_POST['fieldname']){
				//Load default values for this element
				$element_html = $this->get_element_html($element);
				
				wp_send_json(
					[
						'formid'			=> $this->datatype,
						'submissionid'		=> $_POST['submission_id'],
						'subid'				=> $_POST['sub_id'],
						'cellid'			=> $_POST['fieldname'],
						'callback'			=> 'showforminput',
						'html'				=> $element_html
					]
				);
			}
		}
		
		wp_die("No element found with id '{$_POST['fieldname']}'",500);
	}	
}

add_action('init', function(){
	if(wp_doing_ajax()) new FormTableAjax();
});