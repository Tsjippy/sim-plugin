<?php
namespace SIM;

use mikehaertl\shellcommand\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;

//Shortcode for testing
add_shortcode("test", function ($atts){
    global $wpdb;

    global $Modules;

	$prayer	= PRAYER\prayerRequest(false, false, '27-02-2024')['message'];
	$prayer = trim(explode('-', $prayer)[1]);

	$days=2;
	$signalMessage	= "Good day, $days days from now your prayer request will be send out\n\nPlease reply to me with an updated request if needed.\n\nThis is the request I have now:\n\n$prayer\n\nIt will be send on 27-02-2024\n\nTo confirm the update start your reply with 'update prayer'";

	$timestamp	= trySendSignal($signalMessage, 45);
	update_user_meta(45, 'pending-prayer-update', $timestamp);

    /* $posts = get_posts(
		array(
			'post_type'		=> 'any',
			//'author'		=> 137,
			'numberposts'	=> -1,
		)
	);

    foreach($posts as $post){
       
    }  */
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );
