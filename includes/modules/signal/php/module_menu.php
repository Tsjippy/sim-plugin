<?php
namespace SIM;

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	?>
	<label>
		Link to join the Signal group
		<input type='url' name='group_link' value='<?php echo $settings["group_link"]; ?>' style='width:100%'>
	</label>
	<?php
}, 10, 3);