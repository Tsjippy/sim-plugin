<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Multi default values used to prefil the compound dropdown
add_filter( 'add_form_multi_defaults', function($default_array_values, $user_id, $formname){
	if($formname != 'user_location') return $default_array_values;

	global $NigeriaStates;
	
	$compounds = [];
	foreach ( $NigeriaStates as $name=>$state ) {
		$compounds[$name] = str_replace('_',' ',$name);
	}
	$default_array_values['compounds'] 			= $compounds;
	
	return $default_array_values;
},10,3);

//create birthday and anniversary events
add_filter('before_saving_formdata',function($formresults, $formname, $user_id){
	if($formname != 'user_location') return $formresults;

	global $wpdb;
	global $Maps;
	
	//Get the old values from the db
	$old_location = get_user_meta( $user_id, 'location', true );
	
	//Get the location from the post array
	$location = $_POST["location"];
	
	//Only update when needed and if valid coordinates
	if(is_array($location) and $location != $old_location and !empty($location['latitude']) and !empty($location['longitude'])){
		$latitude = $location['latitude'] = filter_var(
			$location['latitude'], 
			FILTER_SANITIZE_NUMBER_FLOAT,
			FILTER_FLAG_ALLOW_FRACTION
		);
		
		$longitude = $location['longitude'] = filter_var(
			$location['longitude'], 
			FILTER_SANITIZE_NUMBER_FLOAT,
			FILTER_FLAG_ALLOW_FRACTION
		);
		
		$location['address'] = sanitize_text_field($location['address']);
		
		SIM\update_family_meta($user_id, "location", $location);
		
		//Update mailchimp tags if needed
		$Mailchimp = new SIM\MAILCHIMP\Mailchimp($user_id);
		if(strpos(strtolower($location['address']),'jos') !== false){
			//Live in Jos, add the tags
			$Mailchimp->update_family_tags(['Jos'], 'active');
			$Mailchimp->update_family_tags(['not-Jos'], 'inactive');
		}else{
			$Mailchimp->update_family_tags(['Jos'], 'inactive');
			$Mailchimp->update_family_tags(['not-Jos'], 'active');
		}
		
		//Get any existing marker id from the db
		$marker_id = get_user_meta($user_id,"marker_id",true);
		if (is_numeric($marker_id)){
			//Retrieve the marker icon id from the db
			$query = $wpdb->prepare("SELECT icon FROM {$wpdb->prefix}ums_markers WHERE id = %d ", $marker_id);
			$marker_icon_id = $wpdb->get_var($query);
			
			//Set the marker_id to null if not found in the db
			if($marker_icon_id == null) $marker_id = null;
		}
	
		//Marker does not exist, create it
		if (!is_numeric($marker_id)){			
			//Create a marker
			$Maps->create_marker($user_id, $location);
		//Marker needs an update
		}else{
			$Maps->update_marker_location($marker_id, $location);
		}
		
		SIM\print_array("Saved location for user id $user_id");
	}elseif(isset($_POST["location"]) and (empty($location['latitude']) or empty($location['longitude']))){
		//Remove location from db if empty
		delete_user_meta( $user_id, 'location');
		delete_user_meta( $user_id, 'marker_id');
		SIM\print_array("Deleted location for user id $user_id");
		//Delete the marker as well
		$Maps->remove_personal_marker($user_id);
	}
	
	return $formresults;
},10,3);

add_action ( 'wp_ajax_add_compound', function(){
	global $CompoundCategoryID;
	
	//print_array($_POST,true);
	
	if(empty($_POST['location_name'])) wp_die("Please give a compound name",500);
	if(empty($_POST['userid']) or !is_numeric($_POST['userid'])) wp_die("Could not find user id",500);
	
	SIM\verify_nonce('add_compound_nonce');
	
	// Insert the post into the database.
	$post_id = wp_insert_post([
		'post_title'    => sanitize_text_field($_POST['location_name']),
		'post_content'  => '',
		'post_status'   => 'publish',
		'post_author'   => $_POST['userid'],
		'post_type'		=> 'location',
	]);
	
	//Save the location
	if($post_id != 0){		
		//Add the compound cat
		wp_set_post_terms($post_id ,$CompoundCategoryID,'locationtype');
		
		//Add the address and the maps
		SIM\LOCATIONS\save_location_meta($post_id, 'location');
		
		$url = get_permalink($post_id);
		
		wp_die(json_encode(
			[
				'message'	=> "Succesfully created new compound page see it <a href='$url'>here</a>",
				'callback'	=> 'add_new_compound_data'
			]
		));
	}else{
		wp_die("Page creaion failed",500);
	}
});

//Add compound modal
add_action('before_form',function ($formname){
	if($formname != 'user_location') return;
	global $CompoundIconID;

	?>
	<div id="add_compound_modal" class="modal hidden">
		<!-- Modal content -->
		<div class="modal-content">
			<span id="modal_close" class="close">&times;</span>
			<form action="" method="post" id="add_compound_form">
				<div style="display: none;" class="error modal-warning" id="add-compound-warning">Longitude is a required field!</div>
				<p>Please fill in the form to add a new compound to the list</p>
				<input type="hidden" name="action"				value = "add_compound">
				<input type="hidden" name="location_type"		value = "<?php echo $CompoundIconID; ?>">			
				<input type="hidden" name="add_compound_nonce"	value = "<?php echo wp_create_nonce("add_compound_nonce"); ?>">
				
				<label>
					<div>Compound name<span class="required">*</span></div>
					<input type="text"  name="location_name">
				</label>
				
				<label>
					<div>Address</div>
					<input type="text" class="address" name="location[address]">
				</label>
				
				<label>
					<div>Latitude</div>
					<input type="text" class="latitude" name="location[latitude]">
				</label>
				
				<label>
					<div>Longitude</div>
					<input type="text" class="longitude" name="location[longitude]">
				</label>
				
				<?php echo SIM\add_save_button('add_compound','Add compound'); ?>
			</form>
		</div>
	</div>
	<?php
});
