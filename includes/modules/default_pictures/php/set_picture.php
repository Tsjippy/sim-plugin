<?php
namespace SIM\DEFAULTPICTURE;
use SIM;

//Set the default picture
function set_default_picture($post_id){
    if(has_post_thumbnail($post_id)) return;

    $picture_ids    = SIM\get_module_option('default_pictures', 'picture_ids');
    $categories     = get_the_category( $post_id );

    $picture_set    = false;
    # Loop over all categories of this post
    foreach($categories as $category){
        # If the current category has a default picture set
        if(is_numeric($picture_ids[$category->slug])){
            # Set the picture
            set_post_thumbnail( $post_id, $picture_ids[$category->slug]);
            $picture_set    = true;
            break;
        }
    }
	
    # Still no picture set
    if(!$picture_set){
        # If the posttype has a default picture set
        $post_type      = get_post_type($post_id);
        if(is_numeric($picture_ids[$post_type])){
            # Set the picture
            set_post_thumbnail( $post_id, $picture_ids[$post_type]);
        }
    }
}

add_action('sim_after_post_save', function($post){
    //check if we need to set an default image
    set_default_picture($post->ID);
});


//If no featured image on post is set, set one
add_action( 'save_post', function ( $post_id, $post, $update ) {
	if($post->post_status == "publish"){
        set_default_picture($post_id);
	}
}, 10,3 );