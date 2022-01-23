<?php
namespace SIM;

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	?><label>Page for logged in users</label>
	<?php echo page_select("home_page", $settings["home_page"]);?>
	<br>

	<label>Page with the password reset form</label>
	<?php echo page_select("pw_reset_page",$settings["pw_reset_page"]); ?>
	<br>
	
	<label>Page with the registration form for new users</label>
	<?php echo page_select("register_page",$settings["register_page"]);

    
}, 10, 3);