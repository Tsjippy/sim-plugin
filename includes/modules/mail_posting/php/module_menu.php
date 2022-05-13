<?php
namespace SIM\MAIL_POSTING;
use SIM;

const ModuleVersion		= '7.0.0';

//run on module activation
add_action('sim_module_activated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	wp_create_category('Finance');
}, 10, 2);

add_action('sim_submenu_description', function($moduleSlug, $module_name){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	?>
	This module depends on the <a href="wordpress.org/plugins/postie/">postie</a> plugin.<br>
	It makes it possible to publish content via the mail.<br>
	E-mails coming from the finance department will automatically get the finance category.<br>
	<?php
},10,2);

add_action('sim_submenu_options', function($moduleSlug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;
	
    ?>
	<label>
		E-mail address used by finance department<br>
		<input type="email" name="finance_email" value="<?php echo $settings['finance_email'];?>">
	</label>
	<?php
}, 10, 3);