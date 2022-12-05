<?php
namespace SIM\WELCOME;
use SIM;

const MODULE_VERSION		= '7.0.8';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();

	?>
	<p>
		This module adds a welcome message in a popup for new users
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
		Welcome message on homepage
		<?php
		$tinyMceSettings = array(
			'wpautop' 					=> false,
			'media_buttons' 			=> false,
			'forced_root_block' 		=> true,
			'convert_newlines_to_brs'	=> true,
			'textarea_name' 			=> "welcome_message",
			'textarea_rows' 			=> 10
		);

		echo wp_editor(
			$settings["welcome_message"],
			"welcome_message",
			$tinyMceSettings
		);
		?>
	</label>

	<?php
	return ob_get_clean();
}, 10, 3);