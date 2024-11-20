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
add_action('sim_module_deactivated', __NAMESPACE__.'\onDeactivation', 10, 2);
function onDeactivation($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return;
	}

	wp_clear_scheduled_hook( 'update_modules_action' );
}

function checkForModuleUpdates(){
	global $moduleDirs;

	$github	= new Github();
	foreach($moduleDirs as $module=>$path){
		// inactive module
		if( ! defined("SIM\\$module\\MODULE_VERSION")){
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