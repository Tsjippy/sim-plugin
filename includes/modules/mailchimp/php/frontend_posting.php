<?php
namespace SIM\MAILCHIMP;
use SIM;

add_action('sim_frontend_post_after_content', function($frontendContend){
    $mailchimpSegmentId	    = $frontendContend->getPostMeta('mailchimp_segment_id');
    $mailchimpEmail		    = $frontendContend->getPostMeta('mailchimp_email');
    $mailchimpExtraMessage  = $frontendContend->getPostMeta('mailchimp_extra_message');
    $Mailchimp              = new Mailchimp($frontendContend->user->ID);
    $segments               = $Mailchimp->getSegments();

    if($segments){
        ?>
        <div id="mailchimp" class="frontendform">
            <h4>Send <span class="replaceposttype"><?php echo $frontendContend->postType;?></span> contents to the following Mailchimp group on <?php echo $frontendContend->update == 'true' ? 'update' : 'publish';?>:</h4>
            <?php
            $sendSegment    = $frontendContend->getPostMeta('mailchimp_message_send');
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
    }else{
        delete_metadata( 'post', $post->ID,'mailchimp_segment_id');
        delete_metadata( 'post', $post->ID,'mailchimp_email');
        delete_metadata( 'post', $post->ID,'mailchimp_extra_message');
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
        $Mailchimp  = new Mailchimp();
        $result     = $Mailchimp->sendEmail($postId, intval($segmentId), $from, $extraMessage);

        // Indicate as send
        if($result == 'succes'){
            update_metadata( 'post', $postId, 'mailchimp_message_send', $segmentId);

            //delete any post metakey
            delete_post_meta($postId,'mailchimp_segment_id');
            delete_post_meta($postId,'mailchimp_email');
            delete_post_meta($postId,'mailchimp_extra_message');
        }
    }
}, 10, 3);