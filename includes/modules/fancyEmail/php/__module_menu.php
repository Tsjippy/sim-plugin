<?php
namespace SIM\FANCYEMAIL;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

//run on module activation
add_action('sim_module_activated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	// Create the dbs
	$fancyEmail     = new FancyEmail();
	$fancyEmail->createDbTables();
});

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	$closing	= SIM\getModuleOption($moduleSlug, 'closing');

	ob_start();
	?>
	This module will place all e-mails send in a nice format.<br>
	It will also add a warning to the bottom of the e-mail about it being an automated e-mail.<br>
	If there is no greeting in the e-mail it will add '<?php if($closing){echo $closing;}else{echo 'Kind regards,';} echo ' '.SITENAME;?>'<br>
	It will also monitor how often an e-mail is opened.<br>
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
		Default e-mail greeting<br>
		<input type='text' name='closing' value='<?php if(isset($settings['closing'])){echo $settings['closing'];}else{echo 'Kind regards'; }?>'>
	</label>
	<br><br>
	<label>
		<input type='checkbox' name='no-staging' value='true' <?php if(isset($settings['no-staging'])){echo 'checked';}?>>
		Do not send e-mails from staging websites
	</label>
	<br>
	<label>
		<input type='checkbox' name='no-localhost' value='true' <?php if(isset($settings['no-localhost'])){echo 'checked';}?>>
		Do not send e-mails from localhost
	</label>
	<br>
	<br>
	<label>
		Max attachment size in MB<br>
		<input type='number' name='maxsize' value='<?php if(isset($settings['maxsize'])){echo $settings['maxsize'];}?>'>
	</label>
	<br>
	<br>
	<label>Select a picture for the e-mail header.</label>
	<?php
	SIM\pictureSelector('header_image', 'e-mail header', $settings);

	return ob_get_clean();
}, 10, 3);
	
add_filter('sim_module_data', function($dataHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $dataHtml;
	}

	return $dataHtml.emailStats();
}, 10, 3);