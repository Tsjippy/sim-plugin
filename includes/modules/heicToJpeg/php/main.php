<?php
namespace SIM\HEICTOJPG;

use Composer\Pcre\Regex;
use SIM;

/**
 * Converts heic images to jpg
 *
 * @param   string  $path       The path to the file
 * @param   string  $dest       The path to the destination file. If left empty it will echo the image to the screen
 *
 * @return  boolean|string      Whether the conversion was succesfull or if destination is empty the image blob(url)
 */
function convert($path, $dest=''){
    if(is_file($path) && \Maestroerror\HeicToJpg::isHeic($path)) {
        if(empty($dest)){
            $jpg    = \Maestroerror\HeicToJpg::convert($path)->get();
            $base64 = base64_encode($jpg);
            return "data:image/jpeg;base64, $base64";
        }
        
        try{
            return \Maestroerror\HeicToJpg::convert($path)->saveAs($dest);
        }catch (\Exception $e) {
            SIM\printArray($e, true);
            return explode(':', $e->getMessage())[0];
        }
    }else{
        return false;
    }
}

// Images in post galleries
add_filter( 'get_post_galleries',          __NAMESPACE__.'\_imgUrlFilter', PHP_INT_MAX );
add_filter( 'widget_media_image_instance', __NAMESPACE__.'\_imgUrlFilter', PHP_INT_MAX );

// Core image retrieval
add_filter( 'image_downsize',               __NAMESPACE__.'\_imgUrlFilter', 10, 3 );

// Responsive image srcset substitution
add_filter( 'wp_calculate_image_srcset',    __NAMESPACE__.'\_imgUrlFilter', 10, 5 );
add_filter( 'wp_calculate_image_sizes',     __NAMESPACE__.'\_imgUrlFilter', 1,  2 );

// Media Library - modal popup
add_filter('wp_prepare_attachment_for_js',  __NAMESPACE__.'\_imgUrlFilter', PHP_INT_MAX );

// Main get image
add_filter('wp_get_attachment_image',       __NAMESPACE__.'\_imgUrlFilter', PHP_INT_MAX );
add_filter('wp_get_attachment_image_src',   __NAMESPACE__.'\_imgUrlFilter', PHP_INT_MAX );
add_filter('wp_get_attachment_url',         __NAMESPACE__.'\_imgUrlFilter', PHP_INT_MAX );

// Jetpack
add_filter( 'jetpack_photon_url',           __NAMESPACE__.'\_imgUrlFilter', 10, 3 );

// When using Insert Media pop-up
add_filter('image_send_to_editor',          __NAMESPACE__.'\_htmlImgUrlFilter');

// content
add_filter('the_content',                   __NAMESPACE__.'\_htmlImgUrlFilter', 999);

// Advanced Custom Feilds
add_filter('acf/fields/post_object/result', __NAMESPACE__.'\_htmlImgUrlFilter');
add_filter('acf/format_value',              __NAMESPACE__.'\_htmlImgUrlFilter');

/**
 * Filter Image URL.
 *
 * @param string $url of an image
 * @return string
 */
function _imgUrlFilter($url) {
    if(gettype($url) != 'string' || empty($url) || !str_contains($url, '.heic')){
        return $url;
    }

    // Convert the heic image
    $result = convert(SIM\urlToPath($url));
    
    if(!$result){
        return $url;
    }

    return $result;
}

/**
 * Extract and replace all URLs inside of an HTML string.
 * 
 * Note this does not factor in external images. Domain check may be required.
 *
 * @param   string $content     HTML that may contain images.
 *
 * @return  string              HTML with possibly images that have been filtered
 */
function _htmlImgUrlFilter($content) {
    // find any heic hyperlinks
    preg_match_all('/<a[^<]*?href=(?:\'|")([^<]*?\.heic)(?:\'|").*?>(.*?)<\/a>/', $content, $images);

    // loop over all the results, $images[0] contains the whole hyperlink html
    foreach ($images[0] as $index=>$hyperlink) {
        // if the content of the hyperlink contains already an image
        if(str_contains($images[2][$index], '<img')){
            continue;
        }

        //$images[1] contains the url
        $newUrl     = _imgUrlFilter($images[1][$index]);

        if($newUrl != $images[1][$index]){
            $new    = "<img src='$newUrl'>";

            $content = str_replace($hyperlink, $new, $content);
        }
    }

    return $content;
}

add_filter('sim_transform_formtable_data', function($output){

    return $output;
});