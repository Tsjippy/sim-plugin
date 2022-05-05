<?php
namespace SIM\FRONTEND_POSTING;
use SIM;

add_filter('sim-media-edit-link', function($link, $id){
    $pageId	= SIM\get_module_option('frontend_posting', 'publish_post_page');
	if(is_numeric($pageId)){
		return"<a href='".get_permalink($pageId)."?post_id=$id' class='button'>Edit</a>";
    }
    return $link;
}, 10, 2);