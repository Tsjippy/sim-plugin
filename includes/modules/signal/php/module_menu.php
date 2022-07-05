<?php
namespace SIM\SIGNAL;
use SIM;

const MODULE_VERSION		= '7.0.0';
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
		This module adds the possibility to send Signal messages to users or groups.<br>
		An external Signal Bot can read and delete these messages and send them on Signal.<br>
		<br>
		Add one shortcode: 'signal_messages'.<br>
		Use this shortcode to display all pending Signal messages and delete them if needed.<br>
		<br>
		Adds 2 urls to the rest api:<br>
		sim/v1/notifications to get any other messages<br>
		sim/v1/firstname to find a users name by phonenumber<br>
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
	
	?>
	<label>
		Link to join the Signal group
		<input type='url' name='group_link' value='<?php echo $settings["group_link"]; ?>' style='width:100%'>
	</label>
	<?php

	return ob_get_clean();
}, 10, 3);