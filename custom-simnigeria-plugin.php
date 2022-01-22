<?php
/**
 * Plugin Name:  Custom SIM Nigeria plugin
 * Description:  Some hacks
 * Version:      1.0
 * Author:       Ewald Harmsen
 * Requires at least: 4.0
 *
 *
 * @author Ewald Harmsen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//do not run for other stuff then documents
if(!isset($_SERVER['HTTP_SEC_FETCH_DEST'])){
	//error_log(print_r($_SERVER,true));
}elseif(!in_array($_SERVER['HTTP_SEC_FETCH_DEST'], ['document','empty'])){
	exit;
}else{
	//error_log(print_r($_SERVER,true));
}

//only call it once
remove_action( 'wp_head', 'adjacent_posts_rel_link');

//load all libraries
require( __DIR__  . '/includes/lib/vendor/autoload.php');

$Modules		= array_merge(get_option('sim_modules', []), ['admin'=>[]]);

//Load all main files
$files = glob(__DIR__  . '/includes/php/*.php');
$files = array_merge($files, glob(__DIR__  . '/includes/php/connections/*.php'));
$files = array_merge($files, glob(__DIR__  . '/includes/php/content/*.php'));
$files = array_merge($files, glob(__DIR__  . '/includes/php/forms/*.php'));
$files = array_merge($files, glob(__DIR__  . '/includes/php/security/*.php'));

//Load files for enabled modules
foreach($Modules as $slug=>$settings){
	$files = array_merge($files, glob(__DIR__  . "/includes/modules/$slug/php/*.php"));
}

foreach ($files as $file) {
    require_once($file);   
}

//Activate
register_activation_hook( __FILE__, function(){
	add_option( 'Activated_Plugin', 'SIMNIGERIA' );

	$formbuilder = new SIM\Formbuilder();
	$formbuilder->create_db_submissions_table();

	$formtable = new SIM\FormTable();
	$formtable->create_db_shortcode_table();
});	
	
//Register a function to run on plugin deactivation
register_deactivation_hook( __FILE__, 'custom_simnigeria_deactivate' );

//Add remove scheduled action on plugin deacivation
function custom_simnigeria_deactivate() {
	global $ScheduledFunctions;
	
	SIM\print_array("Removing cron schedules");
	
	foreach($ScheduledFunctions as $scheduled_function){
		wp_clear_scheduled_hook( $scheduled_function[1].'_action' );
	}
}