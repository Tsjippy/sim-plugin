<?php
namespace SIM\MAILCHIMP;
use SIM;

add_action('frontend_post_after_content', function($frontendcontend){
    // Only show when not yet published
    if($frontendcontend->post->post_status != 'publish'){

        $mailchimpSegmentId	    = get_post_meta($frontendcontend->postId, 'mailchimp_segment_id', true);
        $mailchimpEmail		    = get_post_meta($frontendcontend->postId, 'mailchimp_email', true);
        $mailchimpExtraMessage  = get_post_meta($frontendcontend->postId, 'mailchimp_extra_message', true);

        $Mailchimp = new Mailchimp($frontendcontend->user->ID);

        $segments = $Mailchimp->get_segments();
        if($segments){
            ?>
            <div id="mailchimp" class="frontendform">
                <script>
                    function showMailChimp(target){
                        target.closest('.frontendform').querySelector('.mailchimp-wrapper').classList.toggle('hidden');
                    }
                </script>
                <h4>Send <span class="replaceposttype"><?php echo $frontendcontend->postType;?></span> contents to the following Mailchimp group on publish:</h4>
                <select name='mailchimp_segment_id' onchange="showMailChimp(this)">
                    <option value="">---</option>
                    <?php
                    foreach($segments as $segment){
                        if($mailchimpSegmentId == $segment->id){
                            $selected = 'selected';
                        }else{
                            $selected = '';
                        }
                        echo "<option value='{$segment->id}' $selected>{$segment->name}</option>";
                    }
                    ?>
                </select>
                
                <div class='mailchimp-wrapper hidden'>
                    <h4>Use this from email address</h4>
                    <select name='mailchimp_email'>
                        <option value=''>---</option>
                        
                        <?php
                        $emails = [
                            'jos.personnel@sim.org'	=> 'jos.personnel',
                            'jos.dirassist@sim.org'	=> 'jos.dirassist',
                            'jos.director@sim.org'	=> 'jos.director',
                        ];
                        foreach($emails as $email=>$text){
                            if($mailchimpEmail == $email){
                                $selected = 'selected';
                            }else{
                                $selected = '';
                            }
                            echo "<option value='$email' $selected>$text</option>";
                        }
                        ?>
                    </select>

                    <h4>Prepend the e-mail with this message:</h4>
                    <textarea name='mailchimp-extra-message'><?php
                        echo $mailchimpExtraMessage;
                    ?></textarea>
                </div>
            </div>
            <?php 
        }
    }
});

add_action('sim_after_post_save', function($post){
	//Mailchimp
	if(is_numeric($_POST['mailchimp_segment_id'])){
        $extraMessage   = str_replace("\n", '<br>', sanitize_text_field($_POST['mailchimp-extra-message']));
		if($post->post_status == 'publish'){
			//Send mailchimp
			$Mailchimp = new Mailchimp();
			$result = $Mailchimp->sendEmail($post->ID, intval($_POST['mailchimp_segment_id']), $_POST['mailchimp_email'], $extraMessage);
			
			//delete any post metakey
			delete_post_meta($post->ID,'mailchimp_segment_id');
			delete_post_meta($post->ID,'mailchimp_email');
            delete_post_meta($post->ID,'mailchimp_extra_message');
		}else{
			update_metadata( 'post', $post->ID,'mailchimp_segment_id', $_POST['mailchimp_segment_id']);
			update_metadata( 'post', $post->ID,'mailchimp_email', $_POST['mailchimp_email']);
            update_metadata( 'post', $post->ID,'mailchimp_extra_message', $extraMessage);
		}
	}
});

add_action('sim_roles_changed', function($user, $new_roles){
    //Check if new roles require mailchimp actions
    $Mailchimp = new Mailchimp($user->ID);
    $Mailchimp->role_changed($new_roles);
}, 10, 2);