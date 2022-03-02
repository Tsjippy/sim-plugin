<?php
namespace SIM\EVENTS;
use SIM;

add_filter('sim_after_bot_payer', function($messages){
    global $Events;

    $Events->retrieve_events(date('Y-m-d'),date('Y-m-d'));
    foreach($Events->events as $event){
        $start_year	= get_post_meta($event->ID,'celebrationdate',true);
        //only add events which are not a celebration and start today after curent time
        if(empty($start_year) and $event->startdate == date('Y-m-d') and $event->starttime > date('h:i')){
            $messages['message']    .= "\n\n".$event->post_title.' starts today at '.$event->starttime;
            $messages['urls']	    .= get_permalink($event->ID);
        }
    }

    return $messages;
});