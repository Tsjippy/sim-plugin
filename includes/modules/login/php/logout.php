<?php
namespace SIM\LOGIN;
use SIM;

add_action('wp_ajax_request_logout', function(){
    wp_logout();
    wp_die('Log out success');
});