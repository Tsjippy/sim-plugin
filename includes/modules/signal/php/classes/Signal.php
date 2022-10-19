<?php

namespace SIM\SIGNAL;
use SIM;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use GuzzleHttp;
use mikehaertl\shellcommand\Command;

if(!class_exists('BaconQrCode\Renderer\ImageRenderer')){
    return new \WP_Error('2fa', "bacon-qr-code interface does not exist. Please run 'composer require bacon/bacon-qr-code'");
}

/* Install java apt install openjdk-17-jdk -y
export VERSION=0.11.3
wget https://github.com/AsamK/signal-cli/releases/download/v"${VERSION}"/signal-cli-"${VERSION}"-Linux.tar.gz
sudo tar xf signal-cli-"${VERSION}"-Linux.tar.gz -C /opt
sudo ln -sf /opt/signal-cli-"${VERSION}"/bin/signal-cli /usr/local/bin/ */


class Signal {

    const FORMAT_JSON = 'json';
    const FORMAT_PLAIN_TEST = 'plain-text';

    /**
     * @var string Username is phone number starting with country code starting with "+"
     */
    protected $username;

    /**
     * @var string json|plain-text Many sub-commands still don't support json.
     */
    protected $format;

    public function __construct(string $format){
        require_once( __DIR__  . '/../../lib/vendor/autoload.php');
        
        $this->format = $format;

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

        $this->username           = SIM\getModuleOption(MODULE_SLUG, 'phone');

        $this->command = new Command([
            'command' => $this->path,
            // This is required for binary to be able to find libzkgroup.dylib to support Group V2
            'procCwd' => dirname($this->path),
        ]);

        $this->checkPrerequisites();
    }

    /**
     * Register a phone number with SMS or voice verification. Use the verify command to complete the verification.
     * Default verify with SMS
     * @param bool $voiceVerification The verification should be done over voice, not SMS.
     * @param string $captcha - from https://signalcaptchas.org/registration/generate.html
     * @return bool
     */
    public function register(bool $voiceVerification = false, string $captcha = ''): bool
    {
        $this->command->addArg('-u', $this->username);

        $this->command->addArg('register');

        if($voiceVerification){
            $this->command->addArg('--voice', null);
        }


        if(!empty($captcha)){
            $this->command->addArg('--captcha', $captcha);
        }

        return $this->command->execute();
    }

    /**
     * Disable push support for this device, i.e. this device won’t receive any more messages.
     * If this is the master device, other users can’t send messages to this number anymore.
     * Use "updateAccount" to undo this. To remove a linked device, use "removeDevice" from the master device.
     * @return bool
     */
    public function unregister(): bool
    {
        $this->command->addArg('unregister', null);

        return $this->command->execute();
    }

    /**
     * Uses a list of phone numbers to determine the statuses of those users.
     * Shows if they are registered on the Signal Servers or not.
     * @param string|array $recipients One or more numbers to check.
     * @return string
     */
    public function getUserStatus($recipients): string
    {
        if(!is_array($recipients)){
            $recipients    = [$recipients];
        }

        $this->command->addArg('getUserStatus', $recipients);

        $this->command->execute();

        return $this->command->getOutput(false);
    }

    /**
     * Verify the number using the code received via SMS or voice.
     * @param string $code The verification code e.g 123-456
     * @return bool
     */
    public function verify(string $code): bool
    {
        $this->command->addArg('-u', $this->username);
        $this->command->addArg('verify', $code);

        return $this->command->execute();
    }

    /**
     * Send a message to another user or group
     * @param string|array $recipients Specify the recipients’ phone number
     * @param string $message Specify the message, if missing, standard input is used
     * @param string $groupId Specify the recipient group ID in base64 encoding
     * @return bool
     */
    public function send($recipients, string $message, string $groupId = null): bool
    {
        if(!is_array($recipients)){
            $recipients    = [$recipients];
        }

        $this->command->addArg('-u', $this->username);        
        $this->command->addArg('send', $recipients);

        $this->command->addArg('-m', $message);

        if($groupId){
            $this->command->addArg('-g', $groupId);
        }

        return $this->command->execute();
    }

