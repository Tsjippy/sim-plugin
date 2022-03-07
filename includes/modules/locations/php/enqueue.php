<?php
namespace SIM\LOCATIONS;
use SIM;

add_action( 'wp_enqueue_scripts', function($hook){
	wp_register_style('sim_locations_style', plugins_url('css/locations.min.css', __DIR__), array(), ModuleVersion);
});