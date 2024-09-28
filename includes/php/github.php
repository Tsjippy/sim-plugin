<?php
namespace SIM;
use Github\Exception\ApiLimitExceedException;

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

	$client 	    		= new \Github\Client();
	$release				= getLatestRelease();
	if(is_wp_error($release)){
		return $res;
	}

	$res 					= new \stdClass();

	$res->name 				= 'SIM Plugin';
	$res->slug 				= PLUGINNAME;
	$res->version 			= $release['tag_name'];
	$res->author 			= $release['author']['login'];
	$res->tested			= '6.5.0';
	$res->requires 			= '6.2';
	$res->author_profile 	= $release['author']['url'];
	$res->requires_php 		= '8.1';
	$res->last_updated 		= \Date(DATEFORMAT, strtotime($release['published_at']));

	$description    = get_transient('sim-git-description');
	// if not in transient
	if(!$description){
		$description    = base64_decode($client->api('repo')->contents()->readme('Tsjippy', PLUGINNAME)['content']);
		// Store for 24 hours
		set_transient( 'sim-git-description', $description, DAY_IN_SECONDS );
	}

	$changelog    = get_transient('sim-git-changelog');
	// if not in transient
	if(!$changelog){
		$changelog	= base64_decode($client->api('repo')->contents()->show('Tsjippy', PLUGINNAME, 'CHANGELOG.md')['content']);
		
		//convert to html
		$parser 	= new \Michelf\MarkdownExtra;
		$changelog	= $parser->transform($changelog);
		
		// Store for 24 hours
		set_transient( 'sim-git-changelog', $changelog, DAY_IN_SECONDS );
	}
		
	$res->sections = array(
		'description' 	=> $description,
		'changelog' 	=> $changelog
	);

	return $res;

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

	$release		= getLatestRelease();

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

/**
 * Retrieves the latest github release from cache or github
 *
 * @return	array	Array containing information about the latest release
 */
function getLatestRelease($author='tsjippy', $package=PLUGINNAME){
	if(isset($_GET['update'])){
		$release	= false;
	}else{
		//check github version
		$release    = get_transient("$author-$package");
	}
	
	// if not in transient
	if(!$release){
		try{
			$client 	    = new \Github\Client();

			$release 	    = $client->api('repo')->releases()->latest($author, $package);

			//printArray($release);

			$client->removeCache();
			
			// Store for 1 hours
			set_transient( "$author-$package", $release, HOUR_IN_SECONDS );
		} catch (ApiLimitExceedException $e) {
			printArray('Rate limit reached, please try again in an hour');
			return new \WP_Error('update', 'Rate limit reached, please try again in an hour');
		}catch(\Exception $exception){
			printArray($exception);
			return new \WP_Error('update', $exception->getMessage());
		}
	}
	return $release;
}
