<?php
namespace SIM\VIMEO;
use SIM;


//change hyperlink to shortcode for vimeo videos
add_filter( 'media_send_to_editor', function ($html, $id, $attachment) {
	if(strpos($attachment['url'], 'vimeo.com') !== false){
		$vimeoId	= get_post_meta($id, 'vimeo_id', true);

		$html		= "[vimeo_video id=$vimeoId]";
	}
	
	return $html;
}, 10, 9 );