<?php
namespace SIM\forms;
use SIM;

const ModuleVersion		= '7.0.10';

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
	<label for="reminder_freq">How often should people be reminded of remaining form fields to fill?</label>
	<br>
	<select name="reminder_freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['reminder_freq']);
		?>
	</select>

	<br>
	<label>
		Define the e-mail people get when they need to fill in some mandatory form information.<br>
		There is one e-mail to adults, and one to parents of children with missing info.<br>
	</label>
	<br>

	<?php
	$formAdultEmails    = new AdultEmail(wp_get_current_user());
	$formAdultEmails->printPlaceholders();
	?>

	<h4>E-mail to adults</h4>
	<?php

	$formAdultEmails->printInputs($settings);
	
	?>
	<br>
	<br>
	<h4>E-mail to parents about their child</h4>
	<?php

	$formAdultEmails    = new ChildEmail(wp_get_current_user());

	$formAdultEmails->printInputs($settings);
}, 10, 3);