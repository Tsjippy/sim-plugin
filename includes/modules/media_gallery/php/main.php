<?php
namespace SIM\MEDIAGALLERY;
use SIM;

//change visibility of an attachment when uploaded via frontend even if it is a picture
add_action('sim_after_post_save', function($post){
    // Add to media gallery if post type is attachment
    if($post->post_type == 'attachment'){
        update_metadata( 'post',  $post->ID, 'gallery_visibility', 'show' );
    }
});

// change visibility of an attachment when it is a video or audio
add_action( 'add_attachment', function ( $postId) { 
    $post   = get_post($postId);
    $type   = explode('/', $post->post_mime_type)[0];
    
    if(in_array($type, ['audio', 'video'])){
        update_metadata( 'post',  $post->ID, 'gallery_visibility', 'show' );
    }
});