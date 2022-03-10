<?php
/**
 * Plugin Name:  SIM plugin
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

// Define constants
define('SITEURL', get_site_url());
define('SITENAME', get_bloginfo());
define('INCLUDESURL', plugins_url('includes',__FILE__));
define('INCLUDESPATH', plugin_dir_path(__FILE__).'includes/');
define('PICTURESURL', INCLUDESURL.'/pictures');
define('LOADERIMAGEURL', PICTURESURL.'/loading.gif');

//load all libraries
require( __DIR__  . '/includes/lib/vendor/autoload.php');

$Modules		= get_option('sim_modules', []);

//Load all main files
$files = glob(__DIR__  . '/includes/php/*.php');
$files = array_merge($files, glob(__DIR__  . '/includes/admin/php/*.php'));
$files = array_merge($files, glob(__DIR__  . '/includes/php/content/*.php'));
$files = array_merge($files, glob(__DIR__  . '/includes/php/forms/*.php'));

//Load files for enabled modules
foreach($Modules as $slug=>$settings){
	$files = array_merge($files, glob(__DIR__  . "/includes/modules/$slug/php/*.php"));
}

foreach ($files as $file) {
    require_once($file);   
}

//Activate
register_activation_hook( __FILE__, function(){
	add_option( 'Activated_Plugin', 'SIM' );

	
});	
	
//Register a function to run on plugin deactivation
register_deactivation_hook( __FILE__, 'sim_deactivate' );

//Add remove scheduled action on plugin deacivation
function sim_deactivate() {	
	SIM\print_array("Removing cron schedules");
	
	wp_clear_scheduled_hook( 'check_last_login_date_action' );
	wp_clear_scheduled_hook( 'process_images_action' );
}