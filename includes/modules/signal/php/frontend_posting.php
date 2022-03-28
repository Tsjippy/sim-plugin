<?php
namespace SIM\SIGNAL;
use SIM;

add_action('frontend_post_after_content', function($frontendcontend){
    $hidden	= 'hidden';
    if($frontendcontend->fullrights and ($frontendcontend->post_id == null or !empty(get_post_meta($frontendcontend->post_id,'signal',true)))){
        $checked 	    = 'checked';
        $hidden		    = '';
        $messagetype	= get_post_meta($frontendcontend->post_id,'signalmessagetype',true);
    }

    ?>
    <div id="signalmessage" class="frontendform">
        <h4>Signal</h4>	
        <label>
            <input type='checkbox' name='signal' value='send_signal' <?php echo $checked; ?>>
            Send signal message on publish
        </label>

        <div class='signalmessagetype <?php echo $hidden;?>' style='margin-top:15px;'>
            <label>
                <input type='radio' name='signalmessagetype' value='summary' <?php if($messagetype != 'all') echo 'checked';?>>
                Send a summary
            </label>
            <label>
                <input type='radio' name='signalmessagetype' value='all' <?php if($messagetype == 'all') echo 'checked';?>>
                Send the whole post content
            </label>
            <br>
            <br>
            <label>
                Add this sentence to the signal message:<br>
                <input type="text" name="signal_extra_message">
            </label>
        </div>
    </div>
    <?php
});


// Send Signal message about the new or updated post
add_action('sim_after_post_save', function($post, $update){
    if(isset($_POST['signal']) and $_POST['signal'] == 'send_signal'){
        if($post->post_status == 'publish'){
            delete_post_meta($post->ID, 'signal');
            delete_post_meta($post->ID, 'signalmessagetype');

            //Send signal message
            send_post_notification($post);
        }else{
            update_post_meta($post->ID, 'signal','checked');
            update_post_meta($post->ID, 'signalmessagetype', $_POST['signalmessagetype']);
        }
    }    

    if($post->post_status == 'pending'){
        SIM\FRONTEND_POSTING\send_pending_post_warning($post, $update);
    }
}, 999, 2);