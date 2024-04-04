<?php
namespace SIM\CAPTCHA;
use SIM;

if(!isset($turnstileSettings)){
    $turnstileSettings   = SIM\getModuleOption(MODULE_SLUG, 'turnstile');
}

if(isset($turnstileSettings['login']) && $turnstileSettings['login'] == 'on'){
    add_filter( 'authenticate', __NAMESPACE__.'\turnstileFilter');
}

if(isset($turnstileSettings['newuser']) && $turnstileSettings['newuser'] == 'on'){
    add_filter( 'registration_errors', __NAMESPACE__.'\turnstileFilter' );
}

if(isset($turnstileSettings['password']) && $turnstileSettings['password'] == 'on'){
    add_filter( 'lostpassword_errors', __NAMESPACE__.'\turnstileFilter' );
}

if(isset($turnstileSettings['comment']) && $turnstileSettings['comment'] == 'on'){
    add_filter( 'lostpassword_errors', __NAMESPACE__.'\turnstileFilter' );
}

function turnstileFilter($errors){
    $verficationResult  = verifyTurnstile();

    if(is_wp_error($verficationResult)){
        return $verficationResult;
    }

    return $errors;
}

/**
 * Verifies a turnstile token from $_REQUEST
 *
 * @return	bool			false if no token found
 */
function verifyTurnstile(){
    if(!isset($_REQUEST['cf-turnstile-response'])){
        return false;
    }

    global $turnstileSettings;

    $secret		= $turnstileSettings['secretkey'];
    $verifyUrl 	= "https://challenges.cloudflare.com/turnstile/v0/siteverify";
    $data		= "secret=$secret&response={$_REQUEST['cf-turnstile-response']}";

    $json	    = verifyCaptcha($verifyUrl, $data);

    if(empty($json->success)){
        return new \WP_Error('forms', "Invalid Turnstile Response!");
    }else{
        return true;
    }
}