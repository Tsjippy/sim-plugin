<?php
namespace SIM\GITHUB;
use SIM;
use Github\Exception\ApiLimitExceedException;
use Github\Client;

// https://github.com/KnpLabs/php-github-api 	-- github api
// https://github.com/michelf/php-markdown		-- convert markdown to html

/**
 * Adds a custom description to the plugin in the plugin page
 */
add_filter( 'plugins_api', __NAMESPACE__.'\customDescription', 10, 3);
function customDescription( $res, $action, $args ) {
	// do nothing if you're not getting plugin information or this is not our plugin
	if( 'plugin_information' !== $action || SIM\PLUGINNAME !== $args->slug) {
		return $res;
	}

	$github 	    		= new Github();
	return $github->pluginData(SIM\PLUGIN_PATH, 'Tsjippy', 'sim-plugin', [
		'active_installs'	=> 2, 
		'donate_link'		=> 'harmseninnigeria.nl', 
		'rating'			=> 5, 
		'ratings'			=> [4,5,5,5,5,5], 
		'banners'			=> [
			'high'	=> SIM\PICTURESURL."/banner-1544x500.jpg",
			'low'	=> SIM\PICTURESURL."/banner-772x250.jpg"
		], 
		'tested'			=> '6.6.2'		
	]);
}

/**
 * Checks and shows plugin updates from github
 */
add_filter( 'pre_set_site_transient_update_plugins', __NAMESPACE__.'\showPluginUpdate');
function showPluginUpdate($transient){
	$github			= new Github();

	$item			= $github->getVersionInfo(SIM\PLUGIN_PATH);

	if(!is_object($item)){
		return $transient;
	}

	// Git has a newer version
	if(isset($item->new_version)){
		$transient->response[SIM\PLUGIN]	= $item;
	}else{
		$transient->no_update[SIM\PLUGIN]	= $item;
	}

	return $transient;
}

add_action('sim-after-module-update', __NAMESPACE__.'\afterModuleUpdate', 10, 2);
function afterModuleUpdate($repo, $oldVersion){
	do_action("sim_{$repo}_module_update", $oldVersion);
}