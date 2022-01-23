<?php
namespace SIM;

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	?>
	Choose which posttypes you would want to enable:<br>
	<label>
		<input type='checkbox' name='event' <?php if(isset($settings['event'])) echo 'checked';?>>
		Calendar
	</label>
	<br>
	<label>
		<input type='checkbox' name='location' <?php if(isset($settings['location'])) echo 'checked';?>>
		Locations
	</label>
	<br>
	<label>
		<input type='checkbox' name='recipe' <?php if(isset($settings['recipe'])) echo 'checked';?>>
		Recipes
	</label>
	<br>
	<?php
    
}, 10, 3);