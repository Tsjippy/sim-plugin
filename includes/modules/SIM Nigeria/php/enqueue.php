<?php
namespace SIM;

add_action( 'wp_enqueue_scripts', function(){
    wp_enqueue_script( 'sim_quotajs', plugins_url('js/quota.js', __DIR__), array('sim_other_script'), 2,true);
});