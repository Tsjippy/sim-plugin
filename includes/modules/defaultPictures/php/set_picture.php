<?php
namespace SIM\DEFAULTPICTURE;
use SIM;

/**
 * Set the default picture of a post
 * @param  	int 	$postId		The WP_Post id
*/
function setDefaultPicture($postId){
    if(has_post_thumbnail($postId)){
         return;
    }

    $pictureIds    = SIM\getModuleOption(MODULE_SLUG, 'picture_ids');
    $categories    = get_the_category( $postId );

    $pictureSet    = false;
    # Loop over all categories of this post
    foreach($categories as $category){
        # If the current category has a default picture set
        if(is_numeric($pictureIds[$category->slug])){
            # Set the picture
            set_post_thumbnail( $postId, $pictureIds[$category->slug]);
            $pictureSet    = true;
            break;
        }
    }
	
    # Still no picture set
    if(!$pictureSet){
        # If the posttype has a default picture set
        $postType      = get_post_type($postId);
        if(is_numeric($pictureIds[$postType])){
            # Set the picture
            set_post_thumbnail( $postId, $pictureIds[$postType]);
        }
    }
}

add_action('sim_after_post_save', function($post){
    //check if we need to set an default image
    setDefaultPicture($post->ID);
});

//If no featured image on post is set, set one
add_action( 'wp_after_insert_post', function ( $postId, $post) {
	if($post->post_status == "publish"){
        setDefaultPicture($postId);
	}
}, 10, 2 );