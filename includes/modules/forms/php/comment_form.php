<?php
namespace SIM\FORMS;
use SIM;


// add captcha fields to comment form
add_filter( 'comment_form_defaults', __NAMESPACE__.'\addCaptcha' );
function addCaptcha($args) {
    $recaptchaKey		= SIM\getModuleOption(MODULE_SLUG, 'recaptchakey');
    $recaptchaKeyType	= SIM\getModuleOption(MODULE_SLUG, 'recaptchakeytype');
    $turnstileKey	    = SIM\getModuleOption(MODULE_SLUG, 'turnstilekey');
    $html               = '';

    if($turnstileKey){
        wp_enqueue_script('sim_turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], false, true);

        $html	.= "<div class='cf-turnstile' data-sitekey='$turnstileKey'></div>";
    }

    if($recaptchaKey){
        if(!$recaptchaKeyType || $recaptchaKeyType == 'v2'){
            wp_enqueue_script('sim_recaptcha', "https://www.google.com/recaptcha/api.js", [], false, true);
            $html	.= "<div class='g-recaptcha' data-sitekey='$recaptchaKey' required></div>";
        }else{
            wp_enqueue_script('sim_recaptcha', "https://www.google.com/recaptcha/api.js?render=$recaptchaKey&onload=onloadCallback", [], false, true);
            $html	.= "
                <input type='hidden' name='g-recaptcha-response' id='g-recaptcha-response'>
                <script>
                    document.querySelectorAll('.submit_wrapper .form_submit').forEach(el=>el.disabled=true);
                    function onloadCallback(){
                        console.log('teset');
                        grecaptcha.ready(function() {
                            setInterval(function(){
                                grecaptcha.execute('$recaptchaKey', {action: 'validate_captcha'}).then(function(token) {
                                    document.querySelectorAll('.submit_wrapper .form_submit[disabled]').forEach(el=>el.disabled=false);
                                    console.log( 'refreshed token:', token );
                                    document.getElementById('g-recaptcha-response').value = token;
                                });
                            }, 60000);
                        });
                    }
                </script>
            ";
        }
    }
    
    $args['submit_field']  = $html.$args['submit_field'];

    return $args;
}

// process captcha
add_filter( 'pre_comment_approved' , __NAMESPACE__.'\checkCommentCaptcha' , '99', 2 );

function checkCommentCaptcha( $approved , $commentdata ){
  // insert code here to inspect $commentdata and determine 'approval', 'disapproval', 'trash', or 'spam' status
  $forms    = new SubmitForm();

  $verification = true;
  
  $verification = $forms->verifyTurnstile();

  if(!is_wp_error($verification)){
    $verification = $forms->verifyRecaptcha();
  }

  if(is_wp_error($verification)){
    return $verification;
  }

  return $approved;
}