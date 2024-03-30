<?php
namespace SIM\CAPTCHA;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    $recaptchaKey		= SIM\getModuleOption(MODULE_SLUG, 'recaptchakey');

    wp_register_script('sim_turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], false, true);

    wp_register_script('sim_recaptcha_v2', "https://www.google.com/recaptcha/api.js", [], false, true);

    wp_register_script('sim_recaptcha_v3', "https://www.google.com/recaptcha/api.js?render=$recaptchaKey&onload=onloadCallback", [], false, true);
});