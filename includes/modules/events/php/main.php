<?php
namespace SIM\EVENTS;
use SIM;

add_action('send_event_reminder_action', function ($event_id){
    $events = new DisplayEvents();
    $events->sendEventReminder($event_id);
});

add_action( 'before_delete_post', function($postId){
    $events = new CreateEvents();
    $events->removeDbRows($postId);
});