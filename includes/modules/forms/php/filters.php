<?php
namespace SIM\FORMS;
use SIM;

add_action('init', function(){				
	$formbuilder	= new Formbuilder();
	//Add tinymce plugin
	add_filter('mce_external_plugins', function($plugins)use($formbuilder){
		return $formbuilder->add_tinymce_plugin($plugins);
	},999);
			
	//add tinymce button
	add_filter('mce_buttons', function($buttons)use($formbuilder){
		return $formbuilder->register_buttons($buttons);
	},999);

});