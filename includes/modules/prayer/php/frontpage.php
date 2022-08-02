<?php
namespace SIM\PRAYER;
use SIM;

add_filter('sim_frontpage_message', function($message){
    $prayerRequest = SIM\userPageLinks(prayerRequest());
    if (empty($prayerRequest)){
        return $message;
    }

    $message    .= "<div name='prayer_request' style='text-align: center; margin-left: auto; margin-right: auto; font-size: 18px; color:#999999; width:80%; max-width:800px;'>";
        $message    .= "<h3 id='prayertitle'>The prayer request of today:</h3>";
        $message    .= "<p>$prayerRequest</p>";
    $message    .= "</div>";

    return $message;
}, 5);