<?php
namespace SIM\VIMEO;
use SIM;

// Update vimeo when attachment has changed
add_action('sim_after_post_save', function($post){
    if(isset($_POST['post_type']) and $_POST['post_type'] == 'attachment' and is_numeric($_POST['post_id'])){
        $data			= [];
        if(!empty($_POST['post_title'])){
            $data['name']	= $_POST['post_title'];
        }
        if(!empty($_POST['post_content'])){
            $data['description']	= $_POST['post_content'];
        }

        if(!empty($data)){
            $VimeoApi		= new VimeoApi();
            $VimeoApi->update_meta($_POST['post_id'], $data);
        }
    }    
});