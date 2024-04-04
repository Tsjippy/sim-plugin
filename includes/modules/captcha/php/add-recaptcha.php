<?php
namespace SIM\CAPTCHA;
use SIM;

$recaptchaSettings   = SIM\getModuleOption(MODULE_SLUG, 'recaptcha');
if(isset($recaptchaSettings['login']) && $recaptchaSettings['login'] == 'on'){
    add_action('login_form', __NAMESPACE__.'\addRecaptchaHtml');
}

if(isset($recaptchaSettings['newuser']) && $recaptchaSettings['newuser'] == 'on'){
    add_action('register_form', __NAMESPACE__.'\addRecaptchaHtml');
}

if(isset($recaptchaSettings['password']) && $recaptchaSettings['password'] == 'on'){
    add_action('resetpass_form', __NAMESPACE__.'\addRecaptchaHtml');
}

function addRecaptchaHtml(){
    // If the action we are hooking in was called more than once, return.
    if ( 
        did_action( 'login_form' ) > 1 ||
        did_action( 'register_form' ) > 1 ||
        did_action( 'resetpass_form' ) > 1 
    ) {
        return;
    }

    echo getRecaptchaHtml();
};

if(isset($recaptchaSettings['comment']) && $recaptchaSettings['comment'] == 'on'){
    // add html
    add_filter( 'comment_form_defaults', function($args){
      $html   = getRecaptchaHtml();
      $args['submit_field']  = $html.$args['submit_field'];
  
      return $args;
    } );
}