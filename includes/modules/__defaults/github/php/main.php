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
	return $github->pluginData(PLUGIN_PATH);
}, 10, 3);

/**
 * Checks and shows plugin updates from github
 */
add_filter( 'pre_set_site_transient_update_plugins', function($transient){
	$plugin = explode('/', PLUGIN)[0];

	if( !function_exists('get_plugin_data') ){
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$pluginVersion  = get_plugin_data(WP_PLUGIN_DIR.'/'.PLUGIN)['Version'];

	$github			= new Github();
	$release		= $github->getLatestRelease();

	if(is_wp_error($release)){
		return $release;
	}

	$gitVersion     = $release['tag_name'];

	$item			= (object) array(
		'slug'          => $plugin,
		'new_version'   => $pluginVersion,
		'url'           => 'https://api.github.com/repos/Tsjippy/sim-plugin',
		'package'       => '',
		'plugin'		=> PLUGIN
	);

	// Git has a newer version
	if(version_compare($gitVersion, $pluginVersion) && !empty($release['assets'][0]['browser_download_url'])){
		$item->new_version	= $gitVersion;
		$item->package		= $release['assets'][0]['browser_download_url'];

		$transient->response[PLUGIN]	= $item;
	}else{
		$transient->no_update[PLUGIN]	= $item;
	}

	return $transient;
});