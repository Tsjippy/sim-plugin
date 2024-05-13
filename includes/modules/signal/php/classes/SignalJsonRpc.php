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


class SignalJsonRpc{
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

    public function __construct(){
        global $wpdb;
        require_once( MODULE_PATH  . 'lib/vendor/autoload.php');

        $this->postUrl  = "127.0.0.1:8080/api/v1/rpc";

        $this->os       = 'macOS';
        $this->basePath = WP_CONTENT_DIR.'/signal-cli';
        if (!is_dir($this->basePath )) {
            mkdir($this->basePath , 0777, true);
        }

        $this->attachmentsPath  = $this->basePath.'/attachments';
        if (!is_dir($this->attachmentsPath )) {
            mkdir($this->attachmentsPath , 0777, true);
        }

        // .htaccess to prevent access
        if(!file_exists($this->basePath.'/.htaccess')){
            file_put_contents($this->basePath.'/.htaccess', 'deny from all');
        }
        if(!file_exists($this->basePath.'/index.php')){
            file_put_contents($this->basePath.'/index.php', '<?php');
        }
        if(!file_exists($this->attachmentsPath.'/.htaccess')){
            file_put_contents($this->attachmentsPath.'/.htaccess', 'allow from all');
        }

        if(str_contains(php_uname(), 'Windows')){
            $this->os               = 'Windows';
            $this->basePath         = str_replace('\\', '/', $this->basePath);
        }elseif(str_contains(php_uname(), 'Linux')){
            $this->os               = 'Linux';
        }
        
        $this->programPath      = $this->basePath.'/program/';
        if (!is_dir($this->programPath )) {
            mkdir($this->programPath , 0777, true);
            SIM\printArray("Created $this->programPath");
        }

        // check permissions
        $path   = $this->programPath.'/signal-cli';
        if(!is_executable($path)){
            chmod($path, 0555);
        }

        $this->phoneNumber      = '';
        if(file_exists($this->basePath.'/phone.signal')){
            $this->phoneNumber      = trim(file_get_contents($this->basePath.'/phone.signal'));
        }

        $this->path             = $this->programPath.'bin/signal-cli';

        if(!file_exists($this->path) && file_exists($this->programPath.'signal-cli')){
            $this->path = $this->programPath.'signal-cli';
        }

        $this->daemon           = false;

        $this->tableName        = $wpdb->prefix.'sim_signal_messages';

        $this->receivedTableName= $wpdb->prefix.'sim_received_signal_messages';

        $phone                  = str_replace('+', '', $this->phoneNumber);

        $homefolder   = [];
        exec("bash -c 'echo \$HOME'", $homefolder);
        if(!empty($homefolder)){
            $this->homeFolder   = $homefolder[0];
        }

        // Check daemon
        $this->daemonIsRunning();
        $this->startDaemon();

        $this->socket   = stream_socket_client('unix:////home/simnige1/sockets/signal', $errno, $this->error);

        if(!$this->socket){
            SIM\printArray("$errno: $this->error", true);
        }
    }

