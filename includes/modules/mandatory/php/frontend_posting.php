<?php
namespace SIM\MANDATORY;
use SIM;

/**
 * Get the mandatory audience options
 */
function getAudienceOptions($audience, $postId){
	$keys	= [
		'beforearrival'		=> "People should read this before arriving in the country (pre-field)",
		'afterarrival'		=> "People should read this after arriving in the country",
		'everyone'			=> "Everyone must read this no matter how long in the country"
	];


	if($postId != null && is_array($audience) && !empty($audience)){
		$keys['normal'] = "normal";
	}

	return apply_filters('sim_mandatory_audience_param', $keys);
}

/**
 * Adding fields to the frontend posting screen
 * @param  object $frontendContend 	frontendContend instance
*/
add_action('sim_frontend_post_after_content', function($frontendContend){
	$audience   = $frontendContend->getPostMeta('audience');
    if(!is_array($audience) && !empty($audience)){
        $audience  = json_decode($audience, true);
    }

    ?>
    <div id="recipients" class="frontendform property post page<?php if($frontendContend->postType != 'page' && $frontendContend->postType != 'post'){echo ' hidden'; }?>">
        <h4>Audience</h4>
        <?php
		$keys	= getAudienceOptions($audience, $frontendContend->postId);

		foreach($keys as $key=>$label){
			if(isset($audience[$key])){
				$checked	= 'checked';
			}else{
				$checked	= '';
			}

			echo "<label>";
				echo "<input type='checkbox' name='audience[$key]' value='$key' $checked>";
				echo $label;
			echo "</label><br>";
		}
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
	if(!is_array($_POST['audience'])) {
		delete_post_meta($post->ID, "audience");

		return;
	}
	$audiences = $_POST['audience'];

	//Reset to normal if that box is ticked
	if(isset($audiences['normal']) && $audiences['normal'] == 'normal'){
		delete_post_meta($post->ID, "audience");
	//Store in DB
	}else{
		SIM\cleanUpNestedArray($audiences);

		//Only continue if there are audiences defined
		if(!empty($audiences)){
			update_metadata( 'post', $post->ID, "audience", json_encode($audiences));

			//Mark existing users as if they have read the page if this pages should be read by new people after arrival
			if(isset($audiences['afterarrival']) && !isset($audiences['everyone'])){
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
});

/**
 * Adds a message to the Signal message send about the content being mandatory
 * @param  string $message 	Signal message
 * @return string			The message
*/
add_filter('sim_signal_post_notification_message', function($message, $post){
	$audience   = get_post_meta($post->ID, 'audience', true);
    if(!is_array($audience) && !empty($audience)){
        $audience  = json_decode($audience, true);
    }
	if(is_array($audience) && !empty($audience['everyone'])){
		$message	.= "\n\nThis is a mandatory message, please read it straight away.";
	}

	return $message;
}, 10, 2);