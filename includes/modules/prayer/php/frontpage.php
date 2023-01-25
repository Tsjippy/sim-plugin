<?php
namespace SIM\PRAYER;
use SIM;

add_action('sim_frontpage_before_main_content', function(){
    if(!is_user_logged_in()){
        return;
    }

    // Get the prayerrequest of the day, add extra messages to it, replace names with urls
    $prayerRequest	= prayerRequest();
    if(!$prayerRequest){
        return;
    }

    $message        = SIM\userPageLinks(apply_filters('sim_prayer_message', $prayerRequest['prayer']));

    ?>
    <style>
        #prayer-request{
            padding-left: 80px;
            font-size: 18px;
            color:#999999;
            width:80%;
            max-width:800px;
        }

        @media(max-width:768px) {
            #prayer-request{
                padding-left: 60px;
            }
        }
    </style>
    <div id='prayer-request'>
        <h3 id='prayertitle'>Today's Prayer Request</h3>
        <p><?php echo $message;?></p>
    </div>
    <?php
}, 5);