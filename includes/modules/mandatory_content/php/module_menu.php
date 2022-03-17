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
	<label for="reminder_freq">How often should people be reminded of remaining content to read</label>
	<br>
	<select name="reminder_freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['reminder_freq']);
		?>
	</select>
	<br>
	<br>
	<h4>E-mail with read reminders</h4>
	<label>Define the e-mail people get when they shour read some mandatory content.</label>
	<?php
	$readReminder    = new ReadReminder(wp_get_current_user());
	$readReminder->printPlaceholders();
	$readReminder->printInputs($settings);
}, 10, 3);

add_action('sim_module_updated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	schedule_tasks();
}, 10, 2);