<?php
namespace SIM\EVENTS;
use SIM;

add_action('send_event_reminder_action', function ($eventId){
    $events = new DisplayEvents();
    $events->sendEventReminder($eventId);
});

add_action( 'before_delete_post', function($postId){
    $events = new CreateEvents();
    $events->removeDbRows($postId);
});