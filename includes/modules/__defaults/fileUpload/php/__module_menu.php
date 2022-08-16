<?php
namespace SIM\FILEUPLOAD;
use SIM;

const MODULE_VERSION		= '7.0.1';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	$description	.= "Default module for custom AJAX file upload";

	return $description;
}, 10, 2);