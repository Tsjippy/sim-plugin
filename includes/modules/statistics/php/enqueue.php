<?php
namespace SIM\STATISTICS;
use SIM;

add_action('wp_enqueue_scripts', function(){
    //Load js
    wp_enqueue_script('sim_statistics_script', plugins_url('js/statistics.min.js', __DIR__), array(), MODULE_VERSION,true);
});