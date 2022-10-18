<?php
namespace SIM;

/**
 * Retrievs the value of a certain module setting
 * @param	string 	$moduleName		The module name'
 * @param	string	$option			The option name
 *
 * @return	array|string|false			The option value or false if option is not found
*/
function getModuleOption($moduleName, $option, $return='boolean'){
	global $Modules;

	if(!empty($Modules[$moduleName][$option])){
		return $Modules[$moduleName][$option];
	}elseif($return == 'boolean'){
		return false;
	}else{
		return [];
	}
}

function maybeGetUserPageId($userId){
    $userPageId	= false;

    if(function_exists('SIM\USERPAGE\getUserPageId')){
        $userPageId = USERPAGE\getUserPageId($userId);
    }

    return $userPageId;
}

function maybeGetUserPageUrl($userId){
	$url	= false;

	if(function_exists('SIM\USERPAGE\getUserPageUrl')){
		$url = USERPAGE\getUserPageUrl($userId);
	}

	return $url;
}

/**
 * Checks if Signal module is enabled and if so sends the message
 * @param	string 		$message		The module name'
 * @param	int|WP_User	$recipient		The user or user id the message should be send to
 * @param	int			$postId			Optional post id to add a link to
*/
function trySendSignal($message, $recipient, $postId=""){
	if (function_exists('SIM\SIGNAL\sendSignalMessage')) {
		SIGNAL\sendSignalMessage($message, $recipient, $postId);
	}
}