<?php
namespace SIM\PRAYER;
use SIM;

/**
 * Checks if the current post has the prayer category
 * if so checks if it has an word attachment
 * 
 * returns an error when it does not have the month and year in the title
 */
add_filter('sim_frontend_content_validation', function($error, $frontEndContent){
    //month year
    return $error;
}, 10, 2);