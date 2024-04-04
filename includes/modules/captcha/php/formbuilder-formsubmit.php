<?php
namespace SIM\CAPTCHA;
use SIM;

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