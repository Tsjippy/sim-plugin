<?php
namespace SIM\ARCHIVE;
use SIM;

// Add archive button

add_filter('sim-frontend-buttons', function($html, $fontendContend){
    if(!empty($fontendContend->post) && $fontendContend->post->post_status != 'archived'){
        ob_start();
        ?>
        <div class='submit_wrapper'>
            <button type='submit' class='button' name='archive_post' data-post_id='<?php echo  esc_html($fontendContend->postId); ?>'>
                Archive <?php echo  esc_html($fontendContend->post->post_type); ?>
            </button>
            <img class='loadergif hidden' src='<?php echo SIM\LOADERIMAGEURL; ?>' alt='' loading='lazy' style='max-height:30px;margin-top:0px;'>
        </div>
        <?php

        $html   = ob_get_clean().$html;
    }

    return $html;
}, 10, 2);