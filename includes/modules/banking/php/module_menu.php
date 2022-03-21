<?php
namespace SIM\BANKING;
use SIM;

const ModuleVersion		= '7.0.0';

//run on module activation
add_action('sim_module_activated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
}, 10, 2);

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module makes it possible to upload Account statements to the website.<br>
		These statements will be visible on the dashboard page of an user.<br>
		<br>
		This module adds one shortcode: <code>[account_statements]</code>
		<br>
		This module depends on the postie plugin and the user management module.<br>
	</p>
	<?php
},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
    ?>

	<?php
}, 10, 3);