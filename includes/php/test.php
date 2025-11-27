<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test", function ($atts){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    global $wpdb;
    global $Modules;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    $users  = get_users([
        'meta_key'      => '2fa_hash',
        'meta_compare'  => 'EXISTS'
    ]);

    foreach($users as $user){
        add_user_meta($user->ID, '2fa_methods', 'authenticator'); 
    }

    $users  = get_users([
        'meta_key'      => '2fa_webautn_cred',
        'meta_compare'  => 'EXISTS'
    ]);

    foreach($users as $user){
        add_user_meta($user->ID, '2fa_methods', 'webauthn'); 
    }
    
    $users  = get_users([
        'meta_key'      => '2fa_methods',
        'meta_compare'  => 'NOT EXISTS'
    ]);

    $family = new FAMILY\Family();
    foreach($users as $user){
        if($family->isChild($user->ID)){
            continue;
        }
        add_user_meta($user->ID, '2fa_methods', 'email'); 
    }
    
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );
