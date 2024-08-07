<?php
namespace SIM;

/**
 * Plugin Name:  SIM plugin
 * Description:  A bundle of 25 modules to add AJAX login, forms and other functionality
 * Version:      2.45.8
 * Author:       Ewald Harmsen
 * Requires at least: 4.0
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

define('PLUGIN', plugin_basename(__FILE__));

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
