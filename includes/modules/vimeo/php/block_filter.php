<?php
namespace SIM\VIMEO;
use SIM;

add_filter('render_block', function($blockContent, $block){
    // Video block with a vimeo url
    if($block['blockName'] == 'core/video' && strpos($blockContent, 'vimeo.com') !== false && !empty($block['attrs']['id'])){
        // Find vimeo id
        $vimeoId    = get_post_meta($block['attrs']['id'], 'vimeo_id', true);

        if(is_numeric($vimeoId)){
            return showVimeoVideo($vimeoId);
        }
    }

    return $blockContent;
}, 999999999, 2);