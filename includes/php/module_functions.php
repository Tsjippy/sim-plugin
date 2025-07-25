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

	if(!empty($Modules[$moduleName][$option])){
		return $Modules[$moduleName][$option];
	}elseif($returnBoolean){
		return false;
	}else{
		return [];
	}
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