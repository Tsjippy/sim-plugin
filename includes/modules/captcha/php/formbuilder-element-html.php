<?php
namespace SIM\CAPTCHA;
use SIM;

add_filter('sim-form-element-html', __NAMESPACE__.'\addCaptchaHtml', 99, 3);
function addCaptchaHtml($html, $element, $object){
    switch($element->type){
        case 'hcaptcha':
            $html   = '';
            if(isset($_REQUEST['install-hcaptcha'])){
                ob_start();
                if(SIM\ADMIN\installPlugin('hcaptcha-for-forms-and-more/hcaptcha.php') !== true){
                    // check if api is set
                    $options	= get_option('hcaptcha_settings');

                    if(!$options || empty($options['site_key']) || empty($options['secret_key'])){
                        // redirect to the admin page to set an api key
                        if(current_user_can( 'manage_options' )){
                            wp_redirect(admin_url('options-general.php?page=hcaptcha'));
                        }else{
                            $html	.= "Installation succesfull.<br>Please make sure the hCaptcha api key is set";
                        }
                    }
                };
                ob_end_clean();
            }

            $html	.= do_shortcode( '[hcaptcha auto="true" force="true"]' );
            if(str_contains($html, '[hcaptcha ')){
                $message	= 'Please install the hcaptcha plugin before using this element';
                SIM\printArray($message);
                $html	= '';
                if(isset($_REQUEST['formbuilder'])){
                    $url	= SIM\currentUrl().'&install-hcaptcha=true';
                    $html	= $message." <a href='$url' class='button small'>Install now</a>";
                }
            }else{
                $html	.= '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="6 0 32 32" fill="none"><path opacity="0.5" d="M30 28H26V32H30V28Z" fill="#0074BF"/> <path opacity="0.7" d="M26 28H22V32H26V28Z" fill="#0074BF"/><path opacity="0.7" d="M22 28H18V32H22V28Z" fill="#0074BF"/><path opacity="0.5" d="M18 28H14V32H18V28Z" fill="#0074BF"/> <path opacity="0.7" d="M34 24H30V28H34V24Z" fill="#0082BF"/><path opacity="0.8" d="M30 24H26V28H30V24Z" fill="#0082BF"/><path d="M26 24H22V28H26V24Z" fill="#0082BF"/> <path d="M22 24H18V28H22V24Z" fill="#0082BF"/><path opacity="0.8" d="M18 24H14V28H18V24Z" fill="#0082BF"/><path opacity="0.7" d="M14 24H10V28H14V24Z" fill="#0082BF"/> <path opacity="0.5" d="M38 20H34V24H38V20Z" fill="#008FBF"/><path opacity="0.8" d="M34 20H30V24H34V20Z" fill="#008FBF"/><path d="M30 20H26V24H30V20Z" fill="#008FBF"/> <path d="M26 20H22V24H26V20Z" fill="#008FBF"/><path d="M22 20H18V24H22V20Z" fill="#008FBF"/><path d="M18 20H14V24H18V20Z" fill="#008FBF"/> <path opacity="0.8" d="M14 20H10V24H14V20Z" fill="#008FBF"/><path opacity="0.5" d="M10 20H6V24H10V20Z" fill="#008FBF"/><path opacity="0.7" d="M38 16H34V20H38V16Z" fill="#009DBF"/> <path d="M34 16H30V20H34V16Z" fill="#009DBF"/><path d="M30 16H26V20H30V16Z" fill="#009DBF"/><path d="M26 16H22V20H26V16Z" fill="#009DBF"/> <path d="M22 16H18V20H22V16Z" fill="#009DBF"/><path d="M18 16H14V20H18V16Z" fill="#009DBF"/><path d="M14 16H10V20H14V16Z" fill="#009DBF"/> <path opacity="0.7" d="M10 16H6V20H10V16Z" fill="#009DBF"/><path opacity="0.7" d="M38 12H34V16H38V12Z" fill="#00ABBF"/> <path d="M34 12H30V16H34V12Z" fill="#00ABBF"/><path d="M30 12H26V16H30V12Z" fill="#00ABBF"/><path d="M26 12H22V16H26V12Z" fill="#00ABBF"/> <path d="M22 12H18V16H22V12Z" fill="#00ABBF"/><path d="M18 12H14V16H18V12Z" fill="#00ABBF"/> <path d="M14 12H10V16H14V12Z" fill="#00ABBF"/><path opacity="0.7" d="M10 12H6V16H10V12Z" fill="#00ABBF"/> <path opacity="0.5" d="M38 8H34V12H38V8Z" fill="#00B9BF"/><path opacity="0.8" d="M34 8H30V12H34V8Z" fill="#00B9BF"/> <path d="M30 8H26V12H30V8Z" fill="#00B9BF"/><path d="M26 8H22V12H26V8Z" fill="#00B9BF"/> <path d="M22 8H18V12H22V8Z" fill="#00B9BF"/><path d="M18 8H14V12H18V8Z" fill="#00B9BF"/> <path opacity="0.8" d="M14 8H10V12H14V8Z" fill="#00B9BF"/><path opacity="0.5" d="M10 8H6V12H10V8Z" fill="#00B9BF"/> <path opacity="0.7" d="M34 4H30V8H34V4Z" fill="#00C6BF"/> <path opacity="0.8" d="M30 4H26V8H30V4Z" fill="#00C6BF"/> <path d="M26 4H22V8H26V4Z" fill="#00C6BF"/> <path d="M22 4H18V8H22V4Z" fill="#00C6BF"/> <path opacity="0.8" d="M18 4H14V8H18V4Z" fill="#00C6BF"/> <path opacity="0.7" d="M14 4H10V8H14V4Z" fill="#00C6BF"/> <path opacity="0.5" d="M30 0H26V4H30V0Z" fill="#00D4BF"/> <path opacity="0.7" d="M26 0H22V4H26V0Z" fill="#00D4BF"/> <path opacity="0.7" d="M22 0H18V4H22V0Z" fill="#00D4BF"/> <path opacity="0.5" d="M18 0H14V4H18V0Z" fill="#00D4BF"/> <path d="M16.5141 14.9697L17.6379 12.4572C18.0459 11.8129 17.9958 11.0255 17.5449 10.5745C17.4876 10.5173 17.416 10.46 17.3444 10.4171C17.0366 10.2238 16.6572 10.1808 16.3065 10.2954C15.9199 10.4171 15.5835 10.6748 15.3687 11.0184C15.3687 11.0184 13.8297 14.6046 13.2642 16.2153C12.6987 17.8259 12.9206 20.7822 15.1254 22.987C17.4661 25.3277 20.8448 25.8575 23.0066 24.2397C23.0997 24.1967 23.1784 24.1395 23.2572 24.0751L29.9072 18.5202C30.2293 18.2554 30.7089 17.7042 30.2794 17.0743C29.8642 16.4586 29.0697 16.881 28.7404 17.0886L24.9107 19.8731C24.8391 19.9304 24.7318 19.9232 24.6673 19.8517C24.6673 19.8517 24.6673 19.8445 24.6602 19.8445C24.56 19.7228 24.5456 19.4079 24.696 19.2862L30.5657 14.304C31.074 13.8459 31.1456 13.1802 30.7304 12.7292C30.3295 12.2854 29.6924 12.2997 29.1842 12.7578L23.9157 16.881C23.8155 16.9597 23.6652 16.9454 23.5864 16.8452L23.5793 16.838C23.4719 16.7235 23.4361 16.5231 23.5506 16.4014L29.535 10.596C30.0074 10.1522 30.036 9.4149 29.5922 8.94245C29.3775 8.72054 29.084 8.59169 28.7762 8.59169C28.4612 8.59169 28.1606 8.70623 27.9387 8.92813L21.8255 14.6691C21.6823 14.8122 21.396 14.6691 21.3602 14.4973C21.3459 14.4328 21.3674 14.3684 21.4103 14.3255L26.0918 8.99972C26.5571 8.56306 26.5858 7.83292 26.1491 7.36763C25.7124 6.90234 24.9823 6.87371 24.517 7.31036C24.4955 7.32468 24.4812 7.34615 24.4597 7.36763L17.3659 15.2203C17.1082 15.478 16.736 15.4851 16.557 15.342C16.4425 15.2489 16.4282 15.0843 16.5141 14.9697Z" fill="white"/> </svg>';					
            }
            break;
        case 'recaptcha':
            $html   = '';
            if(isset($_REQUEST['formbuilder'])){
                $key		= SIM\getModuleOption(MODULE_SLUG, 'recaptchakey');
                if(!$key){
                    $html	.= "<Please enter your recaptcha key in the module settings";
                }else{
                    $html   .= "<img src'".SIM\pathToUrl(MODULE_PATH.'/pictures/recaptcha.png')."'>";
                }
            }
            $html   .= getRecaptchaHtml();
            break;
        case 'turnstile':
            $key	= SIM\getModuleOption(MODULE_SLUG, 'turnstilekey');
            if(!$key){
                if(isset($_REQUEST['formbuilder'])){
                    $html	= "<Please enter your turnstile key in the module settings";
                }else{
                    $html	= '';
                }
            }else{
                $extraData	= '';
                if(!isset($_REQUEST['formbuilder']) && $element->hidden){
                    $extraData	        = "data-appearance='interaction-only'";
                    $element->hidden	= false;
                }
                $html	= getTurnstileHtml($extraData);
            }
            break;
    }

    return $html;
}
