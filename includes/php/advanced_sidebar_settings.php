<?php

//Widget title, depending on page
add_filter( 'widget_title', function ($title, $instance=[], $id_base=""){
	if($instance != "" and array_key_exists('widget_name', $instance) and $instance['widget_name'] == "Advanced Sidebar Pages Menu"){
		global $post;
		// This is a subpage
		if ( $post->post_parent) {
			$parentTitle = get_the_title($post->post_parent);
			if (!is_user_logged_in() and $parentTitle == "Directions"){
				return $post->post_title;
			}else{
				return $parentTitle;
			}
		// This is not a subpage, or a subpage of the directions page
		} else {
			return $post->post_title;
		}
	}else{
		return $title;
	}
}, 10, 3);

//Only show direct parent if the user is not logged in
add_action('init', function() {
	if (!is_user_logged_in()){
		add_action('advanced-sidebar-menu/menus/page/top-parent','customTopPage', 10, 4);
	}
});

//Funcion to return the parent page is the advanced side bar menu
function customTopPage( $topPageId, $widgetArgs, $widgetValues, $menuClass ){
	global $DirectionsPageID;
	
	if ( is_page() ) {
		//Get the parent page id
		$parentPageId 		= get_queried_object()->post_parent;
		$grandParentPageId 	= wp_get_post_parent_id($parentPageId);
		//If there is a parent page and the parentpage is not the directions page
		if ( $parentPageId  != 0 and $parentPageId != $DirectionsPageID) {
			if($grandParentPageId == $DirectionsPageID){
				return $parentPageId;
			}else{
				return $grandParentPageId;
			}
		}
		//Else return the current page
		return get_queried_object()->ID;
	}
	
    return $topPageId;
}