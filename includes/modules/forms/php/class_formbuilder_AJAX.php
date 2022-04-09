<?php
namespace SIM\FORMS;
use SIM;

class Formbuilder_Ajax extends Formbuilder{
	function __construct(){
		// call parent constructor
		parent::__construct();
		
		//Make function add_form availbale for AJAX request
		add_action ( 'wp_ajax_add_form', array($this,'add_form'));

		//Make function add_formfield availbale for AJAX request
		add_action ( 'wp_ajax_add_formfield', array($this,'add_formfield'));
		
		//Make function remove_formfield availbale for AJAX request
		add_action ( 'wp_ajax_remove_formfield', array($this,'remove_element'));
		
		//Make function request_form_elements availbale for AJAX request
		add_action ( 'wp_ajax_request_form_elements', array($this,'request_form_elements'));
		
		//Make function reorder_form_elements availbale for AJAX request
		add_action ( 'wp_ajax_reorder_form_elements', array($this,'reorder_form_elements'));
		
		//Make function edit_formfield_width availbale for AJAX request
		add_action ( 'wp_ajax_edit_formfield_width', array($this,'edit_formfield_width'));

		//Make function request_form_conditions_html availbale for AJAX request
		add_action ( 'wp_ajax_request_form_conditions_html', array($this,'request_form_conditions_html'));

		//Make function save_element_conditions availbale for AJAX request
		add_action ( 'wp_ajax_save_element_conditions', array($this,'save_element_conditions'));
		
		//Make function save_form_settings availbale for AJAX request
		add_action ( 'wp_ajax_save_form_settings', array($this,'save_form_settings'));
		
		//Make function save_form_input availbale for AJAX request
		add_action ( 'wp_ajax_save_form_input', array($this,'save_form_input'));
		
		//Make function save_form_emails availbale for AJAX request
		add_action ( 'wp_ajax_save_form_emails', array($this,'save_form_emails'));
	}
	
	function check_permissions(){
		$user	= wp_get_current_user();
		if(!in_array('contentmanager', $user->roles))	wp_die('You do not have the permission to do this',500);
	}

	function add_formfield(){
		global $wpdb;

		SIM\verify_nonce('add_form_element_nonce');

		$this->check_permissions();

		if(!isset($_POST["formfield"])) wp_die('You did not submit any fields', 500);

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
			if ( ! function_exists( $functionname ) ) wp_die("A function with name $functionname does not exist!",500);
		}
		
		if(in_array($element->type, ['label','button'])){
			$element->name	= $element->text;
		}elseif($element->name == ''){
			wp_die("Please enter a formfieldname",500);
		}

		$element->nicename	= $element->name;
		$element->name		= str_replace(" ","_",strtolower(trim($element->name)));

		if(
			in_array($element->type,$this->non_inputs) 		and // this is a non-input
			$element->type != 'datalist'					and // but not a datalist
			strpos($element->name,$element->type) === false		// and the type is not yet added to the name 
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
				foreach($this->formdata->elements as $index=>$field){
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
				$val	= $this->deslash($val);
			}
		}
		
		if($update){
			$message								= "Succesfully updated '{$element->name}'";
			$element->id							= $_POST['element_id'];
			$callback								= 'update_form_element';

			$this->update_form_element($element);
		}else{
			$message								= "Succesfully added '{$element->name}' to this form";
			$callback								= 'after_form_element_submit';

			if(!is_numeric($_POST['insertafter'])){
				$element->priority	= $wpdb->get_var( "SELECT COUNT(`id`) FROM `{$this->el_table_name}` WHERE `form_id`={$element->form_id}") +1;
			}

			$this->insert_element($element);

			if(is_numeric($_POST['insertafter'])){
				$index = $_POST['insertafter']+1;

				$this->reorder_elements(-1, $index, $element);
			}
		}
			
		$html = $this->buildhtml($element, $index);
		
		echo json_encode([
			'message'		=> $message,
			'html'			=> $html,
			'formname'		=> $_POST['formname'],
			'callback'		=> $callback,
			'index'			=> $index,
		]);
		wp_die();
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
			wp_die($wpdb->print_error(),500);
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

