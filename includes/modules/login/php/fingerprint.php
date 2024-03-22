<?php
namespace SIM\LOGIN;
use SIM;

use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use WP_Error;

//https://webauthn-doc.spomky-labs.com/

//check for interface
if(!interface_exists('Webauthn\PublicKeyCredentialSourceRepository')){
    return new \WP_Error('biometric', "Webauthn\PublicKeyCredentialSourceRepository interface does not exist. Please run 'composer require web-auth/webauthn-lib'");
}

function getProfilePicture($userId){
    $attachmentId  = get_user_meta($userId,'profile_picture',true);
    $image          = null;

    if(is_numeric($attachmentId)){
        $path   = get_attached_file($attachmentId);
        if($path){
            $type       = pathinfo($path, PATHINFO_EXTENSION);
            $contents   = file_get_contents(get_attached_file($attachmentId));
            if(!empty($contents)){
                $image = "data:image/$type;base64,".base64_encode($contents);
            }
        }
    }

    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAMAAAC6V+0/AAAAwFBMVEXm7NK41k3w8fDv7+q01Tyy0zqv0DeqyjOszDWnxjClxC6iwCu11z6y1DvA2WbY4rCAmSXO3JZDTxOiwC3q7tyryzTs7uSqyi6tzTCmxSukwi9aaxkWGga+3FLv8Ozh6MTT36MrMwywyVBziSC01TbT5ZW9z3Xi6Mq2y2Xu8Oioxy7f572qxzvI33Tb6KvR35ilwTmvykiwzzvV36/G2IPw8O++02+btyepyDKvzzifvSmw0TmtzTbw8PAAAADx8fEC59dUAAAA50lEQVQYV13RaXPCIBAG4FiVqlhyX5o23vfVqUq6mvD//1XZJY5T9xPzzLuwgKXKslQvZSG+6UXgCnFePtBE7e/ivXP/nRvUUl7UqNclvO3rpLqofPDAD8xiu2pOntjamqRy/RqZxs81oeVzwpCwfyA8A+8mLKFku9XfI0YnSKXnSYZ7ahSII+AwrqoMmEFKriAeVrqGM4O4Z+ADZIhjg3R6LtMpWuW0ERs5zunKVHdnnnMLNQqaUS0kyKkjE1aE98b8y9x9JYHH8aZXFMKO6JFMEvhucj3Wj0kY2D92HlHbE/9Vk77mD6srRZqmVEAZAAAAAElFTkSuQmCC';

    return $image;
}

/**
 * Create random strings for user ID
 *
 * @param   int $length
 *
 * @return  string  the string
 */
