<?php
namespace SIM;

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
    ?>
	<label>
		<input type='checkbox' name='full_screen' <?php if(isset($settings['full_screen'])) echo 'checked';?>>
		Show PDF documents full screen if that is the only page content
	</label>
	<br>
	<label>
		<input type='checkbox' name='pdf_print' <?php if(isset($settings['pdf_print'])) echo 'checked';?>>
		Add a "Print to PDF" button option
	</label>
	<br>
	<?php
}, 10, 3);