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
add_filter( 'plugins_api', function ( $res, $action, $args ) {
	// do nothing if you're not getting plugin information or this is not our plugin
	if( 'plugin_information' !== $action || PLUGINNAME !== $args->slug) {
		return $res;
	}

	$github 	    		= new Github();
	return $github->pluginData(PLUGIN_PATH, 'Tsjippy', 'sim-plugin', [
		'active_installs'	=> 2, 
		'donate_link'		=> 'harmseninnigeria.nl', 
		'rating'			=> 5, 
		'ratings'			=> [4,5,5,5,5,5], 
		'banners'			=> [
			'high'	=> PICTURESURL."/banner-1544x500.jpg",
			'low'	=> PICTURESURL."/banner-772x250.jpg"
		], 
		'tested'			=> '6.6.2'		
	]);
}, 10, 3);

/**
 * Checks and shows plugin updates from github
 */
add_filter( 'pre_set_site_transient_update_plugins', function($transient){
	$github			= new Github();

	$item			= $github->pluginVersionInfo(PLUGIN_PATH);

	// Git has a newer version
	if(isset($item->new_version)){
		$transient->response[PLUGIN]	= $item;
	}else{
		$transient->no_update[PLUGIN]	= $item;
	}

	return $transient;
});