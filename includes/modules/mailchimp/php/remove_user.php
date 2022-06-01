<?php
namespace SIM\MAILCHIMP;
use SIM;

add_action('delete_user', function ($userId){
    //remove category from mailchimp

    // 388, 359, 369, 334
    $userTags			= SIM\getModuleOption('mailchimp', 'user_tags');
    $missionaryTags	    = SIM\getModuleOption('mailchimp', 'user_tags');
    $tags               = array_merge(explode(',', $userTags), explode(',', $missionaryTags));
    $Mailchimp          = new Mailchimp($userId);

    $Mailchimp->changeTags($tags, 'inactive');
});