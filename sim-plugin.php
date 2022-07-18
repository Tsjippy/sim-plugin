<?php
/**
 * Plugin Name:  SIM plugin
 * Description:  A bundle of 25 modules to add AJAX login, forms and other functionality
 * Version:      2.2.11
 * Author:       Ewald Harmsen
 * Requires at least: 4.0
 * 
 * GitHub Plugin URI: Tsjippy/sim-plugin
 * GitHub Plugin URI: https://github.com/Tsjippy/sim-plugin
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
	exit;
}else{
	//error_log(print_r($_SERVER,true));
}

//only call it once
remove_action( 'wp_head', 'adjacent_posts_rel_link');

// Define constants
define('PLUGINNAME', 'sim-plugin');
define('SITEURL', site_url( '', 'https' ));
define('SITENAME', get_bloginfo());
define('INCLUDESURL', plugins_url('includes',__FILE__));
define('PICTURESURL', INCLUDESURL.'/pictures');
define('LOADERIMAGEURL', PICTURESURL.'/loading.gif');
define('PLUGINPATH', plugin_dir_path(__FILE__));
define('INCLUDESPATH', PLUGINPATH.'includes/');
define('MODULESPATH', INCLUDESPATH.'modules/');
define('PICTURESPATH', INCLUDESPATH.'pictures/');

// Store all modulefolders
$dirs    = scandir(MODULESPATH);
unset($dirs[0]);
unset($dirs[1]);

//Sort alphabeticalyy, ignore case
sort($dirs, SORT_STRING | SORT_FLAG_CASE);

$moduleDirs  = [];
foreach($dirs as $dir){
    $moduleDirs[strtolower($dir)] = $dir;
}

//load all libraries
require( __DIR__  . '/includes/lib/vendor/autoload.php');

spl_autoload_register(function ($classname) {
    global $moduleDirs;

    $path       = explode('\\', $classname);

    if($path[0] != 'SIM' || !isset($path[1])){
        return;
    }

    $module     = $moduleDirs[strtolower($path[1])];
    $fileName   = $path[2];

    $modulePath = MODULESPATH."$module/php";    
	$classFile	= "$modulePath/classes/$fileName.php";
    $traitFile	= "$modulePath/traits/$fileName.php";
	if(file_exists($classFile)){
		require_once($classFile);
	}elseif(file_exists($traitFile)){
		require_once($traitFile);
	}else{
        SIM\printArray($classFile.' not found');
    }
});

$Modules		= get_option('sim_modules', []);
//Load all main files
$files = glob(__DIR__  . '/includes/php/*.php');
$files = array_merge($files, glob(__DIR__  . '/includes/admin/php/*.php'));

foreach($Modules as $slug=>$settings){
    if(isset($moduleDirs[$slug])){
        $files = array_merge($files, glob(__DIR__  . "/includes/modules/{$moduleDirs[$slug]}/php/*.php"));
    }
}

foreach ($files as $file) {
    require_once($file);   
}

// run on activation
add_action( 'activated_plugin', function ( $plugin ) {
    if( $plugin == plugin_basename( __FILE__ ) ) {

        // create must use plugins folder if it does not exist
        if (!is_dir(WP_CONTENT_DIR.'/mu-plugins')) {
            mkdir(WP_CONTENT_DIR.'/mu-plugins', 0777, true);
        }

        // Copy must plugin
        copy(__DIR__.'/other/sim.php', WP_CONTENT_DIR.'/mu-plugins/sim.php');

        // Create private upload folder
        $path   = wp_upload_dir()['basedir'].'/private';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        // Copy dl-file.php
        copy(__DIR__.'/other/dl-file.php', ABSPATH.'/dl-file.php');

        //.htaccess
        $htaccess = file_get_contents(ABSPATH.'/.htaccess');
        if(strpos($htaccess, '# BEGIN THIS DL-FILE.PHP ADDITION') === false){
            $htaccess .= "\n\n# BEGIN THIS DL-FILE.PHP ADDITION";
            $htaccess .= "\nRewriteCond %{REQUEST_URI} ^.*wp-content/uploads/private/.*";
            $htaccess .= "\nRewriteRule ^wp-content/uploads/(private/.*)$ dl-file.php?file=$1 [QSA,L] */";
            $htaccess .= "\n# END THIS DL-FILE.PHP ADDITION";
        }
        file_put_contents(ABSPATH.'/.htaccess', $htaccess);

        //redirect after plugin activation
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
	SIM\printArray("Removing cron schedules");
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
