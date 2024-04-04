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