    /**
     * Create the sent messages table if it does not exist
     */
    public function createDbTable(){
		global $wpdb;

		if ( !function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . '/wp-admin/install-helper.php';
		}
		
		//only create db if it does not exist
		$charsetCollate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->tableName} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            timesend bigint(20) NOT NULL,
            recipient longtext NOT NULL,
            message longtext NOT NULL,
            status text NOT NULL,
            PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->tableName, $sql );

        $sql = "CREATE TABLE {$this->receivedTableName} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            timesend bigint(20) NOT NULL,
            sender longtext NOT NULL,
            message longtext NOT NULL,
            chat longtext,
            attachments longtext,
            status text NOT NULL,
            PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->receivedTableName, $sql );
	}

    /**
     * Adds a send message to the log
     */
    private function addToMessageLog($recipient, $message, $timestamp){
        if(empty($recipient) || empty($message)){
            return;
        }
        
        global $wpdb;

        $wpdb->insert(
            $this->tableName,
            array(
                'timesend'      => $timestamp,
                'recipient'     => $recipient,
                'message'		=> $message,
            )
        );

        return $wpdb->insert_id;
    }

    /**
     * Adds a received message to the log
     *
     * @param   string  $sender     The sender phonenumber
     * @param   string  $message    The message sent
     * @param   int     $time       The time the message was sent
     * @param   string  $chat       The groupId is sent in a group chat, defaults to $sender for private chats
     */
    public function addToReceivedMessageLog($sender, $message, $time, $chat='', $attachments=''){
        if(empty($sender) || empty($message)){
            return;
        }

        if(empty($chat) ){
            $chat   = $sender;
        }
        
        global $wpdb;

        $wpdb->insert(
            $this->receivedTableName,
            array(
                'timesend'      => $time,
                'sender'        => $sender,
                'message'	    => $message,
                'chat'          => $chat,
                'attachments'   => maybe_serialize($attachments)
            )
        );

        return $wpdb->insert_id;
    }

    /**
     * Retrieves the messages from the log
     *
     * @param   int     $amount     The amount of rows to get per page, default 100
     * @param   int     $page       Which page to get, default 1
     * @param   int     $minTime    Time after which the message has been send, default empty
     * @param   int     $maxTime    Time before which the message has been send, default empty
     */
    public function getMessageLog($amount=100, $page=1, $minTime='', $maxTime=''){
        global $wpdb;

        $startIndex = 0;

        if($page > 1){
            $startIndex         = ($page - 1) * $amount;
        }

        $totalQuery = "SELECT COUNT(id) as total FROM $this->tableName";
        $query      = "SELECT * FROM $this->tableName";

        if(!empty($minTime)){
            $totalQuery .= " WHERE timesend > {$minTime}000";
            $query      .= " WHERE timesend > {$minTime}000";
        }

        if(!empty($maxTime)){
            $combinator = 'AND';
            if(empty($minTime)){
                $combinator     = 'WHERE';
            }

            $totalQuery .= " $combinator timesend < {$maxTime}000";
            $query      .= " $combinator timesend < {$maxTime}000";
        }

        $query      .= " ORDER BY `timesend` DESC LIMIT $startIndex,$amount;";

        $this->totalMessages    = $wpdb->get_var($totalQuery);

        if($this->totalMessages < $startIndex){
            return [];
        }
        
        return $wpdb->get_results( $query );
    }

        /**
     * Retrieves the messages from the log
     *
     * @param   int     $amount     The amount of rows to get per page, default 100
     * @param   int     $page       Which page to get, default 1
     * @param   int     $minTime    Time after which the message has been send, default empty
     * @param   int     $maxTime    Time before which the message has been send, default empty
     */
    public function getReceivedMessageLog($amount=100, $page=1, $minTime='', $maxTime=''){
        global $wpdb;

        $startIndex = 0;

        if($page > 1){
            $startIndex         = ($page - 1) * $amount;
        }

        $totalQuery = "SELECT COUNT(id) as total FROM $this->receivedTableName";
        $query      = "SELECT * FROM $this->receivedTableName";

        if(!empty($minTime)){
            $totalQuery .= " WHERE timesend > {$minTime}000";
            $query      .= " WHERE timesend > {$minTime}000";
        }

        if(!empty($maxTime)){
            $combinator = 'AND';
            if(empty($minTime)){
                $combinator     = 'WHERE';
            }

            $totalQuery .= " $combinator timesend < {$maxTime}000";
            $query      .= " $combinator timesend < {$maxTime}000";
        }

        $query      .= "  ORDER BY `chat` ASC, `timesend` DESC LIMIT $startIndex,$amount;";

        $this->totalMessages    = $wpdb->get_var($totalQuery);

        if($this->totalMessages < $startIndex){
            return [];
        }
        
        return $wpdb->get_results( $query );
    }

    /**
     * Gets the latest messages from the log for a specific user
     *
     * @param   string  $phoneNumber    The phonenumber or user id
     *
     * @return  array                   The messages
     */
    public function getSendMessagesByUser($phoneNumber){
        if(get_userdata($phoneNumber)){
            $phoneNumber    = get_user_meta($phoneNumber, 'signalnumber', true);

            if(!$phoneNumber){
                return;
            }
        }

        global $wpdb;

        $query      = "SELECT * FROM $this->tableName WHERE `recipient` = '$phoneNumber' ORDER BY `timesend` DESC LIMIT 5; ";

        return $wpdb->get_results( $query );
    }

    /**
     * Gets the latest messages from the log for a specific user
     *
     * @param   int  $timestamp         The timestamp
     *
     * @return  string                   The message
     */
    public function getSendMessageByTimestamp($timestamp){
        global $wpdb;

        $query      = "SELECT message FROM $this->tableName WHERE `timesend` = '$timestamp'";

        return $wpdb->get_var( $query );
    }

    /**
     * Deletes messages from the log
     *
     * @param   string     $maxDate     The date after which messages should be kept Should be in yyyy-mm-dd format
     *
     * @return  string                  The message
     */
    public function clearMessageLog($maxDate){
        global $wpdb;

        $timeSend   = strtotime(get_gmt_from_date($maxDate, 'Y-m-d'));

        // remove sent messages
        $query      = "DELETE FROM $this->tableName WHERE `timesend` < {$timeSend}000";

        $result1    = $wpdb->query( $query );

        // remove attachment files
        $query      = "SELECT * FROM $this->receivedTableName WHERE `timesend` < {$timeSend}000 AND `attachments` is NOT NULL; ";
        foreach($wpdb->get_results($query) as $result){
            $attachments    = unserialize($result->attachments);

            foreach($attachments as $attachment){
                if(file_exists($attachment)){
                    unlink($attachment);
                }
            }
        }

        // remove received messages
        $query      = "DELETE FROM $this->receivedTableName WHERE `timesend` < {$timeSend}000";

        $result2    = $wpdb->query( $query );

        return $result1 && $result2;
    }

    /**
     * Marks a specific message as deleted in the log
     *
     * @param   int     $timesend     The date after which messages should be kept Should be in yyyy-mm-dd format
     *
     * @return  string                  The message
     */
    public function markAsDeleted($timeStamp){
        global $wpdb;

        $query      = "UPDATE $this->tableName SET `status` = 'deleted' WHERE timesend = $timeStamp";

        return $wpdb->query( $query );
    }

    /**
     * Get the response to a request
     */
    public function getRequestResponse($id){

        $response = '';

        SIM\printArray("getting response", true);

        while (!feof($this->socket)) {
            $response .= fread($this->socket, 1024);
            SIM\printArray($response, true);
            $stream_meta_data = stream_get_meta_data($this->socket); //Added line
            SIM\printArray($stream_meta_data, true);
            if($stream_meta_data['unread_bytes'] <= 0) break; //Added line
        }
        flush();

        SIM\printArray($response, true);

        $json   = json_decode($response);

        SIM\printArray($json, true);

        if(!isset($json->result) && !isset($json->error)){
            SIM\printArray($json);
            //$this->getRequestResponse($id);
        }

        return $json;
    }

    /**
     * Performs the json RPC request
     */
    public function doRequest($method, $params=[]){
        if(!$this->socket){
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

        SIM\printArray($json);    

        fwrite($this->socket, $json);         

        flush();
        ob_flush();

        //stream_socket_recvfrom
        $response = $this->getRequestResponse($id);

        //SIM\printArray($response, true);

        if(!empty($response->error)){
            SIM\printArray("Got error {$response->error->message} For command $json");

            $this->error    =  new \WP_Error('sim-signal', $response->error->message . " For command $json");

            return $this->error;
        }

        if(!is_object($response) || empty($response->result)){
            //SIM\printArray("Got faulty result $response");
            return false;
        }

        return $response->result;
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
     * @param   string          $recipient Number to check.
     * @return  string
     */
    public function isRegistered($recipient){

        $params     = [
            "number"     => [$recipient]
        ];

        return $this->doRequest('getUserStatus', $params);
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
    public function send($recipients, string $message, $attachments = '', $timeStamp=''){
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

        $params     = [
            "recipient"     => $recipient,
            "message"       => $message
        ];

        if($group){
            $params['groupId']  = $recipients;
        }

        if(is_array($attachments)){
            foreach($attachments as $index => $attachment){
                if(!file_exists($attachment)){
                    unset($attachments[$index]);
                }
            }
        
            if(!empty($attachments)){
                if(count($attachments) == 1){
                    $params["attachment"]   = $attachments;
                }else{
                    $params["attachments"]  = $attachments;
                }
            }
        }

        if(!empty($timeStamp)){
            $params['quoteTimestamp']   = $timeStamp;
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

    public function markAsRead($recipient, $timestamp){
        $params = [
            "recipient"         => $recipient,
            "targetTimestamp"   => $timestamp
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
    public function listGroups($detailed = false, $groupId = false){
        if(!empty($this->groups)){
            return $this->groups;
        }

        $params = [];

        if($detailed){
            $params['detailed'] = 1; 
        }

        if($groupId){
            $params['groupId'] = $groupId; 
        }
        
        $result = $this->doRequest('listGroups');

        SIM\printArray($result, true);

        if(empty($this->error)){
            SIM\printArray($result, true);

            preg_match_all('/struct {(.*?)array of bytes \[(.*?)\](.*?)}/s', $result, $matches);

            $result = [];
            foreach($matches[2] as $key=>$match){
                $group = new stdClass();
                $id = trim(str_replace("\n", '', $match));

                $idString   = [];
                foreach(explode(' ', $id) as $digid){
                    if(empty($digid)){
                        continue;
                    }

                    $idString[] = hexdec($digid);
                }

                $idString   = implode(',', $idString);

                $group->{"id"}      = $idString;
                $group->{"name"}    = trim(str_replace("\n", '', $matches[3][$key]));
                $group->{"path"}    = trim(str_replace("\n", '', $matches[1][$key]));

                $result[]           = $group;
            }
        }

        $this->groups   = $result;

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

        $params = [
            "recipient"         => $recipient,
            "targetTimestamp"   => $targetSentTimestamp
        ];

        if(str_contains($recipients, '+')){
            $param['recipient'] = $recipients;
        }else{
            $param['groupId']   = $recipients;
        }
        
        $result = $this->doRequest('remoteDelete', $params);

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

    /**
     * Converts a base64 group id to an array of bytes
     *
     * @param   string  $id       the base64encode group id
     *
     * @return  string            A comma seperated list of bytes
     */
    public function groupIdToByteArray($id){
        $id = str_replace('_', '/', $id);

        $id = base64_decode($id);

        $id = unpack('C*',$id);

        $id = implode(',', $id);
        return $id;
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

    private function daemonIsRunning(){
        // check if running
        $command = new Command([
            'command' => "ps -ef | grep -v grep | grep -P 'signal-cli.*daemon'"
        ]);

        $command->execute();

        $result = $command->getOutput();
        if(empty($result)){
            $this->daemon   = false;
        }else{
            $this->daemon   =  true;

            // Running daemon but not for this website
            if(!str_contains($result, $this->basePath)){
                $this->error    = 'The daemon is started but for another website in this user account.<br>';
                $this->error   .= "You can send messages just fine, but not receive any.<br>";
                $this->error   .= "To enable receiving messages add this to your crontab (crontab -e): <br>";
                $this->error   .= "<code>@reboot $this->path -o json --trust-new-identities=always daemon --http --send-read-receipts &</code><br>";
                $this->error   .= "Then reboot your server";
            }
        }
    }

    public function startDaemon(){
        if($this->os == 'Windows'){
            return;
        }

        if(!$this->daemon){
            $cli        = "$this->path -o json --trust-new-identities=always daemon --socket --send-read-receipts;";
            
            $command = new Command([
                'command' => "bash -c '$cli' &"
            ]);

            $command->execute();
        }
 
        $this->daemon   = true;
    }
}
