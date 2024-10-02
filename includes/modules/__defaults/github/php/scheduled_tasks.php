<?php
namespace SIM\GITHUB;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'update_modules_action', __NAMESPACE__.'\checkForModuleUpdates' );
});

function scheduleTasks(){
    SIM\scheduleTask('update_modules_action', 'daily');
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return;
	}

	wp_clear_scheduled_hook( 'update_modules_action' );
}, 10, 2);

function checkForModuleUpdates(){
	global $moduleDirs;

	$github	= new Github();
	foreach($moduleDirs as $module=>$path){
		$oldVersion	= false;
		$oldVersion	= constant("SIM\\$module\\MODULE_VERSION");
		
		$release	= $github->getLatestRelease('Tsjippy', $module, true);

		if(is_wp_error($release)){
			continue;
		}

		$newVersion	= $release['tag_name'];

		// Download the new version
		if(version_compare($newVersion, $oldVersion)){
            $github->downloadFromGithub('Tsjippy', $module, SIM\MODULESPATH.$path);
        }
	}
}