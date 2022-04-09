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

set_error_handler(function ( $errno, $errstr, $errfile, $errline ) {

    if( $errno === E_USER_NOTICE ) {

        $message = 'You have an error notice: "%s" in file "%s" at line: "%s".' ;
        $message = sprintf($message, $errstr, $errfile, $errline);

        error_log(print_r($message,true));
        error_log(print_r(wpse_288408_generate_stack_trace(), true));
    }
});
// Function from php.net http://php.net/manual/en/function.debug-backtrace.php#112238
function wpse_288408_generate_stack_trace() {

    $e = new Exception();

    $trace = explode( "\n" , $e->getTraceAsString() );

    // reverse array to make steps line up chronologically

    $trace = array_reverse($trace);

    array_shift($trace); // remove {main}
    array_pop($trace); // remove call to this method

    $length = count($trace);
    $result = array();

    for ($i = 0; $i < $length; $i++) {
        $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
    }

    $result = implode("\n", $result);
    $result = "\n" . $result . "\n";

    return $result;
}

//only call it once
remove_action( 'wp_head', 'adjacent_posts_rel_link');

// Define constants
define('SITEURL', get_site_url());
define('SITENAME', get_bloginfo());
define('INCLUDESURL', plugins_url('includes',__FILE__));
define('PICTURESURL', INCLUDESURL.'/pictures');
define('LOADERIMAGEURL', PICTURESURL.'/loading.gif');
define('INCLUDESPATH', plugin_dir_path(__FILE__).'includes/');
define('PICTURESPATH', INCLUDESPATH.'pictures');

//load all libraries
require( __DIR__  . '/includes/lib/vendor/autoload.php');

$Modules		= get_option('sim_modules', []);

//Load all main files
$files = glob(__DIR__  . '/includes/php/*.php');
$files = array_merge($files, glob(__DIR__  . '/includes/admin/php/*.php'));

//Load files for enabled modules
foreach($Modules as $slug=>$settings){
	$files = array_merge($files, glob(__DIR__  . "/includes/modules/$slug/php/*.php"));
}

foreach ($files as $file) {
    require_once($file);   
}

//Activate
register_activation_hook( __FILE__, function(){
   // exit( wp_redirect( admin_url( 'admin.php?page=sim' ) ) );
});	

//redirect after plugin activation
add_action( 'activated_plugin', function ( $plugin ) {
    if( $plugin == plugin_basename( __FILE__ ) ) {
        exit( wp_redirect( admin_url( 'admin.php?page=sim' ) ) );
    }
} );

//Add setting link
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", function ($links) { 
    $url            = admin_url( 'admin.php?page=sim' );
    $settings_link  = "<a href='$url'>Settings</a>"; 
    array_unshift($links, $settings_link); 
    return $links; 
});
  
//Register a function to run on plugin deactivation
register_deactivation_hook( __FILE__, 'sim_deactivate' );

//Add remove scheduled action on plugin deacivation
function sim_deactivate() {	
	SIM\print_array("Removing cron schedules");
}