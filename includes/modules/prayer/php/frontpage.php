<?php
namespace SIM\PRAYER;
use SIM;

$hook   = SIM\getModuleOption(MODULE_SLUG, 'frontpagehook');
if($hook ){
    add_action($hook, function(){
        $prayerRequest = SIM\userPageLinks(prayerRequest());
        if (empty($prayerRequest)){
            return;
        }

        ?>
        <div name='prayer_request' style='text-align: center; margin-left: auto; margin-right: auto; font-size: 18px; color:#999999; width:80%; max-width:800px;'>
            <h3 id='prayertitle'>The prayer request of today:</h3>
            <p><?php echo $prayerRequest;?></p>
        </div>
        <?php
    }, 5);
}