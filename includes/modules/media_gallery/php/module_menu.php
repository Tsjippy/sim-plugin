<?php
namespace SIM\MEDIAGALLERY;
use SIM;

const ModuleVersion		= '7.0.16';

//run on module activation
add_action('sim_module_activated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
}, 10, 2);

add_filter('sim_module_updated', function($options, $module_slug){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return $options;

	schedule_tasks();

	return $options;
}, 10, 2);

//run on module deactivation
add_action('sim_module_deactivated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
}, 10, 2);

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>

	<?php
},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
    ?>

	<?php
}, 10, 3);