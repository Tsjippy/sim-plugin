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

//Remove marker when post is sent to trash
add_action('wp_trash_post', function($postId){
	$maps	= new Maps();
	$maps->removePostMarkers($postId);
});

add_filter('sim-template-filter', function($templateFile){
	global $post;

	if(in_array('locations', get_post_taxonomies()) && in_array('ministry', wp_get_post_terms($post->ID, 'locations', ['fields'=>'slugs']))){
		return str_replace('single-location', 'single-ministry', $templateFile);
	}
    return $templateFile;
});

/**
 * Get the people that work at a certain location
 *
 * @param 	int	$postId		The WP_Post id
 */
function getLocationEmployees($post){

	wp_enqueue_style('sim_employee_style');

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
	
	$html 			= "";

	foreach($users as $user){
		$userLocations 	= (array)get_user_meta( $user->ID, "jobs", true);

		$intersect		= array_intersect(array_keys($userLocations), $locations);
	
		//If a user works for this ministry, echo its name and position
		if ($intersect){
			$userPageUrl		= SIM\maybeGetUserPageUrl($user->ID);
			$privacyPreference	= (array)get_user_meta( $user->ID, 'privacy_preference', true );
			$class				= 'description';
			if(isset($privacyPreference['hide_profile_picture'])){
				$class			.= ' empty-picture';
			}
			
			if(!isset($privacyPreference['hide_ministry'])){
				$html .=	"<div class='person-wrapper'>";
					if(!isset($privacyPreference['hide_profile_picture'])){
						$html .= SIM\displayProfilePicture($user->ID);
					}
					
					$pageUrl = "<a class='user_link' href='$userPageUrl'>$user->display_name</a>";
					foreach($intersect as $postId){
						$job	= ucfirst($userLocations[$postId]);
						$html .= "   <div class='$class'>$pageUrl <br>($job)</div>";
					}
				$html .= '</div>';
			}
		}
	}
	

	if(empty($html)){
		$html  .= "No workers are currently affiliated with this ministry.<br>";
		$url	= SIM\ADMIN\getDefaultPageLink('usermanagement', 'account_page');
		$html  .= "If you work here indicate so on the <a href='$url/?main_tab=generic_info#ministries'>Generic Info page</a>";
	}else{
		$html	= "<div class='employee-gallery'>$html</div>";
	}

	$html 	= "<p style='padding:10px;'><h3 style='text-align:center'>People who Work at $post->post_title</h3><br><br>$html</p>";
	
	return $html;
}