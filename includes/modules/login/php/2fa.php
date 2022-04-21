<?php
namespace SIM\LOGIN;
use SIM;
use RobThree\Auth\TwoFactorAuth;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use stdClass;
use WP_Error;

if(!class_exists('BaconQrCode\Renderer\ImageRenderer')){
    return new WP_Error('2fa', "bacon-qr-code interface does not exist. Please run 'composer require bacon/bacon-qr-code'");
}
if(!class_exists('RobThree\Auth\TwoFactorAuth')){
    return new WP_Error('2fa', "twofactorauth interface does not exist. Please run 'composer require robthree/twofactorauth'");
}

//https://robthree.github.io/TwoFactorAuth/getting-started.html
function setupTimeCode(){
    $user                           = wp_get_current_user();
    $user_id                        = $user->ID;
    $twofa                          = new TwoFactorAuth();
    $setup_details                  = new stdClass();
    $setup_details->secretkey       = $twofa->createSecret();

    update_user_meta($user_id,'2fa_hash',password_hash($setup_details->secretkey,PASSWORD_DEFAULT));

    if (!extension_loaded('imagick')){
        $setup_details->image_html     = "<img src=".$twofa->getQRCodeImageAsDataUri(SITENAME." (".get_userdata($user_id)->user_login.")", $setup_details->secretkey).">";
    }else{
        $qrCodeUrl = $twofa->getQRText(SITENAME." (".get_userdata($user_id)->user_login.")",$setup_details->secretkey);

        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );
        $writer         = new Writer($renderer);
        $qrcode_image   = base64_encode($writer->writeString($qrCodeUrl));

        $setup_details->image_html     = "<img src='data:image/png;base64, $qrcode_image'/>";
    }
    otpauth://totp/Example:alice@google.com?secret=JBSWY3DPEHPK3PXP&issuer=Example

    $website_name                   = rawurlencode(get_bloginfo('name'));
    $user_name                      = rawurlencode($user->display_name);
    $totp_url                       = "otpauth://totp/$website_name:$user_name?secret={$setup_details->secretkey}&issuer=$website_name";
    $setup_details->app_link        = "<a href='$totp_url' class='button' id='2fa-authenticator-link'>Go to authenticator app</a>";

    return $setup_details;
}

function sendEmailCode($user){
    $email_code  = mt_rand(1000000000,9999999999);

    if(!isset($_SESSION)) session_start();
    $_SESSION['2fa_email_key']  = $email_code;

    $twoFAEmail    = new TwoFAEmail($user, $email_code);
	$twoFAEmail->filterMail();
						
	wp_mail( $user->user_email, $twoFAEmail->subject, $twoFAEmail->message);
}

function verifyEmailCode(){
    if(!isset($_SESSION)) session_start();
    $email_code = $_SESSION['2fa_email_key'];

    if($email_code == $_POST['email_code'] or $_SERVER['HTTP_HOST'] == 'localhost'){
        return true;
        unset($_SESSION['2fa_email_key']);
    }else{
        return false;
    }
}

function send_2fa_warning_email($user){
    //if this is the first time ever login we do not have to send a warning
    if(!get_user_meta($user->id, 'login_count', true)) return;

    //Send e-mail
    $unsafeLogin    = new UnsafeLogin($user);
	$unsafeLogin->filterMail();
						
	wp_mail( $user->user_email, $unsafeLogin->subject, $unsafeLogin->message);
}

//Reset 2fa
function reset_2fa($user_id){
	global $wpdb;

	//Remove all 2fa keys
    $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '2fa%' AND user_id=$user_id" );
	
	$userdata = get_userdata($user_id);
	//Send email and signal message
	SIM\try_send_signal(
		"Hi ".$userdata->first_name.",\n\nYour account is unlocked you can now login using your credentials. Please enable 2FA as soon as possible",
		$_GET['user_id']
	);

	//Send e-mail
    $twoFaReset    = new TwoFaReset($userdata);
	$twoFaReset->filterMail();
						
	wp_mail( $userdata->user_email, $twoFaReset->subject, $twoFaReset->message);
}

