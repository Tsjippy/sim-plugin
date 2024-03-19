<?php
namespace SIM\MAILCHIMP;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'add_mailchimp_campaigns_action', __NAMESPACE__.'\addMailchimpCampaigns' );
});

function scheduleTasks(){
    SIM\scheduleTask('add_mailchimp_campaigns_action', 'daily');
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
        return;
    }

	wp_clear_scheduled_hook( 'add_mailchimp_campaigns_action' );
}, 10, 2);

function addMailchimpCampaigns(){
    $mailchimp 	= new Mailchimp();

    // get all mailchimp campaigns created yesterday
    $result		= $mailchimp->getCampaigns(date("Y-m-d", strtotime('-1 day')).'T00:00:00+00:00');

    $templateId = SIM\getModuleOption(MODULE_SLUG, 'templateid');

    $pictures	= SIM\getModuleOption(MODULE_SLUG, 'picture_ids');

    $post = array(
        'post_type'		=> 'post',
        'post_status'   => "pending",
        'post_author'   => 1
    );

    foreach($result->campaigns as $campaign){
        // do not add mailchimp campaigns created by the website
        if( $campaign->settings->template_id != $templateId	){
            // make sure we do not add the same post twice
            $posts = get_posts(array(
                'numberposts'      => -1,
                'meta_query' 	=> array(
                    'relation' 		=> 'AND',
                    array(
                        'key' 		=> 'mailchimp_campaign_id',
                        'compare' 	=> 'EXISTS'
                    ),
                    array(
                        'key'	 	=> 'mailchimp_campaign_id',
                        'value' 	=> $campaign->id, 
                        'compare' 	=> '='
                    ),
                )
            ));

            if(empty($posts)){
                $post['post_title']		= $campaign->settings->title;
                if(empty($post['post_title']	)){
                    if(!empty($campaign->settings->subject_line)){
                        $post['post_title']	= $campaign->settings->subject_line;
                    }else{
                        continue;
                    }
                }
                $post['post_content']  	= "[mailchimp id='$campaign->id']";
            
                $postId 				= wp_insert_post( $post, true, false);

                if(is_array($pictures) && isset($pictures['imageId'])){
                    set_post_thumbnail( $postId, $pictures['imageId']);
                }

                update_post_meta($postId, 'mailchimp_campaign_id', $campaign->id);
            }

            SIM\printArray($campaign, true);
        }
    }
}