	//Function for AJAX call
	function add_form(){
		$this->check_permissions();

		$this->datatype	= sanitize_text_field($_POST['form_name']);
		if(empty($this->datatype)) wp_die('Invalid form name', 500);

		$this->insert_form();

		wp_die('Succesfully inserted the form');
	}

	function maybe_insert_form(){
		global $wpdb;
		
		//check if form row already exists
		if($wpdb->get_var("SELECT * FROM {$this->table_name} WHERE `name` = '{$this->datatype}'") != true){
			//Create a new form row
			SIM\print_array("Inserting new form in db");
			
			$this->insert_form();
		}
	}

	function insert_element($element){
		global $wpdb;
		
		$result = $wpdb->insert(
			$this->el_table_name, 
			(array)$element
		);
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

		//Update form version
		$result = $wpdb->update(
			$this->table_name, 
			['version'	=> $this->formdata->version+1], 
			['id'		=> $this->formdata->id],
		);
		
		if($wpdb->last_error !== ''){
			wp_die($wpdb->print_error(),500);
		}
	}
	
	function remove_element(){
		$this->check_permissions();

		global $wpdb;

		SIM\verify_nonce('remove_form_element_nonce');

		$element_id	= $_POST['elementindex'];		
		if(!is_numeric($element_id))	wp_die('Invalid element id',500);

		$wpdb->delete(
			$this->el_table_name,      
			['id' => $element_id],           
		);

		// Fix priorities
		// Get all elements of this form
		$this->get_all_form_elements('priority');
		
		//Loop over all elements and give them the new priority
		foreach($this->form_elements as $key=>$el){
			if($el->priority != $key+1){
				//Update the database
				$result = $wpdb->update($this->el_table_name, 
					array(
						'priority'	=> $key+1
					), 
					array(
						'id'		=> $el->id
					),
				);
				
				if($wpdb->last_error !== ''){
					wp_die($wpdb->print_error(),500);
				}
			}
		}

		echo json_encode([
			'message'		=> "Succesfully removed the element",
			'formid'		=> $_POST['formid'],
			'elementindex'	=> $element_id,
			'callback'		=> 'removerow'
		]);
		wp_die();
	}
	
	function request_form_elements(){
		$this->check_permissions();

		SIM\verify_nonce('request_form_element_nonce');
		
		$form_id = $_POST['formid'];
		if(!is_numeric($form_id)) wp_die("Invalid form id given",500);

		$element_id = $_POST['elementid'];
		if(!is_numeric($element_id)) wp_die("Invalid element index given",500);
		
		$this->loadformdata($form_id);
		
		$condition_html				= $this->element_conditions_form($element_id);

		$warning_conditions_html	= $this->warning_conditions_form($element_id);

		$element					= $this->getelementbyid($element_id);
		
		echo json_encode([
			'message'			=> "",
			'formid'			=> $form_id,
			'element_id'		=> $element_id,
			'original'			=> $element,
			'callback'			=> 'prefillforminput',
			'conditions_html'	=> $condition_html,
			'warning_conditions'=> $warning_conditions_html
		]);
		wp_die();
	}
	
	function reorder_form_elements(){
		$this->check_permissions();

		SIM\verify_nonce('remove_form_element_nonce');
		
		$old_index 	= $_POST['old_index'];
		$new_index	= $_POST['new_index'];
		
		if(!is_numeric($old_index)) wp_die("Invalid element index given",500);
		if(!is_numeric($new_index)) wp_die("Invalid new element index given",500);
		
		$this->reorder_elements($old_index, $new_index, '');
		
		wp_die(json_encode([
			'message'	=> "Succesfully saved new form order",
			'callback'	=> 'after_reorder',
			'formid'	=> $_POST['formname'],
		]));
	}
	
	function edit_formfield_width(){
		$this->check_permissions();

		SIM\verify_nonce('remove_form_element_nonce');
		
		$element_id 	= $_POST['elementid'];
		if(!is_numeric($element_id)) wp_die("Invalid element id given",500);
		$element		= $this->getelementbyid($element_id);
		
		$newwidth 		= $_POST['new_width'];
		if(!is_numeric($newwidth)) wp_die("Invalid width given",500);
		$element->width = min($newwidth,100);
		
		$this->update_form_element($element);
		
		wp_die(json_encode([
			'message'	=> "Succesfully updated formelement width to $newwidth%",
			'callback'	=> 'hide_modals'
		]));
	}

