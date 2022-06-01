<?php
namespace SIM\MAILCHIMP;
use SIM;

add_action('sim_location_update', function($userId, $location){
    //Update mailchimp tags if needed
    $mailchimp = new Mailchimp($userId);
    if(strpos(strtolower($location['address']),'jos') !== false){
        //Live in Jos, add the tags
        $mailchimp->updateFamilyTags(['Jos'], 'active');
        $mailchimp->updateFamilyTags(['not-Jos'], 'inactive');
    }else{
        $mailchimp->updateFamilyTags(['Jos'], 'inactive');
        $mailchimp->updateFamilyTags(['not-Jos'], 'active');
    }
}, 10, 2);