<?php
namespace SIM\PDF;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module adds the possibility to have a print button on pages or posts.<br>
		This will print the webpage to a PDF document.<br>
		It also adds the option for full screen PDF display.<br>
		Both options can be turned on or off below.<br>
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
		<input type='checkbox' name='full_screen' <?php if(isset($settings['full_screen'])){echo 'checked';}?>>
		Show PDF documents full screen if that is the only page content
	</label>
	<br>
	<br>
	<label>
		<input type='checkbox' name='pdf_print' <?php if(isset($settings['pdf_print'])){echo 'checked';}?>>
		Add a "Print to PDF" button option
	</label>
	<br>
	<br>
	<?php
	SIM\pictureSelector('logo', 'Logo for use in PDF headers', $settings, 'jpe');

	return ob_get_clean();
}, 10, 3);