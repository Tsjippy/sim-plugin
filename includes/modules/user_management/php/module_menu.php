<?php
namespace SIM;

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	?>
	<label>
		<input type='checkbox' name='tempuser' value='<?php if(isset($settings['tempuser'])) echo 'checked';?>'>
		Enable temporary user accounts
	</label>
	<?php
    
}, 10, 3);