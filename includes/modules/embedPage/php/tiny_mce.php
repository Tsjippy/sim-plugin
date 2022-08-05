<?php
namespace SIM\FORMS;
use SIM;

add_action('init', function(){
	// do not run during rest request
    if(SIM\isRestApiRequest()){
        return;
    }
	
	//Add tinymce plugin
	add_filter('mce_external_plugins', function($plugins){
		global $wp_scripts;
		
		if(!isset($wp_scripts->registered['sim_script'])){
			return $plugins;
		}
			
		//Add extra variables to the main.js script
		wp_localize_script( 'sim_script', 
			'pageSelect', 
			['html'=>SIM\pageSelect('page-selector')]
		);

		wp_localize_script( 'sim_admin_js', 
			'pageSelect', 
			['html'=>SIM\pageSelect('page-selector')]
		);

		$plugins['insert_embed_shortcode']		= plugins_url("js/tiny_mce.js?ver=".MODULE_VERSION, __DIR__);

		return $plugins;
	},999);
			
	//add tinymce button
	add_filter('mce_buttons', function($buttons){
		array_push($buttons, 'insert_embed_shortcode');
		return $buttons;
	},999);

});