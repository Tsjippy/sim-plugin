<?php
namespace SIM\PRAYER;
use SIM;

const ModuleVersion		= '7.0.0';

//run on module activation
add_action('sim_module_activated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	wp_create_category('Prayer');
}, 10, 2);

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module adds 1 url to the rest api:<br>
		sim/v1/prayermessage to get the current prayer request<br>
		It also adds 1 post category: 'Prayer'<br>
		You should add a new post with the prayer category each month.
		This post should have a prayer request for each day on seperate lines.<br>
		The lines should have this format: '1(T) – '<br>
		So an example will look like this:<br>
		<code>
			1(M) – Prayer for day 1<br>
			2(T) – Prayer for day 2
		</code>
		<br>
		<br>
		If such a post is available the daily prayerrequest will be displayed on the homepage and will be available via the rest-api.<br>

	</p>
	<?php

},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
    
}, 10, 3);