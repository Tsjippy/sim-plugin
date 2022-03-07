<?php
namespace SIM\CONTENTFILTER;
use SIM;

//load js script to change media screen
add_action( 'wp_enqueue_media', function(){
    wp_enqueue_script('sim_library_script', plugins_url('js/library.min.js', __DIR__), [], ModuleVersion);
});