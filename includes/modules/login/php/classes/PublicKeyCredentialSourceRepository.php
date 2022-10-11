<?php
namespace SIM\LOGIN;
use SIM;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialSourceRepository as PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\PublicKeyCredentialSource;
use DeviceDetector\Parser\OperatingSystem as OS_info;

/**
 * Store all publickeys and pubilckey metas
 */
class PublicKeyCredentialSourceRepository implements PublicKeyCredentialSourceRepositoryInterface {
    public $user;

    public function __construct($user){
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
        $os         = $this->getOsInfo()['name'];

        foreach($this->read() as $key=>$data){
            if(
                $data->getUserHandle() === $publicKeyCredentialUserEntity->getId() &&  //should always be true
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
    protected function read(): array {
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

    protected function getOsInfo(){
        $userAgent = $_SERVER['HTTP_USER_AGENT']; // change this to the useragent you want to parse
    
        $info = new OS_info($userAgent);
        return $info->parse();
    }

    // Save credentials data
    protected function write(array $data, string $key): void {
        if($key !== ''){
            // Save credentials's meta separately
            $source = $data[$key]->getUserHandle();
            
            $meta = unserialize(get_user_meta($this->userId, "2fa_webautn_cred_meta", true));
            if(!$meta){
                $meta    = [];
            }

            //already exists
            if(is_array($meta[$key])){
                //nothing updated
                if($meta[$key]["user" ] == $source){
                    return;
                }
                $meta[$key]["user" ]    = $source;
            }else{
                $meta[$key] = array(
                    "identifier"    => getFromTransient("identifier"),
                    "os_info"       => $this->getOsInfo(),
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