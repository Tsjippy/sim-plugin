<?php
namespace SIM\LOCATIONS;
use SIM;

add_action('init', function(){
	SIM\register_post_type_and_tax('location', 'locations');
}, 999);

//Add a map when a location category is added
add_action('sim_after_category_add', function($postType, $name, $result){
	if($postType != 'location') return;
	global $Modules;

	$Maps		= new Maps();
	$map_id		= $Maps->add_map($name);

	//Attach the map id to the term
	update_term_meta($result['term_id'], 'map_id', $map_id);

	$Modules['locations'][$name.'_map']	= $map_id;
	
	update_option('sim_modules', $Modules);
}, 10, 3);

add_filter(
	'widget_categories_args',
	function ( $cat_args, $instance  ) {
		//if we are on a locations page, change to display the location types
		if(is_tax('locations') or is_page('location') or get_post_type()=='location'){
			$cat_args['taxonomy'] 		= 'locations';
			$cat_args['hierarchical']	= true;
			$cat_args['hide_empty'] 	= false;
		}
		
		return $cat_args;
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

//Default content for ministry pages
function ministry_description($post_id){
	$html = "";
	//Get the post id of the page the shortcode is on
	$ministry = get_the_title($post_id);
	//$ministry = strtolower($ministry);
	$args = array(
		'post_parent' => $post_id, // The parent id.
		'post_type'   => 'page',
		'post_status' => 'publish',
		'order'          => 'ASC',
	);
	$child_pages = get_children( $args, ARRAY_A);
	$child_page_html = "";
	if ($child_pages  != false){
		$child_page_html .= "<p><strong>Some of our $ministry are:</strong></p><ul>";
		foreach($child_pages as $child_page){
			$child_page_html .= '<li><a href="'.$child_page['guid'].'">'.$child_page['post_title']."</a></li>";
		}
		$child_page_html .= "</ul>";
	}		
	
	$html		.= get_location_employees($ministry);
	$latitude 	= get_post_meta($post_id,'geo_latitude',true);
	$longitude 	= get_post_meta($post_id,'geo_longitude',true);
	if ($latitude != "" and $longitude != ""){
		$html .= "<p><a class='button' onclick='main.getRoute(this,$latitude,$longitude)'>Get directions to $ministry</a></p>";
	}
	
	if(!empty($child_page_html)){
		$html = $child_page_html."<br><br>".$html; 
	}
	return $html;	
}


function get_location_employees($locationName){
	if (is_user_logged_in()){
		$ministry_name 	= str_replace(" ", "_", $locationName);

		//Loop over all users to see if they work here
		$users 			= get_users('orderby=display_name');
		
		$html 			= '';

		foreach($users as $user){
			$user_ministries = (array)get_user_meta( $user->ID, "user_ministries", true);
		
			//If user works for this ministry, echo its name and position
			if (isset($user_ministries[$ministry_name])){
				$user_page_url		= SIM\getUserPageUrl($user->ID);
				$privacy_preference = (array)get_user_meta( $user->ID, 'privacy_preference', true );
				
				if(!isset($privacy_preference['hide_ministry'])){
					if(!isset($privacy_preference['hide_profile_picture'])){
						$html .= SIM\displayProfilePicture($user->ID);
						$style = "";
					}else{
						$style = ' style="margin-left: 55px; padding-top: 30px; display: block;"';
					}
					
					$page_url = "<a class='missionary_link' href='$user_page_url'>$user->display_name</a>";
					
					$html .= "   <div $style>$page_url ({$user_ministries[$ministry_name]})</div>";
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