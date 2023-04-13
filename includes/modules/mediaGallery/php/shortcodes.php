<?php
namespace SIM\MEDIAGALLERY;
use SIM;

add_shortcode('mediagallery', function($atts){
    $a = shortcode_atts( array(
        'categories' 	=> [],
        'types'         => ['image', 'audio', 'video'],
        'amount'        => 20,
        'color'         => '#FFFFFF'
    ), $atts );

    if(!is_array($a['categories'])){
        $a['categories']    = explode(',', $a['categories']);
    }

    $mediaGallery   = new MediaGallery($a['types'], $a['amount'], $a['categories'], false, 1, '', $a['color'] );

    return $mediaGallery->filterableMediaGallery();
});
