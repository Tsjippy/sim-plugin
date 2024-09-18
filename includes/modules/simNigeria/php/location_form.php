<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Multi default values used to prefil the location dropdown
add_filter( 'sim_add_form_multi_defaults', function($defaultArrayValues, $userId, $formName){
	if($formName != 'user_location' || !defined('NIGERIASTATES')){
		return $defaultArrayValues;
	}

	$states = [];
	foreach ( NIGERIASTATES as $name=>$state ) {
		$states[$name] = str_replace('_',' ',$name);
	}
	$defaultArrayValues['states'] 			= $states;
	
	return $defaultArrayValues;
}, 10, 3);