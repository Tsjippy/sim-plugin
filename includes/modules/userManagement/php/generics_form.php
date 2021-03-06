<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_filter('sim_forms_load_userdata',function($usermeta,$userId){
	$userdata	= (array)get_userdata($userId)->data;

	//Change ID to userid because its a confusing name
	$userdata['user_id']	= $userdata['ID'];
	unset($userdata['ID']);
	
	return array_merge($usermeta, $userdata);
},10,2);

//create  events
add_filter('sim_before_saving_formdata', function($formResults, $formName, $userId){
	if($formName != 'user_generics'){
		return $formResults;
	}
	
	if(class_exists('SIM\EVENTS\Events')){
		$events	= new SIM\EVENTS\CreateEvents();
		$events->createCelebrationEvent('birthday', $userId, 'birthday', $_POST['birthday']);
		$events->createCelebrationEvent(SITENAME.' anniversary', $userId,'arrival_date',$_POST['arrival_date']);
	}

	//check if phonenumber has changed
	$oldPhonenumbers	= (array)get_user_meta($userId, 'phonenumbers', true);
	$newPhonenumbers	= $_POST['phonenumbers'];
	$changedNumbers		= array_diff($newPhonenumbers, $oldPhonenumbers);
	$firstName			= get_userdata($userId)->first_name;
	foreach($changedNumbers as $key=>$changedNumber){
		$link		= SIM\getModuleOption('signal', 'group_link');

		// Make sure the phonenumber is in the right format
		# = should be +
		if($changedNumber[0] == '='){
			$changedNumber = $formResults['phonenumbers'][$key]	= str_replace('=', '+', $changedNumber);
		}

		# 00 should be +
		if(substr($changedNumber, 0, 2) == '00'){
			$changedNumber = $formResults['phonenumbers'][$key]	= '+'.substr($changedNumber, 2);
		}

		# 0 should be +234
		if($changedNumber[0] == '0'){
			$changedNumber = $formResults['phonenumbers'][$key]	= '+234'.substr($changedNumber, 1);
		}

		# Should start with + by now
		if($changedNumber[0] != '+'){
			$changedNumber = $formResults['phonenumbers'][$key]	= '+234'.$changedNumber;
		}

		$message	= "Hi $firstName\n\nI noticed you just updated your phonenumber on simnigeria.org.\n\nIf you want to join our Signal group with this number you can use this url:\n$link";
		SIM\trySendSignal($message, $changedNumber);
	}
	
	return $formResults;
},10,3);

//Add ministry modal
add_action('sim_before_form', function ($formName){
	if($formName != 'user_generics'){
		return;
	}
	?>
	<div id="add_ministry_modal" class="modal hidden">
		<!-- Modal content -->
		<div class="modal-content">
			<span id="modal_close" class="close">&times;</span>
			<form action="" method="post" id="add_ministry_form">
				<p>Please fill in the form to create a page describing your ministry and list it as an option</p>				
				
				<label>
					<h4>Ministry name<span class="required">*</span></h4>
					<input type="text" name="location_name" required>
				</label>
				
				<label>
					<h4>Address</h4>
					<input type="text" class="address" name="location[address]">
				</label>
				
				<label>
					<h4>Latitude</h4>
					<input type="text" class="latitude" name="location[latitude]">
				</label>
				
				<label>
					<h4>Longitude</h4>
					<input type="text" class="longitude" name="location[longitude]">
				</label>
				
				<?php echo SIM\addSaveButton('add_ministry','Add ministry page'); ?>
			</form>
		</div>
	</div>
	<?php
});

/**
 * Get all locations with the ministries category
 * 
 * @return	array	Ministries list
 */
function getMinistries(){	
	//Get all pages describing a ministry
	$ministryPages = get_posts([
		'post_type'			=> 'location',
		'posts_per_page'	=> -1,
		'post_status'		=> 'publish',
		'tax_query' => array(
            array(
                'taxonomy'	=> 'locations',
				'field' => 'term_id',
				'terms' => get_term_by('name', 'Ministries', 'locations')->term_id
            )
        )
	]);
	$ministries = [];
	foreach ( $ministryPages as $ministryPage ) {
		$ministries[] = $ministryPage->post_title;
	}
	//Sort in alphabetical order
	asort($ministries);
	$ministries[] 			= "Other";
	
	return $ministries;
}

/**
 * display ministries defined as php function in generics form
 * 
 * @param	int		$userId		WP_User id
 * 
 * @return	srtring				html
 */
function displayMinistryPositions($userId){
	$userMinistries 	= (array)get_user_meta( $userId, "user_ministries", true);
	
	ob_start();
	?>
	<div id="ministries_list">
	<?php		
		//Retrieve all the ministries from the database
		foreach (getMinistries() as $ministry) {
			$ministryName = str_replace(" ", "_", $ministry);
			//Check which option should be a checked ministry
			if (!empty($userMinistries[$ministryName])){
				$checked	= 'checked';
				$class		= '';
				$position	= $userMinistries[$ministryName];
			}else{
				$checked	= '';
				$class		= 'hidden';
				$position	= "";
			}
			//Add the ministries as options to the checkbox
			?>
			<span>
				<label>
					<input type='checkbox' class='ministry_option_checkbox' name='ministries[]' value='<?php echo $ministryName;?>' <?php echo $checked;?>>
					<span class='optionlabel'><?php echo $ministry;?></span>
				</label>
				<label class='ministryposition <?php echo $class;?>' style='display:block;'>
					<h4 class='labeltext'>Position at <?php echo $ministry;?>:</h4>
					<input type='text' name='user_ministries[<?php echo $ministryName;?>]' value='<?php echo $position;?>'>
					<?php
					if ($ministryName == "Other"){
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