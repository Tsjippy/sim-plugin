<?php
namespace SIM\CAPTCHA;
use SIM;

if(!isset($recaptchaSettings)){
    $recaptchaSettings   = SIM\getModuleOption(MODULE_SLUG, 'recaptcha');
}

if(isset($recaptchaSettings['login']) && $recaptchaSettings['login'] == 'on'){
    add_filter( 'authenticate', __NAMESPACE__.'\recaptchaFilter');
}

if(isset($recaptchaSettings['newuser']) && $recaptchaSettings['newuser'] == 'on'){
    add_filter( 'registration_errors', __NAMESPACE__.'\recaptchaFilter' );
}

if(isset($recaptchaSettings['password']) && $recaptchaSettings['password'] == 'on'){
    add_filter( 'lostpassword_errors', __NAMESPACE__.'\recaptchaFilter' );
}

if(isset($recaptchaSettings['comment']) && $recaptchaSettings['comment'] == 'on'){
    add_filter( 'pre_comment_approved', __NAMESPACE__.'\recaptchaFilter' );
}

function recaptchaFilter($errors){
    $verficationResult  = verifyRecaptcha();

    if(is_wp_error($verficationResult)){
        return $verficationResult;
    }

    return $errors;
}

/**
 * Verifies a recaptcha token from $_REQUEST
 */
function verifyRecaptcha(){
    if(empty($_REQUEST['g-recaptcha-response'])){
        return false;
    }

    $recaptcha		= SIM\getModuleOption(MODULE_SLUG, 'recaptcha');
    $verifyUrl 	= 'https://www.google.com/recaptcha/api/siteverify';

    $queryData = [
        'secret' 	=> $recaptcha['secret'],
        'response' 	=> $_REQUEST['g-recaptcha-response'],
        'remoteip' 	=> (isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"] : $_SERVER['REMOTE_ADDR'])
    ];

    // Collect and build POST data
    $data = http_build_query($queryData, '', '&');

    $json	= verifyCaptcha($verifyUrl, $data, 'reCaptcha');

    if(empty($json->success) || $json->score < 0.5){
        return new \WP_Error('forms', "Invalid Google Response!");
    }else{
        return true;
    }
}