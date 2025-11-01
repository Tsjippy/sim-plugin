<?php
namespace SIM;

add_shortcode('debug', function($atts){
    wp_enqueue_script('sim_debug_script');

    return "<button type='button' id='exportLogsButton'>Export Debug Log</button>";
});
