<?php
namespace SIM;

/**
 * Retrievs the value of a certain module setting
 * @param	string 	$moduleName		The module name'
 * @param	string	$option			The option name
 * @param	string	$returnBoolean	True to return false on not found, false to return an empty array in that case
 *
 * @return	array|string|false			The option value or false if option is not found
*/
function getModuleOption($moduleName, $option, $returnBoolean=true){
	global $Modules;

	$moduleName	= strtolower($moduleName);

	// For backwards compatibility
	if(empty($Modules[$moduleName][$option])){
		if(str_contains($option, '_')){
			printArray("Please update '$option'");
		}
		$option	= str_replace('_', '-', $option);
	}

	if(!empty($Modules[$moduleName][$option])){
		return $Modules[$moduleName][$option];
	}elseif($returnBoolean){
		return false;
	}else{
		return [];
	}
}


/**
 * Removes a certain module setting
 * @param	string 	$moduleName		The module name'
 */
function removeModuleOption($moduleName, $option=''){
	global $Modules;

	if(!empty($Modules[$moduleName])){
		if($option == ''){
			unset($Modules[$moduleName]);	
		}else{
			unset($Modules[$moduleName][$option]);
		}

		update_option('sim_modules', $Modules);
	}
}

/**
 * Update module settings
 * @param	string 	$moduleName		The module name'
 * @param	array	$options		The options to set
 * 
 * @return	array	The updated options
 */
function updateModuleOptions($moduleName, $options, $optionName=''){
	global $Modules;

	if($optionName	==''){
		$Modules[$moduleName]	= $options;
	}else{
		$Modules[$moduleName][$optionName]	= $options;
	}

	update_option('sim_modules', $Modules);

	return $Modules[$moduleName];
}

function maybeGetUserPageId($userId){
    $userPageId	= false;

    if(function_exists('SIM\USERPAGES\getUserPageId')){
        $userPageId = USERPAGES\getUserPageId($userId);
    }

    return $userPageId;
}

function maybeGetUserPageUrl($userId){
	$url	= false;

	if(function_exists('SIM\USERPAGES\getUserPageUrl')){
		$url = USERPAGES\getUserPageUrl($userId);
	}

	return $url;
}