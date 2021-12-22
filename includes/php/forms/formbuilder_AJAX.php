<?php
namespace SIM;

class Formbuilder_Ajax extends Formbuilder{
	function __construct(){
		// call parent constructor
		parent::__construct();
		
		//Make function add_formfield availbale for AJAX request
		add_action ( 'wp_ajax_add_formfield', array($this,'add_formfield'));
		
		//Make function remove_formfield availbale for AJAX request
		add_action ( 'wp_ajax_remove_formfield', array($this,'remove_formfield'));
		
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
	
	function add_formfield(){
		/* print_array('Adding form field');
		print_array($_POST); */
		
		verify_nonce('add_form_element_nonce');
		
		if(!empty($_POST['formname'])){
			$this->datatype = $_POST['formname'];
			$this->submissiontable_name = $this->submissiontable_prefix."_".$this->datatype;
			$this->loadformdata();
		}else{
			wp_die('Invalid form name',500);
		}
	
		$user_id = $_POST['userid'];
		if(is_numeric($user_id)){
			$user 		= get_userdata($user_id);
			$user_roles = $user->roles;
		}else{
			wp_die('Invalid userid',500);
		}
		
		if(in_array('contentmanager',$user_roles)){
			$this->saveform_elements();
		}else{
			wp_die('You do not have the permission to do this',500);
		}
	}
	
	function saveform_elements(){
		//Store form results if submitted
		if(isset($_POST["formfield"])){
			$element		= $_POST["formfield"];
			
			if($element['type'] == 'php'){
				//we store the functionname in the html variable replace any double \ with a single \
				$functionname 					= str_replace('\\\\','\\',$element['functionname']);
				$element['name']			= $functionname;
				$element['functionname']	= $functionname;
				
				//only continue if the function exists
				if ( ! function_exists( $functionname ) ) wp_die("A function with name $functionname does not exist!",500);
			}
			
			if(in_array($element['type'], ['label','button'])){
				$element['name']	= $element['labeltext'];
			}elseif($element['name'] == ''){
				wp_die("Please enter a formfieldname",500);
			}

			$element['nicename']	= $element['name'];
			$element['name']		= str_replace(" ","_",strtolower(trim($element['name'])));

			if(
				in_array($element['type'],$this->non_inputs) 	and //this is a non-input
				$element['type'] != 'datalist'					and //but not a datalist
				strpos($element['name'],$element['type']) === false	// and the type is not yet added to the name 
			){
				$element['name']	.= '_'.$element['type'];
			}
			
			//Give an unique name
			if(strpos($element['name'], '[]') === false){
				$unique = false;
				
				$i = '';
				while($unique == false){
					if($i == ''){
						$fieldname = $element['name'];
					}else{
						$fieldname = "{$element['name']}_$i";
					}
					
					$unique = true;
					//loop over all elements to check if the names are equal
					foreach($this->formdata->elements as $index=>$field){
						//don't compare with itself
						if(is_numeric($_POST['update']) and $index == $_POST['update']) continue;
						//we found a duplicate name
						if($fieldname == $field['name']){
							$i++;
							$unique = false;
						}
					}
				}
				//update the name
				if($i != '') $element['name'] .= "_$i";
			}

			$element['infotext'] 	= wp_kses_post($element['infotext']);
			$element['infotext']	= $this->deslash($element['infotext']);
			
			if(is_numeric($_POST['update'])){
				$message								= "Succesfully updated '{$element['name']}'";
				$index									= $_POST['update'];
				//Add unique id again
				$element['id']						= $this->formdata->form_elements[$index]['id'];
				$this->formdata->form_elements[$index] 	= $element;
				$callback								= 'update_form_element';
			}else{
				$message								= "Succesfully added '{$element['name']}' to this form";
				
				//there is alreay a element counter defined
				if(is_numeric($this->formdata->element_counter)){
					$this->formdata->element_counter+=1;
				//no counter defined yet, start at 0
				}else{
					$this->formdata->element_counter = 0;
				}
				$element['id']						= $this->formdata->element_counter;

				$this->formdata->form_elements[]		= $element;
				$index									= count($this->formdata->form_elements)-1;
				$callback								= 'after_form_element_submit';
			}
			
			if(is_numeric($_POST['insertafter'])){
				$index = $_POST['insertafter']+1;
				$this->moveElement($this->formdata->form_elements,count($this->formdata->form_elements)-1,$index);
			}
			
			$html = $this->buildhtml($element, $index);

			$this->update_form_elements();
			
			echo json_encode([
				'message'		=> $message,
				'html'			=> $html,
				'formid'		=> $this->datatype,
				'callback'		=> $callback,
				'index'			=> $index,
			]);
			wp_die();
		}else{
			wp_die('You did not submit any fields',500);
		}
	}
	
	function moveElement(&$array, $a, $b) {
		if ($a == $b) return;
		//$a is $fromIndex and $b is $toIndex
		$out = array_splice($array, $a, 1);
		array_splice($array, $b, 0, $out);
	}
	
	function maybe_create_row(){
		global $wpdb;
		
		//check if form row already exists
		if($wpdb->get_var("SELECT * FROM {$this->table_name} WHERE `form_name` = '{$this->datatype}'") != true){
			//Create a new form row
			print_array("Inserting new form in db");
			
			$result = $wpdb->insert(
				$this->table_name, 
				array(
					'form_name'			=> $this->datatype,
					'form_elements' 	=> '',
					'form_conditions'	=> '',
					'form_version' 		=> 0,
					'element_counter'	=> 0
				)
			);
		}
	}
	
	function update_form_elements(){
		global $wpdb;
		
		$this->maybe_create_row();
		
		//Update the database
		$result = $wpdb->update($this->table_name, 
			array(
				'form_elements' 	=> maybe_serialize($this->formdata->form_elements),
				'form_conditions'	=> maybe_serialize($this->formdata->form_conditions),
				'form_version' 		=> $this->formdata->form_version+1,
				'element_counter'	=> $this->formdata->element_counter
			), 
			array(
				'form_name'		=> $this->datatype,
			),
		);
		
		if($wpdb->last_error !== ''){
			wp_die($wpdb->print_error(),500);
		}
	}
	
	function remove_formfield(){
		/* print_array('Removing form element');
		print_array($_POST); */
		
		verify_nonce('remove_form_element_nonce');
		
		if(!empty($_POST['formname'])){
			$this->datatype = $_POST['formname'];
			$this->submissiontable_name = $this->submissiontable_prefix."_".$this->datatype;
			$this->loadformdata();
		}else{
			wp_die('Invalid form name',500);
		}
	
		if(is_numeric($_POST['elementindex'])){
			global $wpdb;
			
			//store the fieldname for later use
			$fieldname = $this->formdata->form_elements[$_POST['elementindex']]['name'];
			
			//Remove element from elements array
			unset($this->formdata->form_elements[$_POST['elementindex']]);
			
			//Reorder array
			$this->formdata->form_elements = array_values($this->formdata->form_elements);
			
			//Update the database
			$this->update_form_elements();
			
			echo json_encode([
				'message'		=> "Succesfully removed element $fieldname",
				'formid'		=> $this->datatype,
				'elementindex'	=> $_POST['elementindex'],
				'callback'		=> 'removerow'
			]);
			wp_die();
		}else{
			wp_die('Invalid userid',500);
		}
	}
	
	function request_form_elements(){
		/* print_array('Requesting form elements');
		print_array($_POST); */
		
		verify_nonce('request_form_element_nonce');
		
		$element_index = $_POST['elementindex'];
		
		if(!is_numeric($element_index)) wp_die("Invalid element index given",500);
		
		$this->datatype = $_POST['formname'];
		$this->loadformdata();
		
		$condition_html = $this->element_conditions_form($element_index);
		
		echo json_encode([
			'message'			=> "",
			'formid'			=> $this->datatype,
			'element_id'		=> $element_index,
			'original'			=> $this->formdata->form_elements[$element_index],
			'callback'			=> 'prefillforminput',
			'conditions_html'	=> $condition_html
		]);
		wp_die();
	}
	
	function reorder_form_elements(){
		verify_nonce('remove_form_element_nonce');
		
		$element_index = $_POST['elementindex'];
		
		if(!is_numeric($element_index)) wp_die("Invalid element index given",500);
		
		$this->datatype = $_POST['formname'];
		$this->submissiontable_name = $this->submissiontable_prefix."_".$this->datatype;
		$this->loadformdata();
		
		if(is_numeric($_POST['newIndex'])){
			$this->moveElement($this->formdata->form_elements,$element_index,$_POST['newIndex']);
			
			//Update the database
			$this->update_form_elements();
			
			echo json_encode([
				'message'	=> "Succesfully saved new form order",
				'callback'	=> 'fixindexes',
				'formid'	=> $this->datatype,
			]);
			wp_die();
		}else{
			wp_die("Invalid to index given",500);
		}
	}
	
	function edit_formfield_width(){
		/*global $wpdb;
		print_array($_POST); */
		
		verify_nonce('remove_form_element_nonce');
		
		$element_index = $_POST['elementindex'];
		if(!is_numeric($element_index)) wp_die("Invalid element index given",500);
		
		$newwidth = $_POST['formfield']['width'];
		if(!is_numeric($newwidth)) wp_die("Invalid width given",500);
		$newwidth = min($newwidth,100);
		
		$this->datatype = $_POST['formname'];
		$this->submissiontable_name = $this->submissiontable_prefix."_".$this->datatype;
		$this->loadformdata();
		
		$old_width = $this->formdata->form_elements[$element_index]['with'];
		$this->formdata->form_elements[$element_index]['width']	= $newwidth;
		
		$html = $this->formdata->form_elements[$element_index]['html'];
		$html = str_replace("style='width:$old_width%;'","style='width:$newwidth%;'",$html);
		$this->formdata->form_elements[$element_index]['html'] = $html;
		
		//Update db
		$this->update_form_elements();
		
		echo json_encode([
			'message'	=> "Succesfully updated formelement width to $newwidth%",
			'formid'	=> $this->datatype,
			'callback'	=> 'hide_modals',
			'index'		=> $element_index
		]);
		wp_die();
	}

	function request_form_conditions_html(){
		verify_nonce('request_form_element_nonce');
		
		$element_index = $_POST['elementindex'];
		
		if(!is_numeric($element_index)) wp_die("Invalid element index given",500);
		
		$this->datatype = $_POST['formname'];
		$this->submissiontable_name = $this->submissiontable_prefix."_".$this->datatype;
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
		verify_nonce('element_condition_nonce');

		$element_index = $_POST['element_id'];
		if(!is_numeric($element_index)) wp_die("Invalid element index given",500);
		
		$this->datatype = $_POST['formname'];
		$this->submissiontable_name = $this->submissiontable_prefix."_".$this->datatype;
		$this->loadformdata();
		
		$element = $this->formdata->form_elements[$element_index];
		if(!is_array($this->formdata->form_conditions)) $this->formdata->form_conditions = [];
		
		$element_conditions	= $_POST['element_conditions'];
		if(empty($element_conditions)){
			unset($this->formdata->form_conditions[$element['id']]);
			
			$message = "Succesfully removed all conditions for {$element['name']}";
		}else{
			$this->formdata->form_conditions[$element['id']] = $element_conditions;
			
			$message = "Succesfully updated conditions for {$element['name']}";
		}
		
		//Create new js
		$this->create_js();
		
		//Update db
		$this->update_form_elements();
		
		wp_die(
			json_encode([
				'message'	=> $message,
				'formid'	=> $this->datatype,
				'index'		=> $element_index,
				'callback'	=> '',
			])
		);
	}

	function save_form_settings(){
		global $wpdb;
		
		/*print_array('saving form settings');
		print_array($_POST); */
		
		if(!empty($_POST['formname'])){
			$this->datatype = $_POST['formname'];
			$this->submissiontable_name = $this->submissiontable_prefix."_".$this->datatype;
			$this->loadformdata();
		}else{
			wp_die('Invalid form name',500);
		}
		
		$form_settings = $_POST['settings'];
		
		//remove double slashes
		$form_settings['upload_path']	= str_replace('\\\\','\\',$form_settings['upload_path']);
		
		$this->maybe_create_row();
		
		$wpdb->update($this->table_name, 
			array(
				'settings' 	=> maybe_serialize($form_settings)
			), 
			array(
				'form_name'		=> $this->datatype,
			),
		);
		
		if($wpdb->last_error !== ''){
			wp_die($wpdb->print_error(),500);
		}else{
			wp_die("Succesfully saved your form settings");
		}
	}
		
	function save_form_emails(){
		global $wpdb;
		
		//print_array($_POST);
		
		if(!empty($_POST['formname'])){
			$this->datatype = $_POST['formname'];
			$this->submissiontable_name = $this->submissiontable_prefix."_".$this->datatype;
			$this->loadformdata();
		}else{
			wp_die('Invalid form name',500);
		}
		
		$form_emails = $_POST['emails'];
		
		foreach($form_emails as $index=>$email){
			$form_emails[$index]['message'] = $this->deslash($email['message']);
		}
		
		$this->maybe_create_row();
		$wpdb->update($this->table_name, 
			array(
				'emails'	 	=> maybe_serialize($form_emails)
			), 
			array(
				'form_name'		=> $this->datatype,
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
		
		if(!empty($_POST['formname'])){
			$this->datatype = $_POST['formname'];
			$this->submissiontable_name = $this->submissiontable_prefix."_".$this->datatype;
			$this->loadformdata();
		}else{
			wp_die('Invalid form name',500);
		}
		
		if(is_numeric($_POST['userid'])){
			//If we have the right to save the form for someone else
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
		
		$this->formresults = apply_filters('before_saving_formdata',$this->formresults,$this->datatype,$this->user_id);
		
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
					'timecreated'		=> $creation_date,
					'timelastedited'	=> $creation_date,
					'userid'			=> $this->user_id,
					'formresults'		=> maybe_serialize($this->formresults),
					'archived'			=> false
				)
			);
			
			$this->send_email();
				
			if($wpdb->last_error !== ''){
				wp_die($wpdb->print_error(),500);
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
					remove_from_nested_array($result);
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
			$path		= url_to_path($url);
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
	
	function element_conditions_form($element_index = -1){
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
		
		$element_id	= $this->formdata->form_elements[$element_index]['id'];
		if($element_index == -1 or !is_array($this->formdata->form_conditions[$element_id])){
			if(!is_array($this->formdata->form_conditions)) $this->formdata->form_conditions = [];

			$dummyfieldcondition['rules'][0]["conditional_field"]	= "";
			$dummyfieldcondition['rules'][0]["equation"]				= "";
			$dummyfieldcondition['rules'][0]["conditional_field_2"]	= "";
			$dummyfieldcondition['rules'][0]["equation_2"]			= "";
			$dummyfieldcondition['rules'][0]["conditional_value"]	= "";
			$dummyfieldcondition["action"]					= "";
			$dummyfieldcondition["target_field"]			= "";
			
			if($element_index == -1) $element_index			= 0;
			$this->formdata->form_conditions[$element_id] = [$dummyfieldcondition];
		}
		
		$conditions = $this->formdata->form_conditions[$element_id];
		
		ob_start();
		$counter = 0;
		foreach($this->formdata->form_conditions as $key=>$condition){
			if(is_array($condition['copyto']) and in_array($element_id,$condition['copyto'])){
				$index	= $this->formdata->element_mapping[$key];
				$name	= str_replace('_',' ',$this->formdata->form_elements[$index]['name']);

				$counter++;
				?>
				<div class="form_element_wrapper" data-id="<?php echo $index;?>" data-formname="<?php echo $this->datatype;?>">
					<button type="button" class="edit_form_element button" title="Jump to conditions element">View conditions of '<?php echo $name;?>'</button>
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
			
			<input type='hidden' class='element_condition' name='formname' value='<?php echo $this->datatype;?>'>
			
			<input type='hidden' class='element_condition' name='element_id' value='<?php echo $element_index;?>'>
			
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
							echo $this->input_dropdown($rule['conditional_field'], $element_index);
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
								echo $this->input_dropdown($rule['conditional_field_2'],$element_index);
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
						<?php echo $this->input_dropdown($condition['property_value'], $element_index);?>
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
					foreach($this->formdata->form_elements as $key=>$element){
						//do not show the current element itself or wrapped labels
						if($key != $element_index and empty($element['wrap'])){
							if(!empty($conditions['copyto'][$element['id']])){
								$checked = 'checked';
							}else{
								$checked = '';
							}
							
							echo "<label>";
								echo "<input type='checkbox' name='element_conditions[copyto][{$element['id']}]' value='{$element['id']}' $checked>";
								echo ucfirst(str_replace('_',' ',$element['name']));
							echo "</label><br>";
						}
					}
					?>
				</div>
			</div>
			<?php
			echo add_save_button('submit_form_condition','Save conditions'); ?>
		</form>
		<?php
		$html = ob_get_clean();
		//print_array($html);
		return $html;
	}
}

add_action('init', function(){
	if(wp_doing_ajax()) new Formbuilder_Ajax();
});
