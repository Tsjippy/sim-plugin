<?php

namespace SIM\SIGNAL;
use SIM;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use mikehaertl\shellcommand\Command;
use stdClass;
use WP_Error;

/* Install java apt install openjdk-17-jdk -y
export VERSION=0.11.3
wget https://github.com/AsamK/signal-cli/releases/download/v"${VERSION}"/signal-cli-"${VERSION}"-Linux.tar.gz
sudo tar xf signal-cli-"${VERSION}"-Linux.tar.gz -C /opt
sudo ln -sf /opt/signal-cli-"${VERSION}"/bin/signal-cli /usr/local/bin/ */

// data is stored in $HOME/.local/share/signal-cli


class SignalJsonRpc extends AbstractSignal{
    public $os;
    public $basePath;
    public $programPath;
    public $phoneNumber;
    public $path;
    public $daemon;
    public $command;
    public $error;
    public $attachmentsPath;
    public $tableName;
    public $prefix;
    public $totalMessages;
    public $groups;
    public $receivedTableName;
    public $homeFolder;
    private $postUrl;
    public $socket;
    public $shouldCloseSocket;
    public $getResult;

    public function __construct($shouldCloseSocket=true, $getResult=true){
        parent::__construct();

        // Check daemon
        $this->daemonIsRunning();
        $this->startDaemon();

        $socketPath     = "/home/simnige1/sockets/signal";

        $this->socket   = stream_socket_client("unix:///$socketPath", $errno, $this->error);

        if($errno == 111){
            // remove the old socket file
            unlink($socketPath);

            // try again
            $this->socket   = stream_socket_client("unix:///$socketPath", $errno, $this->error);
        }

        if(!$this->socket){
            SIM\printArray("$errno: $this->error", true);
        }

        $this->shouldCloseSocket   = $shouldCloseSocket;
        $this->getResult           = $getResult;
    }


    /**
     * Get response from db
     *
     * @param   int     $id         the request id should epoch of the request
     *
     * @return  mixed               the result or empty if no result
     */
    public function getResultFromDb($id){
        $signalResults  = get_option('sim-signal-results', []);

        // the id is not found in the db
        if(!!isset($signalResults[$id])){
            return false;
        }

        $result = $signalResults[$id];

        unset($signalResults[$id]);

        // remove the result from the array
        update_option('sim-signal-results', $signalResults);

        return $result;
    }

    /**
     * Get response from socket
     *
     * @param   int     $id         the request id should epoch of the request
     *
     * @return  string              a json result string
     */
    public function getResultFromSocket($id){
        // maybe the daemon is not running, lets read from the socket ourselves
        $response   = '';
        $x          = 0;
        $base       = '{"jsonrpc":';

        while (!feof($this->socket)) {
            $response       .= fread($this->socket, 4096);

            if(!empty(json_decode($response))){
                break;
            }

            $streamMetaData  = stream_get_meta_data($this->socket);

            if($streamMetaData['unread_bytes'] <= 0){
                $x++;

                if( $x > 10 ){
                    break;
                }
            }
        }
        flush();

        $response   = trim($response);

        if(empty($response)){
            return $this->getRequestResponse($id);
        }

        // somehow we have red multiple responses
        if(substr_count($response, $base) > 1){
            SIM\printArray($response);

            $results    = [];
            // loop over each jsonrpc response to find the ones with a result property
            foreach(explode($base, $response) as $jsonString){
                $decoded    = json_decode($base.$jsonString);

                if(!empty($decoded) && isset($decoded->result)){
                    // this is the one we are after
                    if($decoded->id == $id){
                        $response   = json_encode($decoded);
                    }else{
                        // not this one
                        $results[$decoded->id]  = $decoded;
                    }
                }
            }

            // add the results we are not interested in to the db
            if(!empty($results)){
                $signalResults              = get_option('sim-signal-results', []);

                array_merge($signalResults, $results);

                update_option('sim-signal-results', $signalResults);
            }
        }

        $json       = json_decode($response);

        if(empty($json)){
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    SIM\printArray(' - No errors'.$response, true);
                    break;
                case JSON_ERROR_DEPTH:
                    SIM\printArray(' - Maximum stack depth exceeded'.$response, true);
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    SIM\printArray(' - Underflow or the modes mismatch'.$response, true);
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    SIM\printArray(' - Unexpected control character found'.$response, true);
                    break;
                case JSON_ERROR_SYNTAX:
                    SIM\printArray(' - Syntax error, malformed JSON: '.$response, true);
                    break;
                case JSON_ERROR_UTF8:
                    SIM\printArray(' - Malformed UTF-8 characters, possibly incorrectly encoded'.$response, true);
                    break;
                default:
                    break;
            }
        }

