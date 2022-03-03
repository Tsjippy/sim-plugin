<?php
namespace SIM\FRONTPAGE;
use SIM;

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	?>
    <label>
		First button text.<br>
		<input type="text" name="first_button_text" value="<?php echo $settings['first_button_text'];?>">
	</label>
	<br>
	<label>
		First button url.<br>
		<input type="text" name="first_button_url" value="<?php echo $settings['first_button_url'];?>">
	</label>
	<br>

	<label>
		Second button text.<br>
		<input type="text" name="second_button_text" value="<?php echo $settings['second_button_text'];?>">
	</label>
	<br>
	<label>
		Second button url.<br>
		<input type="text" name="second_button_url" value="<?php echo $settings['second_button_url'];?>">
	</label>
	<br>
	<br>

	<label>
		Header hook.<br>
		<input type="text" name="header_hook" value="<?php echo $settings['header_hook'];?>">
	</label>
	<br>
	<label>
		Hook after the main content.<br>
		<input type="text" name="after_main_content_hook" value="<?php echo $settings['after_main_content_hook'];?>">
	</label>
	<br>
	<label>
		Before footer hook.<br>
		<input type="text" name="before_footer_hook" value="<?php echo $settings['before_footer_hook'];?>">
	</label>
	<br>
	
	<?php
}, 10, 3);