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

//Shortcode to display all pages and post who are pending
add_shortcode("pending_pages", function ($atts){
	//Get all the posts with a pending status
	$args = array(
	  'post_status' => 'pending',
	  'post_type'	=> 'any'
	);
	
	//Build de HTML
	$initial_html = "";
	$html = $initial_html;
	$pending_posts = get_posts( $args );
	//Only if there are any pending posts
	if ( $pending_posts ) {
		$html .= "<p><strong>Pending posts, pages and events:</strong><br><ul>";
		//For each pending post add a link to edit the post
		foreach ( $pending_posts as $pending_post ) {
			$url = add_query_arg( ['post_id' => $pending_post->ID], get_permalink( SIM\get_module_option('frontend_posting', 'publish_post_page')) );
			if ($url){
				$html .= '<li>'.$pending_post->post_title.' <a href="'.$url.'">Review and publish</a></li>';
			}
		}
		$html .= "</ul>";
	}
	
	if ($html != $initial_html){
		$html.="</ul></p>";
		return $html;
	}else{
		return "<p>No pending posts or pages found</p>";
	}
});

//Shortcode to display number of pending posts and pages
add_shortcode('pending_post_icon', function ($atts){
	$args = array(
	  'post_status' => 'pending',
	  'post_type'	=> 'any'
	);
	$pending_posts = get_posts( $args );
	if ( $pending_posts ) {
		$pending_total = count($pending_posts);
	}
	
	if ($pending_total > 0){
		return '<span class="numberCircle">'.$pending_total.'</span>';
	}
});

//Add shortcode for the post edit form
add_shortcode("front_end_post", function(){
	$frontEndContent	= new FrontEndContent();
	return $frontEndContent->frontend_post();
});