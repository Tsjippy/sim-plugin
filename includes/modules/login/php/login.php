<?php
namespace SIM;

//disable wp-login.php
add_action('init',function(){
    global $pagenow;
	if( $pagenow == 'wp-login.php'){
        //redirect to login screen
        wp_redirect(SiteURL."/?showlogin");
        exit;
	}
});

//make sure wp_login_url returns correct url
add_filter( 'login_url', function($login_url, $redirect, $force_reauth ){
    return add_query_arg(['showlogin' => '', 'redirect' => $redirect], home_url());
},10,3);

function login_modal($message='', $required=false, $username=''){
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
                            <input id="username" type="text" name="username" value="<?php echo $username;?>" autofocus required>
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

//add hidden login modal to page if not logged in
add_filter( 'the_content', function ( $content ) {
	if (!is_user_logged_in()){
        #if(!isset($_SESSION)) session_start();
        #$_SESSION['login_added']=true;
        
        if(isset($_GET['showlogin'])){
            $content .= login_modal('', true, $_GET['showlogin']);
        }else{
            $content .= login_modal();
        }
    }

    return $content;
}, 99999);

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
    $home_url   = home_url();
    if(current_url() == home_url()){
        //_GET is empty for ajax calls, use the $_SERVER['HTTP_REFERER']
        if(wp_doing_ajax()){
            parse_str($_SERVER['HTTP_REFERER'], $url);
            if(!empty($url['redirect'])){
                wp_die($url['redirect']);
            }
        }elseif(!empty($_GET['redirect'])){
            wp_die($_GET['redirect']);
        }

        $required_fields_status = get_user_meta($user->ID,"required_fields_status",true);
        
        //get 2fa methods for this user
        $methods  = get_user_meta($user->ID,'2fa_methods',true);

        //Redirect to account page if 2fa is not set
        if(!$methods or count($methods ) == 0){
            wp_die(TwoFA_page);
        //redirect to account page to fill in required fields
        }elseif ($required_fields_status != 'done' and !isset($_SESSION['showpage'])){
            wp_die(home_url( '/account/' ));
        }else{
            if(isset($_SESSION['showpage'])) unset($_SESSION['showpage']);
            wp_die('Login successful');
        }
    }else{
        wp_die('Login successful');
    }
};

add_action('wp_ajax_request_logout', function(){
    wp_logout();
    wp_die('Log out success');
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

//Redirect to frontpage for logged in users
add_action( 'template_redirect', 'SIM\homepage_redirect' );
function homepage_redirect(){
	global $Modules;
	if( is_front_page() && is_user_logged_in() ){
        $url    = get_page_link($Modules['login']['home_page']);
        if($url != current_url()){ 
            wp_redirect(add_query_arg($_GET,$url));
            exit();
        }
	}
}