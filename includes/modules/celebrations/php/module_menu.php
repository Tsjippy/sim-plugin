<?php
namespace SIM;

//if this module is enabled, enable events as well
if($_POST['module'] == 'celebrations' and isset($_POST['enable'])){
	global $Modules;
	if(!isset($Modules['extra_post_types']['events'])){
		$Modules['extra_post_types']['events']='on';
		update_option('sim_modules', $Modules);
	}
}

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if( $module_slug != basename(dirname(dirname(__FILE__))))	return;    
}, 10, 3);