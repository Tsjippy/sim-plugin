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
/**
 * Setup the one time key for authenticator
 *
 * @return  object       An object with the secret key and qr code
 */
function setupTimeCode(){
    $user                           = wp_get_current_user();
    $userId                         = $user->ID;
    $twofa                          = new TwoFactorAuth();
    $setupDetails                   = new stdClass();
    $setupDetails->secretKey        = $twofa->createSecret();

    update_user_meta($userId, '2fa_hash', password_hash($setupDetails->secretKey, PASSWORD_DEFAULT));

    if (!extension_loaded('imagick')){
        $setupDetails->imageHtml    = "<img src=".$twofa->getQRCodeImageAsDataUri(SITENAME." (".get_userdata($userId)->user_login.")", $setupDetails->secretKey)." loading='lazy'>";
    }else{
        $qrCodeUrl                  = $twofa->getQRText(SITENAME." (".get_userdata($userId)->user_login.")",$setupDetails->secretKey);

        $renderer                   = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );
        $writer         = new Writer($renderer);
        $qrcodeImage   = base64_encode($writer->writeString($qrCodeUrl));

        $setupDetails->imageHtml     = "<img loading='lazy' src='data:image/png;base64, $qrcodeImage'/>";
    }
    otpauth://totp/Example:alice@google.com?secret=JBSWY3DPEHPK3PXP&issuer=Example

    $websiteName                   = rawurlencode(get_bloginfo('name'));
    $userName                      = rawurlencode($user->display_name);
    $totpUrl                       = "otpauth://totp/$websiteName:$userName?secret={$setupDetails->secretKey}&issuer=$websiteName";
    $setupDetails->appLink        = "<a href='$totpUrl' class='button' id='2fa-authenticator-link'>Go to authenticator app</a>";

    return $setupDetails;
}

/** 
 * Create a randow code and send it via e-mail to an user
 * 
 * @param   object  WP_User
*/
function sendEmailCode($user){
    $emailCode  = mt_rand(1000000000,9999999999);

    if(!isset($_SESSION)){
        session_start();
    }
    $_SESSION['2fa_email_key']  = $emailCode;

    $twoFaEmail    = new TwoFaEmail($user, $emailCode);
	$twoFaEmail->filterMail();
						
	return wp_mail( $user->user_email, $twoFaEmail->subject, $twoFaEmail->message);
}

/**
 * Verify the submitted e-mail code
 * 
 * @return  bool    true if valid code false otherwise
 */
function verifyEmailCode(){
    if(!isset($_SESSION)){
        session_start();
    }
    $emailCode = $_SESSION['2fa_email_key'];

    if($emailCode == $_POST['email_code']){
        unset($_SESSION['2fa_email_key']);
        return true;
    }
    
    return false;
}

/**
 * Send an e-mail if two factor is not enabled and someone logs in
 * 
 * @param   object  $user       WP_User
 */
function send2faWarningEmail($user){
    //if this is the first time ever login we do not have to send a warning
    if(!get_user_meta($user->id, 'login_count', true)){
        return;
    }

    //Send e-mail
    $unsafeLogin    = new UnsafeLogin($user);
	$unsafeLogin->filterMail();
						
	wp_mail( $user->user_email, $unsafeLogin->subject, $unsafeLogin->message);
}

/**
 * Reset 2fa and send a message about it
 * 
 * @param int   $userID
 */
function reset2fa($userId){
	global $wpdb;

	//Remove all 2fa keys
    $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '2fa%' AND user_id=$userId" );
	
	$userdata = get_userdata($userId);
	//Send email and signal message
	SIM\trySendSignal(
		"Hi ".$userdata->first_name.",\n\nYour account is unlocked you can now login using your credentials. Please enable 2FA as soon as possible",
		$_GET['user_id']
	);

	//Send e-mail
    $twoFaReset    = new TwoFaReset($userdata);
	$twoFaReset->filterMail();
						
	wp_mail( $userdata->user_email, $twoFaReset->subject, $twoFaReset->message);
}

//Check 2fa after user credentials are checked
add_filter( 'authenticate', function ( $user) {
    $methods    = get_user_meta($user->ID, '2fa_methods', true);
    if(!empty($methods)){
        if(!isset($_SESSION)){
            session_start();
        }

        // Remove webautn_id if webauthn was unsuccesfull
        if($_SESSION['webautn_id'] && $_SESSION['webauthn'] != 'success'){
            unset($_SESSION['webautn_id']);
        }
        
        //we did a succesfull webauthn or are on localhost
        if($_SERVER['HTTP_HOST'] == 'localhost' || in_array('webauthn', $methods) && $_SESSION['webauthn'] == 'success'){
            //succesfull webauthentication done before
        }elseif(in_array('authenticator', $methods)){
            $twofa      = new TwoFactorAuth();
            $secretKey  = get_user_meta($user->ID,'2fa_key',true);
            /*$hash     = get_user_meta($user->ID,'2fa_hash',true);
             if(!password_verify($secretKey,$hash)){
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
            }elseif($twofa->verifyCode($secretKey, $authcode, 1, null, $timeslice)){
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
        }elseif(in_array('email', $methods)){
            if(!verifyEmailCode()){
                $user = new \WP_Error(
                    '2fa error',
                    'Invalid e-mail code given'
                );
            }
        }else{
            //we have setup an authenticator method but did not use it
            send2faWarningEmail($user);
        }
    }else{
        //no 2fa configured yet
        send2faWarningEmail($user);
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
}, 40);

//Redirect to 2fa page if not setup
add_action('init', function(){
    // do not run during rest request
    if(SIM\isRestApiRequest()){
        return;
    }

    $user		= wp_get_current_user();

    //If 2fa not enabled and we are not on the account page
    $methods	= get_user_meta($user->ID, '2fa_methods', true);
    SIM\cleanUpNestedArray($methods);

    if(!isset($_SESSION)){
        session_start();
    }
    if (
        is_user_logged_in()                             &&	// we are logged in
        strpos($user->user_email,'.empty') === false    && 	// we have a valid email
        (
            !$methods                                   ||	// and we have no 2fa enabled or
            (
                !isset($_SESSION['webauthn'])           &&
                count($methods) == 1                    &&	// and we only have one 2fa method
                in_array('webauthn', $methods)				// and that method is webauthn
            )
        )
    ){
        $url		= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, '2fa_page');
        if(!$url){
            return;
        }

        if(SIM\currentUrl() != $url){
            SIM\printArray("Redirecting from ".SIM\currentUrl()." to $url");
            wp_redirect($url);
            exit();
        }
    }
});