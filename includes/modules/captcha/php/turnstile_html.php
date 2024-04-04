<?php
namespace SIM\CAPTCHA;
use SIM;

function getTurnstileHtml($extraData=''){
    $turnstile	    = SIM\getModuleOption(MODULE_SLUG, 'turnstile');
    $html               = '';

    if($turnstile && !empty($turnstile["key"])){
        wp_enqueue_script('sim_turnstile');

        $html	.= "<div class='cf-turnstile' data-sitekey='{$turnstile["key"]}' $extraData></div>";
    }

    return $html;
}