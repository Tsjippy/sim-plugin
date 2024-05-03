<?php
namespace SIM\MANDATORY;
use SIM;

// add to account dashboard
add_action('sim_dashboard_warnings', function($userId){
	echo mustReadDocuments($userId);
}, 20);

add_shortcode("must_read_documents", __NAMESPACE__.'\mustReadDocuments');

/**
 * Get an unordered list of documents to read
 * @param  int		$userId
 * @param  bool	 	$excludeHeading        	Whether to include a heading for
 * @return string							HTML unordered list
 */
function mustReadDocuments($userId='', $excludeHeading=false){
	if(!is_user_logged_in() || get_user_meta($userId, 'account-type', true) == 'positional'){
		return '';
	}
	
	$html 			= '';
	$beforeHtml 	= '';
	$arrivedHtml 	= '';

	wp_enqueue_script('sim_mandatory_script');

	if(!is_numeric($userId)){
		$userId = get_current_user_id();
	}

	// skip if user has the no mandatory pages role
	$user	= get_userdata($userId);
	if(in_array('no_man_docs', $user->roles)){
		return '';
	}

	//Get all the pages this user already read
	$readPages		= (array)get_user_meta( $userId, 'read_pages', true );

	//Get the users arrival date
	$arrivalDate 	= strtotime(get_user_meta( $userId, 'arrival_date', true ));
	if(!$arrivalDate || $arrivalDate < time()){
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
			$audience   = get_post_meta($page->ID, 'audience', true);
			if(!is_array($audience) && !empty($audience)){
				$audience  = json_decode($audience, true);
			}

			//Add a link if not yet in the country and should read before arriving
			if(isset($audience['beforearrival']) && !$arrived){
				$beforeHtml .= '<li><a href="'.get_permalink($page->ID).'">'.$page->post_title.'</a></li>';
			}

			//Page has not been read, scheck if it should be read
			$mustRead	= false;
			if(
				(
					isset($audience['afterarrival']) 	||			// People should read this after arrival
					(
						isset($audience['everyone'])	&&			// Or everyone should read this
						$arrivalDate < strtotime($page->post_date)	// And arrived before the post was published
					)
				)										&&			// AND
				(
					!isset($audience['beforearrival'])	|| 			// The before arrival is not set
					(
						isset($audience['beforearrival'])	&&		// Or it is set but we have arrived
						$arrived
					)
				)
			){
				$mustRead	= true;
			}

			// filter the value
			$mustRead	= apply_filters('sim_should_read_mandatory_page', $mustRead, $audience, $userId);

			if($mustRead){
				$arrivedHtml .= '<li><a href="'.get_permalink($page->ID).'">'.$page->post_title.'</a></li>';
				$arrivedPagesCount++;
			}
		}
	}

	///Documents to read before arrival
	if(!empty($beforeHtml) && !$arrived){
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
	if(!empty($arrivedHtml)){
		if(!$excludeHeading){
			$html .= "<h3>Important Reading for You Today</h3>";
		}
		$html .= "<ul>$arrivedHtml</ul>";
	}

	if(wp_doing_cron()){
		return '';
	}

	if(empty($html)){
		if(str_contains($_SERVER['REQUEST_URI'], 'wp-admin/post.php')|| str_contains($_SERVER['REQUEST_URI'], 'wp-json')){
			return 'Mandatory pages block<br>This will show empty as you have not pages to read';
		}

		return '';
	}

	$extra	= '';
	if($userId != get_current_user_id()){
		$extra	=  " for {$user->display_name}";
	}
	$html	.= "<button type'button' class='button small mark-all-as-read' data-userid='$userId'>Mark all pages as read$extra</button>";

	return "<div id='personalinfo'>$html</div>";
}