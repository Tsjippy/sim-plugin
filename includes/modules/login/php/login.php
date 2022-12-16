<?php
namespace SIM\LOGIN;
use SIM;

//disable wp-login.php
add_action('init',function(){
    // do not run during rest request
    if(SIM\isRestApiRequest()){
        return;
    }
    
    global $pagenow;
	if( $pagenow == 'wp-login.php' && get_option("wpstg_is_staging_site") != "true"){
        //redirect to login screen
        wp_redirect(SITEURL."/?showlogin");
        exit;
	}
});

//make sure wp_login_url returns correct url
add_filter( 'login_url', function($loginUrl, $redirect ){
    return add_query_arg(['showlogin' => '', 'redirect' => $redirect], home_url());
}, 10, 2);

/**
 * Creates a login form modal
 */
function loginModal($message='', $required=false, $username=''){
    // Login modal already added
    if(isset($GLOBALS['loginadded'])){
        return;
    }
    $GLOBALS['loginadded']   = 'true';

    ?>
    <div id="login_modal" class="modal <?php if(!$required){echo 'hidden';}?>">
		<div class="modal-content">
            <?php
            if(!$required){
                echo '<span class="close">×</span>';
            }
            ?>
            <div id='login_wrapper'>
                <h3>
                    Login form
                </h3>
                <p class="message"><?php
                    if(!empty($message)){echo $message;}
                ?></p>
                <form id="loginform" action="login" method="post">
                    <input type='hidden' name='action' value='request_login'>

                    <div id='usercred_wrapper'>
                        <label>
                            Username
                            <input id="username" type="text" class='wide' name="username" value="<?php echo $username;?>" autofocus required>
                        </label>
                        
                        <div class="password">
                            <label>
                                Password
                                <input id="password" type="password" class='wide' name="password" required>
                            </label>
                            <button type="button" id='toggle_pwd_view' data-toggle="0" title="Show password">
                                <img src="<?php echo PICTURESURL.'/invisible.png';?>" loading='lazy' alt='togglepasword'>
                            </button>
                        </div>
                        <div id='check_cred_wrapper'>
                            <label id='rememberme_label'>
                                <input name="rememberme" type="checkbox" id="rememberme" value="forever" checked>
                                Remember Me
                            </label>
                            <button type='button' id='check_cred' class='button'>Verify credentials</button>
                            <img class='loadergif hidden' src='<?php echo LOADERIMAGEURL;?>' loading='lazy' alt='loader'>
                        </div>
                    </div>

                    <div id='logging_in_wrapper' class='hidden'>
                        <h4 class='status_message'>Logging in...</h4>
                        <img class='loadergif center' src='<?php echo LOADERIMAGEURL; ?>' loading='lazy' alt='loader'>
                    </div>

                    <div id='webauthn_wrapper' class='authenticator_wrapper hidden'>
                        <h4 class='status_message'>Please authenticate...</h4>
                        <img class='loadergif center' src='<?php echo LOADERIMAGEURL; ?>' loading='lazy' alt='loader'>
                    </div>

                    <div id='authenticator_wrapper' class='authenticator_wrapper hidden'>
                        <label>
                            Please enter the two-factor authentication (2FA) verification code below to login.
                            <input type="tel" name="authcode"  class='wide' size="20" pattern="[0-9]*" required>
                        </label>
                    </div>

                    <div id='email_wrapper' class='authenticator_wrapper hidden'>
                        <label>
                            Please enter the code sent to your e-mail below to login.
                            <input type="tel" name="email_code"  class='wide' size="20" pattern="[0-9]*" required>
                        </label>
                    </div>

                    <div id='submit_login_wrapper' class='hidden'>
                        <div class='submit_wrapper'>
		                    <button type='button' class='button' id='login_button' disabled>Login</button>
		                    <img class='loadergif hidden' src='<?php echo LOADERIMAGEURL;?>' loading='lazy' alt='loader'>
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
}

//add hidden login modal to page if not logged in
add_filter( 'wp_footer', function () {
    if(!is_main_query()){
        return;
    }
    
	if (!is_user_logged_in()){
        if(isset($_GET['showlogin'])){
            loginModal('', true, $_GET['showlogin']);
        }else{
            loginModal();
        }
    }
}, 99999);

//add login and logout buttons to main menu
add_filter('wp_nav_menu_items', function ($items, $args) {
    $loginMenus     = SIM\getModuleOption(MODULE_SLUG, 'loginmenu', false);
    $logoutMenus    = SIM\getModuleOption(MODULE_SLUG, 'logoutmenu', false);

    if(
        !in_array($args->menu->term_id, $loginMenus)   &&  // Do not add when not in the list
        !in_array($args->menu->term_id, $logoutMenus)  &&
        !empty(SIM\getModuleOption(MODULE_SLUG, 'menu', false))
    ){
        return $items;
    }

    // We should add a logout menu item
    if(is_user_logged_in() && in_array($args->menu->term_id, $logoutMenus)){
        if($args->menu->slug != 'footer' && has_action('generate_menu_bar_items' )){
            add_action('generate_menu_bar_items', function(){
                echo "<span class='menu-bar-item logout hidden'><a href='#logout' class='logout button'>Log out</a></li>";
            });
        }else{
            $items .= "<li class='menu-item logout hidden'><a href='#logout' class='logout'>Log out</a></li>";
        }
    }

    // We should add a login menu item
    if(!is_user_logged_in() && in_array($args->menu->term_id, $loginMenus)){
        if($args->menu->slug != 'footer' && has_action('generate_menu_bar_items' )){
            add_action('generate_menu_bar_items', function(){
                echo "<span class='menu-bar-item login hidden'><a href='#login' class='login button'>Log in</a></li>";
            });
        }else{
            $items .= "<li class='menu-item login hidden'><a href='#login' class='login'>Log in</a></li>";
        }
    }
  return $items;
}, 10, 2);

// Disable administration email verification
add_filter( 'admin_email_check_interval', '__return_false' );