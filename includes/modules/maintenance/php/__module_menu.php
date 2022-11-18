<?php
namespace SIM\MAINTENANCE;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG){
		return $description;
	}

	ob_start();
	?>
	<p>
		When you enable this module your website will be put in maintenance mode.<br>
		That means the frontend of your website will be no longer accessible.<br>
		The backend (wp-admin) is still available.
	</p>
	<?php

	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();

	?>
	<label>
		<h4>Message title</h4>
		<input type='text' name='title' value='<?php echo $settings['title'];?>' style='width:100%;'>
	</label>

	<h4>Picture for maintenance mode message</h4>
	<?php
	SIM\pictureSelector('image', 'Image', $settings);
	?>
	<br>
	<label>
		<h4>Maintenance mode message</h4>
		<?php
		if(!isset($settings["message"]) || empty($settings["message"])){
			$message	= 'This website is currently unavailable, but will be available again soon';
		}else{
			$message	= $settings["message"];
		}
		
		$tinyMceSettings = array(
			'wpautop' 					=> false,
			'media_buttons' 			=> false,
			'forced_root_block' 		=> true,
			'convert_newlines_to_brs'	=> true,
			'textarea_name' 			=> "message",
			'textarea_rows' 			=> 10
		);

		echo wp_editor(
			$message,
			"message",
			$tinyMceSettings
		);
		?>
	</label>
	<?php

	return ob_get_clean();
}, 10, 3);