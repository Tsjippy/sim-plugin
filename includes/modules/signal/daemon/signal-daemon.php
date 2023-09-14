<?php

/**
 * this file should be run from cron
 * crontab -e -u simnige1
 *
 * Something like:
 * @reboot export DISPLAY=:0.0; export DBUS_SESSION_BUS_ADDRESS=unix:path=/run/user/1001/bus;/home/simnige1/web/simnigeria.org/public_html/wp-content/signal-cli/program/bin/signal-cli -o  json --trust-new-identities=always daemon | while read -r line; do find -name signal-daemon.php 2>/dev/null -exec php "{}" "$line" \; ; done;
 */
use SIM\SIGNAL\SignalBus;
use SIM;

if(!empty($argv) && count($argv) == 2){
    $data      = json_decode($argv[1]);

    // no message found
    if(!isset($data->envelope->dataMessage) || empty($data->envelope->dataMessage->message)){
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
        SIM\printArray($data);
        return;
    }

    // message to group
    if(
        isset($data->envelope->dataMessage->groupInfo)      &&
        isset($data->envelope->dataMessage->mentions)
    ){
        foreach($data->envelope->dataMessage->mentions as $mention){
            if($mention->number == $signal->phoneNumber || $mention->name == '8fc6c236-f07b-4a3c-97c5-0a78efe488ee'){
                $groupId    = $signal->groupIdToByteArray($data->envelope->dataMessage->groupInfo->groupId);
                $signal->sendGroupTyping($groupId);

                $message    = $data->envelope->dataMessage->message;

                // Remove mention from message
                $message    = substr($message, $data->envelope->dataMessage->mentions[0]->length);
                $answer     = getAnswer(trim($message), $data->envelope->source);

                $signal->sendGroupMessage($answer['response'], $groupId, $answer['pictures']);
            }
        }
    }elseif(!isset($data->envelope->dataMessage->groupInfo)){
        $signal->sentTyping($data->envelope->source, $data->envelope->dataMessage->timestamp);

        $answer = getAnswer($data->envelope->dataMessage->message, $data->envelope->source);

        $signal->send($data->envelope->source, $answer['response'], $answer['pictures']);
    }
}

function getAnswer($message, $source){
    $message = strtolower($message);

    //Change the user to the adminaccount otherwise get_users will not work
    wp_set_current_user(1);

    // Find the first name
    $name = false;
    $users = get_users(array(
        'meta_key'     => 'signal_number',
        'meta_value'   => $source ,
    ));

    if(!empty($users)){
        $name = $users[0]->first_name;
    }

    $pictures   = [];
    $response   = '';

    if($message == 'test'){
        $response    = 'Awesome!';
    }elseif($message == 'thanks'){
        $response = 'You`re welcome!';
    }elseif(strpos($message, 'prayer') !== false && $name){
        $prayerRequest  = SIM\PRAYER\prayerRequest(true, true);
        $response       = "This is the prayer for today:\n\n{$prayerRequest['prayer']}";
        $pictures       = $prayerRequest['pictures'];
    }elseif($message == 'hi' || strpos($message, 'hello') !== false){
        $response = "Hi ";
        if($name){
            $response   .= $name;
        }
    }elseif(!empty($message)){
        $response = 'I have no clue, do you know?';
    }else{
        $response = ' ';
    }

    return [
        'response'  => $response,
        'pictures'  => $pictures
    ];
}