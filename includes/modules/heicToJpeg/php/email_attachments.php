<?php
namespace SIM\HEICTOJPEG;
use SIM;

// convert heic attachments to jpg
add_filter('wp_mail', function($args){
    foreach($args['attachments'] as &$attach){
        $ext        = pathinfo($attach, PATHINFO_EXTENSION);

        if($ext == 'heic'){
            global $heicConverter;

            // only instantiate this class once to speed up
            if(!isset($heicConverter)){
                $heicConverter = new HeicConverter();
            }

            $dest   = str_replace($ext, 'jpg', $attach);

            // Convert the heic image
            if($heicConverter->convert($attach, $dest)){
                $attach = $dest;
            }
        }
    }

    return $args;
}, 10, 1);

// remove picture again
add_action( 'wp_mail_succeeded', __NAMESPACE__.'\removeJpg');

add_action( 'wp_mail_failed', __NAMESPACE__.'\removeJpg');

function removeJpg($mailData){
    if(!empty($mailData['attachments'])){
        // loop over all the attachments
        foreach($mailData['attachments'] as $attachment){
            $ext        = pathinfo($attachment, PATHINFO_EXTENSION);
            if($ext == 'jpg'){
                $heicPath   = str_replace($ext, 'heic', $attachment);
                
                // a heic path of this image exists
                if(file_exists($heicPath)){
                    // remove the jpg file
                    unlink($attachment);
                }
            }
        }
    }
}