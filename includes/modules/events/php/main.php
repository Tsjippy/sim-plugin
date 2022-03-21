<?php
namespace SIM\EVENTS;
use SIM;

/* add_action('send_event_reminder_action', function(){
    $events = new Events();
    $events->send_event_reminder();
}); */

add_action('send_event_reminder_action', function ($event_id){
    $events = new Events();
    $events->send_event_reminder($event_id);
});