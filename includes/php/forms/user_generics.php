<?php
namespace SIM;

add_filter('forms_load_userdata',function($usermeta,$user_id){
	$userdata	= (array)get_userdata($user_id)->data;

	//Change ID to userid because its a confusing name
	$userdata['user_id']	= $userdata['ID'];
	unset($userdata['ID']);
	
	return array_merge($usermeta,$userdata);
},10,2);

//create birthday and anniversary events
add_filter('before_saving_formdata',function($formresults, $formname, $user_id){
	global $Events;
	if($formname != 'user_generics') return $formresults;
	
	$Events->create_celebration_event('birthday', $user_id, 'birthday', $_POST['birthday']);
	$Events->create_celebration_event('SIM Nigeria anniversary', $user_id,'arrival_date',$_POST['arrival_date']);

	//check if phonenumber has changed
	$old_phonenumbers	= get_user_meta($user_id, 'phonenumbers', true);
	$new_phonenumbers	= $_POST['phonenumbers'];
	$changed_numbers	= array_diff($new_phonenumbers, $old_phonenumbers);
	$first_name			= get_userdata($user_id)->first_name;
	foreach($changed_numbers as $changed_number){
		global $SignalGroupLink;
		$message	= "Hi $first_name\n\nI noticed you just updated your phonenumber on simnigeria.org.\n\nIf you want to join our Signal group with this number you can use this url:\n$SignalGroupLink";
		send_signal_message($message, $changed_number);
	}
	
	return $formresults;
},10,3);

//add new ministry location via AJAX
add_action ( 'wp_ajax_add_ministry', function(){
	global $MinistryCategoryID;
	
	verify_nonce('add_ministry_nonce');
	
	if (!empty($_POST["location_name"])){
		//Get the post data
		$name = sanitize_text_field($_POST["location_name"]);
		
		//Build the compound page
		$ministry_page = array(
		  'post_title'    => ucfirst($name),
		  'post_content'  => '',
		  'post_status'   => 'publish',
		  'post_type'	  => 'location',
		  'post_author'	  => get_current_user_id(),
		);
		 
		//Insert the page
		$post_id = wp_insert_post( $ministry_page );
		
		//Add the ministry cat
		wp_set_post_terms($post_id ,27,'locationtype');
		
		//Store the ministry location
		if ($post_id != 0){
			//Add the location to the page
			save_location_meta($post_id, 'location');
		}
	
		$url = get_permalink($post_id);
		wp_die(json_encode(
			[
				'message'	=> "Succesfully created new ministry page, see it <a href='$url'>here</a>",
				'callback'	=> 'add_new_ministry_data'
			]
		));
	}else{
		wp_die("Please specify a ministry name",500);
	}
});

//Add ministry modal
add_action('before_form',function ($formname){
	if($formname != 'user_generics') return;
	?>
	<div id="add_ministry_modal" class="modal hidden">
		<!-- Modal content -->
		<div class="modal-content">
			<span id="modal_close" class="close">&times;</span>
			<form action="" method="post" id="add_ministry_form">
				<p>Please fill in the form to create a page describing your ministry and list it as an option</p>				
				<input type="hidden" name="action"				value = "add_ministry">
				<input type="hidden" name="add_ministry_nonce"	value="<?php echo wp_create_nonce("add_ministry_nonce"); ?>">
				
				<label>
					<h4>Ministry name<span class="required">*</span></h4>
					<input type="text" name="location_name" required>
				</label>
				
				<label>
					<h4>Address</h4>
					<input type="text" class="address" name="location[address]">
				</label>
				
				<label>
					<h4>Latitude<h5>
					<input type="text" class="latitude" name="location[latitude]">
				</label>
				
				<label>
					<h4>Longitude</h4>
					<input type="text" class="longitude" name="location[longitude]">
				</label>
				
				<?php echo add_save_button('add_ministry','Add ministry page'); ?>
			</form>
		</div>
	</div>
	<?php
});

//display ministries
function display_ministry_positions($user_id){
	$user_ministries 	= (array)get_user_meta( $user_id, "user_ministries", true);
	
	ob_start();
	?>
	<div id="ministries_list">
	<?php		
		//Retrieve all the ministries from the database
		foreach (get_ministries() as $ministry) {
			$ministry_name = str_replace(" ","_",$ministry);
			//Check which option should be a checked ministry
			if (!empty($user_ministries[$ministry_name])){
				$checked='checked';
				$class = '';
				$position = $user_ministries[$ministry_name];
			}else{
				$checked='';
				$class = 'hidden';
				$position = "";
			}
			//Add the ministries as options to the checkbox
			?>
			<span>
				<label>
					<input type='checkbox' class='ministry_option_checkbox' name='ministries[]' value='<?php echo $ministry_name;?>' <?php echo $checked;?>>
					<span class='optionlabel'><?php echo $ministry;?></span>
				</label>
				<label class='ministryposition <?php echo $class;?>' style='display:block;'>
					<h4 class='labeltext'>Position at <?php echo $ministry;?>:</h4>
					<input type='text' name='user_ministries[<?php echo $ministry_name;?>]' value='<?php echo $position;?>'>
					<?php
					if ($ministry_name == "Other"){
						?>
						<p>Is your ministry not listed? Just add it! <button type='button' class='button' id='add-ministry-button'>Add Ministry</button></p>
						<?php
					}
					?>
				</label>
			</span>
			<br>
			<?php
		}
	?>
	</div>
	<?php
	
	return ob_get_clean();
}