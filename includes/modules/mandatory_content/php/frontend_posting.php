<?php
namespace SIM\MANDATORY;
use SIM;

add_action('frontend_post_after_content', function($frontendcontend){
    $audience = get_post_meta($frontendcontend->post_id, "audience", true);

    $checked	= '';
    if(isset($audience['normal']) or !is_array($audience)){
        $checked	= 'checked';
    }

    ?>
    <div id="recipients" class="frontendform post page<?php if($frontendcontend->post_type != 'page' and $frontendcontend->post_type != 'post') echo ' hidden'; ?>">
        <h4>Audience</h4>				
        <?php
        if($frontendcontend->post_id != null){
            if(is_array($audience) and count($audience)>0){
                ?>
                <input type="checkbox" name="pagetype[normal]" value="normal" <?php echo $checked; ?>>
                <label for="normal">Normal</label><br>
                <?php
            }
        } 
        ?>
        
        <label>
            <input type="checkbox" name="pagetype[beforearrival]" value="beforearrival" <?php if(isset($audience['beforearrival'])) echo 'checked'; ?>>
            People should read this before arriving in the country (pre-field)
        </label><br>
        <label>
            <input type="checkbox" name="pagetype[afterarrival]" value="afterarrival" <?php if(isset($audience['afterarrival'])) echo 'checked'; ?>>
            People should read this after arriving in the country
        </label><br>
        <label>
            <input type="checkbox" name="pagetype[everyone]" value="everyone" <?php if(isset($audience['everyone'])) echo 'checked'; ?>>
            Everyone must read this no matter how long in the country
        </label><br>
    </div>
    <?php
});

//Save the post options
add_action('sim_after_post_save', function($post){
	//store audience
	if(is_array($_POST['pagetype'])) {
		$pagetype = $_POST['pagetype'];
		
		//Reset to normal if that box is ticked
		if(isset($pagetype['normal']) and $pagetype['normal'] == 'normal'){
			delete_post_meta($post->ID, "audience");
		//Store in DB
		}else{
			$audiences = $_POST['pagetype'];
			SIM\clean_up_nested_array($audiences);
			
			//Only continue if there are audiences defined
			if(count($audiences)>0){
				update_post_meta($post->ID,"audience",$audiences);
			
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
						$read_pages		= (array)get_user_meta( $user->ID, 'read_pages', true );
		
						//add current page
						$read_pages[]	= $post->ID;
						//update
						update_user_meta( $user->ID, 'read_pages', $read_pages);
					}
				}
			}
		}
	}else{
		delete_post_meta($post->ID,"audience");
	}
});

add_filter('sim_signal_post_notification_message', function($excerpt, $post){
	$audience	= get_post_meta($post->ID, "audience", true);
	if(is_array($audience) and !empty($audience['everyone'])) $excerpt	.= "\n\nThis is a mandatory message, please read it straight away.";

	return $excerpt;
}, 10, 2);