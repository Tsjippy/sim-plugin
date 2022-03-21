<?php
namespace SIM\MANDATORY;
use SIM;

add_shortcode("must_read_documents", __NAMESPACE__.'\get_must_read_documents');
function get_must_read_documents($user_id='', $exclude_heading=false){
	if(!is_numeric($user_id)) $user_id = get_current_user_id();
	
	//Get all the pages this user already read
	$read_pages		= (array)get_user_meta( $user_id, 'read_pages', true );
	
	//Array of documents unique for each person
	$personal_document_array = [
		'welcomeletter'		=> 'Welcome Letter',
		'mealschedule'		=> 'Meal Schedule',
		'orientation'		=> 'Orientation Schedule',
		'jobdescription'	=> 'Job Description',
	];
	
	$personnel_documents 	= (array)get_user_meta( $user_id, "personnel_documents",true);
	
	$html 			= '';
	$before_html 	= '';
	$arrived_html 	= '';
	
	//Get the users arrival date
	$arrivaldate 	= strtotime(get_user_meta( $user_id, 'arrival_date', true ));
	if($arrivaldate != false and $arrivaldate < time()){
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
			'author' 		=> '-'.$user_id		// exclude own posts
		)
	);
	
	//Loop over the pages while building the html
	$arrived_pages_count = 0;
	foreach($pages as $page){
		//check if already read
		if(!in_array($page->ID, $read_pages)){
			$audience =  get_post_meta($page->ID,"audience",true);
			
			//Add a link if not yet in the country and should read before arriving
			if(isset($audience['beforearrival']) and !$arrived){
				$before_html .= '<li><a href="'.get_permalink($page->ID).'">'.$page->post_title.'</a></li>';
			}
			
			//Page has not been read and should be read by all users
			if(isset($audience['afterarrival']) or isset($audience['everyone'])){
				//If this page also needs to be read by users who are not yet arrived, do not show again
				if(!isset($audience['beforearrival']) or ($arrived and isset($audience['beforearrival']))){
					$arrived_html .= '<li><a href="'.get_permalink($page->ID).'">'.$page->post_title.'</a></li>';
					$arrived_pages_count++;
				}
			}
		}
	}
	
	///Documents to read before arrival
	if($before_html != '' or (count($personal_document_array)>0 and !$arrived)){
		if(!$exclude_heading){
			$html .= "<h3>Welcome!</h3><p>We are so happy to welcome you to Nigeria!<br>";
			$html .= "Please read and/or download the documents below to prepare for your stay.</p>";
		}
		$html .= "<ul>$before_html";
		foreach($personal_document_array as $key=>$document){
			if(isset($personnel_documents[$document])){
				$html .= "<li><a href='".SITEURL.'/'.$personnel_documents[$document]."'>$document</a></li>";
			}
		}
		$html .= "</ul>";
	}
	
	//Documents to read after arrival
	if($arrived_html != ''){
		if($arrived_pages_count == 1){
			$page = "page";
		}else{
			$page = "pages";
		}
		
		if(!$exclude_heading){
			$html .= "<h3>Please read the following $page:</h3>";
		}
		$html .= "<ul>".$arrived_html."</ul>";
	}
	
	if($html != ''){
		return "<div id='personalinfo'>$html</div>";
	}
}