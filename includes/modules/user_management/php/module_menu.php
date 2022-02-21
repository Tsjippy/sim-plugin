<?php
namespace SIM;

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module depends on the forms module. Only activate this module when the forms module is active already.
	</p>
	<?php
},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	?>
	<label>
		<input type='checkbox' name='tempuser' value='tempuser' <?php if(isset($settings['tempuser'])) echo 'checked';?>>
		Enable temporary user accounts
	</label>
	<br>
	<label for="user_edit_page">Page with the user edit shortcode</label>
	<?php
	echo page_select("user_edit_page", $settings["user_edit_page"]);
    
}, 10, 3);