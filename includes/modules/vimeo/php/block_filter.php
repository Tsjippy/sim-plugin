<?php
namespace SIM\VIMEO;
use SIM;

add_filter('render_block', function($blockContent, $block){
    // Video block with a vimeo url
    if($block['blockName'] == 'core/video' && strpos($blockContent, 'vimeo.com') !== false){
        // Find vimeo id
        $result = preg_match('/vimeo.com.*\/(\d*)/', $blockContent, $matches);

        if($result && !empty($matches[1])){
            return showVimeoVideo($matches[1]);
        }
    }

    return $blockContent;
}, 999999999, 2);