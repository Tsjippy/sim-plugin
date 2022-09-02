<?php
namespace SIM\PRAYER;
use SIM;
use WP_Error;

/**
 * Checks if the current post has the prayer category
 * if so checks if it has an word attachment
 * 
 * returns an error when it does not have the month and year in the title
 */
add_filter('sim_frontend_content_validation', function($error, $frontEndContent){
    // do not continue if the post content contains less than 28 prayerpoints
    if(is_wp_error($error) || preg_match_all('/\d{1,2}\([S|M|T|W|F]\)/i', strip_tags($frontEndContent->postContent), $matches) < 20){
        return $error;
    }

    $prayerCatId    = get_cat_ID('prayer');
    if(!$prayerCatId && !in_array($prayerCatId, $frontEndContent->categories)){
        return new WP_Error('prayer', "I guess you are submitting a post with prayerpoints.<br><br>Please add the prayer category.");
    }

    $months = [];
    setlocale(LC_TIME, get_locale());
    for($m=1; $m<=12; $m++){
        $months[]   = strftime("%B", mktime(0, 0, 0, $m, 12));
    }

    // Check if the title contains the month
    $found  = false;
    foreach($months as $month){
        if(strpos($frontEndContent->postTitle, $month) !== false){
            $found  = true;
            break;
        }
    }

    if(!$found){
        return new WP_Error('prayer', "I guess you are submitting a post with prayerpoints?<br><br>Please make sure the monthname is included in the post title.");
    }

    $years  = [Date('Y'), Date('Y')+1];
    $found  = false;
    foreach($years as $year){
        if(strpos($frontEndContent->postTitle, strval($year)) !== false){
            $found  = true;
            break;
        }
    }

    if(!$found){
        return new WP_Error('prayer', "I guess you are submitting a post with prayerpoints?<br><br>Please make sure the year is included in the post title.");
    }

    //month year
    return $error;
}, 10, 2);