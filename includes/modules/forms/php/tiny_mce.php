<?php
namespace SIM\FORMS;
use SIM;

add_action('init', function(){
	//Add tinymce plugin
	add_filter('mce_external_plugins', function($plugins){		
		$simForms	= new SimForms();

		//Add extra variables to the main.js script
		wp_localize_script(
			'sim_script', 
			'formSelect', 
			[$simForms->formSelect()]
		);

		wp_localize_script(
			'sim_admin_js', 
			'formSelect', 
			[$simForms->formSelect()]
		);

		$plugins['insert_form_shortcode']		= plugins_url("js/tiny_mce.js?ver=".MODULE_VERSION, __DIR__);

		return $plugins;
	},999);
			
	//add tinymce button
	add_filter('mce_buttons', function($buttons){
		array_push($buttons, 'insert_form_shortcode');
		return $buttons;
	}, 999);
});