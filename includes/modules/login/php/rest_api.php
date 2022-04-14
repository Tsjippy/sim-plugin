<?php
namespace SIM\LOGIN;
use SIM;
use RobThree\Auth\TwoFactorAuth;
use Webauthn\Server;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSourceRepository as PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\AuthenticatorSelectionCriteria;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use DeviceDetector\Parser\OperatingSystem as OS_info;
use WP_Error;

// Allow rest api urls for non-logged in users
add_filter('sim_allowed_rest_api_urls', function($urls){
    $urls[]	= 'sim/v1/login/auth_finish';
    $urls[]	= 'sim/v1/login/auth_start';
    $urls[] = 'sim/v1/login/request_email_code';
    $urls[] = 'sim/v1/login/check_cred';
    $urls[] = 'sim/v1/login/request_login'; 
    $urls[] = 'sim/v1/login/request_pwd_reset';    
    $urls[] = 'sim/v1/login/update_password';
    $urls[] = 'sim/v1/login/request_user_account'; 

    return $urls;
});

add_action( 'rest_api_init', function () {
    // Send authentication request for storing fingerprint
	register_rest_route( 
        'sim/v1/login', 
        '/fingerprint_options', 
        array(
            'methods'               => 'POST,GET',
            'callback'              => __NAMESPACE__.'\biometricOptions',
            'permission_callback'   => '__return_true',
            'args'					=> array(
				'identifier'		=> array(
					'required'	=> true
				),
			)
		)
	);

    // Verify and store fingerprint
    register_rest_route( 
        'sim/v1/login', 
        '/store_fingerprint', 
        array(
            'methods'               => 'POST,GET',
            'callback'              => __NAMESPACE__.'\storeBiometric',
            'permission_callback'   => '__return_true',
            'args'					=> array(
				'publicKeyCredential'		=> array(
					'required'	=> true
				),
			)
		)
	);

    // Send authentication request for login
    register_rest_route( 
        'sim/v1/login', 
        '/auth_start', 
        array(
            'methods' => 'POST',
            'callback' => __NAMESPACE__.'\startAuthentication',
            'permission_callback' => '__return_true',
            'args'					=> array(
				'username'		=> array(
					'required'	=> true
				),
			)
		)
	);

    //verify fingerprint for login
    register_rest_route( 
        'sim/v1/login', 
        '/auth_finish', 
        array(
            'methods' => 'POST,GET',
            'callback' => __NAMESPACE__.'\finishAuthentication',
            'permission_callback' => '__return_true',
            'args'					=> array(
				'publicKeyCredential'		=> array(
					'required'	=> true
				),
			)
		)
	);

	// send email code
	register_rest_route( 
		'sim/v1/login', 
		'/request_email_code', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
                $username   = sanitize_text_field($_POST['username']);
                $user       = get_user_by('login', $username);
                if($user){
                    sendEmailCode($user);

                    return "E-mail send to ".$user->user_email;
                }
            },
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'username'		=> array(
					'required'	=> true
				),
			)
		)
	);

    // check credentials
	register_rest_route( 
		'sim/v1/login', 
		'/check_cred', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\checkCredentials',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'username'		=> array(
					'required'	=> true
				),
                'password'		=> array(
					'required'	=> true
				),
			)
		)
	);

    // save_2fa_settings
	register_rest_route( 
		'sim/v1/login', 
		'/save_2fa_settings', 
		array(
			'methods' 				=> 'GET,POST',
			'callback' 				=> __NAMESPACE__.'\saveTwoFaSettings',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'2fa_methods'		=> array(
					'required'	=> true,
                    'validate_callback' => function($param) {
						return is_array($param);
					}
				)
			)
		)
	);

    // remove_web_authenticator
	register_rest_route( 
		'sim/v1/login', 
		'/remove_web_authenticator', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
                $key        = sanitize_text_field($_POST['key']);
                $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository(wp_get_current_user());
                $publicKeyCredentialSourceRepository->removeCredential($key);

                return 'Succesfull removed the authenticator';
            },
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'key'		=> array(
					'required'	=> true
				),
			)
		)
	);

    // request_login
	register_rest_route( 
		'sim/v1/login', 
		'/request_login', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\userLogin',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'username'		=> array(
					'required'	=> true
				),
                'password'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // logout
	register_rest_route( 
		'sim/v1/login', 
		'/logout', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> function(){
                wp_logout();
                return 'Log out success';
            },
			'permission_callback' 	=> '__return_true',
		)
	);

    // request_pwd_reset
	register_rest_route( 
		'sim/v1/login', 
		'/request_pwd_reset', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\requestPasswordReset',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'username'		=> array(
					'required'	=> true
				),
                'password'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // update password
	register_rest_route( 
		'sim/v1/login', 
		'/update_password', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\processPasswordUpdate',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'userid'		=> array(
					'required'	=> true,
                    'validate_callback' => 'is_numeric'
				),
                'pass1'		=> array(
					'required'	=> true
				),
                'pass2'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // request_user_account
	register_rest_route( 
		'sim/v1/login', 
		'/request_user_account', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\requestUserAccount',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'first_name'		=> array(
					'required'	=> true
				),
                'last_name'		=> array(
					'required'	=> true
				),
                'email'		=> array(
					'required'	=> true
				)
			)
		)
	);
});

