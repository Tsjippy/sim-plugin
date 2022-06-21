<?php
namespace SIM\MAIL_POSTING;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

//run on module activation
add_action('sim_module_activated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	wp_create_category('Finance');
});

add_action('sim_submenu_description', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	?>
	This module depends on the <a href="wordpress.org/plugins/postie/">postie</a> plugin.<br>
	It makes it possible to publish content via the mail.<br>
	E-mails coming from the finance department will automatically get the finance category.<br>
	<?php
});

add_action('sim_submenu_options', function($moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}
	
    ?>
	<label>
		E-mail address used by finance department<br>
		<input type="email" name="finance_email" value="<?php echo $settings['finance_email'];?>">
	</label>
	<?php
}, 10, 2);