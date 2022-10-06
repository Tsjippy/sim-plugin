<?php
namespace SIM\LOCATIONS;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
	wp_register_style('sim_locations_style', plugins_url('css/locations.min.css', __DIR__), array(), MODULE_VERSION);
    wp_register_style('sim_employee_style', plugins_url('css/employee.min.css', __DIR__), array(), MODULE_VERSION);

	$pages   = SIM\getModuleOption('frontendposting', 'front_end_post_pages');
    if(in_array(get_the_ID(), $pages)){
        wp_enqueue_style('sim_locations_style');
    }

    $apiKey = SIM\getModuleOption(MODULE_SLUG, 'google-maps-api-key');
    if($apiKey){
        wp_localize_script( 'sim_script', 
            'mapsApi', 
            ['key'=>$apiKey]
        );
    }
}, 99);