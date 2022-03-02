<?php
namespace SIM\FRONTEND_POSTING;
use SIM;

add_action('init', function(){
	$GLOBALS['FrontEndContent']	= new FrontEndContent();
});

function send_pending_post_warning($post, $update){
	global $Modules;
	global $WebmasterName;
	
	//Do not continue if already send
	if(get_post_meta($post->ID,'pending_notification_send',true) != '') return;
	
	//get all the content managers
	$users = get_users( array(
		'role'    => 'contentmanager',
	));
	
	if($update){
		$action_text = 'updated';
	}else{
		$action_text = 'created';
	}
	
	$type = $post->post_type;
	
	//send notification to all content managers
	$url = add_query_arg( ['post_id' => $post->ID], get_permalink( $Modules['frontend_posting']['publish_post_page'] ) );
	$author_name = get_userdata($post->post_author)->display_name;
	
	foreach($users as $user){
		//send signal message
		SIM\try_send_signal("$author_name just $action_text a $type. Please review it here:\n\n$url",$user->ID);
		
		//Send e-mail
		$message = "Hi ".$user->first_name."<br><br>";
		$message .= "$author_name just $action_text a $type. Please review it <a href='$url'>here</a><br><br>";
		$message .= 'Cheers,<br><br>'.$WebmasterName;
		$headers = ['Content-Type: text/html; charset=UTF-8'];

		wp_mail( $user->user_email, "Please review a $type", $message, $headers );
	}
	
	//Mark warning as send
	update_post_meta($post->ID,'pending_notification_send',true);
}

//Delete the indicator that the warning has been send
add_action(  'transition_post_status',  function ( $new_status, $old_status, $post ) {
	// Check if signal nonce is set.
	if ($new_status == 'publish' and $old_status == 'pending'){
		delete_post_meta($post->ID,'pending_notification_send');
	}
}, 10, 3 );

add_shortcode('your_posts',function(){
	//load js
	wp_enqueue_script('sim_table_script');
	
	global $Modules;
	
	//Get all posts for the current user
	$post_types	= get_post_types(['public'=>true]);
	unset( $post_types['attachment'] );
	
	$user_user_posts = get_posts( 
		array(
			'post_type'		=> $post_types,
			'post_status'	=> 'any',
			'author'		=> get_current_user_id(),
			'orderby'		=> 'post_date',
			'order'			=> 'ASC',
			'numberposts'	=> -1,
		)
	);
	
	$html = "<h2 class='table_title'>Content submitted by you</h2>";
		$html .= "<table class='table' id='user_posts'>";
			$html .= "<thead class='table-head'>";
				$html .= "<tr>";
					$html .= "<th>Date</th>";
					$html .= "<th>Type</th>";
					$html .= "<th>Title</th>";
					$html .= "<th>Status</th>";
					$html .= "<th>Actions</th>";
				$html .= "</tr>";
			$html .= "</thead>";
		foreach($user_user_posts as $post){
			$date 		= get_the_modified_date('d F Y',$post);
			
			$type		= ucfirst($post->post_type);
			
			$title 		= $post->post_title;
			
			$status		= ucfirst($post->post_status);
			if($status == 'Publish') $status = 'Published';
			
			$url 		= get_permalink($post);
			$edit_url 	= add_query_arg( ['post_id' => $post->ID], get_permalink( $Modules['frontend_posting']['publish_post_page'] ) );
			if($post->post_status == 'publish'){
				$view = 'View';
			}else{
				$view = 'Preview';
			}
			$actions= "<span><a href='$url'>$view</a></span><span style='margin-left:20px;'> <a href='$edit_url'>Edit</a></span>";
			
			$html .= "<tr class='table-row'>";
				$html .= "<td>$date</td>";
				$html .= "<td>$type</td>";
				$html .= "<td>$title</td>";
				$html .= "<td>$status</td>";
				$html .= "<td>$actions</td>";
			$html .= "</tr>";
		}
		$html .= "</table>";
	
	//print_array($user_user_posts);
	return $html;
});