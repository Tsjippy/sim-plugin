<?php
namespace SIM\MAILCHIMP;
use SIM;
add_action('sim_after_post_save', function($post){
	//Mailchimp
	if(is_numeric($_POST['mailchimp_segment_id'])){
		if($post->post_status == 'publish'){
			//Send mailchimp
			$Mailchimp = new Mailchimp();
			$result = $Mailchimp->send_email($post->ID, intval($_POST['mailchimp_segment_id']), $_POST['mailchimp_email']);
			
			//delete any post metakey
			delete_post_meta($post->ID,'mailchimp_segment_id');
			delete_post_meta($post->ID,'mailchimp_email');
		}else{
			update_post_meta($post->ID,'mailchimp_segment_id',$_POST['mailchimp_segment_id']);
			update_post_meta($post->ID,'mailchimp_email',$_POST['mailchimp_email']);
		}
	}
});

// Add to mailchimp on user creation
add_action( 'sim_after_user_approval', function($user_id){
	//Add to mailchimp
	$Mailchimp = new Mailchimp($user_id);
	$Mailchimp->add_to_mailchimp();
});
