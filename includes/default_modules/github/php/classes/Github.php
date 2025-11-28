<?php
namespace SIM\GITHUB;
use SIM;
use Github\Exception\ApiLimitExceedException;
use Github\Api\Repository\Releases;
use Github\Api\Repository\Contents;
use Github\Client;
use WP_Error;

class Github{
    public  $client;
    public  $token;
    public  $authenticated;
    public $repo;
    public $releases;
    public $contents;

    public function __construct() {
        $this->client 	        = new \Github\Client(); 
        $this->token            = '';   
        $this->authenticated    = false; 
        /** @var \Github\Api\Repository $repo **/
        $this->repo             = $this->client->api('repo');
        $this->releases         = new Releases($this->client);
        $this->contents         = new Contents($this->client);
    }

    /**
     * Authenticate using a token
     * Create a token here: https://github.com/settings/tokens/new
     *
     * @param   string  $token  The token
     */
    private function authenticate(){
        if($this->authenticated){
            // Already authenticated
            return true;
        }

        if(empty($this->token)){
            $this->token    = SIM\getModuleOption(MODULE_SLUG, 'token');

            if(!$this->token){
                return new WP_Error('Github', 'Please set a Github token');
            }
        }
        $this->client->authenticate($this->token, null, \Github\AuthMethod::ACCESS_TOKEN);

        $this->authenticated    = true;
    }

    /**
     * Retrieves the latest github release information from cache or github
     * 
     * @param	string	$author		The github author. Default 'Tsjippy'
     * @param	string	$repo	    The github repo name
     * @param   bool    $force      Whether to skip the cached result. Default false
     *
     * @return	array|WP_Error	    Array containing information about the latest release or an WP_Error object
     */
    public function getLatestRelease($author='tsjippy', $repo=SIM\PLUGINNAME, $force=false){
        if(isset($_GET['update']) || $force){
            $release	= false;
        }else{
            //check db version
            $release    = get_transient("$author-$repo");
        }
        
        // if not in transient
        if($release === false){
            $release    = '';

            try{
                $release 	    = $this->releases->latest($author, $repo);
            } catch (ApiLimitExceedException $e) {
                if(!$this->authenticated){
                    $this->authenticate();

                    if($this->authenticated){
                        return $this->getLatestRelease($author, $repo, $force);
                    }
                }
            }catch(\Exception $e){
                if($e->getMessage() == 'Not Found'){
                    if(!$this->authenticated){
                        // authenticate
                        $this->authenticate();
                        
                        // rerun
                        return $this->getLatestRelease($author, $repo, $force);
                    }
                }
            }            

            //printArray($release);
            $this->client->removeCache();
            
            // Store for 1 hours
            set_transient( "$author-$repo", $release, HOUR_IN_SECONDS );

            if(isset($e)){
                if($e->getCode() != 404){
                    SIM\printArray($e);
                }
                return new \WP_Error('update', $e->getMessage());
            }
        }
        return $release;
    }

    /**
     * Downloads and unzips the latest release from a given github location to a given path
     *
     * @param	string	$author		The github author. Default 'Tsjippy'
     * @param	string	$repo	    The github repo name
     * @param	string	$path		The destination path
     * @param   bool    $force      Whether to skip the cached result. Default false
     * 
     * @return	true|WP_Error       True on success, WP_Error object on failure
     */
    public function downloadFromGithub($author='Tsjippy', $repo=SIM\PLUGINNAME, $path='', $force=false){
        if(empty($path)){
            return new WP_Error('Github', 'Path canot be empty');
        }

        $oldVersion	= -1;
        if (defined("SIM\\$repo\\MODULE_VERSION")) {
            $oldVersion	= constant("SIM\\$repo\\MODULE_VERSION");
        }

        // Get latest release info
        $release	= $this->getLatestRelease($author, $repo, $force);

        if(is_wp_error($release) || empty($release)){
            return $release;
        }

        // download latest release
        try{
            $zipContent = $this->releases->assets()->show($author, $repo, $release['assets'][0]['id'], true);
        }catch (\Exception $e){
            if($e->getCode() == 404){
                // Get a new download link, bypass transient
                $release	= $this->getLatestRelease($author, $repo, true);
                try{
                    $zipContent = $this->releases->assets()->show($author, $repo, $release['assets'][0]['id'], true);
                }catch (\Exception $e){
                    SIM\printArray("Could not find asset with id {$release['assets'][0]['id']} for $author-$repo");
                    SIM\printArray($release['assets']);
                }
            }else{
                SIM\printArray($e);
            }

            if(!$zipContent){
                return new WP_Error('Github', "Failed to download the latest release for $author-$repo<br><br>".$e->getMessage()."<br><br>Does the zip file exist in the release?");
            }
        }

        if($this->contents->exists($author, $repo, "preupdate/pre_update.php")){
            $fileContent    = $this->contents->download($author, $repo, "preupdate/pre_update.php");

            $tempFilePath = wp_tempnam(); 
            file_put_contents($tempFilePath, $fileContent);
            require_once($tempFilePath);

            // Action should be defined in the file
            do_action("sim-github-before-updating-module-$repo", $oldVersion, $release['tag_name']);

            // Remove the file
            unlink($tempFilePath);
        }
        
        $tmpZipFile = tmpfile();
        fwrite($tmpZipFile, $zipContent);
        $zipFile    = stream_get_meta_data($tmpZipFile)['uri'];
        $zip = new \ZipArchive();
        $zip->open($zipFile);

        // if the folder already exists, remove it, to accomodate file deletions
        if(is_dir($path)){
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
			global $wp_filesystem;
			$result				= $wp_filesystem->rmdir($path, true);
        }

        // recreate the folder
        wp_mkdir_p($path);

        // Extract the zipfile
        $result = $zip->extractTo($path);

        // close the archive and delete the file
        $zip->close();

        if(!$result){
            SIM\printArray("Unzip failed to $path");
            
            return new WP_Error('Github', "Unzip failed for $repo" );
        }
        
        fclose($tmpZipFile);

        // Run potential pre-update functions
        if(file_exists("$path/php/pre_update.php")){
            // Load the file
            require_once("$path/php/pre_update.php");

            // Delete file so that we can suply a new one the next time
            wp_delete_file("$path/php/pre_update.php");
        }

        // run the update action. We should do so with the updated files so we do it via a single event.
        if($oldVersion > 0){
            wp_schedule_single_event(time(), 'sim-after-module-update', [$repo, $oldVersion]);
        }

        return true;
    }

