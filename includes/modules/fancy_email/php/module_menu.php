<?php
namespace SIM\FANCYMAIL;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));
define('IMAGE_FOLDER', wp_upload_dir()['path'].'/email_pictures');

//run on module activation
add_action('sim_module_activated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	//create folder for temporary e-mail messages
	if (!is_dir(IMAGE_FOLDER)) {
		mkdir(IMAGE_FOLDER, 0777, true);
	}

	// Create the dbs
	$fancyEmail     = new FancyEmail();
	$fancyEmail->createDbTables();
});

add_action('sim_submenu_description', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	?>
	This module will place all e-mails send in a nice format.<br>
	It will also add a warning to the bottom of the e-mail about it being an automated e-mail.<br>
	If there is no greeting in the e-mail it will add 'Kind regards, <?php echo SITENAME;?>'<br>
	It will also monitor how often an e-mail is opened.<br>
	<?php

	if(SIM\getModuleOption($moduleSlug, 'enable')){
		echo emailStats();
	}
});

add_action('sim_submenu_options', function($moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}
	
    ?>
	<label>Select a picture for the e-mail header.</label>
	<?php
	SIM\pictureSelector('header_image', 'e-mail header', $settings);
}, 10, 2);