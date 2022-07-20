<?php

//Widget title, depending on page
add_filter( 'widget_title', function ($title, $instance=[]){
	if(!empty($instance) && array_key_exists('widget_name', $instance) && $instance['widget_name'] == "Advanced Sidebar Pages Menu"){
		global $post;
		// This is a subpage
		if ( $post->post_parent) {
			$parentTitle = get_the_title($post->post_parent);
			if (!is_user_logged_in() && $parentTitle == "Directions"){
				$title	= $post->post_title;
			}else{
				$title	= $parentTitle;
			}
		// This is not a subpage, or a subpage of the directions page
		} else {
			$title	= $post->post_title;
		}
	}
	
	return $title;
}, 10, 2);

//Only show direct parent if the user is not logged in
add_action('init', function() {
	if (!is_user_logged_in()){
		add_action('advanced-sidebar-menu/menus/page/top-parent','customTopPage', 10, 4);
	}
});

//Funcion to return the parent page is the advanced side bar menu
function customTopPage( $topPageId){
	global $DirectionsPageID;
	
	if ( is_page() ) {
		//Get the parent page id
		$parentPageId 		= get_queried_object()->post_parent;
		$grandParentPageId 	= wp_get_post_parent_id($parentPageId);
		//If there is a parent page and the parentpage is not the directions page
		if ( $parentPageId  != 0 && $parentPageId != $DirectionsPageID) {
			if($grandParentPageId == $DirectionsPageID){
				return $parentPageId;
			}else{
				$topPageId	= $grandParentPageId;
			}
		}
		//Else return the current page
		$topPageId	= get_queried_object()->ID;
	}
	
    return $topPageId;
}