// Bind an authenticator
function biometricOptions(){
    try{
        $identifier = sanitize_text_field($_POST['identifier']);
        $clientId   = strval(time()).generate_random_string(24);

        $user       = wp_get_current_user();

        $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository($user);

        $server = new Server(
            get_rp_entity(),
            $publicKeyCredentialSourceRepository,
            null
        );

        // Get user ID or create one
        $webauthnKey = get_user_meta($user->ID, '2fa_webauthn_key', true);
        if(!$webauthnKey){
            $webauthnKey = hash("sha256", $user->user_login."-".$user->display_name."-".generate_random_string(10));
            update_user_meta($user->ID, '2fa_webauthn_key',$webauthnKey);
        }

        $userEntity = new PublicKeyCredentialUserEntity(
            $user->user_login,
            $webauthnKey,
            $user->display_name,
            get_profile_picture($user->ID)
        );

        $credentialSourceRepository = new PublicKeyCredentialSourceRepository($user);

        $credentialSources = $credentialSourceRepository->findAllForUserEntity($userEntity);

        // Convert the Credential Sources into Public Key Credential Descriptors for excluding
        $excludeCredentials = array_map(function (PublicKeyCredentialSource $credential) {
            return $credential->getPublicKeyCredentialDescriptor(['internal']);
        }, $credentialSources);

        // Set authenticator type
        $authenticator_type = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM;
        //$authenticator_type = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_CROSS_PLATFORM;
        //$authenticator_type = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE;

        // Set user verification
        //$user_verification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;
        $user_verification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;

        // Create authenticator selection
        $authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria(
            $authenticator_type,
            false,
            $user_verification
        );

        // Create a creation challenge
        $publicKeyCredentialCreationOptions = $server->generatePublicKeyCredentialCreationOptions(
            $userEntity,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $excludeCredentials,
            $authenticatorSelectionCriteria
        );

        store_in_transient('pkcco', $publicKeyCredentialCreationOptions);
        store_in_transient('userEntity', $userEntity);
        store_in_transient('username', $user->user_login);
        store_in_transient('identifier', $identifier);

        $publicKeyCredentialCreationOptions["clientID"] = $clientId;
        return $publicKeyCredentialCreationOptions;
    }catch(\Exception $exception){
        SIM\print_array("ajax_create: (ERROR)".$exception->getMessage());
        SIM\print_array(generate_call_trace($exception));
        return new WP_Error('Error',"Something went wrong.");
    }catch(\Error $error){
        SIM\print_array("ajax_create: (ERROR)".$error->getMessage());
        SIM\print_array(generate_call_trace($error));
        return new WP_Error('Error',"Something went wrong.");
    }
}

