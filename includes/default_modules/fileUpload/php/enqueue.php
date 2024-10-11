<?php
namespace SIM\FILEUPLOAD;
use SIM;

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\registerUploadScripts', 1);

function registerUploadScripts(){
    //File upload js
    wp_register_script('sim_fileupload_script', plugins_url('js/fileupload.min.js', __DIR__), array('sim_formsubmit_script', 'sim_purify'), MODULE_VERSION, true);

    wp_register_style('sim_image-edit', plugins_url('css/image-edit.min.css', __DIR__), array(), MODULE_VERSION);
}