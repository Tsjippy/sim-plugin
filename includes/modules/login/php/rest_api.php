<?php
namespace SIM\LOGIN;
use SIM;
use RobThree\Auth\TwoFactorAuth;
use Webauthn\Server;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\AuthenticatorSelectionCriteria;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use WP_Error;

// Allow rest api urls for non-logged in users
add_filter('sim_allowed_rest_api_urls', function($urls){
    $urls[]	= RESTAPIPREFIX.'/login/auth_finish';
    $urls[]	= RESTAPIPREFIX.'/login/auth_start';
    $urls[] = RESTAPIPREFIX.'/login/request_email_code';
    $urls[] = RESTAPIPREFIX.'/login/check_cred';
    $urls[] = RESTAPIPREFIX.'/login/request_login';
    $urls[] = RESTAPIPREFIX.'/login/request_pwd_reset';
    $urls[] = RESTAPIPREFIX.'/login/update_password';
    $urls[] = RESTAPIPREFIX.'/login/request_user_account';

    return $urls;
});

add_action( 'rest_api_init', function () {
    // Send authentication request for storing fingerprint
	register_rest_route(
        RESTAPIPREFIX.'/login',
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
        RESTAPIPREFIX.'/login',
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
        RESTAPIPREFIX.'/login',
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
        RESTAPIPREFIX.'/login',
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
		RESTAPIPREFIX.'/login',
		'/request_email_code',
		array(
			'methods' 				=> 'POST, GET',
			'callback' 				=>  __NAMESPACE__.'\requestEmailCode',
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
		RESTAPIPREFIX.'/login',
		'/check_cred',
		array(
			'methods' 				=> 'POST,GET',
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
		RESTAPIPREFIX.'/login',
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
		RESTAPIPREFIX.'/login',
		'/remove_web_authenticator',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\removeWebAuthenticator',
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
		RESTAPIPREFIX.'/login',
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
		RESTAPIPREFIX.'/login',
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
		RESTAPIPREFIX.'/login',
		'/request_pwd_reset',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\requestPasswordReset',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'username'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // update password
	register_rest_route(
		RESTAPIPREFIX.'/login',
		'/update_password',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\processPasswordUpdate',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
                'userid'		=> array(
					'required'	=> true,
                    'validate_callback' => function($userId){
						return is_numeric($userId);
					}
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
		RESTAPIPREFIX.'/login',
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

function requestEmailCode(){
    $username   = sanitize_text_field($_REQUEST['username']);
    if(is_numeric($username)){
        $user       = get_user_by('id', $username);
    }else{
        $user       = get_user_by('login', $username);
    }

    if($user){
        $result = sendEmailCode($user);

        if($result){
            return "E-mail sent to ".$user->user_email;
        }
        return new WP_Error('login', 'Sending e-mail failed');
    }else{
        return new WP_Error('login', 'Invalid username given');
    }
}

function removeWebAuthenticator(){
    $key        = sanitize_text_field($_POST['key']);
    $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository(wp_get_current_user());
    $publicKeyCredentialSourceRepository->removeCredential($key);

    return 'Succesfull removed the authenticator';
}

// Bind an authenticator
function biometricOptions(){
    try{
        $identifier = sanitize_text_field($_POST['identifier']);

        $user       = wp_get_current_user();

        $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository($user);

        $server = new Server(
            getRpEntity(),
            $publicKeyCredentialSourceRepository,
            null
        );

        // Get user ID or create one
        $webauthnKey = get_user_meta($user->ID, '2fa_webauthn_key', true);
        if(!$webauthnKey){
            $webauthnKey = hash("sha256", $user->user_login."-".$user->display_name."-".generateRandomString(10));
            update_user_meta($user->ID, '2fa_webauthn_key',$webauthnKey);
        }

        $userEntity = new PublicKeyCredentialUserEntity(
            $user->user_login,
            $webauthnKey,
            $user->display_name,
            getProfilePicture($user->ID)
        );

        $credentialSourceRepository = new PublicKeyCredentialSourceRepository($user);

        $credentialSources = $credentialSourceRepository->findAllForUserEntity($userEntity);

        // Convert the Credential Sources into Public Key Credential Descriptors for excluding
        $excludeCredentials = array_map(function (PublicKeyCredentialSource $credential) {
            return $credential->getPublicKeyCredentialDescriptor(['internal']);
        }, $credentialSources);

        // Set authenticator type
        $authenticatorType = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM;
        //$authenticatorType = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_CROSS_PLATFORM;
        //$authenticatorType = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE;

        // Set user verification
        //$userVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;
        $userVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;

        // Create authenticator selection
        $authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria(
            $authenticatorType,
            false,
            $userVerification
        );

        // Create a creation challenge
        $publicKeyCredentialCreationOptions = $server->generatePublicKeyCredentialCreationOptions(
            $userEntity,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $excludeCredentials,
            $authenticatorSelectionCriteria
        );

        storeInTransient('pkcco', $publicKeyCredentialCreationOptions);
        storeInTransient('userEntity', $userEntity);
        storeInTransient('username', $user->user_login);
        storeInTransient('identifier', $identifier);

        return $publicKeyCredentialCreationOptions;
    }catch(\Exception $exception){
        SIM\printArray("ajax_create: (ERROR)".$exception->getMessage());
        SIM\printArray(generateCallTrace($exception));
        return new WP_Error('Error',"Something went wrong.");
    }catch(\Error $error){
        SIM\printArray("ajax_create: (ERROR)".$error->getMessage());
        SIM\printArray(generateCallTrace($error));
        return new WP_Error('Error',"Something went wrong.");
    }
}

// Verify and save the attestation
function storeBiometric(){
    try{
        $credentialId  = sanitize_text_field($_POST["publicKeyCredential"]);

        // Check param
        if(empty($credentialId)){
            return new WP_Error('Logged in error', "No credential id given");
        }

        $user                                   = wp_get_current_user();
        $username                               = $user->user_login;
        $userEntity                             = getFromTransient('userEntity');
        $publicKeyCredentialCreationOptions     = getFromTransient('pkcco');

        // May not get the challenge yet
        if(empty($publicKeyCredentialCreationOptions) || empty($userEntity)){
            return new WP_Error('Logged in error', "No challenge given");
        }

        if(strtolower(getFromTransient('username')) !== strtolower($username)){
            return new WP_Error('Logged in error', "Invalid username given");
        }

        // Check global unique credential ID
        $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository($user);
        if($publicKeyCredentialSourceRepository->findOneMetaByCredentialId($credentialId) !== null){
            SIM\printArray("ajax_create_response: (ERROR)Credential ID not unique, ID => \"".base64_encode($credentialId)."\" , exit");
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
            getRpEntity(),
            $publicKeyCredentialSourceRepository,
            null
        );

        // Allow to bypass scheme verification when under localhost
        $currentDomain = 'localhost';
        if($currentDomain === "localhost" || $currentDomain === "127.0.0.1"){
            $server->setSecuredRelyingPartyId([$currentDomain]);
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
            SIM\printArray("ajax_create_response: (ERROR)".$exception->getMessage());
            SIM\printArray(generateCallTrace($exception));
            return new \WP_Error('error', $exception->getMessage(), ['status'=> 500]);
        }

        // Store as a 2fa option
        $methods    = (array)get_user_meta($user->ID,'2fa_methods',true);
        if(!in_array('webauthn', $methods)){
            $methods[]  = 'webauthn';
            update_user_meta($user->ID, '2fa_methods', $methods);
        }

        // Success
        return authTable();
    }catch(\Exception $exception){
        SIM\printArray("ajax_create_response: (ERROR)".$exception->getMessage());
        SIM\printArray(generateCallTrace($exception));
        return new WP_Error('Logged in error', "Something went wrong.");
    }catch(\Error $error){
        SIM\printArray("ajax_create_response: (ERROR)".$error->getMessage());
        SIM\printArray(generateCallTrace($error));
        return new WP_Error('Logged in error', "Something went wrong.");
    }
}

// Auth challenge
function startAuthentication(){
    try{
        $user           = get_user_by('login', $_POST['username']);

        if(!$user){
            return new WP_Error('User error', "No user with user name {$_POST['username']} found.");
        }

        $webauthnKey    = get_user_meta($user->ID, '2fa_webauthn_key', true);

        //User has no webauthn yet
        if(!$webauthnKey){
            if(!isset($_SESSION)){
                session_start();
            }
            //indicate a failed webauth for content filtering
            $_SESSION['webauthn'] = 'failed';
            return;
        }

        $userEntity = new PublicKeyCredentialUserEntity(
            $user->user_login,
            $webauthnKey,
            $user->display_name,
            getProfilePicture($user->ID)
        );

        $credentialSourceRepository = new PublicKeyCredentialSourceRepository($user);

        $server = new Server(
            getRpEntity(),
            $credentialSourceRepository,
            null
        );

        // Get the list of authenticators associated to the user
        $credentialSources = $credentialSourceRepository->findAllForUserEntity($userEntity);

        // If the user haven't bind a authenticator yet, exit
        if(count($credentialSources) === 0){
            SIM\printArray("ajax_auth: (ERROR)No authenticator found");
            SIM\printArray($userEntity);
            return new WP_Error('authenticator error',"No authenticator available");
        }

        // Convert the Credential Sources into Public Key Credential Descriptors for excluding
        $allowedCredentials = array_map(function(PublicKeyCredentialSource $credential){
            return $credential->getPublicKeyCredentialDescriptor();
        }, $credentialSources);

        // Set user verification
        $userVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;
        //$userVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED;
        //$userVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED;

        // Create a auth challenge
        $publicKeyCredentialRequestOptions = $server->generatePublicKeyCredentialRequestOptions(
            $userVerification,
            $allowedCredentials
        );

        // Save for future use
        storeInTransient("pkcco_auth", $publicKeyCredentialRequestOptions);
        storeInTransient("user_name_auth", $user->user_login);
        storeInTransient("user_auth", $userEntity);
        storeInTransient("user", $user);

        return $publicKeyCredentialRequestOptions;
    }catch(\Exception $exception){
        SIM\printArray("ajax_auth: (ERROR)".$exception->getMessage());
        SIM\printArray(generateCallTrace($exception));
        return new WP_Error('webauthn error',"Something went wrong.");
    }catch(\Error $error){
        SIM\printArray("ajax_auth: (ERROR)".$error->getMessage());
        SIM\printArray(generateCallTrace($error));
        return new WP_Error('webauthn error',"Something went wrong.");
    }
}

// Verify webauthn
function finishAuthentication(){
    try{
        $publicKeyCredential                = sanitize_text_field(stripslashes($_POST['publicKeyCredential']));

        $publicKeyCredentialRequestOptions  = getFromTransient("pkcco_auth");
        $userNameAuth                       = getFromTransient("user_name_auth");
        $userEntity                         = getFromTransient("user_auth");
        $user                               = getFromTransient("user");

        // May not get the challenge yet
        if(empty($publicKeyCredentialRequestOptions) || empty($userNameAuth) || empty($userEntity)){
            SIM\printArray("ajax_auth_response: (ERROR)Challenge not found in transient, exit");
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
            SIM\printArray("ajax_auth_response: (ERROR)User not initialized, exit");
            return new WP_Error('webauthn',"User not inited.");
        }

        $userEntity = new PublicKeyCredentialUserEntity(
            $user->user_login,
            $webauthnKey,
            $user->display_name,
            getProfilePicture($user->ID)
        );

        $server = new Server(
            getRpEntity(),
            $publicKeyCredentialSourceRepository,
            null
        );

        // Allow to bypass scheme verification when under localhost
        $currentDomain = $_SERVER['HTTP_HOST'];
        if($currentDomain === "localhost" || $currentDomain === "127.0.0.1"){
            $server->setSecuredRelyingPartyId([$currentDomain]);
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

            if(!isset($_SESSION)){
                session_start();
            }
            $_SESSION['webauthn']   = 'success';
            return "true";
        }catch(\Throwable $exception){
            // Failed to verify
            SIM\printArray("ajax_auth_response: (ERROR)".$exception->getMessage());
            SIM\printArray(generateCallTrace($exception));
            return new WP_Error('webauthn', "Something went wrong.");
        }
    }catch(\Exception $exception){
        SIM\printArray("ajax_auth_response: (ERROR)".$exception->getMessage());
        SIM\printArray(generateCallTrace($exception));
        return new WP_Error('webauthn', "Something went wrong.");
    }catch(\Error $error){
        SIM\printArray("ajax_auth_response: (ERROR)".$error->getMessage());
        SIM\printArray(generateCallTrace($error));
        return new WP_Error('webauthn', "Something went wrong.");
    }
}

add_filter( 'check_password', function($check, $password, $storedHash, $userId ){
    if(empty($check) && empty($storedHash)){
        $user           = get_user_by('id', $userId);
        $storedHash    = $user->data->user_pass;

        global $wp_hasher;

        if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			// By default, use the portable hash from phpass.
			$wp_hasher = new \PasswordHash( 8, true );
		}

		if ( strlen( $password ) > 4096 ) {
			return false;
		}

		$hash = $wp_hasher->crypt_private($password, $storedHash);

		if ($hash[0] === '*')
			$hash = crypt($password, $stored_hash);

        //SIM\printArray(wp_hash_password( $password ));
        //SIM\printArray($storedHash);
        //SIM\printArray($hash);

        $check  = $hash === $storedHash;
        if($check){
            wp_set_password( $password, $userId );
            //SIM\printArray($userId);
        }
    }

    return $check;
}, 10, 4);


// Verify username and password
function checkCredentials(){
    $username   = sanitize_text_field($_POST['username']);
    $password   = sanitize_text_field($_POST['password']);

    $user   = get_user_by('login', $username);

    //validate credentials
    if($user && wp_check_password($password, $user->data->user_pass, $user->ID)){
        //get 2fa methods for this user
        $methods  = get_user_meta($user->ID,'2fa_methods',true);

        SIM\cleanUpNestedArray($methods);

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
    $userId = get_current_user_id();

    $newMethods    = $_POST['2fa_methods'];

    $oldMethods    = (array)get_user_meta($userId,'2fa_methods', true);

    $twofa          = new TwoFactorAuth();

    $message        = 'Nothing to update';

    //we just enabled the authenticator
    if(in_array('authenticator', $newMethods) && !in_array('authenticator', $oldMethods)){
        $secret     = $_POST['auth_secret'];
        $secretkey  = $_POST['secretkey'];
        $hash       = get_user_meta($userId,'2fa_hash',true);

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
            update_user_meta($userId, '2fa_key', $secretkey);
            update_user_meta($userId, '2fa_last', $last2fa);
        }else{
            return new WP_Error('Invalid 2fa code', "Your code is expired");
        }

        $message    = "Succesfully enabled authenticator as a second factor";
    }

    //we just enabled email verification
    if(in_array('email', $newMethods) && !in_array('email', $oldMethods)){
        // verify the code
        if(verifyEmailCode()){
            $userdata   = get_userdata($userId);

            SIM\trySendSignal(
                "Hi ".$userdata->first_name.",\n\nYou have succesfully setup e-mail verification on ".SITENAME,
                $userId,
                true
            );

            //Send e-mail
            $emailVerfEnabled    = new EmailVerfEnabled($userdata);
            $emailVerfEnabled->filterMail();

            wp_mail( $userdata->user_email, $emailVerfEnabled->subject, $emailVerfEnabled->message);

            $message    = 'Enabled e-mail verification';
        }else{
            return new WP_Error('login', 'Invalid e-mail code');
        }
    }

    //make sure we keep webauthn enabled
    if(in_array('webauthn',$oldMethods)){
        $newMethods[]  = 'webauthn';
    }

    //store all methods. We will not come here if one of the failed
    update_user_meta($userId, '2fa_methods', $newMethods);

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

    $accountPageId  = SIM\getModuleOption('usermanagement', 'account_page');

    // GEt mandatory or recommended fields
    if(function_exists('SIM\FORMS\getAllEmptyRequiredElements') && is_numeric($accountPageId)){
        $fieldList   = SIM\FORMS\getAllEmptyRequiredElements($user->ID, 'all');
    }

    /* check if we should redirect */
    $urlComp    = parse_url($_SERVER['HTTP_REFERER']);
    $redirect   = '';
    if(isset($urlComp['query'])){
        parse_str($urlComp['query'], $urlParam);
        if(isset($urlParam['redirect'])){
            $redirect   = $urlParam['redirect'];
        }
    }elseif(!empty($_GET['redirect'])){
        $redirect   = $_GET['redirect'];
    }

    if(!empty($redirect)){
        return $redirect;
    }elseif(rtrim( $_SERVER['HTTP_REFERER'], '/' ) == rtrim(home_url(), '/')){
        //get 2fa methods for this user
        $methods  = get_user_meta($user->ID,'2fa_methods',true);

        //Redirect to account page if 2fa is not set
        if(!$methods || empty($methods)){
            $url		= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, '2fa_page');
            if($url){
                return $url;
            }
        }

        //redirect to account page to fill in required fields
        if (!isset($_SESSION['showpage']) && !empty($fieldList) && is_numeric($accountPageId)){
            return get_permalink($accountPageId);
        }else{
            if(isset($_SESSION['showpage'])){
                unset($_SESSION['showpage']);
            }
            return 'Login successful';
        }
    }else{
        return 'Login successful';
    }
}

// Send password reset e-mail
function requestPasswordReset(){
    $username   = sanitize_text_field($_POST['username']);

	$user	= get_user_by('login', $username);
    if(!$user){
        return new WP_Error('Username error', 'Invalid username');
    }

	$email  = $user->user_email;
    if(!$email || strpos('.empty', $email) !== false){
        return new WP_Error('email error',"No valid e-mail found for user $username");
    }

	$result = sendPasswordResetMessage($user);

    if(is_wp_error($result)){
        return new WP_Error('pw reset error', $result->get_error_message());
    }

	return "Password reset link send to $email";
}

//Save a new password
function processPasswordUpdate(){
	$userId	= $_POST['userid'];

	$user   = get_userdata($userId);
	if(!$user){
        return new WP_Error('userid error','Invalid user id given');
    }

	if($_POST['pass1'] != $_POST['pass2']){
        return new WP_Error('Password error', "Passwords do not match, try again.");
    }

    add_filter('application_password_is_api_request', '__return_false');
	
	wp_set_password( $_POST['pass1'], $userId );

    $message    = 'Changed password succesfully';
    if(is_user_logged_in()){
        if(get_current_user_id() == $userId){
            $message .= ', please login again';
        }else{
            $message .= " for $user->display_name";
        }
    }
	return [
        'message'	=> $message,
        'redirect'	=> SITEURL."/?showlogin=$user->user_login"
    ];
}

// Request user account.
function requestUserAccount(){
	$first_name	= $_POST['first_name'];
	$last_name	= $_POST['last_name'];
	$email	    = $_POST['email'];
	$pass1	    = $_POST['pass1'];
	$pass2	    = $_POST['pass2'];

	if($pass1 != $pass2){
        return new WP_Error('Password error', "Passwords do not match, try again.");
    }

	$username	= SIM\getAvailableUsername($first_name, $last_name);

	// Creating account
	//Build the user
	$userdata = array(
		'user_login'    => $username,
		'last_name'     => $last_name,
		'first_name'    => $first_name,
		'user_email'    => $email,
		'display_name'  => "$first_name $last_name",
	);

    if(!empty($pass1)){
        $userdata['user_pass']     = $pass1;
    }

	//Insert the user
	$userId = wp_insert_user( $userdata ) ;
	
	if(is_wp_error($userId)){
		SIM\printArray($userId->get_error_message());
		return new WP_Error('User insert error', $userId->get_error_message());
	}

	// Disable the useraccount until approved by admin
	update_user_meta( $userId, 'disabled', 'pending' );

	return 'Useraccount successfully created, you will receive an e-mail as soon as it gets approved.';
}