// Verify and save the attestation
function storeBiometric(){
    try{
        $credential_id  = sanitize_text_field($_POST["publicKeyCredential"]);

        // Check param
        if(empty($credential_id)){
            return new WP_Error('Logged in error', "No credential_id given");
        }

        $user                                   = wp_get_current_user();
        $username                               = $user->user_login;
        $userEntity                             = get_from_transient('userEntity');
        $publicKeyCredentialCreationOptions     = get_from_transient('pkcco');

        // May not get the challenge yet
        if(empty($publicKeyCredentialCreationOptions) or empty($userEntity)){
            return new WP_Error('Logged in error', "No challenge given");
        }

        if(strtolower(get_from_transient('username')) !== strtolower($username)){
            return new WP_Error('Logged in error', "Invalid username given");
        }

        // Check global unique credential ID
        $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository($user);
        if($publicKeyCredentialSourceRepository->findOneMetaByCredentialId($credential_id) !== null){
            SIM\print_array("ajax_create_response: (ERROR)Credential ID not unique, ID => \"".base64_encode($credential_id)."\" , exit");
            return new WP_Error('Logged in error', "Credential ID not unique");
        }

        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );

        $serverRequest = $creator->fromGlobals();

        $server = new Server(
            get_rp_entity(),
            $publicKeyCredentialSourceRepository,
            null
        );

        // Allow to bypass scheme verification when under localhost
        $current_domain = 'localhost';
        if($current_domain === "localhost" || $current_domain === "127.0.0.1"){
            $server->setSecuredRelyingPartyId([$current_domain]);
        }

        // Verify
        try {
            $publicKeyCredentialSource = $server->loadAndCheckAttestationResponse(
                stripslashes($_POST['publicKeyCredential']),
                $publicKeyCredentialCreationOptions,
                $serverRequest
            );

            //recreate the publicKeyCredentialSource to include the internal transport mode.
            $publicKeyCredentialSource = new PublicKeyCredentialSource(
                $publicKeyCredentialSource->getPublicKeyCredentialId(),
                'public-key',
                ['internal'],
                $publicKeyCredentialSource->getAttestationType(),
                $publicKeyCredentialSource->getTrustPath(),
                $publicKeyCredentialSource->getAaguid(),
                $publicKeyCredentialSource->getCredentialPublicKey(),
                $publicKeyCredentialSource->getUserHandle(),
                $publicKeyCredentialSource->getCounter(),
                $publicKeyCredentialSource->getOtherUI()
            );

            $publicKeyCredentialSourceRepository->saveCredentialSource($publicKeyCredentialSource);
        }catch(\Throwable $exception){
            // Failed to verify
            SIM\print_array("ajax_create_response: (ERROR)".$exception->getMessage());
            SIM\print_array(generate_call_trace($exception));
            return new \WP_Error('error', $exception->getMessage(), ['status'=> 500]);
        }

        // Store as a 2fa option
        $methods    = (array)get_user_meta($user->ID,'2fa_methods',true);
        if(!in_array('webauthn', $methods)){
            $methods[]  = 'webauthn';
            update_user_meta($user->ID, '2fa_methods', $methods);
        }
        
        // Success
        return auth_table();
    }catch(\Exception $exception){
        SIM\print_array("ajax_create_response: (ERROR)".$exception->getMessage());
        SIM\print_array(generate_call_trace($exception));
        return new WP_Error('Logged in error', "Something went wrong.");
    }catch(\Error $error){
        SIM\print_array("ajax_create_response: (ERROR)".$error->getMessage());
        SIM\print_array(generate_call_trace($error));
        return new WP_Error('Logged in error', "Something went wrong.");
    }
}

