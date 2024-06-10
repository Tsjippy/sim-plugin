<?php
namespace SIM;

use Exception;
use mikehaertl\shellcommand\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use SIM\FORMS\SimForms;

//Shortcode for testing
add_shortcode("test", function ($atts){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    

    $simForms = new FORMS\SimForms();

        maybe_add_column($simForms->tableName, 'reminder_frequency', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_frequency` text");
        maybe_add_column($simForms->tableName, 'reminder_period', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_period` text");
        maybe_add_column($simForms->tableName, 'reminder_startdate', "ALTER TABLE $simForms->tableName ADD COLUMN `reminder_startdate` text");

        maybe_drop_column($simForms->tableName, 'settings', "ALTER TABLE $simForms->tableName DROP COLUMN `settings`");


    global $wpdb;
    global $Modules;


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

function works(){
    $socket   = stream_socket_client('unix:////home/simnige1/sockets/signal', $errno, $error);

    $params     = [
        "recipient"     => "+2349045252526",
        "message"       => "message" 
    ];
    $params["account"]  = "+2349011531222";

    $id     = time(); 

    $data   = [
        "jsonrpc"       => "2.0",
        "method"        => "send",
        "params"        => $params,
        "id"            => $id
    ];

    printArray(json_encode($data)."\n", true);

    fwrite($socket, json_encode($data));         

    flush();
    ob_flush();

    $request    = fread($socket, 4096);
    flush();

    $json   = json_decode($request);

    printArray($json, true);

}