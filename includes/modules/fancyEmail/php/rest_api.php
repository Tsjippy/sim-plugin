<?php
namespace SIM\FANCYEMAIL;
use SIM;

global $wpdb;

add_action( 'rest_api_init', function () {
	//Route for e-mail tracking of today
	register_rest_route( RESTAPIPREFIX, '/mailtracker', array(
			'methods' => 'GET',
			'callback' => __NAMESPACE__.'\mailTracker',
			'permission_callback' => '__return_true',
		)
	);
} );

// Make mailtracker rest api url publicy available
add_filter('sim_allowed_rest_api_urls', function($urls){
	$urls[]	= RESTAPIPREFIX.'/mailtracker';

	return $urls;
});

/**
 * Tracks if an e-mail is opened or not using an image with a url
 */
function mailTracker(\WP_REST_Request $request) {
	global $wpdb;

	$mailId		= $request->get_param('mailid');
	$url		= strval(urldecode($request->get_param('url')));

	// Store mail open or link clicked in db
	if(is_numeric($mailId)){
		if(empty($url)){
			$type	= 'mail-opened';
		}else{
			$type	= 'link-clicked';
		}

		$fancyEmail     = new FancyEmail();

		// Add e-mail to e-mails db
		$wpdb->insert(
			$fancyEmail->mailEventTable,
			array(
				'email_id'		=> $mailId,
				'type'			=> $type,
				'time'			=> current_time('U'),
				'url'			=> str_replace(SITEURL, '', $url)
			)
		);

		if($wpdb->last_error !== ''){
			SIM\printArray($wpdb->last_error);
		}
	}

	if(empty($url)){
		// redirect to picture
		$url = plugins_url('pictures/transparent.png', __DIR__).'?ver='.time();
	}

	wp_redirect( $url );
	exit();
}