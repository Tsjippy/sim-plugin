<?php
namespace SIM\VIMEO;
use SIM;

add_filter('sim_media_gallery_item_html', function($mediaHtml, $type, $postId){
    if($type != 'video'){
        return $mediaHtml;
    }

    $vimeoApi      = new VimeoApi();

    $vimeoId    = $vimeoApi->getVimeoId($postId);

    // Vimeo video
    if(is_numeric($vimeoId)){
        return showVimeoVideo($vimeoId);
    }

    return $mediaHtml;
}, 10, 3);

add_filter('sim_media_gallery_download_url', function($url, $postId){
    $vimeoApi   = new VimeoApi();
    $path       = $vimeoApi->getVideoPath($postId);

    if($path){
        return SIM\pathToUrl($path);
    }

    return $url;
}, 10, 2);

add_filter('sim_media_gallery_download_filename', function($fileName, $type, $postId){
    if($type != 'video'){
        return $fileName;
    }

    $vimeoApi   = new VimeoApi();
    $path       = $vimeoApi->getVideoPath($postId);

    if($path){
        $fileName   = basename($path);
        $vimeoId    = $vimeoApi->getVimeoId($postId);

        $fileName   = str_replace($vimeoId.'_', '', $fileName);

        return $fileName;
    }

    return $fileName;
}, 10, 3);