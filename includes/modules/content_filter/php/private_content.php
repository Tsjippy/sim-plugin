<?php

if(!empty($GLOBALS['Modules']['private_content']['enable'])){
	//load js script to change media screen
	add_action( 'wp_enqueue_media', function(){
        global $StyleVersion;
		wp_enqueue_script('sim_library_script', IncludesUrl.'/modules/private_content/js/library.js', [], $StyleVersion);
	});
}