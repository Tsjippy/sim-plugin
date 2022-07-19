<?php
namespace SIM\USERPAGE;
use SIM;

/**
 * Create a user page on user registration or approval
 */
add_action( 'sim_approved_user', function($userId){
    if(get_user_meta( $userId, 'disabled', true) == 'pending'){
        return;
    }

    createUserPage($userId);
} );