<?php
namespace SIM\PRAYER;
use SIM;

add_action('sim_frontpage_before_main_content', function(){
    if(!is_user_logged_in()){
        return;
    }

    $prayerRequest	= SIM\userPageLinks(apply_filters('sim_prayer_message', prayerRequest()));

    if (empty($prayerRequest)){
        return;
    }

    ?>
    <div name='prayer_request' style='margin-left: auto; margin-right: auto; font-size: 18px; color:#999999; width:80%; max-width:800px;'>
        <h3 id='prayertitle'>The prayer request of today:</h3>
        <p><?php echo $prayerRequest;?></p>
    </div>
    <?php
}, 5);