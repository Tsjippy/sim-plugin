<?php
namespace SIM\SIGNAL;
use SIM;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use GuzzleHttp;
use jigarakatidus\Signal;

if(!class_exists('BaconQrCode\Renderer\ImageRenderer')){
    return new \WP_Error('2fa', "bacon-qr-code interface does not exist. Please run 'composer require bacon/bacon-qr-code'");
}

/* Install java apt install openjdk-17-jdk -y
export VERSION=0.11.3
wget https://github.com/AsamK/signal-cli/releases/download/v"${VERSION}"/signal-cli-"${VERSION}"-Linux.tar.gz
sudo tar xf signal-cli-"${VERSION}"-Linux.tar.gz -C /opt
sudo ln -sf /opt/signal-cli-"${VERSION}"/bin/signal-cli /usr/local/bin/ */

class SignalWrapperNew{

    public function __construct()
    {
        require_once( __DIR__  . '/../../lib/vendor/autoload.php');

        $this->valid    = true;

        $this->type   = 'macOS';
        $this->path = '';
        if(strpos(php_uname(), 'Windows') !== false){
            $this->type     = 'Windows';
            $this->basePath = __DIR__.'/../../data/signal-cli';
            $this->path     = $this->basePath.'/bin/signal-cli';
        }elseif(strpos(php_uname(), 'Linux') !== false){
            $this->type     = 'Linux';
            $this->basePath = "/usr/local";
            $this->path     = $this->basePath."/bin/signal-cli";
        }

        $this->nr           = SIM\getModuleOption(MODULE_SLUG, 'phone');

        $this->checkPrerequisites();

        $this->client = new Signal(
            $this->path, // Binary Path
            $this->nr, // Username/Number including Country Code with '+'
            Signal::FORMAT_JSON // Format
        );
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
            $this->installSignal(str_replace('v', '', $release['tag_name']));

            if(!file_exists($this->path)){
                $errorMessage .= "Please install signal-cli<br>";
                $this->valid    = false;
            }
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
            if(file_exists($this->basePath)){
                rmdir($this->basePath);
            }
            rename("$folder/signal-cli-$version", $this->basePath);
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
        SIM\clearOutput();
        header("X-Accel-Buffering: no");
        header('Content-Encoding: none');

        // Turn on implicit flushing
        ob_implicit_flush(1);

        $link   = $this->client->link(get_bloginfo('name'));

        echo "Link is <code>$link</code>";

        SIM\printArray($this->client->listDevices(), true);

        if (!extension_loaded('imagick')){
            return $link;
        }

        $renderer       = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );
        $writer         = new Writer($renderer);
        $qrcodeImage    = base64_encode($writer->writeString($link));

        return "<img loading='lazy' src='data:image/png;base64, $qrcodeImage'/><br>$link";
    }

    public function sendText($nrs, $msg){
        if(!is_array($nrs)){
            $nrs    = [$nrs];
        }
        return $this->client->send($nrs, $msg);
    }

    public function setProfilePicture($path){
        return $this->client->updateProfile(get_bloginfo('name'), $path);
    }

    public function getGroups(){
        return $this->client->listGroups();
    }

    public function receive(){
        echo $this->client->receive();
    }

    public function isSignalNumber($nrs){
        if(!is_array($nrs)){
            $nrs    = [$nrs];
        }
        $this->client->getUserStatus($nrs);
    }

    public function listDevices(){
        echo $this->client->listDevices();
    }

       /*  $version    = shell_exec('curl --silent "https://api.github.com/repos/AsamK/signal-cli/releases/latest" | jq ".. .tag_name? // empty"');
        echo $version; */
}
