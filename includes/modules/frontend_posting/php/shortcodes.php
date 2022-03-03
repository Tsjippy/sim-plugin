<?php
namespace SIM\FRONTEND_POSTING;
use SIM;

add_shortcode('your_posts',function(){
	//load js
	wp_enqueue_script('sim_table_script');
	
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
			$edit_url 	= add_query_arg( ['post_id' => $post->ID], get_permalink( SIM\get_module_option('frontend_posting','publish_post_page')) );
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