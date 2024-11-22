<?php

//Widget title, depending on page
add_filter( 'widget_title', __NAMESPACE__.'\widgetTitle', 10, 2);
function widgetTitle($title, $instance=[]){
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
}