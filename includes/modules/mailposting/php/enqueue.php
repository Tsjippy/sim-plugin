<?php
namespace SIM\MAIL_POSTING;
use SIM;

//load js and css
add_action( 'admin_enqueue_scripts', function ($hook) {
	//Only load on sim settings pages
	if(strpos($hook, 'sim-settings_page_sim_mailposting') === false) {
		return;
	}

	wp_enqueue_script('sim_posting_admin', plugins_url('js/admin.min.js', __DIR__), array() , MODULE_VERSION, true);
});