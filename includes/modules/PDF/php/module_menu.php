<?php
namespace SIM\PDF;
use SIM;

const ModuleVersion		= '7.0.0';

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module adds the possibility to have a print button on pages or posts.<br>
		This will print the webpage to a PDF document.<br>
		It also adds the option for full screen PDF display.<br>
		Both options can be turned on or off below.<br>
	</p>
	<?php

},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
    ?>
	<label>
		<input type='checkbox' name='full_screen' <?php if(isset($settings['full_screen'])) echo 'checked';?>>
		Show PDF documents full screen if that is the only page content
	</label>
	<br>
	<br>
	<label>
		<input type='checkbox' name='pdf_print' <?php if(isset($settings['pdf_print'])) echo 'checked';?>>
		Add a "Print to PDF" button option
	</label>
	<br>
	<br>
	<?php
	SIM\picture_selector('logo',  'Logo for use in PDF headers', $settings);
}, 10, 3);