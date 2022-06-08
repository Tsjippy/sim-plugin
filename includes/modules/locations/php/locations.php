<?php
namespace SIM\LOCATIONS;
use SIM;

add_action('init', function(){
	SIM\registerPostTypeAndTax('location', 'locations');
}, 999);

//Add a map when a location category is added
add_action('sim_after_category_add', function($postType, $name, $result){
	if($postType != 'location') return;
	global $Modules;

	$Maps		= new Maps();
	$mapId		= $Maps->addMap($name);

	//Attach the map id to the term
	update_term_meta($result['term_id'], 'map_id', $mapId);

	$Modules['locations'][$name.'_map']	= $mapId;
	
	update_option('sim_modules', $Modules);
}, 10, 3);

add_filter(
	'widget_categories_args',
	function ( $catArgs, $instance  ) {
		//if we are on a locations page, change to display the location types
		if(is_tax('locations') or is_page('location') or get_post_type()=='location'){
			$catArgs['taxonomy'] 		= 'locations';
			$catArgs['hierarchical']	= true;
			$catArgs['hide_empty'] 	= false;
		}
		
		return $catArgs;
	},
	10, 
	2 
);

add_filter('widget_title', function ($title, $widget_id=null){
	//Change the title of the location category widget if not logged in
	if(is_tax('locations') and $widget_id == 'categories' and !is_user_logged_in()){
		$url = SITEURL.'/locations/ministry/';
		return "<a href='$url'>Ministries</a>";
	}
    return $title;
}, 999, 2);

/**
 * Default content for ministry pages
 */
function ministryDescription($postId){
	$html		= "";
	//Get the post id of the page the shortcode is on
	$ministry	= get_the_title($postId);
	//$ministry = strtolower($ministry);
	$args		= array(
		'post_parent' => $postId, // The parent id.
		'post_type'   => 'page',
		'post_status' => 'publish',
		'order'          => 'ASC',
	);
	$childPages 		= get_children( $args, ARRAY_A);
	$childPageHtml 	= "";
	if ($childPages  != false){
		$childPageHtml .= "<p><strong>Some of our $ministry are:</strong></p><ul>";
		foreach($childPages as $childPage){
			$childPageHtml .= '<li><a href="'.$childPage['guid'].'">'.$childPage['post_title']."</a></li>";
		}
		$childPageHtml .= "</ul>";
	}		
	
	$html		.= getLocationEmployees($ministry);
	$latitude 	= get_post_meta($postId,'geo_latitude',true);
	$longitude 	= get_post_meta($postId,'geo_longitude',true);
	if ($latitude != "" and $longitude != ""){
		$html .= "<p><a class='button' onclick='Main.getRoute(this,$latitude,$longitude)'>Get directions to $ministry</a></p>";
	}
	
	if(!empty($childPageHtml)){
		$html = $childPageHtml."<br><br>".$html; 
	}
	return $html;	
}

/**
 * Get the people that work at a certain location
 */
function getLocationEmployees($locationName){
	if (is_user_logged_in()){
		$ministryName 	= str_replace(" ", "_", $locationName);

		//Loop over all users to see if they work here
		$users 			= get_users('orderby=display_name');
		
		$html 			= '';

		foreach($users as $user){
			$userMinistries = (array)get_user_meta( $user->ID, "user_ministries", true);
		
			//If user works for this ministry, echo its name and position
			if (isset($userMinistries[$ministryName])){
				$userPageUrl		= SIM\getUserPageUrl($user->ID);
				$privacyPreference	= (array)get_user_meta( $user->ID, 'privacy_preference', true );
				
				if(!isset($privacyPreference['hide_ministry'])){
					if(!isset($privacyPreference['hide_profile_picture'])){
						$html .= SIM\displayProfilePicture($user->ID);
						$style = "";
					}else{
						$style = ' style="margin-left: 55px; padding-top: 30px; display: block;"';
					}
					
					$pageUrl = "<a class='user_link' href='$userPageUrl'>$user->display_name</a>";
					
					$html .= "   <div $style>$pageUrl ({$userMinistries[$ministryName]})</div>";
					$html .= '<br>';
				}					
			}
		}
		if(empty($html)){
			$html .= "No one dares to say they are working here!";
		}

		$html 	= "<p><strong>People working at $locationName are:</strong><br><br>$html</p>";
		
		return $html;
	}
}

//Remove marker when post is sent to trash
add_action('wp_trash_post', function($postId){
	$maps	= new Maps();
	$maps->removePostMarkers($postId);
});