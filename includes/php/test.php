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

    $github		= new GITHUB\Github();

		$result		= $github->downloadFromGithub('Tsjippy', 'login', MODULESPATH.'login', true);
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );
