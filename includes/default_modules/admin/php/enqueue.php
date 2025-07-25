<?php
namespace SIM\ADMIN;
use SIM;

//load js and css
add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\loadAdminAssets');
function loadAdminAssets($hook) {
	//Only load on sim settings pages
	if(!str_contains($hook, '_sim')) {
		return;
	}

	wp_enqueue_style('sim_admin_css', plugins_url('css/admin.min.css', __DIR__), array(), MODULE_VERSION);
	wp_enqueue_script('sim_admin_js', plugins_url('js/admin.min.js', __DIR__), array() , MODULE_VERSION, true);

	wp_localize_script( 'sim_admin_js',
		'sim',
		array(
			'ajaxUrl' 		=> admin_url( 'admin-ajax.php' ),
			"userId"		=> wp_get_current_user()->ID,
			'loadingGif' 	=> SIM\LOADERIMAGEURL,
			'baseUrl' 		=> get_home_url(),
			'maxFileSize'	=> wp_max_upload_size(),
			'restNonce'		=> wp_create_nonce('wp_rest'),
			'restApiPrefix'	=> '/'.RESTAPIPREFIX
		)
	);
}