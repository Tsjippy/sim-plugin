<?php
namespace SIM\MAILCHIMP;
use SIM;

add_action('sim_frontend_post_after_content', function($frontendcontend){
    $mailchimpSegmentId	    = get_post_meta($frontendcontend->postId, 'mailchimp_segment_id', true);
    $mailchimpEmail		    = get_post_meta($frontendcontend->postId, 'mailchimp_email', true);
    $mailchimpExtraMessage  = get_post_meta($frontendcontend->postId, 'mailchimp_extra_message', true);
    $Mailchimp              = new Mailchimp($frontendcontend->user->ID);
    $segments               = $Mailchimp->getSegments();

    if($segments){
        ?>
        <div id="mailchimp" class="frontendform">
            <h4>Send <span class="replaceposttype"><?php echo $frontendcontend->postType;?></span> contents to the following Mailchimp group on <?php echo $frontendcontend->update == 'true' ? 'update' : 'publish';?>:</h4>
            <?php
            $sendSegment    = get_post_meta($frontendcontend->postId, 'mailchimp_message_send', true);
            if(is_numeric($sendSegment)){
                foreach($segments as $segment){
                    if($sendSegment == $segment->id){
                        $sendSegment    = $segment->name;
                    }
                }
                ?>
                <div class='warning' style='width: fit-content;'>
                    An e-mail has already been send to the <?php echo $sendSegment;?> group.
                </div>
                <?php
            }
            ?>
            <select name='mailchimp_segment_id' onchange="showMailChimp(this)">
                <option value="">---</option>
                <?php
                foreach($segments as $segment){
                    // Do not send it to the same group twice
                    if($sendSegment == $segment->id){
                        continue;
                    }elseif($mailchimpSegmentId == $segment->id){
                        $selected = 'selected="selected"';
                    }else{
                        $selected = '';
                    }
                    echo "<option value='{$segment->id}' $selected>{$segment->name}</option>";
                }
                ?>
            </select>

            <div class='mailchimp-wrapper hidden'>
                <h4>Use this from email address</h4>
                <input type='text' name='mailchimp_email' list='emails' value='<?php echo $mailchimpEmail;?>'>
                <datalist id='emails'>
                    <?php
                    $emails = [
                        'jos.personnel@sim.org'	=> 'jos.personnel',
                        'jos.dirassist@sim.org'	=> 'jos.dirassist',
                        'jos.director@sim.org'	=> 'jos.director',
                        'jos.health@sim.org'	=> 'jos.health',
                    ];
                    foreach($emails as $email=>$text){
                        echo "<option value='$email'>$text</option>";
                    }
                    ?>
                </datalist>

                <h4>Prepend the e-mail with this message:</h4>
                <textarea name='mailchimp-extra-message'><?php
                    echo $mailchimpExtraMessage;
                ?></textarea>
            </div>
        </div>
        <?php
    }
});

add_action('sim_after_post_save', function($post){
	//Mailchimp
	if(is_numeric($_POST['mailchimp_segment_id'])){
        $extraMessage   = str_replace("\n", '<br>', sanitize_text_field($_POST['mailchimp-extra-message']));
        update_metadata( 'post', $post->ID,'mailchimp_segment_id', $_POST['mailchimp_segment_id']);
        update_metadata( 'post', $post->ID,'mailchimp_email', $_POST['mailchimp_email']);
        update_metadata( 'post', $post->ID,'mailchimp_extra_message', $extraMessage);
    }
});

add_action( 'wp_after_insert_post', function( $postId, $post ){
    if(in_array($post->post_status, ['publish', 'inherit'])){
        $segmentId      = get_post_meta($postId, 'mailchimp_segment_id', true);
        $from           = get_post_meta($postId, 'mailchimp_email', true);
        $extraMessage   = get_post_meta($postId, 'mailchimp_extra_message', true);

        if(empty($segmentId) || empty($from)){
            return;
        }

        //Send mailchimp message
        $Mailchimp = new Mailchimp();
        $Mailchimp->sendEmail($postId, intval($segmentId), $from, $extraMessage);

        // Indicate as send
        update_metadata( 'post', $postId, 'mailchimp_message_send', $segmentId);

        //delete any post metakey
        delete_post_meta($postId,'mailchimp_segment_id');
        delete_post_meta($postId,'mailchimp_email');
        delete_post_meta($postId,'mailchimp_extra_message');
    }
}, 10, 3);