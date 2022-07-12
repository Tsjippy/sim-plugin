<?php
namespace SIM\USERMANAGEMENT;
use SIM;

/* HELPER FUNCTIONS */
//add special js to the dynamic form js
add_filter('sim_form_extra_js', function($js, $formName, $minimized){
	$path	= plugin_dir_path( __DIR__)."js/$formName.min.js";
	if(!$minimized or !file_exists($path)){
		$path	= plugin_dir_path( __DIR__)."js/$formName.js";
	}

	if(file_exists($path)){
		$js		= file_get_contents($path);
	}

	return $js;
}, 10, 3);