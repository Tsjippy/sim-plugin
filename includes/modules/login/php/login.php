<?php
namespace SIM\LOGIN;
use SIM;

//disable wp-login.php except for logout
add_action('init',function(){
    // do not run during rest request
    if(SIM\isRestApiRequest()){
        return;
    }
    
    global $pagenow;
	if( $pagenow == 'wp-login.php' && get_option("wpstg_is_staging_site") != "true" && (!isset($_GET['action']) || $_GET['action'] != 'logout')){
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
    $GLOBALS['loginadded']  = 'true';

    $imgSvg                 = '<svg name="fingerprintpicture" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="m14.8579131 12.1577821c.5598453.5733693.8766502 1.2679907 1.1061601 2.2285828l.0994115.4560993.1790668.9464016c.0890571.4448145.15792.691858.2542597.9077542.3006717.6738019.8300644 1.3642584 1.5954609 2.0659196.3053297.2799046.3259408.7543309.0460362 1.0596606-.2799047.3053298-.7543309.3259409-1.0596607.0460362-.9073553-.831799-1.5603307-1.6834374-1.9516436-2.5603651-.1488524-.3335765-.2394876-.6504816-.3464654-1.1799063l-.2103523-1.100556-.0127257-.0601994c-.1806532-.8490686-.4039704-1.3837716-.7727894-1.7615001-.7126979-.7299144-2.3566814-.5369986-2.7966371.1586538-.561812.8883299-.7051329 2.3958974-.2590186 3.9155949.3446763 1.1741469.8020058 2.3295885 1.3723777 3.4667038.1857144.3702473.0361209.8209433-.3341263 1.0066577-.3702473.1857143-.8209433.0361209-1.0066577-.3341264-.6102482-1.2166142-1.10066504-2.4556511-1.47086108-3.7167314-.56148918-1.9127244-.37591982-3.8646952.43054349-5.1398648.95306179-1.5069691 3.74192009-1.834232 5.13762149-.404815zm-2.3656485 2.3153337c.4139848-.0137641.7607447.310678.7745098.7246628.0514444 1.547178.5547097 3.0429054 1.4440799 4.3040918l.1964651.2665975.3001983.3900338c.2526415.3282456.1913523.7991476-.1368932 1.0517891-.298405.2296741-.714708.1999014-.9777466-.0540257l-.0740425-.0828675-.3001982-.3900338c-1.203097-1.5631286-1.8854835-3.4643121-1.9510474-5.4357367-.013752-.4139852.3106901-.7607451.7246748-.7745113zm-3.70931344-5.08261589c2.11183774-1.5636159 4.87429944-1.47875277 6.73251394-.28432859.9299205.59773477 1.6341654 1.29474548 2.1023631 2.09336348.20949.357333.0896396.8168337-.2676934 1.0263237-.3573329.2094899-.8168337.0896395-1.0263236-.2676934-.3416632-.5827845-.8780106-1.1136224-1.6194141-1.5901821-1.3624962-.87578606-3.4513488-.93995585-5.02886573.2280461-1.63857821 1.213212-2.32843531 3.2912274-2.1229976 5.4818515.11505777 1.2268844.48478465 2.4672481 1.11319913 3.7238621.18526728.3704712.03512984.8209863-.33534136 1.0062536-.3704712.1852672-.82098625.0351298-1.00625353-.3353414-.7090817-1.4179209-1.13210498-2.8370839-1.26505135-4.2547182-.25031808-2.6691925.6107232-5.2628557 2.7238645-6.82743679zm9.17975604 4.30103689c.4136665-.0212821.766262.2968083.7875441.7104748.0310254.6030499.1800491 1.0962121.4414284 1.4928816.2194961.3331076.4145504.5254953.5634591.5997043l.0609294.0246341c.3964862.1198816.6207186.5384803.500837.9349665-.1198815.3964862-.5384803.6207185-.9349664.500837-.5464885-.1652362-1.0166181-.5880548-1.4427864-1.2348084-.4156416-.6307783-.642734-1.382292-.6869199-2.2411458-.0212821-.4136665.2968083-.766262.7104747-.7875441zm-7.4521454-7.58944673c.1700071.37771743.0016245.8217359-.3760929.99174299-1.8401728.82824461-3.19419315 2.00853134-4.08367294 3.54867604-1.11323872 1.9275859-1.41352476 4.1809407-1.16428903 6.0800544.05389835.4106919-.23533997.7873167-.64603188.841215-.4106919.0538984-.78731673-.2353399-.84121508-.6460318-.28880737-2.2006396.05465787-4.7780125 1.352599-7.02541273 1.05181579-1.82123138 2.64800635-3.21261558 4.76695979-4.16633675.37771744-.1700071.82173594-.00162459.99174304.37609285zm2.3436871-.7870938c1.8299089.0375013 3.6514268.87836454 5.4587031 2.48636515 1.8229115 1.6219118 2.9214344 3.82659758 3.294332 6.58019138.0555863.4104669-.2321012.7882776-.6425681.8438639-.4104668.0555863-.7882776-.2321012-.8438639-.642568-.3265838-2.4115982-1.2611637-4.287258-2.8049765-5.66084537-1.559448-1.38749856-3.0549512-2.07786436-4.4923605-2.10732195-.4141266-.00848692-.7429625-.35108288-.7344756-.76520948.0084869-.41412661.3510828-.74296255.7652095-.73447563zm-6.87149564.01440066c.27548898.30931973.24806374.78340086-.06125599 1.05888984-.26812869.23880307-.54947898.52384333-.84326049.85501357-.27633942.31150833-.55942822.71588224-.84631701 1.2125823-.20717117.35868229-.66588602.48150632-1.02456831.27433516-.35868229-.20717117-.48150633-.66588602-.27433516-1.02456831.33446878-.57907686.67452265-1.06482167 1.02311099-1.45777383.33114625-.37329027.6534615-.69983277.96773612-.97973472.30931973-.27548898.78340086-.24806374 1.05888985.06125599zm6.58555724-2.96592608c2.192677.10270867 4.0291983.70220261 5.5297283 1.86091902.3278438.25316259.3883849.72416135.1352223 1.05200519s-.7241613.38838495-1.0520052.13522237c-1.2452989-.96162583-2.7854827-1.46090045-4.6831309-1.54978949-1.9031707-.08914771-3.46161728.28408253-4.71462528.97903145-.38396359.21295561-.8148212.10639048-1.03160316-.24656626-.21678195-.35295674-.11935259-.81636141.24656627-1.03160316 1.53571998-.90334524 3.38269317-1.30166912 5.56984767-1.19921912z" fill="#bd2919"/></svg>';

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
                            <input id="username" type="text" class='wide' name="username" value="<?php echo $username;?>" autofocus autocomplete="username webauthn" style='width: calc(100% - 40px);'>
                            <?php echo $imgSvg;?>
                        </label>

                        <div class="password">
                            <label>
                                Password
                                <input id="password" type="password" class='wide' name="password" autocomplete="password webauthn">
                            </label>
                            <button type="button" class='toggle_pwd_view' data-toggle="0" title="Show password">
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
                <form id="captcha-form" class='hidden'>
                    <div id='captcha'>
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