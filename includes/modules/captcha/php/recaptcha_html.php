<?php
namespace SIM\CAPTCHA;
use SIM;

function getRecaptchaHtml(){
    $recaptcha		= SIM\getModuleOption(MODULE_SLUG, 'recaptcha');
    $html               = '';

    if($recaptcha && !empty($recaptcha['key'])){
        if(!$recaptcha['keytype'] || $recaptcha['keytype'] == 'v2'){
            wp_enqueue_script('sim_recaptcha_v2');
            $html	.= "<div class='g-recaptcha' data-sitekey='{$recaptcha['key']}' required></div>";
        }else{
            wp_enqueue_script('sim_recaptcha_v3');
            ob_start();
            ?>
            <input type='hidden' name='g-recaptcha-response' id='g-recaptcha-response'>
            <script>
                document.querySelectorAll('.submit_wrapper .form_submit').forEach(el=>el.disabled=true);
                function onloadCallback(){
                    grecaptcha.ready(function() {
                        setInterval(function(){
                            grecaptcha.execute('<?php echo $recaptcha['key'];?>', {action: 'validate_captcha'}).then(function(token) {
                                document.querySelectorAll('.submit_wrapper .form_submit[disabled]').forEach(el=>el.disabled=false);
                                console.log( 'refreshed token:', token );
                                document.getElementById('g-recaptcha-response').value = token;
                            });
                        }, 60000);
                    });
                }
            </script>
            <?php
            $html   .= ob_get_clean();
        }
    }

    return $html;
}