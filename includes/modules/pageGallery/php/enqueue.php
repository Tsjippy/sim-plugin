<?php
namespace SIM\PAGEGALLERY;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
	wp_register_script('sim_page_gallery_script', plugins_url('js/page_gallery.min.js', __DIR__), array('sim_formsubmit_script'), MODULE_VERSION, true);
});