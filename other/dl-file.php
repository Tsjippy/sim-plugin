<?php
/*
 * dl-file.php
 *
 * Protect uploaded files with login.
 *
 * @link http://wordpress.stackexchange.com/questions/37144/protect-wordpress-uploads-if-user-is-not-logged-in
 * @link https://gist.github.com/hakre/1552239
 *
 */

 //Do not check if logged when requests comes from the server
$whitelist = ['127.0.0.1','::1'];
if(in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
	showFile();
}else{
	ob_start();
	define( 'WP_USE_THEMES', false ); // Do not use the theme files
	define( 'COOKIE_DOMAIN', false ); // Do not append verify the domain to the cookie
	define( 'DISABLE_WP_CRON', true );

	require("wp-load.php");
	require_once ABSPATH . WPINC . '/functions.php';

	$discard = ob_get_clean();

	if(is_user_logged_in() || auth_redirect()){
		//get the current users username
		$user		= wp_get_current_user();
		$username	= $user->user_login;

		//If the file part contains the account statements folder
		//and the filename does not contain the username
		//Block access
		if (strpos($file, 'account_statements') !== false) {
			$partnerName		= $username;
			
			$family = get_user_meta($user->ID,'family',true);
			if(isset($family['partner'])){
				//The partners name
				$partnerName	= get_userdata($family['partner'])->user_login;
			}

			//Block access if the filename does not contain the own or partners username
			if(strpos($file, $username) === false && strpos($file, $partnerName) === false){
				status_header(403);
				die('<div style="text-align: center;"><p>Stop spying at someone elses file!</p></div>');
			}
		}
		
		$allowedRoles	= ["medicalinfo", "administrator"];
		//If this is a medical file it is only visible to that person and the user with the correct role
		if(strpos($file, 'medical_uploads') !== false && !array_intersect($allowedRoles, $user->roles ) && strpos($file, $username) === false) {
			status_header(403);
			die('<div style="text-align: center;"><p>You do not have permission to view this file!</p></div>');
		}
		
		$allowedRoles	= ["visainfo", "administrator"];
		//If this is a visa file it is only visible to that person and the user with the correct role
		if(strpos($file, 'visa_uploads') !== false && !array_intersect($allowedRoles, $user->roles ) && strpos($file, $username) === false) {
			status_header(403);
			die('<div style="text-align: center;"><p>You do not have permission to view this file!</p></div>');
		}

		showFile();
	}
}

function showFile(){
	$file	= __DIR__ . '/wp-content/uploads/private/'.(isset($_GET['file']) ? $_GET['file'] : '');
	$file	= realpath($file);

	if ($file === FALSE || !is_file($file)) {
		require_once ABSPATH . WPINC . '/functions.php';
		status_header(404);
		die('404 &#8212; File not found.');
	}

	$mime[ 'type' ] = mime_content_type( $file );

	if( $mime[ 'type' ] ){
		$mimetype = $mime[ 'type' ];
	}else{
		$mimetype = 'image/' . pathinfo($file, PATHINFO_EXTENSION);
	}

	header( 'Content-Type: ' . $mimetype ); // always send this
	if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) ){
		header( 'Content-Length: ' . filesize( $file ) );
	}

	$lastModified 	= gmdate( 'D, d M Y H:i:s', filemtime( $file ) );
	$etag 			= '"' . md5( $lastModified ) . '"';
	header( "Last-Modified: $lastModified GMT" );
	header( 'ETag: ' . $etag );
	header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );

	// Support for Conditional GET
	$clientEtag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;

	if( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ){
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;
	}

	$clientLastModified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
	// If string is empty, return 0. If not, attempt to parse into a timestamp
	$clientModifiedTimestamp = $clientLastModified ? strtotime( $clientLastModified ) : 0;

	// Make a timestamp for our most recent modification...
	$modifiedTimestamp = strtotime($lastModified);

	if ( ( $clientLastModified && $clientEtag )
		? ( ( $clientModifiedTimestamp >= $modifiedTimestamp) && ( $clientEtag == $etag ) )
		: ( ( $clientModifiedTimestamp >= $modifiedTimestamp) || ( $clientEtag == $etag ) )
		) {
		require_once ABSPATH . WPINC . '/functions.php';
		status_header( 304 );
		exit;
	}

	// If we made it this far, just serve the file
	readfile( $file );
}