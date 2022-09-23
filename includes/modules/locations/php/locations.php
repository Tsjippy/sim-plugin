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