<?php
namespace SIM\EVENTS;
use SIM;

add_filter('sim_after_bot_payer', function($messages){
    $events		= new DisplayEvents();

    $events->retrieveEvents(date('Y-m-d'),date('Y-m-d'));
    foreach($events->events as $event){
        $startYear	= get_post_meta($event->ID, 'celebrationdate', true);

        //only add events which are not a celebration and start today after curent time
        if(empty($startYear) && $event->startdate == date('Y-m-d') && $event->starttime > date('H:i', current_time('U'))){
            $messages['message']    .= "\n\n".$event->post_title.' starts today at '.$event->starttime;
            $messages['urls']	    .= get_permalink($event->ID);
        }
    }

    return $messages;
});