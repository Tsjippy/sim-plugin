<?php
namespace SIM\GITHUB;
use SIM;

add_action('init', __NAMESPACE__.'\init');
function init(){
	//add action for use in scheduled task
	add_action( 'update_modules_action', __NAMESPACE__.'\checkForModuleUpdates' );
}

function scheduleTasks(){
    SIM\scheduleTask('update_modules_action', 'daily');
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', __NAMESPACE__.'\onDeactivation');
function onDeactivation($options){
	wp_clear_scheduled_hook( 'update_modules_action' );
}

function checkForModuleUpdates(){
	global $moduleDirs;
	global $defaultModules;

	// DO not run on localhost
	if($_SERVER['HTTP_HOST'] == 'localhost' || str_contains($_SERVER['HTTP_HOST'], '.local')) {
		return;
	}

	// update the plugin first
	$url    = self_admin_url( 'update.php?action=update-selected&amp;plugin=' . urlencode( PLUGINNAME ) );
    $url    = wp_nonce_url( $url, 'bulk-update-plugins' );
	$page 	= file_get_contents($url);

	// Now check for module updates
	$github	= new Github();
	foreach($moduleDirs as $module => $path){
		// Default module
		if(in_array($module, $defaultModules)){
			continue;
		}

		// inactive module
		if( ! defined("SIM\\$module\\MODULE_VERSION") ){
			SIM\printArray("Constant does not exist for $module ");
			continue;
		}

		$oldVersion	= false;

		$oldVersion	= constant("SIM\\$module\\MODULE_VERSION");
		
		$release	= $github->getLatestRelease('Tsjippy', $module, true);

		if(is_wp_error($release)){
			SIM\printArray("Error checking for update for module $module: ");
			SIM\printArray($release);
			continue;
		}

		$newVersion	= $release['tag_name'];

		// Download the new version
		//SIM\printArray("Name: $module. Current Version $oldVersion, new version $newVersion. ");
		if(version_compare($newVersion, $oldVersion)){
			SIM\printArray("Updating $module");
			
            $github->downloadFromGithub('Tsjippy', $module, $path);
        }
	}
}