	function request_form_conditions_html(){
		SIM\verify_nonce('request_form_element_nonce');
		
		$element_index = $_POST['elementindex'];
		
		if(!is_numeric($element_index)) wp_die("Invalid element index given",500);
		
		$this->datatype = $_POST['formname'];
		$this->loadformdata();
		
		$condition_html = $this->element_conditions_form($element_index);
		
		echo json_encode([
			'message'			=> "",
			'formid'			=> $this->datatype,
			'callback'			=> 'conditions_html',
			'conditions_html'	=> $condition_html
		]);
		wp_die();
	}

	function save_element_conditions(){
		$this->check_permissions();

		SIM\verify_nonce('element_condition_nonce');

		$element_id = $_POST['element_id'];
		if(!is_numeric($element_id)) wp_die("Invalid element id given",500);

		$form_id	= $_POST['formid'];
		if(!is_numeric($form_id)) wp_die("Invalid form id given",500);
		
		$this->loadformdata($form_id);
		
		$element = $this->getelementbyid($element_id);
		
		$element_conditions	= $_POST['element_conditions'];
		if(empty($element_conditions)){
			$element->conditions	= '';
			
			$message = "Succesfully removed all conditions for {$element->name}";
		}else{
			$element->conditions = serialize($element_conditions);
			
			$message = "Succesfully updated conditions for {$element->name}";
		}

		$this->update_form_element($element);
		
		//Create new js
		$errors		 = $this->create_js();

		if(!empty($errors)){
			$message	.= "\n\nThere were some errors:\n";
			$message	.= implode("\n", $errors);
		}

		wp_die(
			json_encode([
				'message'	=> $message,
				'formid'	=> $this->datatype,
				'index'		=> $element_id,
				'callback'	=> '',
			])
		);
	}

	function save_form_settings(){
		$this->check_permissions();

		global $wpdb;
		
		if(!empty($_POST['formname'])){
			$this->datatype = $_POST['formname'];
			$this->loadformdata();
		}else{
			wp_die('Invalid form name',500);
		}
		
		$form_settings = $_POST['settings'];
		
		//remove double slashes
		$form_settings['upload_path']	= str_replace('\\\\','\\',$form_settings['upload_path']);
		
		$this->maybe_insert_form();
		
		$wpdb->update($this->table_name, 
			array(
				'settings' 	=> maybe_serialize($form_settings)
			), 
			array(
				'name'		=> $this->datatype,
			),
		);
		
		if($wpdb->last_error !== ''){
			wp_die($wpdb->print_error(),500);
		}else{
			wp_die("Succesfully saved your form settings");
		}
	}
		
	function save_form_emails(){
		$this->check_permissions();
		
		global $wpdb;
		
		if(!empty($_POST['formname'])){
			$this->datatype = $_POST['formname'];
			$this->loadformdata();
		}else{
			wp_die('Invalid form name',500);
		}
		
		$form_emails = $_POST['emails'];
		
		foreach($form_emails as $index=>$email){
			$form_emails[$index]['message'] = $this->deslash($email['message']);
		}
		
		$this->maybe_insert_form();
		$wpdb->update($this->table_name, 
			array(
				'emails'	 	=> maybe_serialize($form_emails)
			), 
			array(
				'name'		=> $this->datatype,
			),
		);
		
		if($wpdb->last_error !== ''){
			wp_die($wpdb->print_error(),500);
		}else{
			wp_die("Succesfully saved your form e-mail configuration");
		}
	}

