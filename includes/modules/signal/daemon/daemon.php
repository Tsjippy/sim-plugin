<?php
use SIM\SIGNAL\Signal;

$pidFile    = __DIR__.'\running.signal';

if(file_exists($pidFile)){
    return;
}

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

/* Iteration interval in seconds */
$sleep_time = 600;

file_put_contents($pidFile, 'running');

include_once __DIR__.'/../php/__module_menu.php';
include_once __DIR__.'/../php/classes/Signal.php';

$signal = new Signal();

while (file_exists($pidFile)){
    $result = $signal->receive();

    if(!empty($result)){
        $sender     = $result->envelope->source;

        $sourceName = $result->envelope->sourceName;

        if(isset($result->envelope->dataMessage)){
            $timestamp  = $result->envelope->dataMessage->timestamp;

            $message    = $result->envelope->dataMessage->message;

            $signal->sentTyping($recipient, $timestamp);

            if($message == 'Test'){
                echo "Sending reply\n";
                echo $signal->send($sender, 'Awesome!');
            }

            echo $signal->send($sender, 'I have seen it!');
        }

        echo "Received:\n";
        print_r($result, true);
        echo "\n";
    }else{
        echo "Nothing to process\n";
    }
}

echo "Stop command received";