    /**
     * Read the data of a file on github
     * 
     * @param   string  $fileName   The filename
     * 
     * @return  string|false        The content or false on failure
     */
    public function getFileContents($author, $repo, $fileName){
        try{
            $file   = $this->contents->show($author, $repo, $fileName);
            
            if(!empty($file)){
                $content	= base64_decode($file['content']);
                //convert to html
                $parser 	= new \Michelf\MarkdownExtra;
                $content	= $parser->transform($content);
            }
        }catch (\Exception $e) {
            // 404 is not found
            if($e->getCode() != 404){
                SIM\printArray($e);
            }

            $content    = false;
        }

        return $content;
    }

    /**
     * Parses plugin info from github
     *
     * @param   string  $pluginFilePath     The main file of the plugin you want to have info of
     * @param   string  $author             The github author
     * @param   string  $repo               The github repository, default SIM\PLUGINNAME
     * @param   array   $extraData          Extra data to include an array of active_installs, donate_link, rating, ratings banners, tested
     * 
     * @return  object                      The details object
     */
    public function pluginData($pluginFilePath, $author, $repo=SIM\PLUGINNAME, $extraData=[]){
        if( ! function_exists('get_plugin_data') ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $pluginData             = get_plugin_data( $pluginFilePath, false, true );

        $res 					= (object)$pluginData;

        $release				= $this->getLatestRelease();
        if(is_wp_error($release)){
            return $res;
        }

        // Add available Sections
        $res->sections = [];
        foreach(['README', 'INSTALLATION', 'FAQ', 'CHANGELOG', 'screenshots', 'reviews', 'hooks'] as $item){
            $content    = get_transient("sim-git-$item");
            // if not in transient
            if($content === false){
                $content    = $this->getFileContents($author, $repo, $item.'.md');

                // Store for 24 hours
                set_transient( "sim-git-$item", $content, DAY_IN_SECONDS );
            }

            if(empty($content) && file_exists(SIM\PLUGINFOLDER."/$item.md")){
                $content    = file_get_contents(SIM\PLUGINFOLDER."/$item.md");
            }

            if(!empty($content)){
                // do not use h2 for layout purposes
                $content    = str_replace('h4', 'h5', trim($content));
                $content    = str_replace('h3', 'h4', trim($content));
                $content    = str_replace('h2', 'h3', trim($content));
                
                //convert to html
                $parser     = new \Michelf\MarkdownExtra;
                $content	= $parser->transform($content);
                
                $res->sections[strtolower(ucfirst($item))]    = str_replace('h2', 'h3', trim($content));
            }
        }

        // Add meta's
        $res->version 			= $release['tag_name'];
        $res->last_updated 		= \Date(DATEFORMAT, strtotime($release['published_at']));
        $res->author            = $res->Author;
        $res->requires          = $res->RequiresWP;
        //$res->requires_php      = $res->RequiresPhp;
        $res->homepage          = $res->PluginURI;
        $res->slug              = 'sim';

        foreach($extraData  as $key=>$data){
            $res->$key  = $data;

            if($key == 'ratings'){
                $res->num_ratings       = count($data);
            }
        }

        return $res;
    }

    /**
     * Checks for update from github
     *
     * @param   string  $path     The fullpath to the plugin or themes main file
     *
     * @return  object            Version information
     */
    public function getVersionInfo($path, $author='Tsjippy', $repo='sim-plugin'){
        $slug       = pathinfo($path, PATHINFO_FILENAME);
        if(str_contains($path, 'themes')){
            $oldVersion = wp_get_theme($slug)->get('Version');
        }else{
            if( !function_exists('get_plugin_data') ){
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $oldVersion = get_plugin_data($path)['Version'];
        }

        $release    = $this->getLatestRelease($author, $repo);

        if(is_wp_error($release) || empty($release)){
            return $release;
        }

        $gitVersion     = $release['tag_name'];

        $item			= (object) array(
            'slug'          => $slug,
            'url'           => "https://api.github.com/repos/$author/$repo",
            'package'       => '',
            'plugin'		=> $path
        );

        if(version_compare($gitVersion, $oldVersion) && !empty($release['assets'][0]['browser_download_url'])){
            $item->new_version	= $gitVersion;
            $item->package		= $release['assets'][0]['browser_download_url'];
        }

        return $item;
    }
}