//Check 2fa during login
add_filter( 'wp_authenticate_user', function ( $user) {
    $methods    = get_user_meta($user->ID,'2fa_methods',true);
    if(!empty($methods)){
        if(!isset($_SESSION)) session_start();

        // Remove webautn_id if webauthn was unsuccesfull
        if($_SESSION['webautn_id'] and $_SESSION['webauthn'] != 'success'){
            unset($_SESSION['webautn_id']);
        }
        
        //we did a succesfull webauthn
        if(in_array('webauthn',$methods) and $_SESSION['webauthn'] == 'success'){
            //succesfull webauthentication done before
        }elseif(in_array('authenticator',$methods)){
            $twofa      = new TwoFactorAuth();
            $secretkey  = get_user_meta($user->ID,'2fa_key',true);
            /*$hash     = get_user_meta($user->ID,'2fa_hash',true);
             if(!password_verify($secretkey,$hash)){
                $user = new \WP_Error(
                    '2fa error',
                    '2fa key has changed!<br>Please contact your site admin.' 
                );
            } */
        
            $authcode   = $_POST['authcode'];
            $last2fa    = get_user_meta($user->ID,'2fa_last',true);
            $timeslice  = '';

            if(!is_numeric($authcode)){
                $user = new \WP_Error(
                    '2fa error',
                    'No 2FA code given' 
                );
            }elseif($twofa->verifyCode($secretkey, $authcode, 1, null, $timeslice)){
                //timeslice should be larger then last2fa
                if($timeslice<= $last2fa){
                    $user = new \WP_Error(
                        '2fa error',
                        'Invalid 2FA code given' 
                    );
                }else{
                    //store last time
                    update_user_meta($user->ID, '2fa_last', $last2fa);
                }
            }else{
                $user = new \WP_Error(
                    '2fa error',
                    'Invalid 2FA code given' 
                );
            }
        }elseif(in_array('email',$methods)){
            if(!verifyEmailCode()){
                $user = new \WP_Error(
                    '2fa error',
                    'Invalid e-mail code given' 
                );
            }
        }else{
            //we have setup an authenticator method but did not use it
            send_2fa_warning_email($user);
        }
    }else{
        //no 2fa configured yet
        send_2fa_warning_email($user);
    }

    if(isset($_SESSION)){
        unset($_SESSION['pkcco_auth']);
        unset($_SESSION['user_name_auth']);
        unset($_SESSION['user_auth']);
        unset($_SESSION['user_info']);
        if($_SESSION['webauthn'] == 'success'){
            unset($_SESSION['webauthn']);
        }
    }

    return $user;
}); 

//Redirect to 2fa page if not setup
add_action('wp_footer', function(){
    $user		= wp_get_current_user();

    //If 2fa not enabled and we are not on the account page
    $methods	= get_user_meta($user->ID,'2fa_methods',true);
    if(!isset($_SESSION)) session_start();
    if (
        is_user_logged_in() and 							// we are logged in
        strpos($user->user_email,'.empty') === false and 	// we have a valid email
        (
            !$methods or									// and we have no 2fa enabled or
            (
                isset($_SESSION['webauthn']) and
                $_SESSION['webauthn'] == 'failed' and 		// we have a failed webauthn
                count($methods) == 1 and					// and we only have one 2fa method
                in_array('webauthn',$methods)				// and that method is webauthn
            )
        )
    ){
        $twofa_page      = get_page_link(SIM\get_module_option('login', '2fa_page'));
        $twofa_page     .= SIM\get_module_option('login', '2fa_page_extras');

        //Only redirect if we are not currently on the page already
        if(strpos(SIM\current_url(),$twofa_page) === false){
            SIM\print_array("Redirecting from ".SIM\current_url()." to $twofa_page");
            wp_redirect($twofa_page);
            exit();
        }
    }
});