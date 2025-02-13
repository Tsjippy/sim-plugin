<?php
namespace SIM\FILEUPLOAD;
use SIM;

const MODULE_VERSION		= '8.0.0';

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_fileupload_description', __NAMESPACE__.'\subMenuDescription');
function subMenuDescription($description){
	$description	.= "Default module for custom AJAX file upload";

	return $description;
}