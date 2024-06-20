<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Multi default values used to prefil the compound dropdown
add_filter( 'sim_add_form_multi_defaults', function($defaultArrayValues, $userId, $formName){
	if($formName != 'user_location' || !defined('NIGERIASTATES')){
		return $defaultArrayValues;
	}

	$compounds = [];
	foreach ( NIGERIASTATES as $name=>$state ) {
		$compounds[$name] = str_replace('_',' ',$name);
	}
	$defaultArrayValues['compounds'] 			= $compounds;
	
	return $defaultArrayValues;
}, 10, 3);

//create birthday and anniversary events
add_filter('sim_before_saving_formdata',function($formResults, $object){
	if($object->formData->name != 'user_location'){
		return $formResults;
	}
	
	//Get the old values from the db
	$oldLocation = get_user_meta( $object->userId, 'location', true );
	
	//Get the location from the post array
	$location = $_POST["location"];
	
	//Only update when needed and if valid coordinates
	if(is_array($location) && $location != $oldLocation && !empty($location['latitude']) && !empty($location['longitude'])){
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
		
		SIM\updateFamilyMeta($object->userId, "location", $location);

		do_action('sim_location_update', $object->userId, $location);
		
		SIM\printArray("Saved location for user id $object->userId");
	}elseif(isset($_POST["location"]) && (empty($location['latitude']) || empty($location['longitude']))){
		//Remove location from db if empty
		delete_user_meta( $object->userId, 'location');
		SIM\printArray("Deleted location for user id $object->userId");

		do_action('sim_location_removal', $object->userId);
	}
	
	return $formResults;
}, 10, 2);