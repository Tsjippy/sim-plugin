<?php
namespace SIM\BANKING;
use SIM;

add_action('sim_user_dashboard', function($userId, $admin) {
    if(!$admin){
        ?>
        <div id="Account statements" style="margin-top:20px;">
            <?php
            echo show_statements();
            ?>
        </div>
        <?php
    }
}, 10, 2);