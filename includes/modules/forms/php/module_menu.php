<?php
namespace SIM\forms;
use SIM;

const ModuleVersion		= '7.0.0';

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module adds 4 shortcodes:<br>
		<ol>
			<li>formbuilder</li>
			<li>formresults</li>
			<li>formselector</li>
			<li>missing_form_fields</li>
		</ol>
	</p>
	<h4>Formbuilder</h4>
	<p>
		This shortcode allows you to build a form with a unique name.<br>
		Use like this: <code>[formbuilder datatype=SOMENAME]</code>.<br>
		The datatype must supply a valid formname.<br>
	</p>
	<h4>Formresults</h4>
	<p>
		This shortcode allows you to display a form's results.<br>
		Use like this: <code>[formresults datatype=SOMENAME]</code>
	</p>
	<h4>Formselector</h4>
	<p>
		This shortcode will display a dropdown with all forms.<br>
		Upon selection of a form, the form will be displayed as well as the results of the form.
		You can exclude certain forms by using the 'exclude' key word.<br>
		Forms that save their submission to the usermeta table are exclude by default.<br>
		You can include them by using the 'no_meta' key word.<br>
		Use like this: <code>[formresults exclude="SOMENAME, SOMEOTHERNAME" no_meta="false"]</code>
	</p>
	<h4>Missing form fields</h4>
	<p>
		This shortcode allows you to display a list of all mandatory or recommended fields still to be filled in.<br>
		The type should be 'all', 'mandatory' or 'recommended'.<br>
		Use like this: <code>[missing_form_fields type="recommended"]</code>
	</p>
	<?php

},10,2);

add_action('sim_module_activated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	$formbuilder = new Formbuilder();
	$formbuilder->create_db_table();

	$formtable = new FormTable();
	$formtable->create_db_shortcode_table();

	schedule_tasks();
}, 10, 2);

add_action('sim_module_updated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	schedule_tasks();
}, 10, 2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<label for="reminder_freq">How often should people be reminded of remaining content to read</label>
	<br>
	<select name="reminder_freq">
		<option value=''>---</option>
		<option value='daily' <?php if($settings["reminder_freq"] == 'daily') echo 'selected';?>>Daily</option>
		<option value='weekly' <?php if($settings["reminder_freq"] == 'weekly') echo 'selected';?>>Weekly</option>
		<option value='monthly' <?php if($settings["reminder_freq"] == 'monthly') echo 'selected';?>>Monthly</option>
		<option value='threemonthly' <?php if($settings["reminder_freq"] == 'threemonthly') echo 'selected';?>>Every quarter</option>
		<option value='yearly' <?php if($settings["reminder_freq"] == 'yearly') echo 'selected';?>>Yearly</option>
	</select>
	<?php
}, 10, 3);