<?php
namespace SIM\VIMEO;
use SIM;

// Update vimeo when attachment has changed
add_action('sim_after_post_save', function($post){
    if($post->post_type == 'attachment' && is_numeric($post->ID)){
        $vimeoApi		= new VimeoApi();
        $vimeoId        = $vimeoApi->getVimeoId($post->ID);
        if(!is_numeric($vimeoId)){
            return;
        }

        $data			= [];

        $newTitle       = sanitize_text_field($_POST['post_title']);

        // Only update when needed
        if(!empty($newTitle) && $newTitle != $post->post_title){
            $data['name']	= $newTitle;
        }
        
        if(!empty($_POST['post_content']) && $_POST['post_content'] != $post->post_content){
            $data['description']	= $_POST['post_content'];
        }

        if(!empty($data)){
            $vimeoApi->updateMeta($post->ID, $data);
        }
    }
});

add_filter('sim_attachment_preview', function($image, $postId){
    $vimeo      = new VimeoApi();
    $vimeoId    = $vimeo->getVimeoId($postId);

    if($vimeoId){
        return showVimeoVideo($vimeoId);
    }

    return  $image;
}, 10, 2);