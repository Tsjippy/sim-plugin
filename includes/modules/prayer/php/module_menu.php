<?php
namespace SIM\PRAYER;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

//run on module activation
add_action('sim_module_activated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	wp_create_category('Prayer');
});

add_action('sim_submenu_description', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

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

});