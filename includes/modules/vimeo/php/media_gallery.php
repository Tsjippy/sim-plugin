<?php
namespace SIM\VIMEO;
use SIM;

add_filter('sim_media_gallery_item_html', function($mediaHtml, $type, $postId){
    if($type != 'video') return $mediaHtml;

    $vimeo      = new VimeoApi();
    $vimeoId    = $vimeo->getVimeoId($postId);

    // Vimeo video
    if(is_numeric($vimeoId)){
        return show_vimeo_video($vimeoId);
    }

    return $mediaHtml;
}, 10, 3);

add_filter('sim_media_gallery_download_url', function($url, $postId){
    $vimeo      = new VimeoApi();
    $path       = $vimeo->getVideoPath($postId);

    if($path){
        return SIM\pathToUrl($path);
    }

    return $url;
}, 10, 2);

add_filter('sim_media_gallery_download_filename', function($fileName, $type, $postId){
    if($type != 'video') return $fileName;

    $vimeo      = new VimeoApi();
    $path       = $vimeo->getVideoPath($postId);

    if($path){
        $fileName   = basename($path);
        $vimeoId    = $vimeo->getVimeoId($postId);

        $fileName   = str_replace($vimeoId.'_', '', $fileName);

        return $fileName;
    }

    return $fileName;
}, 10, 3);