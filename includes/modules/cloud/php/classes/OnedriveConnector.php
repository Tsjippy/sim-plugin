<?php
namespace SIM\CLOUD;
use SIM;
use Krizalys\Onedrive\Onedrive;

class OnedriveConnector{

    protected   $clientId;
    protected   $clientSecret;
    
    public      $client;

    public function __construct() {
        require_once( MODULE_PATH  . 'lib/vendor/autoload.php');

        $this->clientId     = SIM\getModuleOption(MODULE_SLUG, 'client_id');
        $this->clientSecret = SIM\getModuleOption(MODULE_SLUG, 'client_secret');

        $state              = new \stdClass();

        if(!empty($_SESSION['onedrive.client.state'])){
            $state              = $_SESSION['onedrive.client.state'];
        }

        // Instantiates a OneDrive client bound to your OneDrive application.
        $this->client = Onedrive::client(
            $this->clientId,
            [
                // Restore the previous state while instantiating this client to proceed in obtaining an access token.
                'state' => $state,
            ]
        );
    }

    /**
     * Login to OneDrive
     */
    function login(){
        $redirectUri   =  admin_url( "admin.php?page=".$_GET["page"]."&main_tab=settings" );

        // Gets a log in URL with sufficient privileges from the OneDrive API.
        $url = $this->client->getLogInUrl([
            'files.read',
            'files.read.all',
            'files.readwrite',
        ], $redirectUri);

        session_start();

        // Persist the OneDrive client' state for next API requests.
        $_SESSION['onedrive.client.state'] = $this->client->getState();

        // Redirect the user to the log in URL.
        wp_redirect($url);

        echo "<script>location.href='$url'</script>";
    }

    /**
     * Get token
     */
    function getToken(){
        // If we don't have a code in the query string (meaning that the user did not
        // log in successfully or did not grant privileges requested), we cannot proceed
        // in obtaining an access token.
        if (!array_key_exists('code', $_GET)) {
            throw new \Exception('code undefined in $_GET');
        }

        session_start();

        // Attempt to load the OneDrive client' state persisted from the previous
        // request.
        if (!array_key_exists('onedrive.client.state', $_SESSION)) {
            throw new \Exception('onedrive.client.state undefined in $_SESSION');
        }
        
        // Obtain the token using the code received by the OneDrive API.
        $this->client->obtainAccessToken($this->clientSecret, $_GET['code']);
        
        // Persist the OneDrive client' state for next API requests.
        $_SESSION['onedrive.client.state'] = $this->client->getState();
        
        // Past this point, you can start using file/folder functions from the SDK, eg:
        $file = $this->client->getRoot()->upload('hello.txt', 'Hello World!');
        echo $file->download();
        // => Hello World!
        
        $file->delete();
    }

    public function test($path='test'){
        $file = $this->client->getDriveItemByPath($path);

        $file = $this->client->getShared();

        $list = $this->client->getRoot()->getChildren(); 
        foreach($list as $file) { 
            var_dump($file);
            //$file->delete();
        }

        $drives = $this->client->getDrives();

        $driveItems = $this->client->getShared();

        $this->client->getRoot()->createFolder('test');
      

    }

    public function upload( $file, $path ) {    
        // Check if the folder exists
        try {
            $folder = $this->client->getDriveItemById( $path );
            $folder = $this->client->getDriveItemByPath($path);
        } catch ( \Exception $e ) {
            SIM\printArray( 'There was an error getting OneDrive file properties for `' . $path . '`: ' . $e->getMessage(), 'error' );
            return false;
        }

        // check if a file with the same name already exists
    
        try {
            $upload = $folder->startUpload( basename( $file ), fopen( $file, 'r' ));
        } catch ( \Exception $e ) {
            SIM\printArray( 'Error: Could not initiate upload for `' . basename( $file ) . '`. Details: ' . $e->getMessage(), 'error' );
            return false;
        }
    
        try {
            $new_file = $upload->complete();
        } catch ( \Exception $e ) {
            SIM\printArray( 'Error: OneDrive upload failed for `' . basename( $file ) . '`. Details: ' . $e->getMessage(), 'error' );
            return false;
        }
    
        // Don't use isset or empty here.
        if ( ! $new_file->id ) {
            SIM\printArray( 'OneDrive upload status for `' . basename( $file ) . '` was missing ID property: ' . print_r( $new_file->id, true ), 'error' );
            return false;
        }
    
        return true;
    }
}
