<?php
namespace SIM\MANDATORY;
use SIM;

//Save the post options
add_action('sim_after_post_save', function($post){
	//store audience
	if(is_array($_POST['pagetype'])) {
		$pagetype = $_POST['pagetype'];
		
		//Reset to normal if that box is ticked
		if(isset($pagetype['normal']) and $pagetype['normal'] == 'normal'){
			delete_post_meta($post->ID,"audience");
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

// Show button if needed
add_filter( 'the_content', function ($content){
	if (is_user_logged_in()){
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
			!in_array($post_id,$read_pages)						and 
			((isset($audience['beforearrival']) and !$arrived)	or 
			isset($audience['afterarrival']) 					or
			isset($audience['everyone']))
		){
			wp_enqueue_style('sim_mandatory_style', plugins_url('css/mandatory.min.css', __DIR__), array(), ModuleVersion);
			wp_enqueue_script('sim_mandatory_script', plugins_url('js/mandatory.min.js', __DIR__), array(), ModuleVersion,true);
			$message = '<p class="mandatory_content_warning">
				This is mandatory content.<br>
				Make sure you have clicked the "I have read this" button after reading.
			</p>';
			$content	 = $message.$content;
			$content	.= "<div class='mandatory_content_button'>";
				$content	.= "<button class='mark_as_read button' data-postid='$post_id' data-userid='$user_id'>I have read this</button>";
			$content	.= "</div>";
		}
	}
	
	return $content;
});

//Process button click
add_action ( 'wp_ajax_mark_page_as_read', function(){
	if(is_numeric($_POST['userid']) and is_numeric($_POST['postid'])){
		$user_id = $_POST['userid'];
		$post_id = $_POST['postid'];
		
		//get current alread read pages
		$read_pages		= (array)get_user_meta( $user_id, 'read_pages', true );
		
		//add current page
		$read_pages[]	= $post_id;
		//update
		update_user_meta( $user_id, 'read_pages', $read_pages);
		
		wp_die("Succesfully marked this page as read");
	}else{
		wp_die('Invalid user or post id',500);
	}
});