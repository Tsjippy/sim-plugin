<?php
namespace SIM\EMBEDPAGE;
use SIM;

add_action( 'rest_api_init', function () {
    // query for posts
	register_rest_route(
        RESTAPIPREFIX.'/embedpage',
        '/find',
        array(
            'methods'               => 'POST,GET',
            'callback'              => function($wpRequest){
                $search = $wpRequest->get_param('search');

                if(strlen($search) < 3){
                    return [];
                }

                $args = array(
                    'post_status'       => 'publish',
                    'post_type'         => 'any',
                    's'                 => $search,
                    'posts_per_page'    => -1
                );
        
                $wpQuery  = new \WP_Query( $args );

                return $wpQuery->posts;
            },
            'permission_callback'   => '__return_true',
            'args'					=> array(
				'search'	=> array(
					'required'	=> true
				),
			)
		)
	);
});