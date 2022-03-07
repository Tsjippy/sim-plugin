<?php
namespace SIM\USERMANAGEMENT;
use SIM;

function profile_picture_change($user_id){
	//Create all sizes of the image
	SIM\process_images();
	
	global $Maps;
	
	SIM\print_array("Updating profile image for $user_id");
	
	$privacy_preference = (array)get_user_meta( $user_id, 'privacy_preference', true );
	
	if (empty($privacy_preference['hide_profile_picture'])){
		$marker_id = get_user_meta($user_id,"marker_id",true);
		SIM\print_array("marker_id is $marker_id, user_id is $user_id");
		
		//New profile picture is set, update the marker icon
		if(is_numeric(get_user_meta($user_id,'profile_picture',true))){
			$icon_url = get_profile_picture_url($user_id);
			
			//Save profile picture as icon
			$Maps->create_icon($marker_id, get_userdata($user_id)->user_login, $icon_url, 1);
		}else{
			//remove the icon
			$Maps->remove_icon($marker_id);
		}
	}
}

function get_profile_picture_url($user_id,$size=[50,50]){
	$attachment_id	= get_user_meta($user_id,'profile_picture',true);
	$url			= false;
	if(is_numeric($attachment_id)){
		$url = wp_get_attachment_image_url($attachment_id,$size);
	}
	
	return $url;
}

function display_profile_picture($user_id, $size=[50,50], $show_default = true){
	$attachment_id = get_user_meta($user_id,'profile_picture',true);
	if(is_numeric($attachment_id)){
		$url = wp_get_attachment_image_url($attachment_id,'Full size');
		$img = wp_get_attachment_image($attachment_id,$size);
		return "<a href='$url'>$img</a>";
	}elseif($show_default){
		$url = plugins_url('',__DIR__).'/../pictures/usericon.png';
		return "<img width='50' height='50' src='$url' class='attachment-50x50 size-50x50' loading='lazy'>";
	}else{
		return false;
	}
}

// Apply filter
add_filter( 'get_avatar' , 'SIM\custom_avatar' , 1 , 5 );
function custom_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
    $user = false;
 
	//CHeck if an, id, email or user is given
    if ( is_numeric( $id_or_email ) ) {
        $id = (int) $id_or_email;
        $user = get_user_by( 'id' , $id );
 
    } elseif ( is_object( $id_or_email ) ) {
        if ( ! empty( $id_or_email->user_id ) ) {
            $id = (int) $id_or_email->user_id;
            $user = get_user_by( 'id' , $id );
        }
    } else {
        $user = get_user_by( 'email', $id_or_email );   
    }
	
	//If we have valid user, return there profile picture or the default
    if ( $user && is_object( $user ) ) {
		//Get profile picture id from db
		$url = get_profile_picture_url($user->ID);
		if ( $url == '' ) $url = plugins_url('',__DIR__).'/../pictures/usericon.png';
		$avatar = "<img alt='$alt' src='$url' class='avatar avatar-{$size} photo' height='$size' width='$size' />";
    }
    return $avatar;
}