// Auth challenge
function startAuthentication(){
    try{
        $clientId       = strval(time()).generate_random_string(24);

        $user           = get_user_by('login', $_POST['username']);
        
        if(!$user) return new WP_Error('User error', "No user with user name {$_POST['username']} found.");
        
        $webauthnKey    = get_user_meta($user->ID, '2fa_webauthn_key', true);
        
        //User has no webauthn yet
        if(!$webauthnKey){
            if(!isset($_SESSION)) session_start();
            //indicate a failed webauth for content filtering
            $_SESSION['webauthn'] = 'failed';
            return;
        }

        $userEntity = new PublicKeyCredentialUserEntity(
            $user->user_login,
            $webauthnKey,
            $user->display_name,
            get_profile_picture($user->ID)
        );

        $credentialSourceRepository = new PublicKeyCredentialSourceRepository($user);

        $server = new Server(
            get_rp_entity(),
            $credentialSourceRepository,
            null
        );

        // Get the list of authenticators associated to the user
        $credentialSources = $credentialSourceRepository->findAllForUserEntity($userEntity);

        // If the user haven't bind a authenticator yet, exit
        if(count($credentialSources) === 0){
            SIM\print_array("ajax_auth: (ERROR)No authenticator found");
            SIM\print_array($userEntity);
            return new WP_Error('authenticator error',"No authenticator available");
        }

        // Convert the Credential Sources into Public Key Credential Descriptors for excluding
        $allowedCredentials = array_map(function(PublicKeyCredentialSource $credential){
            return $credential->getPublicKeyCredentialDescriptor();
        }, $credentialSources);

        // Set user verification
        $user_verification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;
        //$user_verification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;
        //$user_verification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED;

        // Create a auth challenge
        $publicKeyCredentialRequestOptions = $server->generatePublicKeyCredentialRequestOptions(
            $user_verification,
            $allowedCredentials
        );

        // Save for future use
        store_in_transient("pkcco_auth", $publicKeyCredentialRequestOptions);
        store_in_transient("user_name_auth", $user->user_login);
        store_in_transient("user_auth", $userEntity);
        store_in_transient("user", $user);

        //$publicKeyCredentialRequestOptions["clientID"] = $clientId;
        return $publicKeyCredentialRequestOptions;
    }catch(\Exception $exception){
        SIM\print_array("ajax_auth: (ERROR)".$exception->getMessage());
        SIM\print_array(generate_call_trace($exception));
        return new WP_Error('webauthn error',"Something went wrong.");
    }catch(\Error $error){
        SIM\print_array("ajax_auth: (ERROR)".$error->getMessage());
        SIM\print_array(generate_call_trace($error));
        return new WP_Error('webauthn error',"Something went wrong.");
    }
}

// Verify webauthn
function finishAuthentication(){
    $clientId = false;
    try{
        $publicKeyCredential                = sanitize_text_field(stripslashes($_POST['publicKeyCredential']));

        $publicKeyCredentialRequestOptions  = get_from_transient("pkcco_auth");
        $user_name_auth                     = get_from_transient("user_name_auth");
        $userEntity                         = get_from_transient("user_auth");
        $user                               = get_from_transient("user");

        // May not get the challenge yet
        if(empty($publicKeyCredentialRequestOptions) or empty($user_name_auth) or empty($userEntity)){
            SIM\print_array("ajax_auth_response: (ERROR)Challenge not found in transient, exit");
            return new WP_Error('webauthn',"Bad request.");
        }

        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );

        $serverRequest = $creator->fromGlobals();
        $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository($user);

        // If user entity is not saved, read from WordPress
        $webauthnKey   = get_user_meta($user->ID, '2fa_webauthn_key', true);
        if(!$webauthnKey){
            SIM\print_array("ajax_auth_response: (ERROR)User not initialized, exit");
            return new WP_Error('webauthn',"User not inited.");
        }

        $userEntity = new PublicKeyCredentialUserEntity(
            $user->user_login,
            $webauthnKey,
            $user->display_name,
            get_profile_picture($user->ID)
        );

        $server = new Server(
            get_rp_entity(),
            $publicKeyCredentialSourceRepository,
            null
        );

        // Allow to bypass scheme verification when under localhost
        $current_domain = $_SERVER['HTTP_HOST'];
        if($current_domain === "localhost" || $current_domain === "127.0.0.1"){
            $server->setSecuredRelyingPartyId([$current_domain]);
            //bypass webauthn on local host
            //$_SESSION['webauthn']   = 'success';
            //return true;
        }

        // Verify
        try {
            $server->loadAndCheckAssertionResponse(
                $publicKeyCredential,
                $publicKeyCredentialRequestOptions,
                $userEntity,
                $serverRequest
            );

            // Store last used
            $publicKeyCredentialSourceRepository->updateCredentialLastUsed($publicKeyCredential);

            if(!isset($_SESSION)) session_start();
            $_SESSION['webauthn']   = 'success';
            return "true";
        }catch(\Throwable $exception){
            // Failed to verify
            SIM\print_array("ajax_auth_response: (ERROR)".$exception->getMessage());
            SIM\print_array(generate_call_trace($exception));
            return new WP_Error('webauthn', "Something went wrong.");
        }
    }catch(\Exception $exception){
        SIM\print_array("ajax_auth_response: (ERROR)".$exception->getMessage());
        SIM\print_array(generate_call_trace($exception));
        return new WP_Error('webauthn', "Something went wrong.");
    }catch(\Error $error){
        SIM\print_array("ajax_auth_response: (ERROR)".$error->getMessage());
        SIM\print_array(generate_call_trace($error));
        return new WP_Error('webauthn', "Something went wrong.");
    }
}

