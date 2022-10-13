<?php
namespace SIM\MANDATORY;
use SIM;

/**
 * Adds a message to the content that it is mandaory.
 * Also add a button to mark the post as read
 * @param  string $content	post content
 * @return string $content	post content
*/
add_filter( 'the_content', function ($content){
	if (!is_user_logged_in()){
		return $content;
	}

	$postId 	= get_the_ID();
	$userId 	= get_current_user_id();
	$audience   = get_post_meta($postId, 'audience', true);
    if(!is_array($audience) && !empty($audience)){
        $audience  = json_decode($audience, true);
    }
	$readPages	= (array)get_user_meta( $userId, 'read_pages', true );
	
	//Get the users arrival date
	$arrivalDate 	= strtotime(get_user_meta( $userId, 'arrival_date', true ));
	$arrived 		= false;
	if($arrivalDate && $arrivalDate < time()){
		$arrived = true;
	}
	
	//People should read this, and have not read it yet
	if(
		get_the_author_meta('ID') != $userId					&&
		!in_array($postId, $readPages)							&&
		(
			(isset($audience['beforearrival']) && !$arrived)	||
			isset($audience['afterarrival']) 					||
			isset($audience['everyone'])
		)
	){
		wp_enqueue_style('sim_mandatory_style');
		wp_enqueue_script('sim_mandatory_script');
		$message = '<p class="mandatory_content_warning">
			This is mandatory content.<br>
			Make sure you have clicked the "I have read this" button after reading.
		</p>';
		$content	 = $message.$content;
		$content	.= "<div class='mandatory_content_button'>";
			$content	.= "<button class='mark_as_read button' data-postid='$postId' data-userid='$userId'>I have read this</button>";
		$content	.= "</div>";
	}
	
	return $content;
});