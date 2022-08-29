<?php
namespace SIM\FRONTPAGE;
use SIM;

//Redirect to frontpage for logged in users
add_action( 'template_redirect', function (){
	if( is_front_page() && is_user_logged_in() ){
        $url    = SIM\getValidPageLink(SIM\getModuleOption(MODULE_SLUG, 'home_page'));
        if($url && $url != SIM\currentUrl()){ 
            wp_redirect(add_query_arg($_GET, $url));
            exit();
        }
	}
});