<?php
namespace SIM\MANDATORY;
use SIM;

add_action( 'user_register', function($userId, $user){
    // mark all documents as read for this new user so now old pages need to be read
    markAllAsRead($userId);
}, 10, 2 );