<?php
namespace SIM\CONTENTFILTER;
use SIM;

add_filter('sim-frontpage-post-gallery-posts', function($args, $postTypes){
    if(is_user_logged_in()){
        return $args;
    }

    // Build the sub-query for public cats
    $publicQuery    = ['relation' => 'OR'];
    foreach($postTypes as $type){
        $taxonomies = get_object_taxonomies( $type );

        foreach($taxonomies as $tax){
            $publicQuery[]  = array(
                'taxonomy' => $tax,
                'field'    => 'slug',
                'terms'    => array( 'public' ),
            );
        }
    }

    // create a nested tax query
    if(isset($args['tax_query'])){
        $args['tax_query']  = array(
            'relation' => 'AND',
            $publicQuery,
            $args['tax_query']
        );
    }else{
        $args['tax_query'] = $publicQuery;
    }

    return $args;
}, 10, 2);