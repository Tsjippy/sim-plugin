<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

add_filter('sim-media-edit-link', function($link, $id){
    $pageId	= SIM\getModuleOption(MODULE_SLUG, 'publish_post_page')[0];
	if(is_numeric($pageId)){
		return"<a href='".get_permalink($pageId)."?post_id=$id' class='button'>Edit</a>";
    }
    return $link;
}, 10, 2);