        if(isset($json->error)){
            SIM\printArray("Error, error is: ");
            SIM\printArray($json);
        }elseif(!isset($json->result)){
            SIM\printArray("Trying again");

            $json   = $this->getRequestResponse($id);
        }elseif(!isset($json->id)){
            SIM\printArray("Response has no id");
            SIM\printArray($response);
            SIM\printArray($json);
            $json   = $this->getRequestResponse($id);
        }elseif($json->id != $id){
            SIM\printArray("Id '$json->id' is not the right id '$id', trying again");
            SIM\printArray($response);
            SIM\printArray($json);
            $json   = $this->getRequestResponse($id);
        }

        return $json;
    }

    /**
     * Get the response to a request
     *
     * @param   int     $id         the request id should epoch of the request
     */
    public function getRequestResponse(int $id){
        if(empty($id)){
            SIM\printArray("Got an empty Id");
            return false;
        }

        // maximum of 5 minutes
        if(time() - $id > 60){
            SIM\printArray('Cancelling as this has been running for '.time() - $id.' seconds');
            return false;
        }

        $json   = $this->getResultFromSocket($id);

        if(!$json){
            $json   = $this->getResultFromDb($id);
        }        

        return $json; 
    }

    /**
     * Performs the json RPC request
     *
     * @param   string      $method     The command to perform
     * @param   array       $params     The parameters for the command
     *
     * @return  mixed                   The result or false in case of trouble or nothing if $getResult is false
     */
    public function doRequest($method, $params=[]){
        if($this->shouldCloseSocket){
            $this->socket   = stream_socket_client('unix:////home/simnige1/sockets/signal', $errno, $this->error);
        }

        if(!$this->socket){
            //SIM\printArray("$errno: $this->error", true);
            return false;
        }

        $params["account"]  = $this->phoneNumber;

        $id     = time(); 

        $data   = [
            "jsonrpc"       => "2.0",
            "method"        => $method,
            "params"        => $params,
            "id"            => $id
        ];

        $json   = json_encode($data)."\n";

        fwrite($this->socket, $json);         

        flush();

        if($this->getResult){
            //stream_socket_recvfrom
            $response = $this->getRequestResponse($id);
        }

        if($this->shouldCloseSocket){
            fclose($this->socket);
        }

        if($this->getResult){
            if(!empty($response->error)){
                SIM\printArray("Got error {$response->error->message} For command $json");

                $this->error    =  new \WP_Error('sim-signal', $response->error->message . " For command $json");

                return $this->error;
            }else{
                //SIM\printArray("No error for command $json");
            }

            if(!is_object($response) || empty($response->result)){
                //SIM\printArray("Got faulty result $response");
                return false;
            }

            return $response->result;
        }
    }

    /**
     * Disable push support for this device, i.e. this device won’t receive any more messages.
     * If this is the master device, other users can’t send messages to this number anymore.
     * Use "updateAccount" to undo this. To remove a linked device, use "removeDevice" from the master device.
     *
     * @return bool|string
     */
    public function unregister(){
        $result = $this->doRequest('unregister');

        if($result){
            unlink($this->basePath.'/phone.signal');
        }

        return $result;
    }

    /**
     * Register a phone number with SMS or voice verification. Use the verify command to complete the verification.
     * Default verify with SMS
     * @param bool $voiceVerification The verification should be done over voice, not SMS.
     * @param string $captcha - from https://signalcaptchas.org/registration/generate.html
     * @return bool|string
     */
    public function register(string $phone, string $captcha, bool $voiceVerification = false)
    {
        $voice  = false;
        if($voiceVerification){
            $voice  = 'false';
        }

        file_put_contents($this->basePath.'/phone.signal', $phone);

        $captcha    = str_replace('signalcaptcha://', '', $captcha);

        $params     = [
            "voice"     => $voice,
            "captcha"   => $captcha
        ];

        return $this->doRequest('register', $params);
    }

    /**
     * Verify the number using the code received via SMS or voice.
     * @param string $code The verification code e.g 123-456
     * @return bool|string
     */
    public function verify(string $code)
    {

        /* if($this->command->getExitCode()){
            unlink($this->basePath.'/phone.signal');
        } */

        $params     = [
            "VERIFICATIONCODE"     => $code
        ];

        return $this->doRequest('verify', $params);
    }
    
    /**
     * Link to an existing device, instead of registering a new number.
     * This shows a "tsdevice:/…" URI.
     * If you want to connect to another signal-cli instance, you can just use this URI.
     * If you want to link to an Android/iOS device, create a QR code with the URI (e.g. with qrencode) and scan that in the Signal app.
     * @param string|null $name Optionally specify a name to describe this new device. By default "cli" will be used
     * @return string
     */
    public function link(string $name = ''): string
    {
        $result = $this->doRequest('startLink');

        if(!$result){
            return false;
        }

        $uri    = $result['deviceLinkUri'];
        $id     = $result['id'];

        if(empty($name)){
            $name   = get_bloginfo('name');
        }

        $client     = new \GuzzleHttp\Client();
        $data    = [
            "jsonrpc"       => "2.0",
            "method"        => 'finishLink',
            "params"        => [
                "deviceLinkUri"     => $uri,
                "deviceName"        => $name
            ]
        ];

        $promise  = $client->requestAsync(
            "POST",
            $this->postUrl,
            ["json"  => $data]        
        );
        $promise->then(
            function ($res){
                if($res->getStatusCode() != 200){
                    SIM\printArray("Got ".$res->getStatusCode()." from $this->postUrl");
                    return false;
                }
        
                $result = $res->getBody()->getContents();
                $json   = json_decode($result);
            }
        );
        

        $link   = str_replace(['\n', '"'], ['\0', ''], $uri);

        if (!extension_loaded('imagick')){
            return $uri;
        }

        $renderer       = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );
        $writer         = new Writer($renderer);
        $qrcodeImage    = base64_encode($writer->writeString($link));

        return "<img src='data:image/png;base64, $qrcodeImage'/><br>$link";
    }
     
    /**
     * Shows if a number is registered on the Signal Servers or not.
     * @param   string|array          $recipient or array of recipients number to check.
     *
     * @return  array|bool              If more than one recipient returns an array of results, if only one returns a boolean true or false
     */
    public function isRegistered($recipient){
        if(!is_array($recipient)){
            $recipient  = [$recipient];
        }

        $params     = [
            "recipient"     => $recipient
        ];

        $result = $this->doRequest('getUserStatus', $params);

        if(!$result){
            return true;
        }

        if(count($result) == 1){
            return $result[0]->isRegistered;
        }

        return $result;
    }

    /**
     * Send a message to another user or group
     * @param string|array  $recipients     Specify the recipients’ phone number or a group id
     * @param string        $message        Specify the message, if missing, standard input is used
     * @param string|array  $attachments    Image file path or array of file paths
     * @param int           $timeStamp      The timestamp of a message to reply to
     *
     * @return bool|string
     */
    public function send($recipients, string $message, $attachments = [], int $timeStamp=0, $quoteAuthor='', $quoteMessage='', $style=''){
        if(empty($recipients)){
            return new WP_Error('Signal', 'You should submit at least one recipient');
        }

        $group  = false;

        if(!is_array($recipients)){
            // first character is a +
            if(strpos( $recipients , '+' ) === 0){
                $recipient  = $recipients;
            }else{
                $group  = true;
            }
        }else{
            foreach($recipients as $recipient){
                $this->send($recipients, $message, $attachments, $timeStamp);
            }
            
            return;
        }

        extract($this->parseMessageLayout($message));

        $params     = [
            "message"       => $message
        ];

        if($group){
            $params['groupId']  = $recipients;
        }else{
            $params['recipient']  = $recipients;
        }

        if(!empty($attachments)){
            if(!is_array($attachments)){
                $attachments    = [$attachments];
            }

            foreach($attachments as $index => $attachment){
                if(!file_exists($attachment)){
                    unset($attachments[$index]);
                }
            }
        
            if(!empty($attachments)){
                $params["attachments"]  = array_values($attachments);
            }
        }

        if(!empty($timeStamp) && !empty($quoteAuthor) && !empty($quoteMessage)){
            $params['quoteTimestamp']   = $timeStamp;
       
            $params['quoteAuthor']   = $quoteAuthor;
        
            $params['quoteMessage']   = $quoteMessage;
        }

        if(!empty($style)){
            $params['textStyle']   = $style;
        }

        $result   =  $this->doRequest('send', $params);

        if(!empty($result->timestamp)){
            $ownTimeStamp = $result->timestamp;
        }

        if(is_numeric($ownTimeStamp)){
            $this->addToMessageLog($recipient, $message, $ownTimeStamp);
        }else{
            SIM\printArray($ownTimeStamp);
        }

        return $ownTimeStamp;
    }

    /**
     * Compliancy function
     */
    public function sendGroupMessage($message, $groupId, $attachments=[], int $timeStamp=0, $quoteAuthor='', $quoteMessage='', $style=''){
        return $this->send($groupId, $message, $attachments, $timeStamp, $quoteAuthor, $quoteMessage);
    }

    public function markAsRead($recipient, $timestamp){
        $params = [
            "recipient"         => $recipient,
            "targetTimestamp"   => $timestamp,
            "type"              => "read"
        ];
        
        return $this->doRequest('sendReceipt', $params);
    }

    /**
     * List Groups
     *
     * @param   bool    $detailed   wheter to get details
     * @param   string  $groupId    The group id of a specif group you want details of
     *
     * @return array|string
     */
    public function listGroups($detailed = false, $groupId = false, $force=false){
        if(!empty($this->groups) && !$force){
            return $this->groups;
        }

        $transientGroups    = get_transient('sim-signal-groups');

        if($transientGroups && !$force){
            return $transientGroups;
        }

        $params = [];

        if($detailed){
            $params['detailed'] = 1; 
        }

        if($groupId){
            $params['groupId'] = $groupId; 
        }
        
        $result = $this->doRequest('listGroups');

        if(empty($this->error) && !empty($result)){
            $this->groups   = $result;
            set_transient('sim-signal-groups', $result, WEEK_IN_SECONDS);
        }

        return $this->groups;
    }

    /**
     * Deletes a message
     *
     * @param   int             $targetSentTimestamp    The original timestamp
     * @param   string|array    $recipients             The original recipient(s)
     */
    public function deleteMessage($targetSentTimestamp, $recipients){

        if(is_array($recipients)){
            foreach($recipients as $recipient){
                $this->deleteMessage($targetSentTimestamp, $recipient);
            }
        }

        $param = [
            "targetTimestamp"   => $targetSentTimestamp
        ];

        if(str_contains($recipients, '+')){
            $param['recipient'] = $recipients;
        }else{
            $param['groupId']   = $recipients;
        }

        SIM\printArray($param, true);
        
        $result = $this->doRequest('remoteDelete', $param);

        if(is_numeric($result)){
            $this->markAsDeleted($targetSentTimestamp);
        }else{
            SIM\printArray($result,true);
        }

        return $result;
    }

    /**
     * Sends a typing indicator to number
     *
     * @param   string  $recipient  The phonenumber
     * @param   int     $timestamp  Optional timestamp of a message to mark as read
     *
     * @return string               The result
     */
    public function sentTyping($recipient, $timestamp='', $groupId=''){
        if(!empty($timestamp)){
            // Mark as read
            $this->markAsRead($recipient, $timestamp);
        }

        $params = [
            "recipient" => $recipient
        ];

        if(!empty($groupId)){
            $params["groupId"] = $groupId;
        }

        return $this->doRequest('sendTyping', $params);
    }

    /**
     * Dummy function for complicance to signal-dbus
     */
    public function sendGroupTyping($recipient, $timestamp='', $groupId=''){
        $this->sentTyping($recipient, $timestamp, $groupId);
    }

    public function sendMessageReaction($recipient, $timestamp, $groupId='', $emoji=''){
        if(empty($emoji)){
            $emoji  = "🦘";
        }

        $params = [
            "recipient"         => $recipient,
            "emoji"             => $emoji,
            "targetAuthor"      => $recipient,
            "targetTimestamp"   => $timestamp
        ];

        if(!empty($groupId)){
            $params['groupId']  = $groupId;
        }

        return $this->doRequest('sendReaction', $params);
    }

    /**
     * Update the name and avatar image visible by message recipients for the current users.
     * The profile is stored encrypted on the Signal servers.
     * The decryption key is sent with every outgoing messages to contacts.
     *
     * @param string    $name           New name visible by message recipients
     * @param string    $avatarPath     Path to the new avatar visible by message recipients
     * @param bool      $removeAvatar   Remove the avatar visible by message recipients
     * 
     * @return bool|string
     */
    public function updateProfile(string $name, string $avatarPath = null, bool $removeAvatar = false){

        $params = [];

        if(!empty($name)){
            $params['name'] = $name;
        }

        if(!empty($avatarPath) && file_exists($avatarPath)){
            $params['avatar'] = $avatarPath;
        }

        if($removeAvatar){
            $params['removeAvatar'] = true;
        }

        return $this->doRequest('updateProfile', $params);
    }

    /**
     * Submit a challenge
     *
     * @param   string  $challenge  The challenge string
     * @param   string  $captcha    The captcha as found on https://signalcaptchas.org/challenge/generate.html
     *
     * @return string               The result
     */
    public function submitRateLimitChallenge($challenge, $captcha){
        $params = [
            "challenge" => $challenge,
            "captcha"   => $captcha
        ];

        return $this->doRequest('updateProfile', $params);
    }

    /**
     * gets the invitation link of a specific group
     */
    public function getGroupInvitationLink($groupPath){
        $result = $this->listGroups(true, $groupPath);

        SIM\printArray($result, true);

        return $result[0]['groupInviteLink'];
    }

    public function findGroupName($id){
        $groups = (array)$this->listGroups();

        foreach($groups as $group){
            if(gettype($group) == 'string'){
                return $group;
            }

            if($group->id == $id){
                return $group->name;
            }
        }

        return '';
    }
}
