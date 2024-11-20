<?php
namespace SIM;

/**
 * Plugin Name:  		SIM plugin
 * Description:  		A bundle of 25 modules to add AJAX login, forms and other functionality
 * Version:      		5.1.6
 * Author:       		Ewald Harmsen
 * AuthorURI:			harmseninnigeria.nl
 * Requires at least:	6.0
 * Requires PHP: 		8.0
 * Tested up to: 		6.6.2
 * Plugin URI:			https://github.com/Tsjippy/sim-plugin/
 * Tested:				6.5.0	
 * TextDomain:			sim-plugin
 *
 * @author Ewald Harmsen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//do not run for other stuff then documents
if(!isset($_SERVER['HTTP_SEC_FETCH_DEST'])){
	//error_log(print_r($_SERVER,true));
}elseif(!in_array($_SERVER['HTTP_SEC_FETCH_DEST'], ['document','empty','iframe'])){
	// Do not run plugin when requesting an image
	exit;
}else{
	//error_log(print_r($_SERVER,true));
}

//only call it once
//remove_action( 'wp_head', 'adjacent_posts_rel_link');
define(__NAMESPACE__ .'\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ .'\PLUGIN_PATH', __FILE__);

$files = glob(__DIR__  . '/*.php');
foreach ($files as $file) {
    require_once($file);
}

// Check if is updated
if( ! function_exists('get_plugin_data') ){
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}
if(get_option('sim_version') != get_plugin_data(__FILE__)['Version']){
	update_option('sim_version', get_plugin_data(__FILE__)['Version']);
}

//Register a function to run on plugin deactivation
register_deactivation_hook( __FILE__, function() {
	printArray("Removing cron schedules");
});

// Make sure we have an active user when doing cron
add_action('init', function(){
	if(wp_doing_cron()){
		wp_set_current_user(1);
	}
});