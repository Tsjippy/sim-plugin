<?php
namespace SIM\ADMIN;
use SIM;

//load js and css
add_action( 'admin_enqueue_scripts', function ($hook) {
	//Only load on sim settings pages
	if(!str_contains($hook, '_sim_')) {
		return;
	}

	wp_enqueue_style('sim_admin_css', plugins_url('css/admin.min.css', __DIR__), array(), MODULE_VERSION);
	wp_enqueue_script('sim_admin_js', plugins_url('js/admin.min.js', __DIR__), array('niceselect') , MODULE_VERSION, true);
});