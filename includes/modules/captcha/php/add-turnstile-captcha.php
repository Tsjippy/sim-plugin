<?php
namespace SIM\CAPTCHA;
use SIM;

$turnstileSettings   = SIM\getModuleOption(MODULE_SLUG, 'turnstile');
if(isset($turnstileSettings['login']) && $turnstileSettings['login'] == 'on'){
    add_action('login_form', __NAMESPACE__.'\addTurnstileHtml');
}

if(isset($turnstileSettings['newuser']) && $turnstileSettings['newuser'] == 'on'){
    add_action('register_form', __NAMESPACE__.'\addTurnstileHtml');
}

if(isset($turnstileSettings['password']) && $turnstileSettings['password'] == 'on'){
    add_action('resetpass_form', __NAMESPACE__.'\addTurnstileHtml');
}

if(isset($turnstileSettings['comment']) && $turnstileSettings['comment'] == 'on'){
    // add html
    add_filter( 'comment_form_defaults', function($args){
      $html                 = getTurnstileHtml();
      $args['submit_field'] = $html.$args['submit_field'];
  
      return $args;
    } );
}

function addTurnstileHtml(){
    // If the action we are hooking in was called more than once, return.
    if ( 
        did_action( 'login_form' ) > 1 ||
        did_action( 'register_form' ) > 1 ||
        did_action( 'resetpass_form' ) > 1 
    ) {
        return;
    }

    $extraData	= '';

    // reset pass is on the same page as login so make it hidden
    if(current_filter() == 'resetpass_form'){
        $extraData = "data-appearance='interaction-only'";
    }

    echo getTurnstileHtml($extraData);
};
