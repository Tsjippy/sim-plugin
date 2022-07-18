<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

add_filter('sim-media-edit-link', function($link, $id){
    $url			= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'front_end_post_pages');
	if($url){
		return"<a href='$url?post_id=$id' class='button'>Edit</a>";
    }
    return $link;
}, 10, 2);