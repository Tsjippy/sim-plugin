<?php
namespace SIM\FILEUPLOAD;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    //File upload js
	wp_register_script('sim_fileupload_script', plugins_url('js/fileupload.min.js', __DIR__), array('sim_formsubmit_script', 'sim_purify'), MODULE_VERSION, true);
}, 1);