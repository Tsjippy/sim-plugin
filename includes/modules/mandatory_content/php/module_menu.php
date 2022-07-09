<?php
namespace SIM\MANDATORY;
use SIM;

const MODULE_VERSION		= '7.0.6';
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
		This module adds the possibility to make certain posts and pages mandatory.<br>
		That means people have to mark the content as read.<br>
		If they do not do so they will be reminded to read it until they do.<br>
		A "I have read this" button will be automatically added to the e-mail if it is send by mailchimp.<br>
		<br>
		Adds one shortcode 'must_read_documents', which displays the pages to be read as links.<br>
		Use like this <code>[must_read_documents]</code>.<br>
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
	<label for="reminder_freq">How often should people be reminded of remaining content to read</label>
	<br>
	<select name="reminder_freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['reminder_freq']);
		?>
	</select>
	<?php

	return ob_get_clean();
}, 10, 3);

add_filter('sim_email_settings', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
    ?>
	<h4>E-mail with read reminders</h4>
	<label>Define the e-mail people get when they shour read some mandatory content.</label>
	<?php
	$readReminder    = new ReadReminder(wp_get_current_user());
	$readReminder->printPlaceholders();
	$readReminder->printInputs($settings);

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	scheduleTasks();

	return $options;
}, 10, 2);