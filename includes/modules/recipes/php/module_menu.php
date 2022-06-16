<?php
namespace SIM\RECIPES;
use SIM;

const MODULE_VERSION		= '7.0.1';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

add_action('sim_submenu_description', function($moduleSlug, $moduleName){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

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


add_action('sim_submenu_options', function($moduleSlug, $moduleName, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}    
}, 10, 3);