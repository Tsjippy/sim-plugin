<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Multi default values used to prefil the compound dropdown
add_filter( 'sim_add_form_multi_defaults', function($defaultArrayValues, $userId, $formName){
	if($formName != 'user_location') return $defaultArrayValues;

	$compounds = [];
	foreach ( NIGERIASTATES as $name=>$state ) {
		$compounds[$name] = str_replace('_',' ',$name);
	}
	$defaultArrayValues['compounds'] 			= $compounds;
	
	return $defaultArrayValues;
}, 10, 3);

//create birthday and anniversary events
add_filter('sim_before_saving_formdata',function($formResults, $formName, $userId){
	if($formName != 'user_location') return $formResults;
	
	//Get the old values from the db
	$oldLocation = get_user_meta( $userId, 'location', true );
	
	//Get the location from the post array
	$location = $_POST["location"];
	
	//Only update when needed and if valid coordinates
	if(is_array($location) and $location != $oldLocation and !empty($location['latitude']) and !empty($location['longitude'])){
		$latitude = $location['latitude'] = filter_var(
			$location['latitude'], 
			FILTER_SANITIZE_NUMBER_FLOAT,
			FILTER_FLAG_ALLOW_FRACTION
		);
		
		$location['longitude'] = filter_var(
			$location['longitude'], 
			FILTER_SANITIZE_NUMBER_FLOAT,
			FILTER_FLAG_ALLOW_FRACTION
		);
		
		$location['address'] = sanitize_text_field($location['address']);
		
		SIM\updateFamilyMeta($userId, "location", $location);

		do_action('sim_location_update', $userId, $location);
		
		SIM\printArray("Saved location for user id $userId");
	}elseif(isset($_POST["location"]) and (empty($location['latitude']) or empty($location['longitude']))){
		//Remove location from db if empty
		delete_user_meta( $userId, 'location');
		SIM\printArray("Deleted location for user id $userId");

		do_action('sim_location_removal', $userId);
	}
	
	return $formResults;
},10,3);