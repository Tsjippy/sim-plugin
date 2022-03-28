<?php
namespace SIM\MAILCHIMP;
use SIM;

add_action('frontend_post_after_content', function($frontendcontend){
    $mailchimp_segment_id	= get_post_meta($frontendcontend->post_id, 'mailchimp_segment_id', true);
    $mailchimp_email		= get_post_meta($frontendcontend->post_id, 'mailchimp_email', true);

    $Mailchimp = new Mailchimp($frontendcontend->user->ID);

    $segments = $Mailchimp->get_segments();
    if($segments){
        ?>
        <div id="mailchimp" class="frontendform">
            <h4>Send <span class="replaceposttype"><?php echo $frontendcontend->post_type;?></span> contents to the following Mailchimp group on publish:</h4>
            <select name='mailchimp_segment_id'>
                <option value="">---</option>
            <?php
                foreach($segments as $segment){
                    if($mailchimp_segment_id == $segment->id){
                        $selected = 'selected';
                    }else{
                        $selected = '';
                    }
                    echo "<option value='{$segment->id}' $selected>{$segment->name}</option>";
                }
            ?>
            </select>
            
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
                    if($mailchimp_email == $email){
                        $selected = 'selected';
                    }else{
                        $selected = '';
                    }
                    echo "<option value='$email' $selected>$text</option>";
                }
                ?>
            </select>
        </div>
        <?php 
    }
});