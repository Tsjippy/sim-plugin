<?php
namespace SIM\MANDATORY;
use SIM;

add_action('sim_dashboard_warnings', function($userId){	
	echo mustReadDocuments($userId);
}, 5);

add_filter('sim_loggedin_homepage',  function($content){
	$content	.= mustReadDocuments();
	return $content;
});

add_shortcode("must_read_documents", __NAMESPACE__.'\mustReadDocuments');

/**
 * Get an unordered list of documents to read
 * @param  int		$userId 		
 * @param  bool	 	$excludeHeading        	Whether to include a heading for 
 * @return string							HTML unordered list              
 */
function mustReadDocuments($userId='', $excludeHeading=false){
	$html 			= '';
	$beforeHtml 	= '';
	$arrivedHtml 	= '';
	
	wp_enqueue_script('sim_mandatory_script');

	if(!is_numeric($userId)){
		$userId = get_current_user_id();
	}

	// skip if user has the no mandatory pages role
	$user	= get_userdata($userId);
	if(in_array('no_man_docs', $user->roles)) return '';
	
	
	//Get all the pages this user already read
	$readPages		= (array)get_user_meta( $userId, 'read_pages', true );
	
	//Get the users arrival date
	$arrivalDate 	= strtotime(get_user_meta( $userId, 'arrival_date', true ));
	if($arrivalDate != false and $arrivalDate < time()){
		$arrived = true;
	}else{
		$arrived = false;
	}

	//Get all the pages with an audience meta key
	$pages = get_posts(
		array(
			'orderby' 		=> 'post_name',
			'order' 		=> 'asc',
			'post_type' 	=> 'any',
			'post_status' 	=> 'publish',
			'meta_key' 		=> "audience",
			'numberposts'	=> -1,				// all posts
			'author' 		=> '-'.$userId		// exclude own posts
		)
	);
	
	//Loop over the pages while building the html
	$arrivedPagesCount = 0;
	foreach($pages as $page){
		//check if already read
		if(!in_array($page->ID, $readPages)){
			$audience =  get_post_meta($page->ID, "audience", true);
			
			//Add a link if not yet in the country and should read before arriving
			if(isset($audience['beforearrival']) and !$arrived){
				$beforeHtml .= '<li><a href="'.get_permalink($page->ID).'">'.$page->post_title.'</a></li>';
			}
			
			//Page has not been read and should be read by all users
			if(isset($audience['afterarrival']) or isset($audience['everyone'])){
				//If this page also needs to be read by users who are not yet arrived, do not show again
				if(!isset($audience['beforearrival']) or ($arrived and isset($audience['beforearrival']))){
					$arrivedHtml .= '<li><a href="'.get_permalink($page->ID).'">'.$page->post_title.'</a></li>';
					$arrivedPagesCount++;
				}
			}
		}
	}
	
	///Documents to read before arrival
	if(!empty($beforeHtml) and !$arrived){
		if(!$excludeHeading){
			$html .= "<h3>Welcome!</h3>";
			$html .= "<p>";
				$html .= "We are so happy to welcome you!<br>";
				$html .= "Please read and/or download the documents below to prepare for your stay.";
			$html .= "</p>";
		}
		$html .= "<ul>$beforeHtml</ul>";
	}
	
	//Documents to read after arrival
	if($arrivedHtml != ''){
		if($arrivedPagesCount == 1){
			$page = "page";
		}else{
			$page = "pages";
		}
		
		if(!$excludeHeading){
			$html .= "<h3>Please read the following $page:</h3>";
		}
		$html .= "<ul>$arrivedHtml</ul>";
	}
	

	if(!empty($html)){
		if($userId != get_current_user_id() and !wp_doing_cron()){
			$html	= "<button type'button' class='button small mark-all-as-read' data-userid='$userId'>Mark all pages as read for {$user->display_name}</button>".$html;
		}

		return "<div id='personalinfo'>$html</div>";
	}
}