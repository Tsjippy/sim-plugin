<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

add_shortcode('your_posts', __NAMESPACE__.'\yourPosts');

function yourPosts(){
	//load js
	wp_enqueue_script('sim_table_script');
	
	//Get all posts for the current user
	$postTypes	= get_post_types(['public'=>true]);
	unset( $postTypes['attachment'] );
	
	$userUserPosts = get_posts(
		array(
			'post_type'		=> $postTypes,
			'post_status'	=> 'any',
			'author'		=> get_current_user_id(),
			'orderby'		=> 'post_date',
			'order'			=> 'ASC',
			'numberposts'	=> -1,
		)
	);
	
	$html = "<h2 class='table_title'>Content submitted by you</h2>";
		$html .= "<table class='sim-table' id='user_posts'>";
			$html .= "<thead>";
				$html .= "<tr>";
					$html .= "<th>Date</th>";
					$html .= "<th>Type</th>";
					$html .= "<th>Title</th>";
					$html .= "<th>Status</th>";
					$html .= "<th>Actions</th>";
				$html .= "</tr>";
			$html .= "</thead>";
		foreach($userUserPosts as $post){
			$date 		= get_the_modified_date('d F Y',$post);
			
			$type		= ucfirst($post->post_type);
			
			$title 		= $post->post_title;
			
			$status		= ucfirst($post->post_status);
			if($status == 'Publish'){
				$status = 'Published';
			}
			
			$url 		= get_permalink($post);
			$editUrl	= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'front_end_post_pages');
			if(!$editUrl){
				$editUrl = '';
			}
			$editUrl 	= add_query_arg( ['post_id' => $post->ID], $editUrl);
			if($post->post_status == 'publish'){
				$view = 'View';
			}else{
				$view = 'Preview';
			}
			$actions= "<span><a href='$url'>$view</a></span><span style='margin-left:20px;'> <a href='$editUrl'>Edit</a></span>";
			
			$html .= "<tr class='table-row'>";
				$html .= "<td>$date</td>";
				$html .= "<td>$type</td>";
				$html .= "<td>$title</td>";
				$html .= "<td>$status</td>";
				$html .= "<td>$actions</td>";
			$html .= "</tr>";
		}
		$html .= "</table>";
	
	return $html;
}

//Shortcode to display all pages and post who are pending
add_shortcode("pending_pages", __NAMESPACE__.'\pendingPages');

function pendingPages(){
	//Get all the posts with a pending status
	$pendingPosts 	= get_posts( 
		array(
			'post_status'	=> 'pending',
			'post_type'		=> 'any',
			'numberposts'	=> -1
		)
	);

	//Get all the posts with a pending revision
	$pendingRevisions 	= get_posts( 
		array(
			'post_status'	=> 'inherit',
			'post_type'		=> 'change',
			'numberposts'	=> -1
		)
		
	);

	$url			= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'front_end_post_pages');
	if(!$url){
		return '';
	}

	$html='';
	//Only if there are any pending posts
	if ( $pendingPosts) {
		$html .= "<strong>Pending content:</strong><br>";
		$html .= "<ul>";
		//For each pending post add a link to edit the post
		foreach ( $pendingPosts as $post ) {
			$url = add_query_arg( ['post_id' => $post->ID], $url );
			if(strtotime($post->post_date_gmt) > time()){
				$date	= date('d-M-Y', strtotime($post->post_date_gmt));
				$html .= "<li>$post->post_title (scheduled for $date) <a href='$url'>Publish now</a></li>";
			}else{
				$html .= '<li>'.$post->post_title.' <a href="'.$url.'">Review and publish</a></li>';
			}
		}
		$html .= "</ul>";
	}

	if ( $pendingRevisions) {
		$html .= "<br><br><strong>Pending content revisions:</strong><br>";
		$html .= "<ul>";
		//For each pendingRevisions post add a link to edit the post
		foreach ( $pendingRevisions as $post ) {
			$url = add_query_arg( ['post_id' => $post->ID], $url );
			$html .= "<li>$post->post_title <a href='$url'>Review changes</a></li>";
		}
		$html .= "</ul>";
	}

	if(!empty($html)){
		return "<p>$html</p>";
	}
	
	return "<p>No pending posts or pages found</p>";
}

//Shortcode to display number of pending posts and pages
add_shortcode('pending_post_icon', function (){
	//Get all the posts with a pending status
	$pendingPosts 	= get_posts( 
		array(
			'post_status'	=> 'pending',
			'post_type'		=> 'any',
			'numberposts'	=> -1
		)
	);

	//Get all the posts with a pending revision
	$pendingRevisions 	= get_posts( 
		array(
			'post_status'	=> 'inherit',
			'post_type'		=> 'change',
			'numberposts'	=> -1
		)
	);

	if ( $pendingPosts || $pendingRevisions) {
		$pendingTotal = count($pendingPosts) + count($pendingRevisions);
		return "<span class='numberCircle'>$pendingTotal</span>";
	}
});

//Add shortcode for the post edit form
add_shortcode("front_end_post", function(){
	$frontEndContent	= new FrontEndContent();
	return $frontEndContent->frontendPost();
});

//Add shortcode for the post edit form
add_shortcode("old-pages", function(){
	$oldPages	= getOldPages();

	$html	= '<table class="sim-table">';
		$html	.= "<tr>";
			$html	.= "<th>";
				$html	.= "Title";
			$html	.= "</th>";
			$html	.= "<th>";
				$html	.= "Last Modified";
			$html	.= "</th>";
			$html	.= "<th>";
				$html	.= "Author";
			$html	.= "</th>";
		$html	.= "</tr>";
		
		foreach($oldPages as $page){
			$url					= get_permalink($page);
			$authorUrl				= SIM\maybeGetUserPageUrl($page->post_author);
			$authorName				= get_userdata($page->post_author)->first_name;
			$secondsSinceUpdated 	= time() - get_post_modified_time('U', true, $page);
			$pageAge				= round($secondsSinceUpdated /60 /60 /24);

			$html	.= "<tr>";
				$html	.= "<td>";
					$html	.= "<a href='$url'>$page->post_title</a>";
				$html	.= "</td>";
				$html	.= "<td>";
					$html	.= "<a href='$url'>$pageAge days</a>";
				$html	.= "</td>";
				$html	.= "<td>";
					$html	.= "<a href='$authorUrl'>$authorName</a>";
				$html	.= "</td>";
			$html	.= "</tr>";
		}
	$html	.= '</table>';

	return $html;
});