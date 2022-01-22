<?php
namespace SIM;

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	echo '<div>No special settings needed for this module</div>';
}, 10, 3);
