<?php
namespace SIM\FILEUPLOAD;
use SIM;

const MODULE_VERSION		= '8.0.0';

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', __NAMESPACE__.'\subMenuDescription', 10, 2);
function subMenuDescription($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	$description	.= "Default module for custom AJAX file upload";

	return $description;
}