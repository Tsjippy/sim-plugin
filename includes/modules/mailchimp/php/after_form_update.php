<?php
namespace SIM\MAILCHIMP;
use SIM;

add_action('sim_location_update', function($userId, $location){
    //Update mailchimp tags if needed
    $mailchimp = new Mailchimp($userId);
    if(strpos(strtolower($location['address']),'jos') !== false){
        //Live in Jos, add the tags
        $mailchimp->update_family_tags(['Jos'], 'active');
        $mailchimp->update_family_tags(['not-Jos'], 'inactive');
    }else{
        $mailchimp->update_family_tags(['Jos'], 'inactive');
        $mailchimp->update_family_tags(['not-Jos'], 'active');
    }
}, 10, 2);