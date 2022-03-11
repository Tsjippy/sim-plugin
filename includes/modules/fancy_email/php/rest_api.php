<?php
namespace SIM\FANCYMAIL;
use SIM;

global $wpdb;

define('MAILTABLE', $wpdb->prefix."sim_emails");
define('MAILEVENTTABLE', $wpdb->prefix."sim_email_events");

add_action( 'rest_api_init', function () {
	//Route for e-mail tracking of today
	register_rest_route( 'sim/v1', '/mailtracker', array(
		'methods' => 'GET',
		'callback' => __NAMESPACE__.'\email_tracking',
		'permission_callback' => '__return_true',
		)
	);
} );

// Make mailtracker rest api url publicy available
add_filter('sim_allowed_rest_api_urls', function($urls){
	$urls[]	= 'sim/v1/mailtracker';

	return $urls;
});

function email_tracking(\WP_REST_Request $request) {
	global $wpdb;

	$mailId		= $request->get_params()['mailid'];

	if(is_numeric($mailId)){
		// Add e-mail to e-mails db
		$wpdb->insert(
			MAILEVENTTABLE, 
			array(
				'email_id'		=> $mailId,
				'type'			=> 'mail_opened',
				'time'			=> date('Y-m-d')
			)
		);
	}

	// open the file in a binary mode
	$name = PICTURESPATH.'/transparent.png';
	$fp = fopen($name, 'rb');

	// send the right headers
	header("Content-Type: image/png");
	header("Content-Length: " . filesize($name));

	// dump the picture and stop the script
	fpassthru($fp);
	exit;
}

function create_db_tables(){
    if ( !function_exists( 'maybe_create_table' ) ) { 
        require_once ABSPATH . '/wp-admin/install-helper.php'; 
    }
    
    //only create db if it does not exist
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    //Main table
    $sql = "CREATE TABLE ".MAILTABLE." (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      subject tinytext NOT NULL,
      recipients longtext NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    maybe_create_table(MAILTABLE, $sql );

    // Form element table
    $sql = "CREATE TABLE ".MAILEVENTTABLE." (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        email_id int NOT NULL,
        type text NOT NULL,
        time text NOT NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";

    maybe_create_table(MAILEVENTTABLE, $sql );
}