// Verify username and password
function checkCredentials(){
    $username   = sanitize_text_field($_POST['username']);
    $password   = sanitize_text_field($_POST['password']);

    //get user
    $user   = get_user_by('login',$username);

    //validate credentials
    if($user and wp_check_password($password, $user->data->user_pass, $user->ID)){
        //get 2fa methods for this user
        $methods  = get_user_meta($user->ID,'2fa_methods',true);

        SIM\clean_up_nested_array($methods);
        
        //return the methods
        if(!empty($methods)){
            return array_values($methods);
        //no 2fa setup yet, login straight away
        }else{
            return userLogin();
        }
    }

    return 'false';
}

// Save 2fa options
function saveTwoFaSettings(){    
    $user_id = get_current_user_id();

    $new_methods    = $_POST['2fa_methods'];

    $old_methods    = (array)get_user_meta($user_id,'2fa_methods', true);
    
    $twofa          = new TwoFactorAuth();

    $message        = 'Nothing to update';

    //we just enabled the authenticator
    if(in_array('authenticator', $new_methods) and !in_array('authenticator', $old_methods)){
        $secret     = $_POST['auth_secret'];
        $secretkey  = $_POST['secretkey'];
        $hash       = get_user_meta($user_id,'2fa_hash',true);

        //we should have submitted a secret
        if(empty($secret)){
            return new WP_Error('No code',"You have to submit a code when setting up the authenticator");
        }

        //we should not have changed the secretkey
        if(!password_verify($secretkey,$hash)){
            return new WP_Error('Secretkey error',"Why do you try to hack me?");
        }
            
        $last2fa        = '';
        if($twofa->verifyCode($secretkey, $secret, 1, null, $last2fa)){
            //store in usermeta
            update_user_meta($user_id, '2fa_key', $secretkey);
            update_user_meta($user_id, '2fa_last', $last2fa);
        }else{
            return new WP_Error('Invalid 2fa code', "Your code is expired");
        } 

        $message    = "Succesfully enabled authenticator as a second factor";
    }

    //we just enabled email verification
    if(in_array('email', $new_methods) and !in_array('email', $old_methods)){
        $userdata   = get_userdata($user_id);

        SIM\try_send_signal(
            "Hi ".$userdata->first_name.",\n\nYou have succesfully setup e-mail verification on ".SITENAME,
            $user_id
        );

        //Send e-mail
        $emailVerfEnabled    = new EmailVerfEnabled($userdata);
	    $emailVerfEnabled->filterMail();
						
	    wp_mail( $userdata->user_email, $emailVerfEnabled->subject, $emailVerfEnabled->message);

        $message    = 'Enabled e-mail verification';
    }

    //make sure we keep webauthn enabled
    if(in_array('webauthn',$old_methods)){
        $new_methods[]  = 'webauthn';
    }

    //store all methods. We will not come here if one of the failed
    update_user_meta($user_id,'2fa_methods',$new_methods);

    return $message;
}

