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

        $this->nr           = SIM\getModuleOption(MODULE_SLUG, 'phone');

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

        $this->groups	    = $this->userObj->getGroups(); 
        */
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
        #https://www.qr-code-generator.com/

        $userName   = shell_exec("whoami 2>&1");

        # signal-cli --config /home/simnig1/.config/signal link

        header("X-Accel-Buffering: no");
        header('Content-Encoding: none');
        ob_implicit_flush(true);
        ob_end_flush();

        for ($i=0; $i<5; $i++) {
        echo $i.'<br>';
        sleep(1);
        }

        ob_implicit_flush();

        ob_start();

          
        for($i=0;$i<9;$i++) {
            echo $i.'<br>';
            ob_end_flush();
            ob_flush();
            sleep(1);
      }

        /* $cmd = $this->path.' link &'; //example command

        $descriptorspec = array(
            0 => array("pipe", "r"), 
            1 => array("pipe", "w"), 
            2 => array("pipe", "a")
        );

        $pipes = array();

        $process = proc_open($cmd, $descriptorspec, $pipes, null, null);

        echo "Start process:<br>";
        ob_end_flush();
        ob_flush();

        $str = "";

        if(is_resource($process)) {
            do {
                $curStr = fgets($pipes[1]);  //will wait for a end of line

                echo $curStr;
                ob_end_flush();
                ob_flush();
                if(strpos($curStr, 'sgnl://') !== false){
                    $renderer                   = new ImageRenderer(
                        new RendererStyle(400),
                        new ImagickImageBackEnd()
                    );
                    $writer         = new Writer($renderer);
                    $qrcodeImage    = base64_encode($writer->writeString($curStr));
            
                    echo "<img loading='lazy' src='data:image/png;base64, $qrcodeImage'/><br>";
                    ob_end_flush();
                    ob_flush();
                }
                
                $str .= $curStr;

                $arr = proc_get_status($process);

            }while($arr['running']);
        }else{
            echo "Unable to start process<br>";
            ob_end_flush();
              ob_flush();
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        echo "\n\n\nDone\n";
        ob_end_flush();
              ob_flush();

        echo "Result is:<br>----<br>".$str."<br>----<br>"; */

        ob_end_flush();
        ob_flush();

        //$uri		= $this->clientObj->getDeviceLinkUri(get_bloginfo('name')); //will throw on error
        /*         $uri        = system($this->path.' link');


        if (!extension_loaded('imagick')){
            return $uri;
        }

        $renderer                   = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );
        $writer         = new Writer($renderer);
        $qrcodeImage    = base64_encode($writer->writeString($uri));

        return "<img loading='lazy' src='data:image/png;base64, $qrcodeImage'/><br>$uri"; */
    }
        

    public function sendText($nr, $msg){
        SIM\clearOutput();
        header("X-Accel-Buffering: no");
        header('Content-Encoding: none');

        // Turn on implicit flushing
        ob_implicit_flush(1);

        // Some browsers will not display the content if it is too short
        // We use str_pad() to make the output long enough
        echo str_pad("Hello World!<br>", 4096);

        // Even though the script is still running, the browser already can see the content
        sleep(3);

        echo str_pad("Hello World!<br>", 4096);


        $userName   = str_replace('<br>', '', trim(shell_exec("whoami 2>&1"))).'<br>';

        SIM\printArray($userName);

        echo str_pad("'$userName'<br>", 4096);

        echo "signal-cli --config /home/$userName/.config/signal link".'<br>';

        #echo shell_exec("$this->path link & 2>&1").'<br>';

        #$uri        = system("$this->path --config /home/simnig1/.config/signal -a $this->nr send -m 'This is a message' +2349045252526  2>&1 | tee /home/admin/test.txt").'<br>';

        echo "signal-cli --config /home/simnig1/.config/signal -a $this->nr send -m 'This is a message' +2349045252526 2>&1";
        echo shell_exec("signal-cli --config /home/simnig1/.config/signal -a $this->nr send -m 'This is a message' +2349045252526 2>&1").'<br>';
        #echo shell_exec("whoami 2>&1").'<br>';

        

        /*         // send to individual
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
            
        } */
        
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
