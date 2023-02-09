<?php
namespace SIM\ARCHIVE;
use SIM;

// Add archive button

add_filter('sim-frontend-buttons', function($html, $fontendContend){
    if($fontendContend->post->post_status != 'archived'){
        ob_start();
        ?>
        <div class='submit_wrapper'>
            <form>
                <input hidden name='post_id' value='<?php echo  esc_html($fontendContend->postId); ?>'>

                <button type='submit' class='button' name='archive_post'>Archive <?php echo  esc_html($fontendContend->post->post_type); ?></button>
                <img class='loadergif hidden' src='<?php echo LOADERIMAGEURL; ?>' alt='' loading='lazy'>
            </form>
        </div>
        <?php

        $html   = ob_get_clean().$html;
    }

    return $html;
}, 10, 2);