	function save_form_input(){
		global $wpdb;

		$form_id	= $_POST['formid'];
		if(!is_numeric($form_id))	wp_die('Invalid form id',500);
		
		$this->loadformdata($form_id);
		
		$this->user_id	= 0;
		if(is_numeric($_POST['userid'])){
			//If we are submitting for someone else and we do not have the right to save the form for someone else
			if(array_intersect($this->user_roles,$this->submit_roles) === false and $this->user->ID != $_POST['userid']){
				wp_die('You do not have permission to save data for user with id '.$_POST['userid'],500);
			}else{
				$this->user_id = $_POST['userid'];
			}
		}
		
		$this->formresults = $_POST;
			
		//remove the action and the formname
		unset($this->formresults['action']);
		unset($this->formresults['formname']);
		unset($this->formresults['fileupload']);
		unset($this->formresults['userid']);
		
		$this->formresults = apply_filters('before_saving_formdata', $this->formresults, $this->formdata->name, $this->user_id);
		
		$message = $this->formdata->settings['succesmessage'];
		if(empty($message)) $message = 'succes';
		
		//save to submission table
		if(empty($this->formdata->settings['save_in_meta'])){			
			//Get the id from db before insert so we can use it in emails and file uploads
			$this->formresults['id']	= $wpdb->get_var( "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE (TABLE_NAME = '{$this->submissiontable_name}')");
			
			//sort arrays
			foreach($this->formresults as $key=>$result){
				if(is_array($result)){
					//check if this a aray of uploaded files
					if(!is_array(array_values($result)[0]) and strpos(array_values($result)[0],'wp-content/uploads/') !== false){
						//rename the file
						$this->process_files($result, $key);
					}else{
						//sort the array
						ksort($result);
						$this->formresults[$key] = $result;
					}
				}
			}

			$creation_date	= date("Y-m-d H:i:s");
			$form_url		= $_POST['formurl'];
			
			$this->formresults['formurl']			= $form_url;
			$this->formresults['submissiontime']	= $creation_date;
			$this->formresults['edittime']			= $creation_date;
			
			$wpdb->insert(
				$this->submissiontable_name, 
				array(
					'form_id'			=> $form_id,
					'timecreated'		=> $creation_date,
					'timelastedited'	=> $creation_date,
					'userid'			=> $this->user_id,
					'formresults'		=> maybe_serialize($this->formresults),
					'archived'			=> false
				)
			);
			
			$this->send_email();
				
			if($wpdb->last_error !== ''){
				wp_die($wpdb->last_error,500);
			}else{
				wp_die("$message  \nYour id is {$this->formresults['id']}");
			}
		//save to user meta
		}else{
			unset($this->formresults['formurl']);
			
			//get user data as array
			$userdata		= (array)get_userdata($this->user_id)->data;
			foreach($this->formresults as $key=>$result){
				//remove empty elements from the array
				if(is_array($result)){
					SIM\clean_up_nested_array($result);
					$this->formresults[$key]	= $result;
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
						delete_user_meta($this->user_id,$key);
					}else{
						update_user_meta($this->user_id,$key,$result);
					}
				}
			}

			if($update_userdata)wp_update_user($userdata);
			wp_die($message );
		}

		do_action('after_saving_formdata',$this->formresults,$this->datatype,$this->user_id);
	}
	
	function process_files($uploaded_files,$inputname){
		//loop over all files uploaded in this fileinput
		foreach ($uploaded_files as $key => $url){
			$urlparts 	= explode('/',$url);
			$filename	= end($urlparts);
			$path		= SIM\url_to_path($url);
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

		$element	= $this->getelementbyid($element_id);

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
			<input type='hidden' class='element_condition' name='action' value='save_element_conditions'>
			
			<input type='hidden' class='element_condition' name='formid' value='<?php echo $this->formdata->id;?>'>
			
			<input type='hidden' class='element_condition' name='element_id' value='<?php echo $element_id;?>'>
			
			<input type='hidden' class='element_condition' name='element_condition_nonce' value='<?php echo wp_create_nonce('element_condition_nonce'); ?>'>
			
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
		//print_array($html);
		return $html;
	}

	function warning_conditions_form($element_id = -1){
		$element	= $this->getelementbyid($element_id);

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

		<div id="conditions_wrapper">
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
					
					<button type='button' class='warn_cond button <?php if(!empty($rule['combinator']) and $rule['combinator'] == 'AND') echo 'active';?>'	title='Add a new "AND" rule' value="and">AND</button>
					<button type='button' class='warn_cond button  <?php if(!empty($rule['combinator']) and $rule['combinator'] == 'OR') echo 'active';?>'	title='Add a new "OR"  rule' value="or">OR</button>
					<button type='button' class='remove_warn_cond  button' title='Remove rule'>-</button>

					<br>
				</div>
				<?php 
			}
			?>
		</div>
		<?php
		$html = ob_get_clean();
		//print_array($html);
		return $html;
	}
}

add_action('init', function(){
	if(wp_doing_ajax()){
		new Formbuilder_Ajax();
	}
});
