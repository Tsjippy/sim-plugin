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
 * @param	bool		$async			Whether to send the signal later
 * @param	int|array	$postId			Optional post id to add a link to or an array of filepaths of pictures
 *
 * @return	string						The result
*/
function trySendSignal($message, $recipient, $async=false, $postId=""){
	if (function_exists('SIM\SIGNAL\sendSignalMessage')) {
		if($async){
			SIGNAL\asyncSignalMessageSend($message, $recipient, $postId);
		}else{
			return SIGNAL\sendSignalMessage($message, $recipient, $postId);
		}
	}
}