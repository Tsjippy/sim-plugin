<?php
namespace SIM\FANCYMAIL;
use SIM;

const ModuleVersion		= '7.0.0';
define('ImageFolder', wp_upload_dir()['path'].'/email_pictures');

//run on module activation
add_action('sim_module_activated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	//create folder for temporary e-mail messages
	if (!is_dir(ImageFolder)) {
		mkdir(ImageFolder, 0777, true);
	}

	// Create the dbs
	create_db_tables();
}, 10, 2);

add_action('sim_module_updated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	//schedule_tasks();
}, 10, 2);


add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	This module will place all e-mails send in a nice format.<br>
	It will also add a warning to the bottom of the e-mail about it being an automated e-mail.<br>
	If there is no greeting in the e-mail it will add 'Kind regards, <?php echo SITENAME;?>'<br>
	It will also monitor how often an e-mail is opened.<br>
	<?php
},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
    ?>
	<label>Select a picture for the e-mail header.</label>
	<?php
	SIM\picture_selector('header_image', 'e-mail header', $settings);

	echo email_stats();
}, 10, 3);