<?php
namespace SIM\SIGNAL;
use SIM;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

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

        echo MTM_FS_TEMP_PATH;
        $this->safePath		= MTM_FS_TEMP_PATH. "sigtest";
        $this->nr           = SIM\getModuleOption(MODULE_SLUG, 'phone');

        if( !$this->nr || empty( $this->nr )){
            return new \WP_Error('signal', 'Please register a phone first');
        }

        $this->clientObj	= \MTM\SignalApi\Facts::getClients()->getSignalCli($this->safePath);
        $this->userObj	    = $this->clientObj->getUser($this->nr);

        if( !$this->userObj->isPhoneRegistered($this->nr)){
            return $this->linkPhoneNumber();
        }

        $this->groups	    = $this->userObj->getGroups();
    }

    public function linkPhoneNumber(){
        $uri		= $this->clientObj->getDeviceLinkUri(get_bloginfo('name')); //will throw on error

        $renderer                   = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );
        $writer         = new Writer($renderer);
        $qrcodeImage   = base64_encode($writer->writeString($uri));

        return "<img loading='lazy' src='data:image/png;base64, $qrcodeImage'/>";
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
