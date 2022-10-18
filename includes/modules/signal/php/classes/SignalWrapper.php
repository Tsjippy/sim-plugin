<?php
namespace SIM\SIGNAL;
use SIM;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use GuzzleHttp;

if(!class_exists('BaconQrCode\Renderer\ImageRenderer')){
    return new \WP_Error('2fa', "bacon-qr-code interface does not exist. Please run 'composer require bacon/bacon-qr-code'");
}

/* Install java apt install openjdk-17-jdk -y
export VERSION=0.11.3
wget https://github.com/AsamK/signal-cli/releases/download/v"${VERSION}"/signal-cli-"${VERSION}"-Linux.tar.gz
sudo tar xf signal-cli-"${VERSION}"-Linux.tar.gz -C /opt
sudo ln -sf /opt/signal-cli-"${VERSION}"/bin/signal-cli /usr/local/bin/ */

class SignalWrapper{

    public function __construct()
    {
        require_once( __DIR__  . '/../../lib/vendor/autoload.php');

        $this->valid    = true;

        $this->type   = 'macOS';
        $this->path = '';
        if(strpos(php_uname(), 'Windows') !== false){
            $this->type     = 'Windows';
            $this->path     = '"C:/Program Files/signal-cli/bin/signal-cli"';
        }elseif(strpos(php_uname(), 'Linux') !== false){
            $this->type     = 'Linux';
            $this->path     = "/usr/local/bin/signal-cli";
        }

        $this->checkPrerequisites();

        #curl --silent "https://api.github.com/repos/USER/REPO/releases/latest" | jq ".. .tag_name? // empty"

        

/*         $this->safePath   = __DIR__.'/../../data';
        if (!is_dir($this->safePath)) {
            $result =  mkdir($this->safePath, 0777, true);
            if($result){
                echo 'sdfdsfdsf<br>';
            }else{
                echo 'failed!<br>';
            }
        }

        //$this->safePath		= sys_get_temp_dir(). "/sigtest";
        $this->nr           = SIM\getModuleOption(MODULE_SLUG, 'phone');

        if( !$this->nr || empty( $this->nr )){
            return new \WP_Error('signal', 'Please register a phone first');
        }

        $this->clientObj	= \MTM\SignalApi\Facts::getClients()->getSignalCli($this->safePath);
        $this->userObj	    = $this->clientObj->getUser($this->nr);
        SIM\printArray($this->userObj, true);
        if( !$this->userObj->isPhoneRegistered($this->nr)){
            return $this->linkPhoneNumber();
        }

        $this->groups	    = $this->userObj->getGroups(); */
    }

    public function checkPrerequisites(){
        $errorMessage   = '';

        $curVersion = str_replace('javac ', '', shell_exec('javac -version'));
        if(version_compare('17.0.0.0', $curVersion) > 0){
            $errorMessage .= "Please install Java JDK, at least version 17<br>";
            $this->valid    = false;
        }

        $release        = SIM\getLatestRelease('AsamK', 'signal-cli');

        $curVersion     = str_replace('signal-cli ', 'v', trim(shell_exec($this->path.' --version')));
        if(empty($curVersion)){
            $this->installSignal(str_replace('v','',$release['tag_name']));
            $errorMessage .= "Please install signal-cli<br>";
            $this->valid    = false;
        }elseif($curVersion  != $release['tag_name']){
            $errorMessage .= "Please update to version {$release['tag_name']}<br>";
        }

        if(empty($errorMessage)){
            return true;
        }

        $this->error = new \WP_Error('signal', $errorMessage);
    }

    private function installSignal($version){
        $path   = $this->downloadSignal("https://github.com/AsamK/signal-cli/releases/download/v$version/signal-cli-$version-$this->type.tar.gz");
        
        // Unzip the gz
        $fileName = str_replace('.gz', '', $path);

        // Raising this value may increase performance
        $bufferSize = 4096; // read 4kb at a time

        // Open our files (in binary mode)
        $file       = gzopen($path, 'rb');
        $outFile    = fopen($fileName, 'wb');

        // Keep repeating until the end of the input file
        while (!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($outFile, gzread($file, $bufferSize));
        }

        // Files are done, close files
        fclose($outFile);
        gzclose($file);

        // unzip the tar

        $folder = str_replace('.tar.gz', '', $path);
        try {
            $phar = new \PharData($fileName);
            $phar->extractTo($folder); // extract all files
        } catch (\Exception $e) {
            // handle errors
            return new \WP_Error('signal', 'installation error');
        }

        // move the folder
        if($this->type == 'Linux'){
            rename("$folder/signal-cli-$version", "/opt");
            echo shell_exec("sudo ln -sf /opt/signal-cli-$version/bin/signal-cli /usr/local/bin/");
        }elseif($this->type == 'Windows'){
            rmdir("C:/Program Files/signal-cli");
            rename("$folder/signal-cli-$version", "C:/Program Files/signal-cli");
        }
    }

    private function downloadSignal($url){
        $filename   = basename($url);
        $path       = sys_get_temp_dir().'/'.$filename;

        if(file_exists($path)){
            return $path;
        }

        $client     = new GuzzleHttp\Client();
        try{
            $client->request(
                'GET',
                $url,
                array('sink' => $path)
            );

            return $path;
        }catch (\GuzzleHttp\Exception\ClientException $e) {
            unlink($path);

            if($e->getResponse()->getReasonPhrase() == 'Gone'){
                return new \WP_Error('signal', "The link has expired, please get a new one");
            }
            return new \WP_Error('signal', $e->getResponse()->getReasonPhrase());
        }
    }

    public function linkPhoneNumber(){
        //$uri		= $this->clientObj->getDeviceLinkUri(get_bloginfo('name')); //will throw on error
        $uri        = shell_exec($this->path.' link &');


        if (!extension_loaded('imagick')){
            return $uri;
        }

        $renderer                   = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );
        $writer         = new Writer($renderer);
        $qrcodeImage    = base64_encode($writer->writeString($uri));

        return "<img loading='lazy' src='data:image/png;base64, $qrcodeImage'/><br>$uri";
    }
        

    public function sendText($nr, $msg){
        // send to individual
        if(strpos($nr, '+') !== false){
            $timeStamp		= $this->userObj->sendText($nr, $msg);
        }else{
            // send to group
            foreach($this->groups as $group){
                if($group->name == $nr){
                    //$groupObj		= $this->userObj->getGroupById($id, false);
                    $group->sendText($msg);
                }
            }
            
        }
        
    }

    public function receive(){
        //return array of message objects
        $msgObjs		= $this->userObj->receive(); //will throw on error
        foreach ($msgObjs as $msgObj) {
            if ($msgObj->getType() == "DataMessage") {
                    echo $msgObj->getMessage()."<br><br>";
            } elseif ($msgObj->getType() == "ReceiptMessage") {
                    echo $msgObj->getWhen()."<br><br>";
            }
        }
    }

       /*  $version    = shell_exec('curl --silent "https://api.github.com/repos/AsamK/signal-cli/releases/latest" | jq ".. .tag_name? // empty"');
        echo $version; */
}
