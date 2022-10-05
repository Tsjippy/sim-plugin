<?php
namespace SIM\MEDIAGALLERY;
use SIM;

add_shortcode('mediagallery', function($atts){
    $a = shortcode_atts( array(
        'categories' 	=> []
    ), $atts );

    if(!is_array($a['categories'])){
        $a['categories']    = explode(',', $a['categories']);
    }

    $mediaGallery   = new MediaGallery($acceptedMimes, $amount, $page, $a['categories'], $search='');

    return $mediaGallery->filterableMediaGallery();
});
