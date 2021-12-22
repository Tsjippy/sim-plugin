<?php

//Widget title, depending on page
add_filter( 'widget_title', 'custom_widget_title',10,3 );
function custom_widget_title($title, $instance=[], $id_base=""){
	if($instance != "" and array_key_exists('widget_name', $instance) and $instance['widget_name'] == "Advanced Sidebar Pages Menu"){
		global $post;
		// This is a subpage
		if ( $post->post_parent) {
			$parent_title = get_the_title($post->post_parent);
			if (!is_user_logged_in() and $parent_title == "Directions"){
				return $post->post_title;
			}else{
				return $parent_title;
			}
		// This is not a subpage, or a subpage of the directions page
		} else {
			return $post->post_title;
		}
	}else{
		return $title;
	}
}

//Only show direct parent if the user is not logged in
add_action('init', function() {
	if (!is_user_logged_in()){
		add_action('advanced-sidebar-menu/menus/page/top-parent','custom_top_page', 10, 4);
	}
});

//Funcion to return the parent page is the advanced side bar menu
function custom_top_page( $top_page_id, $widget_args, $widget_values, $menu_class ){
	global $DirectionsPageID;
	global $MinistriesPageID;
	
	if ( is_page() ) {
		//Get the parent page id
		$parent_page_id = get_queried_object()->post_parent;
		$grand_parent_page_id = wp_get_post_parent_id($parent_page_id);
		//If there is a parent page and the parentpage is not the directions page
		if ( $parent_page_id  != 0 and $parent_page_id != $DirectionsPageID) {
			if($grand_parent_page_id == $DirectionsPageID){
				return $parent_page_id;
			}else{
				return $grand_parent_page_id;
			}
		}
		//Else return the current page
		return get_queried_object()->ID;
	}
	
    return $top_page_id;
}


//Check if it is a specific page or a child of a page, returns true or false
function is_page_or_subpage( $page_id = "" ) {
    global $post;
	global $MinistriesPageID;
	
	SIM\print_array('do I ever come here');
	
	
	if ($page_id == ""){
		$page_id = $MinistriesPageID;
	}
    return ( (is_page( $page_id ) or in_array( $page_id, get_post_ancestors( $post ) )) and is_user_logged_in() );
}