// Perform the login
function userLogin(){
    $username       = sanitize_text_field($_POST['username']);
    $password       = sanitize_text_field($_POST['password']);
    $remember       = sanitize_text_field($_POST['rememberme']);

    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember 
    );
    $user = wp_signon( $creds);
 
    if ( is_wp_error( $user ) ) {
        return new WP_Error('Login error', $user->get_error_message());
    }

    //Update the current logon count
    $current_login_count = get_user_meta( $user->ID, 'login_count', true );
    if(is_numeric($current_login_count)){
        $login_count = intval( $current_login_count ) + 1;
    }else{
        //it is the first time a user logs in
        $login_count = 1;
        //Save the first login data
        update_user_meta( $user->ID, 'first_login', time() );
        //Get the account validity
        $validity = get_user_meta( $user->ID, 'account_validity',true);
        //If the validity is set in months
        if(is_numeric($validity)){
            //Get the timestamp of today plus X months
            $expiry_time = strtotime('+'.$validity.' month', time());
            //Convert to date
            $expiry_date = date('Y-m-d', $expiry_time);
            //Save the date
            update_user_meta( $user->ID, 'account_validity',$expiry_date);
        }
    }
    update_user_meta( $user->ID, 'login_count', $login_count );
    
    //store login date
    update_user_meta( $user->ID, 'last_login_date',date('Y-m-d'));

    /* check if we should redirect */


    if(rtrim( $_SERVER['HTTP_REFERER'], '/' ) == rtrim(home_url(), '/')){
        if(!empty($_GET['redirect'])){
            return $_GET['redirect'];
        }

        $required_fields_status = get_user_meta($user->ID,"required_fields_status",true);
        
        //get 2fa methods for this user
        $methods  = get_user_meta($user->ID,'2fa_methods',true);

        //Redirect to account page if 2fa is not set
        if(!$methods or count($methods ) == 0){

            $twofa_page      = get_page_link(SIM\get_module_option('login', '2fa_page'));
            $twofa_page     .= SIM\get_module_option('login', '2fa_page_extras');
            return $twofa_page;
        //redirect to account page to fill in required fields
        }elseif ($required_fields_status != 'done' and !isset($_SESSION['showpage'])){
            return home_url( '/account/' );
        }else{
            if(isset($_SESSION['showpage'])) unset($_SESSION['showpage']);
            return 'Login successful';
        }
    }else{
        return 'Login successful';
    }
};

// Send password reset e-mail
function requestPasswordReset(){
    $username   = sanitize_text_field($_POST['username']);

	$user	= get_user_by('login', $username);
    if(!$user)	return new WP_Error('Username error', 'Invalid username');

	$email  = $user->user_email;
    if(!$email or strpos('.empty', $email) !== false) return new WP_Error('email error',"No valid e-mail found for user $username");

	$result = send_password_reset_message($user);

    if(is_wp_error($result)){
        return new WP_Error('pw reset error', $result->get_error_message());
    }
    
	return "Password reset link send to $email";
} 

//Save a new password
function processPasswordUpdate(){
	SIM\print_array("updating password");

	$user_id	= $_POST['userid'];

	$userdata	= get_userdata($user_id);	
	if(!$userdata)	return new WP_Error('userid error','Invalid user id given');

	if($_POST['pass1'] != $_POST['pass2'])	return new WP_Error('Password error', "Passwords do not match, try again.");
	
	wp_set_password( $_POST['pass1'], $user_id );
	return [
        'message'	=>'Changed password succesfully',
        'redirect'	=> SITEURL."/?showlogin=$userdata->user_login"
    ];
}

// Request user account.
function requestUserAccount(){
	$first_name	= $_POST['first_name'];

	$last_name	= $_POST['last_name'];

	$email	= $_POST['email'];

	$pass1	= $_POST['pass1'];
	$pass2	= $_POST['pass2'];

	if($pass1 != $pass2)	return new WP_Error('Password error', "Passwords do not match, try again.");

	$username	= get_available_username($first_name, $last_name);

	// Creating account
	//Build the user
	$userdata = array(
		'user_login'    => $username,
		'last_name'     => $last_name,
		'first_name'    => $first_name,
		'user_email'    => $email,
		'display_name'  => "$first_name $last_name",
		'user_pass'     => $pass1
	);

	//Insert the user
	$user_id = wp_insert_user( $userdata ) ;
	
	if(is_wp_error($user_id)){
		SIM\print_array($user_id->get_error_message());
		return new WP_Error('User insert error', $user_id->get_error_message());
	}

	// Disable the useraccount until approved by admin
	update_user_meta( $user_id, 'disabled', 'pending' );

	return 'Useraccount successfully created, you will receive an e-mail as soon as it gets approved.';
}