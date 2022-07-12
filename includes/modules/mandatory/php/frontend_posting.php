<?php
namespace SIM\MANDATORY;
use SIM;

/**
 * Adding fields to the frontend posting screen
 * @param  object $frontendContend 	frontendContend instance            
*/
add_action('sim_frontend_post_after_content', function($frontendContend){
    $audience = get_post_meta($frontendContend->postId, "audience", true);

    $checked	= '';
    if(isset($audience['normal']) or !is_array($audience)){
        $checked	= 'checked';
    }

    ?>
    <div id="recipients" class="frontendform post page<?php if($frontendContend->postType != 'page' and $frontendContend->postType != 'post') echo ' hidden'; ?>">
        <h4>Audience</h4>				
        <?php
        if($frontendContend->postId != null){
            if(is_array($audience) and count($audience)>0){
                ?>
                <input type="checkbox" name="audience[normal]" value="normal" <?php echo $checked; ?>>
                <label for="normal">Normal</label><br>
                <?php
            }
        }
        
        $audienceHtml	= "<label>";
			if(isset($audience['beforearrival'])){
				$checked	= 'checked';
			}else{
				$checked	= '';
			}
			$audienceHtml	.= "<input type='checkbox' name='audience[beforearrival]' value='beforearrival' $checked>";
			$audienceHtml	.= "People should read this before arriving in the country (pre-field)";
        $audienceHtml	.= "</label><br>";
        $audienceHtml	.= "<label>";
			if(isset($audience['afterarrival'])){
				$checked	= 'checked';
			}else{
				$checked	= '';
			}
			$audienceHtml	.= "<input type='checkbox' name='audience[afterarrival]' value='afterarrival' $checked>";
			$audienceHtml	.= "People should read this after arriving in the country";
        $audienceHtml	.= "</label><br>";
        $audienceHtml	.= "<label>";
			if(isset($audience['everyone'])){
				$checked	= 'checked';
			}else{
				$checked	= '';
			}
			$audienceHtml	.= "<input type='checkbox' name='audience[everyone]' value='everyone' $checked>";
			$audienceHtml	.= "Everyone must read this no matter how long in the country";
        $audienceHtml	.= "</label><br>";

		echo apply_filters('sim_mandatory_audience_param', $audienceHtml, $audience);
	?>
    </div>
    <?php
});

/**
 * Save the mandatory options
 * @param  object $frontendContend 	frontendContend instance            
*/
add_action('sim_after_post_save', function($post){
	//store audience
	if(is_array($_POST['audience'])) {
		$audiences = $_POST['audience'];
		
		//Reset to normal if that box is ticked
		if(isset($audiences['normal']) and $audiences['normal'] == 'normal'){
			delete_post_meta($post->ID, "audience");
		//Store in DB
		}else{
			SIM\cleanUpNestedArray($audiences);
			
			//Only continue if there are audiences defined
			if(count($audiences)>0){
				update_metadata( 'post', $post->ID, "audience", $audiences);
			
				//Mark existing users as if they have read the page if this pages should be read by new people after arrival
				if(isset($audiences['afterarrival']) and !isset($audiences['everyone'])){
					//Get all users who are longer than 1 month in the country
					$users = get_users(array(
						'meta_query' => array(
							array(
								'key' => 'arrival_date',
								'value' => date('Y-m-d', strtotime("-1 months")),
								'type' => 'date',
								'compare' => '<='
							)
						),
					));
					
					//Loop over the users
					foreach($users as $user){
						//get current already read pages
						$readPages		= (array)get_user_meta( $user->ID, 'read_pages', true );
		
						//add current page
						$readPages[]	= $post->ID;
						//update
						update_user_meta( $user->ID, 'read_pages', $readPages);
					}
				}

				do_action('sim_mandatory_save_audience_param', $audiences, $post);
			}
		}
	}else{
		delete_post_meta($post->ID, "audience");
	}
});

/**
 * Adds a message to the Signal message send about the content being mandatory
 * @param  string $message 	Signal message   
 * @return string			The message         
*/
add_filter('sim_signal_post_notification_message', function($message, $post){
	$audience	= get_post_meta($post->ID, "audience", true);
	if(is_array($audience) and !empty($audience['everyone'])) $message	.= "\n\nThis is a mandatory message, please read it straight away.";

	return $message;
}, 10, 2);