<?php
namespace SIM\EVENTS;
use SIM;

add_action('send_event_reminder_action', function ($event_id){
    $events = new Events();
    $events->send_event_reminder($event_id);
});

add_action( 'before_delete_post', function($post_id){
    $events = new Events();
    $events->remove_db_rows($post_id);
});