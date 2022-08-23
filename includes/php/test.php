<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $Modules;

	return 'e-mail send';
});

function sendTestEmail(){
    wp_mail('enharmsen@gmail.com' , 'test e-mail', 'test');
 
    return 'E-mail send';
}

add_action( 'rest_api_init', function () {
    // add element to form
    register_rest_route(
        'sim/v1',
        '/sendtestemail',
        array(
            'methods'               => 'GET',
            'callback'              =>  __NAMESPACE__.'\sendTestEmail',
            'permission_callback'   => '__return_true',
        )
    );
});