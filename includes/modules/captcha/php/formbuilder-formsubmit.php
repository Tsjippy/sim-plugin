<?php
namespace SIM\CAPTCHA;
use SIM;

/**
 * Generic function to retrieve token status for captchas
 */
function verifyCaptcha($verifyUrl, $data){
    if (function_exists('curl_init') && function_exists('curl_setopt') && function_exists('curl_exec')){
        // Use cURL to get data 10x faster than using file_get_contents or other methods
        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-type: application/x-www-form-urlencoded'));
        $response = curl_exec($ch);
        curl_close($ch);
    }else{
        // If server not have active cURL module, use file_get_contents
        $opts = array('http' =>
            array(
                'method' 	=> 'POST',
                'header'	=> 'Content-type: application/x-www-form-urlencoded',
                'content' 	=> $data
            )
        );
        $context 	= stream_context_create($opts);
        $response 	= file_get_contents($verifyUrl, false, $context);
    }

    return json_decode($response);
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

    $secret		= SIM\getModuleOption(MODULE_SLUG, 'turnstilesecretkey');
    $verifyUrl 	= "https://challenges.cloudflare.com/turnstile/v0/siteverify";
    $data		= "secret=$secret&response={$_REQUEST['cf-turnstile-response']}";

    $json	    = verifyCaptcha($verifyUrl, $data);

    if(empty($json->success)){
        return new \WP_Error('forms', "Invalid Turnstile Response!");
    }else{
        return true;
    }
}

/**
 * Verifies a recaptcha token from $_REQUEST
 */
function verifyRecaptcha(){
    if(empty($_REQUEST['g-recaptcha-response'])){
        return false;
    }

    $secret		= SIM\getModuleOption(MODULE_SLUG, 'recaptchasecret');
    $verifyUrl 	= 'https://www.google.com/recaptcha/api/siteverify';

    $queryData = [
        'secret' 	=> $secret,
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

add_filter('sim_abefore_saving_formdata', __NAMESPACE__.'\verifyFormCaptcha', 10, 2);

function verifyFormCaptcha($verification, $object){
    if($object->getElementByType('turnstile')){
        $verifcation	= verifyTurnstile();
    }

    if($verifcation && $object->getElementByType('recaptcha')){
        $verifcation	= verifyRecaptcha();
    }

    if($verifcation !== true){
        return $verifcation;
    }
}