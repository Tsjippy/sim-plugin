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
        $this->user_id = $user->ID;
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
        $meta = unserialize(get_user_meta($this->user_id,"2fa_webautn_cred_meta",true));
        if(isset($meta[$publicKeyCredentialId])){
            return $meta[$publicKeyCredentialId];
        }
        return null;
    }

    // Get all credentials of one user
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array {
        $sources = [];

        //check if the platform matches
        $metadata   = unserialize(get_user_meta($this->user_id,"2fa_webautn_cred_meta",true));
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
            $meta = unserialize(get_user_meta($this->user_id,"2fa_webautn_cred_meta",true));
            $meta[$publicKeyCredentialId] = $credential;
            update_user_meta($this->user_id,"2fa_webautn_cred_meta", serialize($meta));

            //store temporary so that we know we did webauth
            store_in_transient("webautn_id",$publicKeyCredentialId);
        }
    }

    // List all authenticators
    public function getShowList(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array {
        $data   = unserialize(get_user_meta($this->user_id,"2fa_webautn_cred_meta",true));
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
            delete_user_meta($this->user_id, "2fa_webautn_cred");
            delete_user_meta($this->user_id, "2fa_webautn_cred_meta");
            delete_user_meta($this->user_id, "2fa_webautn_key");
    
            $methods    = get_user_meta($this->user_id, "2fa_methods",true);
            unset($methods[array_search('webauthn', $methods)]);
            SIM\clean_up_nested_array($methods);

            if(empty($methods)){
                delete_user_meta($this->user_id, "2fa_methods");
            }else{
                update_user_meta($this->user_id, "2fa_methods", $methods);
            }
        }else{
            update_user_meta($this->user_id,"2fa_webautn_cred",base64_encode(serialize($data)));

            $meta = unserialize(get_user_meta($this->user_id,"2fa_webautn_cred_meta",true));
            unset($meta[$id]);

            update_user_meta($this->user_id, "2fa_webautn_cred_meta", serialize($meta));
        }
    }

    // Read credential database
    private function read(): array {
        $user_cred  = get_user_meta($this->user_id,"2fa_webautn_cred",true);
        if($user_cred){
            try{
                return unserialize(base64_decode($user_cred));
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
            
            $meta = unserialize(get_user_meta($this->user_id,"2fa_webautn_cred_meta",true));
            if(!$meta) $meta    = [];

            //already exists
            if(is_array($meta[$key])){
                //nothing updated
                if($meta[$key]["user" ] == $source) return;
                $meta[$key]["user" ]    = $source;
            }else{
                $meta[$key] = array(
                    "identifier"    => get_from_transient("identifier"),
                    "os_info"       => $this->get_os_info(),
                    "added"         => date('Y-m-d H:i:s', current_time('timestamp')), 
                    "user"          => $source, 
                    "last_used"     => "-"
                );
            }
            update_user_meta($this->user_id,"2fa_webautn_cred_meta", serialize($meta));
        }
        update_user_meta($this->user_id,"2fa_webautn_cred",base64_encode(serialize($data)));
    }
}

function get_profile_picture($user_id){
    $attachment_id  = get_user_meta($user_id,'profile_picture',true);
    $image          = null;

    if(is_numeric($attachment_id)){
        $path   = get_attached_file($attachment_id);
        if($path){
            $type       = pathinfo($path, PATHINFO_EXTENSION);
            $contents   = file_get_contents(get_attached_file($attachment_id));
            if(!empty($contents)){
                $image = "data:image/$type;base64,".base64_encode($contents);
            }
        }   
    }

    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAMAAAC6V+0/AAAAwFBMVEXm7NK41k3w8fDv7+q01Tyy0zqv0DeqyjOszDWnxjClxC6iwCu11z6y1DvA2WbY4rCAmSXO3JZDTxOiwC3q7tyryzTs7uSqyi6tzTCmxSukwi9aaxkWGga+3FLv8Ozh6MTT36MrMwywyVBziSC01TbT5ZW9z3Xi6Mq2y2Xu8Oioxy7f572qxzvI33Tb6KvR35ilwTmvykiwzzvV36/G2IPw8O++02+btyepyDKvzzifvSmw0TmtzTbw8PAAAADx8fEC59dUAAAA50lEQVQYV13RaXPCIBAG4FiVqlhyX5o23vfVqUq6mvD//1XZJY5T9xPzzLuwgKXKslQvZSG+6UXgCnFePtBE7e/ivXP/nRvUUl7UqNclvO3rpLqofPDAD8xiu2pOntjamqRy/RqZxs81oeVzwpCwfyA8A+8mLKFku9XfI0YnSKXnSYZ7ahSII+AwrqoMmEFKriAeVrqGM4O4Z+ADZIhjg3R6LtMpWuW0ERs5zunKVHdnnnMLNQqaUS0kyKkjE1aE98b8y9x9JYHH8aZXFMKO6JFMEvhucj3Wj0kY2D92HlHbE/9Vk77mD6srRZqmVEAZAAAAAElFTkSuQmCC';

    return $image;
}

