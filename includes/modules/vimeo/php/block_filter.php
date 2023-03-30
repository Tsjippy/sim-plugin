<?php
namespace SIM\VIMEO;
use SIM;

add_filter('render_block', function($blockContent, $block){
    global $post;

    // Video block with a vimeo url
    if($block['blockName'] == 'core/video'){
        // Find vimeo id
        $vimeoId    = get_post_meta($post->ID, 'vimeo_id', true);

        if(is_numeric($vimeoId)){
            return showVimeoVideo($vimeoId);
        }
    }

    return $blockContent;
}, 999999999, 2);