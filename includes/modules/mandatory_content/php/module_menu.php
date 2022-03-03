<?php
namespace SIM\MANDATORY;
use SIM;

const ModuleVersion		= '7.0.0';

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module adds the possibility to make certain posts and pages mandatory.<br>
		That means people have to mark the content as read.<br>
		If they do not do so they will be reminded to read it until they do.<br>
		A "I have read this" button will be automatically added to the e-mail if it is send by mailchimp.<br>
		<br>
		Adds one shortcode 'must_read_documents', which displays the pages to be read as links.<br>
		Use like this <code>[must_read_documents]</code>.<br>
	</p>
	<?php

},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
    ?>
	<label for="warning_freq">How often should people be reminded of remaining required fields</label>
	<br>
	<select name="warning_freq">
		<option value=''>---</option>
		<option value='daily' <?php if($settings["warning_freq"] == 'daily') echo 'selected';?>>Daily</option>
		<option value='weekly' <?php if($settings["warning_freq"] == 'weekly') echo 'selected';?>>Weekly</option>
		<option value='monthly' <?php if($settings["warning_freq"] == 'monthly') echo 'selected';?>>Monthly</option>
		<option value='threemonthly' <?php if($settings["warning_freq"] == 'threemonthly') echo 'selected';?>>Every quarter</option>
		<option value='yearly' <?php if($settings["warning_freq"] == 'yearly') echo 'selected';?>>Yearly</option>
	</select>
	<?php
}, 10, 3);