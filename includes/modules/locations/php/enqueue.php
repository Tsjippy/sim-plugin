<?php
namespace SIM\LOCATIONS;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
	wp_register_style('sim_locations_style', plugins_url('css/locations.min.css', __DIR__), array(), MODULE_VERSION);

	$page   = SIM\getModuleOption('frontendposting', 'publish_post_page');
    if(get_the_ID() == $page){
        wp_enqueue_style('sim_locations_style');
    }
});