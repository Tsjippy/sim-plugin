<?php
namespace SIM;

add_action( 'rest_api_init', function () {
	register_rest_route( 
		'sim/v1/', 
		'/user_roles', 
		array(
			'methods' 				=> 'GET',
			'callback' 				=> function(){
                global $wp_roles;
	            return $wp_roles->role_names;
            },
			'permission_callback' 	=> '__return_true',
		)
	);

});
