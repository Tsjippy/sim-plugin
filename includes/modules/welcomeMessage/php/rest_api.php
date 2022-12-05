<?php

add_action( 'rest_api_init', function () {
	// get_attachment_contents
	register_rest_route(
		'sim/v2/frontpage',
		'/hide_welcome',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
                update_user_meta(get_current_user_id(), 'welcomemessage', true);
            },
			'permission_callback' 	=> '__return_true',
		)
	);
});