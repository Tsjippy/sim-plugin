<?php
namespace SIM\forms;
use SIM;

const MODULE_VERSION		= '7.0.30';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

add_action('sim_submenu_description', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	if(isset($_POST['import-form'])){
		$formBuilder	= new FormBuilder();
		$formBuilder->importForm($_FILES['formfile']['tmp_name']);
	}

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
		Use like this: <code>[formbuilder formname=SOMENAME]</code>.<br>
		The formname must supply a valid formname.<br>
	</p>
	<h4>Formresults</h4>
	<p>
		This shortcode allows you to display a form's results.<br>
		Use like this: <code>[formresults formname=SOMENAME]</code>
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

	<h4>Form import</h4>
	<p>
		It is possible to import forms exported from this plugin previously.<br>
		Use the button below to do so.
	</p>
	<form method='POST' enctype="multipart/form-data">
		<label>
			Select a form export file
			<input type='file' name='formfile'>
		</label>
		<br>
		<button type='submit' name='import-form'>Import the form</button>
	</form>
	<br><br>
	<?php

});

add_action('sim_module_activated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}
	
	$formBuilder = new Formbuilder();
	$formBuilder->createDbTable();

	$formTable = new FormTable();
	$formTable->createDbShortcodeTable();

	scheduleTasks();
});

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	scheduleTasks();

	return $options;
}, 10, 2);

add_action('sim_submenu_options', function($moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return;
	}

	if(!class_exists(__NAMESPACE__.'\AdultEmail')){
		require_once(__DIR__.'/class_Emails.php');
	}

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
}, 10, 2);