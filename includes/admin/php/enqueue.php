<?php
namespace SIM\ADMIN;
use SIM;

//load js and css
add_action( 'admin_enqueue_scripts', function ($hook) {
	//Only load on sim settings pages
	if(strpos($hook, 'sim-settings') === false) return;
	wp_enqueue_style('sim_admin_css', plugins_url('css/admin.min.css', __DIR__), array(), ModuleVersion);
	wp_enqueue_script('sim_admin_js', plugins_url('js/admin.js', __DIR__), array('niceselect') ,ModuleVersion, true);
});