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