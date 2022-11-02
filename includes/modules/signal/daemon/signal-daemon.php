<?php
use SIM\SIGNAL\SignalBus;

if(!empty($argv) && count($argv) == 2){
    $data      = json_decode($argv[1]);

    if(!isset($data->envelope->dataMessage)){
        return;
    }

    // load wp
    ob_start();
    define( 'WP_USE_THEMES', false ); // Do not use the theme files
    define( 'COOKIE_DOMAIN', false ); // Do not append verify the domain to the cookie

    require(__DIR__."/../../../../../../../wp-load.php");
    require_once ABSPATH . WPINC . '/functions.php';

    $discard = ob_get_clean();

    /* Remove the execution time limit */
    set_time_limit(0);

    include_once __DIR__.'/../php/__module_menu.php';
    include_once __DIR__.'/../php/classes/Signal.php';

    $signal = new SignalBus();

    if($data->account != $signal->phoneNumber){
        return;
    }

    $signal->sentTyping($data->envelope->source, $data->envelope->dataMessage->timestamp);

    $message = strtolower($data->envelope->dataMessage->message);
    if($message == 'test'){
        echo "Sending reply\n";
        echo $signal->send($data->envelope->source, 'Awesome!');
    }elseif(strpos($message, 'prayer') !== false){
        $signal->send($data->envelope->source, 'This is the prayer for today');
        $signal->send($data->envelope->source, SIM\PRAYER\prayerRequest(true));
    }

    echo $signal->send($data->envelope->source, 'I have seen it!');
}