    /**
     * Update the name and avatar image visible by message recipients for the current users.
     * The profile is stored encrypted on the Signal servers.
     * The decryption key is sent with every outgoing messages to contacts.
     * @param string $name New name visible by message recipients
     * @param string $avatarPath Path to the new avatar visible by message recipients
     * @param bool $removeAvatar Remove the avatar visible by message recipients
     * @return bool
     */
    public function updateProfile(string $name, string $avatarPath = null, bool $removeAvatar = false): bool
    {
        $this->command->addArg('updateProfile', null);

        $this->command->addArg('--name', $name);

        if($avatarPath){
            $this->command->addArg('--avatar', $avatarPath);
        }

        if($removeAvatar){
            $this->command->addArg('--removeAvatar', null);
        }

        return $this->command->execute();
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
        if(empty($name)){
            $name   = get_bloginfo('name');
        }

        $this->command->nonBlockingMode = false;

        $this->command->addArg('link', null);

        if($name) {
            $this->command->addArg('-n', $name);
        }

        // TODO: Better response handling
        $randFile = sys_get_temp_dir().'/'.rand() . time() . '.device';
        $this->command->addArg(" > $randFile 2>&1 &", null, false); // Ugly hack!
        sleep(1); // wait for file to get populated

        $this->command->execute();

        $link   = '';
        while(empty($link)){
            $link   = file_get_contents($randFile);
        }

        SIM\clearOutput();
        header("X-Accel-Buffering: no");
        header('Content-Encoding: none');

        // Turn on implicit flushing
        ob_implicit_flush(1);

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

    /**
     * Link another device to this device.
     * Only works, if this is the master device
     * @param string $uri Specify the uri contained in the QR code shown by the new device.
     *                    You will need the full uri enclosed in quotation marks, such as "tsdevice:/?uuid=…​.."
     * @return bool
     */
    public function addDevice(string $uri): bool
    {
        $this->command->addArg('--uri', $uri);

        return $this->command->execute();
    }

    /**
     * Show a list of connected devices
     * @return string
     */
    public function listDevices(): string
    {
        $this->command->addArg('listDevices', null);

        // This command doesn't support JSON format

        $this->command->execute();

        return $this->command->getOutput();
    }

    /**
     * Remove a connected device. Only works, if this is the master device
     * @param int $deviceId Specify the device you want to remove. Use listDevices to see the deviceIds
     * @return bool
     */
    public function removeDevice(int $deviceId): bool
    {
        $this->command->addArg('removeDevice', null);

        $this->command->addArg('-d', $deviceId);

        return $this->command->execute();
    }

    /**
     * Update the account attributes on the signal server.
     * Can fix problems with receiving messages
     * @return bool
     */
    public function updateAccount(): bool
    {
        $this->command->addArg('updateAccount', null);

        return $this->command->execute();
    }

    /**
     * Private function to create group, update group and add members in the group
     * @param string|null $name Specify the new group name
     * @param array $members Specify one or more members to add to the group
     * @param string|null $avatarPath Specify a new group avatar image file
     * @param string|null $groupId Specify the recipient group ID in base64 encoding.
     *                             If not specified, a new group with a new random ID is generated
     * @return bool
     */
    private function _createOrUpdateGroup(string $name = null, array $members = [], string $avatarPath = null, string $groupId = null): bool
    {
        $this->command->addArg('updateGroup', null);

        if(!empty($groupId)){
            $this->command->addArg('-g', $groupId);
        }

        if($name){
            $this->command->addArg('-n', $name);
        }

        if(!empty($members)){
            $this->command->addArg('-m', $members);
        }

        if(!empty($avatarPath)){
            $this->command->addArg('-a', $avatarPath);
        }

        return $this->command->execute();
    }

    /**
     * Create Group
     * @param string $name
     * @param array $members
     * @param string|null $avatarPath
     * @return bool
     */
    public function createGroup(string $name, array $members = [], string $avatarPath = null): bool
    {
        return $this->_createOrUpdateGroup($name, $members, $avatarPath);
    }

    public function updateGroup(string $groupId, string $name = null, array $members = [], string $avatarPath = null): bool
    {
        return $this->_createOrUpdateGroup($name, $members, $avatarPath, $groupId);
    }

    public function addMembersToGroup(string $groupId, array $members)
    {
        return $this->_createOrUpdateGroup(null,$members,null,$groupId);
    }

    /**
     * List Groups
     * @return string
     */
    public function listGroups(): string
    {
        $this->command->addArg('-o', $this->format);

        $this->command->addArg('listGroups', null);

        $this->command->execute();

        return $this->command->getOutput();
    }

    /**
     * Join a group via an invitation link.
     * To be able to join a v2 group the account needs to have a profile (can be created with the updateProfile command)
     * @param string $uri The invitation link URI (starts with https://signal.group/#)
     * @return bool
     */
    public function joinGroup(string $uri): bool
    {
        $this->command->addArg('joinGroup', null);

        $this->command->addArg('--uri', $uri);

        return $this->command->execute();
    }

    /**
     * Send a quit group message to all group members and remove self from member list.
     * If the user is a pending member, this command will decline the group invitation
     * @param string $groupId Specify the recipient group ID in base64 encoding
     * @return bool
     */
    public function quitGroup(string $groupId): bool
    {
        $this->command->addArg('quitGroup', null);

        $this->command->addArg('-g', $groupId);

        return $this->command->execute();
    }

    /**
     * Query the server for new messages.
     * New messages are printed on standard output and attachments are downloaded to the config directory.
     * In json mode this is outputted as one json object per line
     * @param int $timeout Number of seconds to wait for new messages (negative values disable timeout). Default is 5 seconds
     * @return string
     */
    public function receive(int $timeout = 5) : string
    {
        $this->command->addArg('-o', $this->format);

        $this->command->addArg('receive', null);

        $this->command->addArg('-t', $timeout);

        $this->command->execute();

        return $this->command->getOutput();
    }

    /**
     * Get Command to further get output, error or more details of the command.
     * @return Command
     */
    public function getCommand()
    {
        return $this->command;
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
}
