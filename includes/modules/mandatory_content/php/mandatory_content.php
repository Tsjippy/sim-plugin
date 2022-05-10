<?php
namespace SIM\MANDATORY;
use SIM;

// Show button if needed
add_filter( 'the_content', function ($content){
	if (!is_user_logged_in()) return $content;

	$post_id 	= get_the_ID();
	$user_id 	= get_current_user_id();
	$audience 	= (array)get_post_meta($post_id,"audience",true);
	$read_pages	= (array)get_user_meta( $user_id, 'read_pages', true );
	
	//Get the users arrival date
	$arrivaldate 	= strtotime(get_user_meta( $user_id, 'arrival_date', true ));
	if($arrivaldate){
		if($arrivaldate < time()){
			$arrived = true;
		}else{
			$arrived = false;
		}
	}else{
		$arrived = false;
	}
	
	//People should read this, and have not read it yet
	if(
		get_the_author_meta('ID') != $user_id					and
		!in_array($post_id,$read_pages)							and 
		(
			(isset($audience['beforearrival']) and !$arrived)	or 
			isset($audience['afterarrival']) 					or
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
			$content	.= "<button class='mark_as_read button' data-postid='$post_id' data-userid='$user_id'>I have read this</button>";
		$content	.= "</div>";
	}
	
	return $content;
});