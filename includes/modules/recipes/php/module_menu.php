<?php
namespace SIM\RECIPES;
use SIM;

const MODULE_VERSION		= '7.0.1';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
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
	return ob_get_clean();
},10,2);