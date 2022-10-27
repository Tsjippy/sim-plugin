<?php
use SIM\SIGNAL\Signal;

error_log(print_r($argv,true));

// load wp
ob_start();
define( 'WP_USE_THEMES', false ); // Do not use the theme files
define( 'COOKIE_DOMAIN', false ); // Do not append verify the domain to the cookie
define( 'DISABLE_WP_CRON', true );

require(__DIR__."/../../../../../../../wp-load.php");
require_once ABSPATH . WPINC . '/functions.php';

$discard = ob_get_clean();

/* Remove the execution time limit */
set_time_limit(0);

include_once __DIR__.'/../php/__module_menu.php';
include_once __DIR__.'/../php/classes/Signal.php';

$signal = new Signal();

if(!empty($argv) && count($argv) == 5){

    SIM\printArray($argv, true);

    $timestamp      = $argv[0];
    $source         = $argv[1];
    $groupId        = $argv[2];
    $message        = $argv[3];
    $attachments    = $argv[4];
    
    $sender     = $result->envelope->source;

    $sourceName = $result->envelope->sourceName;

    if(isset($result->envelope->dataMessage)){
        $timestamp  = $result->envelope->dataMessage->timestamp;

        $message    = strtolower($result->envelope->dataMessage->message);

        $signal->sentTyping($recipient, $timestamp);

        if($message == 'Test'){
            echo "Sending reply\n";
            echo $signal->send($sender, 'Awesome!');
        }elseif(strpos($message, 'prayer') !== false){
            $signal->send($sender, SIM\PRAYER\prayerRequest(true));
        }

        echo $signal->send($sender, 'I have seen it!');
    }

    echo "Received:\n";
    print_r($result, true);
    echo "\n";
}