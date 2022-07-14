<?php
namespace SIM\PRAYER;
use SIM;

//load js and css
add_action( 'admin_enqueue_scripts', function ($hook) {
	//Only load on sim settings pages
	if(strpos($hook, 'sim-settings_page_sim_prayer') === false) {
		return;
	}

	wp_enqueue_script('sim_prayer_admin', plugins_url('js/admin.min.js', __DIR__), array() ,MODULE_VERSION, true);
});