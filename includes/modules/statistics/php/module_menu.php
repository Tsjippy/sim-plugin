<?php
namespace SIM\STATISTICS;
use SIM;

const MODULE_VERSION		= '7.0.3';
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
		This module stores page visits per user in the db.<br>
	</p>
	<?php

	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
	global $wp_roles;
	?>
    <label>Who should see the statistics?</label><br>
	<?php
	foreach($wp_roles->role_names as $key=>$name){
		if(in_array($key, $settings['view_rights'])){
			$checked = 'checked';
		}else{
			$checked = '';
		}
		echo "<input type='checkbox' name='view_rights[]' value='$key' $checked> $name<br>";
	}

	return ob_get_clean();
}, 10, 3);

add_action('sim_module_activated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}
	
	$statistics = new Statistics();
	$statistics->createDbTable();
});