// Create random strings for user ID
function generate_random_string($length = 10){
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

// Format trackback
function generate_call_trace($exception = false){
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

function get_rp_entity(){
    $logo       = null;
    $path       = get_attached_file(get_option( 'site_icon' ));
    $type   = pathinfo($path, PATHINFO_EXTENSION);
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

function store_in_transient($key, $value){
    #$value=serialize(base64_encode(serialize($value)));
    #set_transient( $key, $value, 120 );

    if(!isset($_SESSION)) session_start();
    $_SESSION[$key] = $value;
}

function get_from_transient($key){
    #return unserialize(base64_decode(unserialize(get_transient( $key))));

    if(!isset($_SESSION)) session_start();
    return $_SESSION[$key];
}

// Get authenticator list
function authenticator_list(){
    $user_info = wp_get_current_user();

    if(!current_user_can("read")){
        $name = $user_info->display_name;
        SIM\print_array("$name has not enough permissions");
        return;
    }

    if(isset($_GET["user_id"])){
        $user_id = intval(sanitize_text_field($_GET["user_id"]));
        if($user_id <= 0){
            SIM\print_array("ajax_ajax_authenticator_list: (ERROR)Wrong parameters, exit");
            return new WP_Error('webauthn', "Bad Request.");
        }

        if($user_info->ID !== $user_id){
            if(!current_user_can("edit_user", $user_id)){
                SIM\print_array("ajax_ajax_authenticator_list: (ERROR)No permission, exit");
                return new WP_Error('webauthn', "Bad Request.");
            }
            $user_info = get_user_by('id', $user_id);

            if($user_info === false){
                SIM\print_array("ajax_ajax_authenticator_list: (ERROR)Wrong user ID, exit");
                return new WP_Error('webauthn', "Bad Request.");
            }
        }
    }

    $user_key   = get_user_meta($user_info->ID, '2fa_webauthn_key', true);
    if(!$user_key){
        // The user haven't bound any authenticator, return empty list
        if(defined('REST_REQUEST')){
            return "[]";
        }else{
            return array();
        }
    }

    $userEntity = new PublicKeyCredentialUserEntity(
        $user_info->user_login,
        $user_key,
        $user_info->display_name
    );

    $publicKeyCredentialSourceRepository = new PublicKeyCredentialSourceRepository($user_info);
    $authenticator_list   = $publicKeyCredentialSourceRepository->getShowList($userEntity);
    
    return $authenticator_list;
}

function auth_table($auth_id=''){
    $webauthn_list	= authenticator_list();

    ob_start();
	if(!empty($webauthn_list)){
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
					foreach($webauthn_list as $key=>$device_meta){
						$identifier		= $device_meta['identifier'];
						$os_name		= $device_meta['os_info']['name'];
						$added			= date('jS M Y', strtotime($device_meta['added']));
                        $last_used      = $device_meta['last_used'];

                        if($last_used != '-'){
						    $last_used		= date('jS M Y', strtotime($device_meta['last_used']));
                        }

                        if($key == $auth_id){
                            echo "<tr class='current_device'>";
                        }else{
                            echo "<tr>";
                        }
                        
						?>
							<td><?php echo $identifier;?></td>
							<td><?php echo $os_name;?></td>
							<td><?php echo $added;?></td>
							<td><?php echo $last_used;?></td>
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