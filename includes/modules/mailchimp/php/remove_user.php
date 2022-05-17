<?php
namespace SIM\MAILCHIMP;
use SIM;

add_action('delete_user', function ($userId){
    //remove category from mailchimp

    // 388, 359, 369, 334
    $user_tags			= SIM\get_module_option('mailchimp', 'user_tags');
    $missionary_tags	= SIM\get_module_option('mailchimp', 'user_tags');
    $tags               = array_merge(explode(',', $user_tags), explode(',', $$missionary_tags));
    $Mailchimp          = new Mailchimp($userId);

    $Mailchimp->change_tags($tags, 'inactive');
});