function generateRandomString($length = 10){
    // Use cryptographically secure pseudo-random generator in PHP 7+
    if(function_exists('random_bytes')){
        $bytes = random_bytes(round($length/2));
        return bin2hex($bytes);
    }else{
        // Not supported, use normal random generator instead
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
        $randomString = '';
        for($i = 0; $i < $length; $i++){
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
}

/**
 * Format trackback
 */
function generateCallTrace($exception = false){
    $e = $exception;
    if($exception === false){
        $e = new \Exception();
    }
    $trace = explode("\n", $e->getTraceAsString());
    $trace = array_reverse($trace);
    array_shift($trace);
    array_pop($trace);
    $length = count($trace);
    $result = array();

    for($i = 0; $i < $length; $i++){
        $result[] = ($i + 1).')'.substr($trace[$i], strpos($trace[$i], ' '));
    }

    return "Traceback:\n                              ".implode("\n                              ", $result);
}

/**
 * Creates a new rp entity
 *
 * @return  object the rprntity
 */
function getRpEntity(){
    $logo       = null;
    $path       = get_attached_file(get_option( 'site_icon' ));
    $type       = pathinfo($path, PATHINFO_EXTENSION);
    if(!empty($path)){
        $data = file_get_contents($path);
        if(!empty($contents)){
            $logo   = "data:image/$type;base64,".base64_encode($data);
        }
    }

    return new PublicKeyCredentialRpEntity(
        get_bloginfo('name').' Webauthn Server', // The application name
        $_SERVER['SERVER_NAME'],       // The application ID = the domain
        //$logo
        'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAMAAAC6V+0/AAAAwFBMVEXm7NK41k3w8fDv7+q01Tyy0zqv0DeqyjOszDWnxjClxC6iwCu11z6y1DvA2WbY4rCAmSXO3JZDTxOiwC3q7tyryzTs7uSqyi6tzTCmxSukwi9aaxkWGga+3FLv8Ozh6MTT36MrMwywyVBziSC01TbT5ZW9z3Xi6Mq2y2Xu8Oioxy7f572qxzvI33Tb6KvR35ilwTmvykiwzzvV36/G2IPw8O++02+btyepyDKvzzifvSmw0TmtzTbw8PAAAADx8fEC59dUAAAA50lEQVQYV13RaXPCIBAG4FiVqlhyX5o23vfVqUq6mvD//1XZJY5T9xPzzLuwgKXKslQvZSG+6UXgCnFePtBE7e/ivXP/nRvUUl7UqNclvO3rpLqofPDAD8xiu2pOntjamqRy/RqZxs81oeVzwpCwfyA8A+8mLKFku9XfI0YnSKXnSYZ7ahSII+AwrqoMmEFKriAeVrqGM4O4Z+ADZIhjg3R6LtMpWuW0ERs5zunKVHdnnnMLNQqaUS0kyKkjE1aE98b8y9x9JYHH8aZXFMKO6JFMEvhucj3Wj0kY2D92HlHbE/9Vk77mD6srRZqmVEAZAAAAAElFTkSuQmCC'
    );
}

/**
 * Temporary store a value
 *
 * @param   string  $key        The identifier
 * @param   string|int|array|object     $value  The value
 */
function storeInTransient($key, $value){
    #$value=serialize(base64_encode(serialize($value)));
    #set_transient( $key, $value, 120 );

    if(!isset($_SESSION)){
        session_start();
    }
    $_SESSION[$key] = $value;

    session_write_close();
}

/**
 * Retrieves a temporary stored value
 *
 * @param   string  $key    The key the values was stored with
 *
 * @return  string|int|array|object             The value
 */
function getFromTransient($key){
    #return unserialize(base64_decode(unserialize(get_transient( $key))));

    if(!isset($_SESSION)){
        session_start();
    }

    $value  = $_SESSION[$key]; 
    session_write_close();

    return $value;
}

/**
 * Deletes a temporary stored value
 *
 * @param   string  $key    The key the values was stored with
 *
 * @return  string|int|array|object             The value
 */
function deleteFromTransient($key){
    #delete_transient( $key);

    if(!isset($_SESSION)){
        session_start();
    }
    unset( $_SESSION[$key]);

    session_write_close();
}

/**
 * Get authenticator list
 *
 * @return  object The autenticator object
 */
function authenticatorList(){
    $user = wp_get_current_user();

    if(!current_user_can("read")){
        $name = $user->display_name;
        //SIM\printArray("$name has not enough permissions");
        //return;
    }

    if(isset($_GET["user_id"])){
        $userId = intval(sanitize_text_field($_GET["user_id"]));
        if($userId <= 0){
            SIM\printArray("ajax_ajax_authenticator_list: (ERROR)Wrong parameters, exit");
            return new WP_Error('webauthn', "Bad Request.");
        }

        if($user->ID !== $userId){
            if(!current_user_can("edit_user", $userId)){
                SIM\printArray("ajax_ajax_authenticator_list: (ERROR)No permission, exit");
                return new WP_Error('webauthn', "Bad Request.");
            }
            $user = get_user_by('id', $userId);

            if($user === false){
                SIM\printArray("ajax_ajax_authenticator_list: (ERROR)Wrong user ID, exit");
                return new WP_Error('webauthn', "Bad Request.");
            }
        }
    }

    $userKey   = get_user_meta($user->ID, '2fa_webauthn_key', true);
    if(!$userKey){
        // The user haven't bound any authenticator, return empty list
        if(defined('REST_REQUEST')){
            return "[]";
        }else{
            return array();
        }
    }

    $userEntity = new PublicKeyCredentialUserEntity(
        $user->user_login,
        $userKey,
        $user->display_name
    );

    $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository($user);

    return $publicKeyCredentialSourceRepository->getShowList($userEntity);
}

/**
 * Creates a table listing all the webauthn methods of an user
 *
 * @param   int     $authId     The current used webauthn id
 *
 * @return  string              The table html
 */
function authTable($authId=''){
    $webauthnList	= authenticatorList();

    ob_start();
	if(!empty($webauthnList)){
		?>
		<div id='webautn_devices_wrapper'>
            <h4>Biometric authenticators overview</h4>
			<table class='sim-table'>
				<thead>
					<tr>
						<th>Name</th>
						<th>OS</th>
						<th>Added</th>
						<th>Last used</th>
						<th>Delete</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach($webauthnList as $key=>$deviceMeta){
						$identifier		= $deviceMeta['identifier'];
						$osName		    = $deviceMeta['os_info']['name'];
						$added			= date('jS M Y', strtotime($deviceMeta['added']));
                        $lastUsed       = $deviceMeta['last_used'];

                        if($lastUsed != '-'){
						    $lastUsed		= date('jS M Y', strtotime($deviceMeta['last_used']));
                        }

                        if($key == $authId){
                            echo "<tr class='current_device'>";
                        }else{
                            echo "<tr>";
                        }
             
						?>
							<td><?php echo $identifier;?></td>
							<td><?php echo $osName;?></td>
							<td><?php echo $added;?></td>
							<td><?php echo $lastUsed;?></td>
							<td>
								<button type='button' class='button small remove_webauthn' title='Remove this method' data-key='<?php echo $key;?>'>-</button>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
	    <?php
	}

    return ob_get_clean();
}