<?php
namespace SIM\CAPTCHA;
use SIM;

// add captcha fields to comment form
add_filter( 'comment_form_defaults', __NAMESPACE__.'\addCaptcha' );
function addCaptcha($args) {
    $turnstileKey	    = SIM\getModuleOption(MODULE_SLUG, 'turnstilekey');
    $html               = '';

    if($turnstileKey){
        wp_enqueue_script('sim_turnstile');

        $html	.= "<div class='cf-turnstile' data-sitekey='$turnstileKey'></div>";
    }

    $html   .= getRecaptchHtml();
    
    
    $args['submit_field']  = $html.$args['submit_field'];

    return $args;
}

// process captcha results
add_filter( 'pre_comment_approved' , __NAMESPACE__.'\checkCommentCaptcha' , '99', 2 );

function checkCommentCaptcha( $approved , $commentdata ){
  $verification     = true;
  
  $verification     = verifyTurnstile();

  if(!is_wp_error($verification)){
    $verification   = verifyRecaptcha();
  }

  if(is_wp_error($verification)){
    return $verification;
  }

  return $approved;
}