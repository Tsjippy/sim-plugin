<?php
namespace SIM\STATISTICS;
use SIM;

const ModuleVersion		= '7.0.3';

add_action('sim_submenu_description', function($moduleSlug, $moduleName){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module stores page visits per user in the db.<br>
	</p>
	<?php

},10,2);

add_action('sim_submenu_options', function($moduleSlug, $moduleName, $settings){
	global $wp_roles;

	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;
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
}, 10, 3);

add_action('sim_module_activated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;
	
	$statistics = new Statistics();
	$statistics->createDbTable();
}, 10, 2);