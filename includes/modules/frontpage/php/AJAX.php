<?php
namespace SIM\FRONTPAGE;
use SIM;

add_action ( 'wp_ajax_hideWelcome', function(){
    update_user_meta(get_current_user_id(), 'welcomemessage', true);
});