<?php
namespace SIM\LOGIN;
use SIM;

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

//https://webauthn-doc.spomky-labs.com/

//check for interface
if(!interface_exists('Webauthn\PublicKeyCredentialSourceRepository')){
    return new WP_Error('biometric', "Webauthn\PublicKeyCredentialSourceRepository interface does not exist. Please run 'composer require web-auth/webauthn-lib'");
}

/**
 * Store all publickeys and pubilckey metas
 */
class PublicKeyCredentialSourceRepository implements PublicKeyCredentialSourceRepositoryInterface {
    public $user;

    function __construct($user){
        $this->userId = $user->ID;
    }

    // Get one credential by credential ID
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource {
        $data = $this->read();
        if(isset($data[base64_encode($publicKeyCredentialId)])){
            return $data[base64_encode($publicKeyCredentialId)];
        }
        return null;
    }

    // Get one credential's meta by credential ID
    public function findOneMetaByCredentialId(string $publicKeyCredentialId): ?array {
        $meta = unserialize(get_user_meta($this->userId,"2fa_webautn_cred_meta",true));
        if(isset($meta[$publicKeyCredentialId])){
            return $meta[$publicKeyCredentialId];
        }
        return null;
    }

    // Get all credentials of one user
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array {
        $sources = [];

        //check if the platform matches
        $metadata   = unserialize(get_user_meta($this->userId,"2fa_webautn_cred_meta",true));
        $os         = $this->get_os_info()['name'];

        foreach($this->read() as $key=>$data){
            if(
                $data->getUserHandle() === $publicKeyCredentialUserEntity->getId() and  //should always be true
                $os == $metadata[$key]['os_info']['name']                               // Only return same OS
            ){
                $sources[] = $data;
            }
        }

        return $sources;
    }

    // Save credential into database
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void {
        $data = $this->read();
        $data_key = base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId());
        $data[$data_key] = $publicKeyCredentialSource;
        $this->write($data, $data_key);
    }

    // Update credential's last used
    public function updateCredentialLastUsed(string $publicKeyCredentialId): void {
        $credential = $this->findOneMetaByCredentialId($publicKeyCredentialId);
        if($credential !== null){
            $credential["last_used"] = date('Y-m-d H:i:s', current_time('timestamp'));
            $meta = unserialize(get_user_meta($this->userId, "2fa_webautn_cred_meta", true));
            $meta[$publicKeyCredentialId] = $credential;
            update_user_meta($this->userId, "2fa_webautn_cred_meta", serialize($meta));

            //store temporary so that we know we did webauth
            storeInTransient("webautn_id", $publicKeyCredentialId);
        }
    }

    // List all authenticators
    public function getShowList(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array {
        $data   = unserialize(get_user_meta($this->userId, "2fa_webautn_cred_meta", true));
        if($data){
            return $data;
        }else{
            return [];
        }
    }

    // Remove a credential from database by credential ID
    public function removeCredential(string $id): void {
        $data = $this->read();
        unset($data[$id]);
        if(empty($data)){
            delete_user_meta($this->userId, "2fa_webautn_cred");
            delete_user_meta($this->userId, "2fa_webautn_cred_meta");
            delete_user_meta($this->userId, "2fa_webautn_key");
    
            $methods    = get_user_meta($this->userId, "2fa_methods",true);
            unset($methods[array_search('webauthn', $methods)]);
            SIM\cleanUpNestedArray($methods);

            if(empty($methods)){
                delete_user_meta($this->userId, "2fa_methods");
            }else{
                update_user_meta($this->userId, "2fa_methods", $methods);
            }
        }else{
            update_user_meta($this->userId, "2fa_webautn_cred", base64_encode(serialize($data)));

            $meta = unserialize(get_user_meta($this->userId, "2fa_webautn_cred_meta", true));
            unset($meta[$id]);

            update_user_meta($this->userId, "2fa_webautn_cred_meta", serialize($meta));
        }
    }

    // Read credential database
    private function read(): array {
        $userCred  = get_user_meta($this->userId, "2fa_webautn_cred", true);
        if($userCred){
            try{
                return unserialize(base64_decode($userCred));
            }catch(\Throwable $exception) {
                return [];
            }
        }
        return [];
    }

    function get_os_info(){
        $userAgent = $_SERVER['HTTP_USER_AGENT']; // change this to the useragent you want to parse
    
        $info = new OS_info($userAgent);
        return $info->parse();
    }

    // Save credentials data
    private function write(array $data, string $key): void {
        if($key !== ''){
            // Save credentials's meta separately
            $source = $data[$key]->getUserHandle();
            
            $meta = unserialize(get_user_meta($this->userId, "2fa_webautn_cred_meta", true));
            if(!$meta) $meta    = [];

            //already exists
            if(is_array($meta[$key])){
                //nothing updated
                if($meta[$key]["user" ] == $source) return;
                $meta[$key]["user" ]    = $source;
            }else{
                $meta[$key] = array(
                    "identifier"    => getFromTransient("identifier"),
                    "os_info"       => $this->get_os_info(),
                    "added"         => date('Y-m-d H:i:s', current_time('timestamp')), 
                    "user"          => $source, 
                    "last_used"     => "-"
                );
            }
            update_user_meta($this->userId, "2fa_webautn_cred_meta", serialize($meta));
        }
        update_user_meta($this->userId, "2fa_webautn_cred", base64_encode(serialize($data)));
    }
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

    $rpEntity = new PublicKeyCredentialRpEntity(
        get_bloginfo('name').' Webauthn Server', // The application name
        $_SERVER['SERVER_NAME'],       // The application ID = the domain
        //$logo
        'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAMAAAC6V+0/AAAAwFBMVEXm7NK41k3w8fDv7+q01Tyy0zqv0DeqyjOszDWnxjClxC6iwCu11z6y1DvA2WbY4rCAmSXO3JZDTxOiwC3q7tyryzTs7uSqyi6tzTCmxSukwi9aaxkWGga+3FLv8Ozh6MTT36MrMwywyVBziSC01TbT5ZW9z3Xi6Mq2y2Xu8Oioxy7f572qxzvI33Tb6KvR35ilwTmvykiwzzvV36/G2IPw8O++02+btyepyDKvzzifvSmw0TmtzTbw8PAAAADx8fEC59dUAAAA50lEQVQYV13RaXPCIBAG4FiVqlhyX5o23vfVqUq6mvD//1XZJY5T9xPzzLuwgKXKslQvZSG+6UXgCnFePtBE7e/ivXP/nRvUUl7UqNclvO3rpLqofPDAD8xiu2pOntjamqRy/RqZxs81oeVzwpCwfyA8A+8mLKFku9XfI0YnSKXnSYZ7ahSII+AwrqoMmEFKriAeVrqGM4O4Z+ADZIhjg3R6LtMpWuW0ERs5zunKVHdnnnMLNQqaUS0kyKkjE1aE98b8y9x9JYHH8aZXFMKO6JFMEvhucj3Wj0kY2D92HlHbE/9Vk77mD6srRZqmVEAZAAAAAElFTkSuQmCC'
    );

    return $rpEntity;
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

    if(!isset($_SESSION)) session_start();
    $_SESSION[$key] = $value;
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

    if(!isset($_SESSION)) session_start();
    return $_SESSION[$key];
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
        SIM\printArray("$name has not enough permissions");
        return;
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
    $authenticatorList   = $publicKeyCredentialSourceRepository->getShowList($userEntity);
    
    return $authenticatorList;
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