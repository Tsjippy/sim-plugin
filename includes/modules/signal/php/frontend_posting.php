<?php
namespace SIM\SIGNAL;
use SIM;

add_action('sim_frontend_post_after_content', function($frontendContend){
    $hidden	= 'hidden';
    if($frontendContend->fullrights and ($frontendContend->postId == null or !empty(get_post_meta($frontendContend->postId, 'signal', true)))){
        $checked 	    = 'checked';
        $hidden		    = '';
        $messageType	= get_post_meta($frontendContend->postId,'signalmessagetype',true);
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
                <input type='radio' name='signalmessagetype' value='summary' <?php if($messageType != 'all') echo 'checked';?>>
                Send a summary
            </label>
            <label>
                <input type='radio' name='signalmessagetype' value='all' <?php if($messageType == 'all') echo 'checked';?>>
                Send the whole post content
            </label>
            <br>
            <br>
            <label>
                Add this sentence to the signal message:<br>
                <input type="text" name="signal_extra_message">
            </label>
            <br>
            <br>
            <label>
                <input type="checkbox" name="signal_url">
                Include the url in the message even if the whole content is posted
            </label>
        </div>
    </div>
    <?php
});


// Send Signal message about the new or updated post
add_action('sim_after_post_save', function($post, $update){
    if(isset($_POST['signal']) and $_POST['signal'] == 'send_signal'){
        update_metadata( 'post', $post->ID, 'signal','checked');
        update_metadata( 'post', $post->ID, 'signalmessagetype', $_POST['signalmessagetype']);
        update_metadata( 'post', $post->ID, 'signal_url', $_POST['signal_url']);
        update_metadata( 'post', $post->ID, 'signal_extra_message', $_POST['signal_extra_message']);

        if(in_array($post->post_status, ['publish', 'inherit'])){
            //Send signal message
            sendPostNotification($post);
        }
    }
}, 999, 2);