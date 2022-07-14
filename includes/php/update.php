<?php
namespace SIM;

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
	$res 					= new \stdClass();

	$res->name 				= 'SIM Plugin';
	$res->slug 				= PLUGINNAME;
	$res->version 			= $release['tag_name'];
	$res->author 			= $release['author']['login'];
	$res->tested			= '6.0.0';
	$res->requires 			= '5.5';
	$res->author_profile 	= $release['author']['url'];
	$res->requires_php 		= '7.1';
	$res->last_updated 		= \Date('d-m-Y', strtotime($release['published_at']));

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
 * Checks github for updates of this plugin
 * 
 */
add_filter( 'site_transient_update_plugins', __NAMESPACE__.'\checkForUpdate' );
add_filter( 'transient_update_plugins', __NAMESPACE__.'\checkForUpdate' );
function checkForUpdate( $updatePlugins ) {

	if ( ! is_object( $updatePlugins ) ){
		return $updatePlugins;
	}

	if ( ! isset( $updatePlugins->response ) || ! is_array( $updatePlugins->response ) ){
		$updatePlugins->response = array();
	}

	if( !function_exists('get_plugin_data') ){
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$pluginVersion  = get_plugin_data(PLUGINPATH.PLUGINNAME.'.php')['Version'];
	$pluginFile     = PLUGINNAME.'/'.PLUGINNAME.'.php';

	$release		= getLatestRelease();

	$gitVersion     = $release['tag_name'];

	// Git has a newer version
	if($gitVersion > $pluginVersion){
		$updatePlugins->response[$pluginFile] = (object)array(
			'slug'         => PLUGINNAME, 
			'new_version'  => $gitVersion,
			'url'          => 'https://api.github.com/repos/Tsjippy/sim-plugin',
			'package'      => $release['assets'][0]['browser_download_url'], 
		);
	}

	return $updatePlugins;
}

/**
 * Retrieves the latest github release from cache or github
 * 
 * @return	array	Array containing information about the latest release
 */
function getLatestRelease(){
	//check github version
	$release    = get_transient('sim-git-release');
	// if not in transient
	if(!$release){
		$client 	    = new \Github\Client();
		$release 	    = $client->api('repo')->releases()->latest('tsjippy', PLUGINNAME);
		
		// Store for 24 hours
		set_transient( 'sim-git-release', $release, DAY_IN_SECONDS );
	}

	return $release;
}
