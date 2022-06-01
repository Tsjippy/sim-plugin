<?php
namespace SIM\SIMNIGERIA;
use SIM;

const ModuleVersion		= '7.0.2';

add_action('sim_submenu_description', function($moduleSlug, $moduleName){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module adds all code specific to SIM Nigeria.
	</p>
	<?php

},10,2);

add_action('sim_submenu_options', function($moduleSlug, $moduleName, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;
	
    SIM\pictureSelector('tc_signature',  'Signature of the travel coordinator', $settings);

	?>
	<label>How often should we send the contact list</label>
	<select name="freq">
		<?php
		SIM\ADMIN\recurrenceSelector($settings['freq']);
		?>
	</select>
	<br>
	<br>
	<h4>E-mail the contactlist</h4>
	<label>Define the e-mail people get whith the contactlist attached</label>
	<?php
	$contactList    = new ContactList(wp_get_current_user());
	$contactList->printPlaceholders();
	$contactList->printInputs($settings);
}, 10, 3);

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return $options;

	scheduleTasks();

	return $options;
}, 10, 2);