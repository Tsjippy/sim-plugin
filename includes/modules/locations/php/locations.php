<?php
namespace SIM\LOCATIONS;
use SIM;

// Create the location custom post type 
add_action('init', function(){
	SIM\registerPostTypeAndTax('location', 'locations');
}, 999);

//Add a map when a location category is added
add_action('sim_after_category_add', function($postType, $name, $result){
	if($postType != 'location'){
		return;
	}
	global $Modules;

	$Maps		= new Maps();
	$mapId		= $Maps->addMap($name);

	//Attach the map id to the term
	update_term_meta($result['term_id'], 'map_id', $mapId);

	$Modules[MODULE_SLUG][$name.'_map']	= $mapId;
	
	update_option('sim_modules', $Modules);
}, 10, 3);

add_filter(
	'widget_categories_args',
	function ( $catArgs ) {
		//if we are on a locations page, change to display the location types
		if(is_tax('locations') || is_page('location') || get_post_type()=='location'){
			$catArgs['taxonomy'] 		= 'locations';
			$catArgs['hierarchical']	= true;
			$catArgs['hide_empty'] 		= false;
		}
		
		return $catArgs;
	}
);

add_filter('widget_title', function ($title, $widgetId=null){
	//Change the title of the location category widget if not logged in
	if(is_tax('locations') && $widgetId == 'categories' && !is_user_logged_in()){
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
	if ($childPages){
		$childPageHtml .= "<p><strong>Some of our $ministry are:</strong></p><ul>";
		foreach($childPages as $childPage){
			$childPageHtml .= '<li><a href="'.$childPage['guid'].'">'.$childPage['post_title']."</a></li>";
		}
		$childPageHtml .= "</ul>";
	}		
	
	$html		.= getLocationEmployees($postId);
	$latitude 	= get_post_meta($postId,'geo_latitude',true);
	$longitude 	= get_post_meta($postId,'geo_longitude',true);
	if (!empty($latitude) && !empty($longitude)){
		$html .= "<p><a class='button' onclick='Main.getRoute(this,$latitude,$longitude)'>Get directions to $ministry</a></p>";
	}
	
	if(!empty($childPageHtml)){
		$html = $childPageHtml."<br><br>".$html; 
	}
	return $html;	
}

/**
 * Get the people that work at a certain location
 * 
 * @param 	int	$postId		The WP_Post id
 */
function getLocationEmployees($post){
	if (!is_user_logged_in()){
		return '';
	}

	if(is_numeric($post)){
		$post	= get_post($post);
	}

	$locations		= array_keys(get_children(array(
		'post_parent'	=> $post->ID,
		'post_type'   	=> 'location',
		'post_status' 	=> 'publish',
	)));
	$locations[]	= $post->ID;

	//Loop over all users to see if they work here
	$users 			= get_users('orderby=display_name');
	
	$html 			= '';

	foreach($users as $user){
		$userLocations 	= (array)get_user_meta( $user->ID, "jobs", true);

		$intersect		= array_intersect(array_keys($userLocations), $locations);
	
		//If a user works for this ministry, echo its name and position
		if ($intersect){
			$userPageUrl		= SIM\maybeGetUserPageUrl($user->ID);
			$privacyPreference	= (array)get_user_meta( $user->ID, 'privacy_preference', true );
			
			if(!isset($privacyPreference['hide_ministry'])){
				if(!isset($privacyPreference['hide_profile_picture'])){
					$html .= SIM\displayProfilePicture($user->ID);
					$style = "";
				}else{
					$style = ' style="margin-left: 55px; padding-top: 30px; display: block;"';
				}
				
				$pageUrl = "<a class='user_link' href='$userPageUrl'>$user->display_name</a>";
				foreach($intersect as $postId){
					$html .= "   <div $style>$pageUrl ({$userLocations[$postId]})</div>";
				}
				$html .= '<br>';
			}					
		}
	}
	if(empty($html)){
		$html .= "No one dares to say they are working here!";
	}

	$html 	= "<p><strong>People working at $post->post_title are:</strong><br><br>$html</p>";
	
	return $html;
}

//Remove marker when post is sent to trash
add_action('wp_trash_post', function($postId){
	$maps	= new Maps();
	$maps->removePostMarkers($postId);
});