<?php
namespace SIM;

//disable wp-login.php
add_action('init',function(){
    global $pagenow;
	if( $pagenow == 'wp-login.php'){
		wp_die('Sorry we do not use this page.<br> <a href="'.SiteURL.'">Go back</a>');
	}
});

function login_modal($message='', $required=false){
    global $LoaderImageURL;

    ob_start();

    ?>
    <div id="login_modal" class="modal <?php if(!$required) echo 'hidden';?>">
		<div class="modal-content">
            <?php
            if($required == false){
                echo '<span class="close">×</span>';
            }
            ?>
            <div id='login_wrapper'>
                <h3>
                    Login form
                </h3>
                <p class="message"><?php
                    if(!empty($message))echo $message;
                ?></p>
                <form id="loginform" action="login" method="post">
                    <input type='hidden' name='action' value='request_login'>

                    <div id='usercred_wrapper'>
                        <label>
                            Username
                            <input id="username" type="text" name="username" autofocus required>
                        </label>
                        
                        <div class="password">
                            <label>
                                Password
                                <input id="password" type="password" name="password" required>
                            </label>
                            <button type="button" id='toggle_pwd_view' data-toggle="0" title="Show password">
                                <img src="<?php echo PicturesUrl.'/invisible.png';?>">
                            </button>
                        </div>
                        <div id='check_cred_wrapper'>
                            <label id='rememberme_label'>
                                <input name="rememberme" type="checkbox" id="rememberme" value="forever" checked>
                                Remember Me
                            </label>
                            <button type='button' id='check_cred' class='button'>Verify credentials</button>
                            <img class='loadergif hidden' src='<?php echo $LoaderImageURL;?>'>
                        </div>
                    </div>

                    <div id='logging_in_wrapper' class='hidden'>
                        <h4 class='status_message'>Logging in...</h4>
                        <img class='loadergif center' src='<?php echo LoaderImageURL; ?>'>
                    </div>

                    <div id='webauthn_wrapper' class='authenticator_wrapper hidden'>
                        <h4 class='status_message'>Please authenticate...</h4>
                        <img class='loadergif center' src='<?php echo LoaderImageURL; ?>'>
                    </div>

                    <div id='authenticator_wrapper' class='authenticator_wrapper hidden'>
                        <label>
                            Please enter the two-factor authentication (2FA) verification code below to login. 
                            <input type="tel" name="authcode" size="20" pattern="[0-9]*" required>
                        </label>
                    </div>

                    <div id='email_wrapper' class='authenticator_wrapper hidden'>
                        <label>
                            Please enter the code send to your e-mail below to login. 
                            <input type="tel" name="email_code" size="20" pattern="[0-9]*" required>
                        </label>
                    </div>

                    <div id='submit_login_wrapper' class='hidden'>                       
                        <div class='submit_wrapper'>
		                    <button type='button' class='button' id='login_button' disabled>Login</button>
		                    <img class='loadergif hidden' src='<?php echo $LoaderImageURL;?>'>
	                    </div>
                    </div>
                </form>
                <form id="login_nav">
                    <div id='captcha' class='hidden'>
                        <p>
                            First complete the captcha before sending the request.
                        </p>
                        <?php echo do_shortcode('[hcaptcha auto="true"]');?>
                    </div>
                    <a href='#pwd_reset' id='lost_pwd_link'>Request password reset</a>
                </form>
            </div>
		</div>
	</div>
    <?php

    return ob_get_clean();
}

//add login modal to page if not logged in
add_filter( 'the_content', function ( $content ) {
	if (!is_user_logged_in()){ 
        $content .= login_modal();
    }

    return $content;
}, 99);

add_action('wp_ajax_nopriv_request_login', 'SIM\user_login');
function user_login(){
    $username       = sanitize_text_field($_POST['username']);
    $password       = sanitize_text_field($_POST['password']);
    $remember       = sanitize_text_field($_POST['rememberme']);

    if(empty($username) or empty($password)) wp_die('Invalid username or password', 500);
    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember 
    );
    $user = wp_signon( $creds);
 
    if ( is_wp_error( $user ) ) {
        wp_die($user->get_error_message(),500);
    }

    //Update the currentlogon count
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

    //check if we should redirect
    $required_fields_status = get_user_meta($user->ID,"required_fields_status",true);
    //get 2fa methods for this user
    $methods  = get_user_meta($user->ID,'2fa_methods',true);
    //Redirect to account page if there are some required fields to be filled in
    if(!$methods or count($methods ) == 0){
        wp_die(TwoFA_page);
    }elseif (!$required_fields_status){
        wp_die(home_url( '/account/' ));
    }else{
        wp_die('Login successful');
    }
};

add_action('wp_ajax_request_logout', function(){
    wp_logout();
    wp_die('Log out success');
});

add_action('wp_ajax_nopriv_request_pwd_reset', function(){
    $username   = sanitize_text_field($_POST['username']);

    if(empty($username))   wp_die('No username given', 500);

    if(empty($_POST['h-captcha-response'])) wp_die('No captcha given', 500);
    //$result = hcaptcha_request_verify($_POST['h-captcha-response']);
    //if($result != 'success') wp_die('Captcha failed, try again', 500);

    $result = retrieve_password($username);

    if(is_wp_error($result)){
        wp_die( $result->get_error_message(), 500);
    }

    $email  = get_user_by('login', $username)->user_email;
    if(!$email or strpos('.empty', $email) !== false) wp_die("No valid e-mail found for user $username");
    if($result){
        wp_die("Password reset link send to $email");
    }else{
        wp_die($result->get_error_message(), 500);
    }
}); 

//add login and logout buttons to main menu
add_filter('wp_nav_menu_items', function ($items, $args) {
    if( $args->container_id == 'primary-menu' ){
        if(is_user_logged_in()){
            $items .= '<li id="logout" class="menu-item logout"><a href="#logout">Log out</a></li>';
        }else{
            $items .= '<li id="login" class="menu-item login"><a href="#login">Login</a></li>';
        }
    }
  return $items;
}, 10, 2);