<?php
namespace SIM\RECIPES;
use SIM;

const ModuleVersion		= '7.0.1';

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module adds a custom post type 'recipes'.<br>
		This is usefull for sharing food recipes with each other so people can adjust a little easier to the foreign food.<br>
	</p>
	<p>
		<b>Auto created page:</b><br>
		<a href='<?php echo home_url('/recipes');?>'>Recipes</a><br>
	</p>
	<?php

},10,2);


add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;    
}, 10, 3);