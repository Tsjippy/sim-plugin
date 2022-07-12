<?php
namespace SIM\MAIL_POSTING;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

//run on module activation
add_action('sim_module_activated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	wp_create_category('Finance');
});

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	This module depends on the <a href="wordpress.org/plugins/postie/">postie</a> plugin.<br>
	It makes it possible to publish content via the mail.<br>
	E-mails coming from the finance department will automatically get the finance category.<br>
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
		E-mail address used by finance department<br>
		<input type="email" name="finance_email" value="<?php echo $settings['finance_email'];?>">
	</label>
	<?php
	return ob_get_clean();
}, 10, 3);