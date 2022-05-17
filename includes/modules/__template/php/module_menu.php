<?php
namespace SIM\FORMS;
use SIM;

const ModuleVersion		= '7.0.0';

//run on module deactivation
add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;
});

//run on module activation
add_action('sim_module_activated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;
}, 10, 2);

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return $options;

	schedule_tasks();

	return $options;
}, 10, 2);

//run on module deactivation
add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;
}, 10, 2);

add_action('sim_submenu_description', function($moduleSlug, $moduleName){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	?>

	<?php
},10,2);

add_action('sim_submenu_options', function($moduleSlug, $moduleName, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;
	
    ?>

	<?php
}, 10, 3);