<?php
namespace SIM;

use Exception;
use mikehaertl\shellcommand\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use SIM\FORMS\SimForms;
use wpdb;

//Shortcode for testing
add_shortcode("test", function ($atts){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    global $wpdb;
    global $Modules;

/*     $results    = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}sim_events` WHERE `enddate` = ''");
    foreach($results as $result){
        $meta   = json_decode(get_post_meta($result->post_id, 'eventdetails', true));

        foreach(['id', 'post_id', 'enddate', 'starttime', 'endtime', 'location', 'organizer', 'location_id', 'organizer_id', 'atendees', 'onlyfor'] as $column){
            if(isset($meta->$column)){
                $args[$column]	= $meta->$column;
            }
        }

        $wpdb->update("{$wpdb->prefix}sim_events",
            $args,
            array(
                'id'		=> $result->id
            ),
        );
    } */
    
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


function updatePrayerRequest($message, $users){
    $signal = new SIGNAL\SignalJsonRpc(false, true);

    $timeStamp      = get_user_meta($users[0]->ID, 'pending-prayer-update', true);
    if(!$timeStamp || !is_numeric($timeStamp)){
        return "You do not have a pending prayer request";
    }

    $sendMessage    = $signal->getSendMessageByTimestamp($timeStamp);

    if(!preg_match_all("/[\d]{2}-[\d]{2}-[\d]{4}/m", $sendMessage, $matches, PREG_SET_ORDER, 0)){
        return "Not sure which prayer request is pending for you";
    }

    $replaceDate	= $matches[0][0];

    // get the prayer request to be replaced
    $prayer	= PRAYER\prayerRequest(false, false, $replaceDate);

    if(!$prayer){
        return "Could not find prayer request to update for $replaceDate";
    }

    // Split on the - 
    $exploded   = explode('-', $prayer['message']);
    if(count($exploded) < 2){
        return "Could not find prayer request to update.\nNot sure whose prayer request it is";
    }
    $prayerMessage = trim($exploded[1]);

    // perform the replacement
    if(strtolower($message) == 'update prayer correct'){
        foreach($users as $user){
            delete_user_meta($user->ID, 'pending-prayer-update');
        }

        $replacetext    = get_user_meta($user->ID, 'pending-prayer-update-text', true);
        delete_user_meta($user->ID, 'pending-prayer-update-text');

        if(empty($replacetext)){
            return 'Something went wrong';
        }

        $post               = get_post($prayer['post']);

        if(empty($post)){
            return 'no post found to replace in'.implode(';', $prayer);
        }

        $post->post_content = str_replace($prayerMessage, $replacetext, $post->post_content);
        // do the actual replacement
        wp_update_post(
            $post,
            false,
            false
        );

        return "Replaced:\n'$prayerMessage'\n\nwith:\n'$replacetext'";
    }

    // confirm the replacement
    $replacetext    = trim(str_ireplace('update prayer', '', $message));

    foreach($users as $user){
        update_user_meta($user->ID, 'pending-prayer-update-text', $replacetext);
    }

	return "I am going to replace:\n'$prayerMessage'\n\nwith\n'$replacetext'\n\nReply with 'update prayer correct' if I should continue";
}