<?php
namespace SIM\MAILCHIMP;
use SIM;

add_action('delete_user', function ($user_id){
    //remove category from mailchimp
    $user_tags			= SIM\get_module_option('mailchimp', 'user_tags');
    $missionary_tags	= SIM\get_module_option('mailchimp', 'user_tags');
    $tags               = array_merge(explode(',', $user_tags), explode(',', $$missionary_tags));
    $Mailchimp          = new Mailchimp($user_id);

    $Mailchimp->change_tags($tags, 'inactive');
});