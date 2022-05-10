<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Multi default values used to prefil the compound dropdown
add_filter( 'add_form_multi_defaults', function($default_array_values, $user_id, $formname){
	if($formname != 'user_location') return $default_array_values;

	$compounds = [];
	foreach ( NIGERIASTATES as $name=>$state ) {
		$compounds[$name] = str_replace('_',' ',$name);
	}
	$default_array_values['compounds'] 			= $compounds;
	
	return $default_array_values;
},10,3);

//create birthday and anniversary events
add_filter('before_saving_formdata',function($formresults, $formname, $user_id){
	if($formname != 'user_location') return $formresults;

	global $wpdb;
	
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

		do_action('sim_location_update', $user_id, $location);
		
		SIM\print_array("Saved location for user id $user_id");
	}elseif(isset($_POST["location"]) and (empty($location['latitude']) or empty($location['longitude']))){
		//Remove location from db if empty
		delete_user_meta( $user_id, 'location');
		SIM\print_array("Deleted location for user id $user_id");

		do_action('sim_location_removal', $user_id);
	}
	
	return $formresults;
},10,3);