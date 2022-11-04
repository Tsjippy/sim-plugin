<?php
namespace SIM;

use mikehaertl\shellcommand\Command;

//Shortcode for testing
add_shortcode("test",function ($atts){
    global $wpdb;

    require_once( __DIR__  . '/../modules/signal/lib/vendor/autoload.php');

    //$signal = new SIGNAL\Signal();


    $command = new Command([
        'command' => "php /home/web/demo.simnigeria.org/public_html/wp-content/plugins/sim-plugin/includes/modules/signal/daemon/signal-daemon.php 'test'"
    ]);

    $command->execute();

    printArray($command, true);
  /*   if(!$signal->valid){
        return '<div class="error">'.$signal->error->get_error_message().'</div>';
    } */

    
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );