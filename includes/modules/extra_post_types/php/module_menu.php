<?php
namespace SIM;

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	?>
	Choose which posttypes you would want to enable:<br>
	<label>
		<input type='checkbox' name='events' <?php if(isset($settings['events'])) echo 'checked';?>>
		Calendar
	</label>
	<br>
	<label>
		<input type='checkbox' name='locations' <?php if(isset($settings['locations'])) echo 'checked';?>>
		Locations
	</label>
	<br>
	<label>
		<input type='checkbox' name='recipes' <?php if(isset($settings['recipes'])) echo 'checked';?>>
		Recipes
	</label>
	<br>
	<?php
    
}, 10, 3);