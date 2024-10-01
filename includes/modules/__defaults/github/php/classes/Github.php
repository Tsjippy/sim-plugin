<?php
namespace SIM\GITHUB;
use SIM;
use Github\Exception\ApiLimitExceedException;
use Github\Client;
use WP_Error;

class Github{
    public $client;
    public $token;

    public function __construct() {
        $this->client 	    = new \Github\Client(); 
        $this->token        = '';               
    }

    /**
     * Authenticate using a token
     * Create a token here: https://github.com/settings/tokens/new
     *
     * @param   string  $token  The token
     */
    private function authenticate(){
        if(empty($this->token)){
            $this->token    = SIM\getModuleOption(MODULE_SLUG, 'token');

            if(!$this->token){
                return new WP_Error('Github', 'Please set a Github token');
            }
        }
        $this->client->authenticate($this->token, null, \Github\AuthMethod::ACCESS_TOKEN);
    }

    /**
     * Retrieves the latest github release information from cache or github
     * 
     * @param	string	$author		The github author. Default 'Tsjippy'
     * @param	string	$repo	    The github repo name
     * @param   bool    $force      Whether to skip the cached result. Default false
     *
     * @return	array	Array containing information about the latest release
     */
    public function getLatestRelease($author='tsjippy', $repo=SIM\PLUGINNAME, $force=false){
        if(isset($_GET['update']) || $force){
            $release	= false;
        }else{
            //check db version
            $release    = get_transient("$author-$repo");
        }
        
        // if not in transient
        if(!$release){
            try{
                $release 	    = $this->client->api('repo')->releases()->latest($author, $repo);
            } catch (ApiLimitExceedException $e) {
                SIM\printArray('Rate limit reached, please try again in an hour');
                return new \WP_Error('update', 'Rate limit reached, please try again in an hour');
            }catch(\Exception $exception){
                SIM\printArray($exception);
                if($exception->getMessage() == 'Not Found'){
                    // authenticate
                    $this->authenticate();
                    
                    // rerun
                    return $this->getLatestRelease($author, $repo);
                }else{
                    return new \WP_Error('update', $exception->getMessage());
                }
            }            

            //printArray($release);
            $this->client->removeCache();
            
            // Store for 1 hours
            set_transient( "$author-$repo", $release, HOUR_IN_SECONDS );
        }
        return $release;
    }

    /**
     * Downloads and unzips the latest release from a given github location to a given path
     *
     * @param	string	$author		The github author. Default 'Tsjippy'
     * @param	string	$repo	    The github repo name
     * @param	string	$path		The destination path
     * 
     * @return	true|WP_Error       True on success, WP_Error object on failure
     */
    public function downloadFromGithub($author='Tsjippy', $repo=SIM\PLUGINNAME, $path){
        // Get latest release info
        $release	= $this->getLatestRelease($author, $repo, true);

        if(is_wp_error($release)){
            return $release;
        }

        // download latest release
        $zipContent = $this->client->api('repo')->releases()->assets()->show($author, $repo, $release['assets'][0]['id'], true);
        
        $tmpZipFile = tmpfile();
        fwrite($tmpZipFile, $zipContent);
        $zip = new \ZipArchive();
        $zip->open(stream_get_meta_data($tmpZipFile)['uri']);

        if(!is_dir($path)){
            mkdir($path);
        }
        $result = $zip->extractTo($path);
        if(!$result){
            return new WP_Error('Github', 'Unzip failed');
        }

        // close the archive and delete the file
        $zip->close();
        fclose($tmpZipFile);

        return true;
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
        foreach(['description', 'installation', 'faq', 'changelog', 'screenshots', 'reviews', 'hooks'] as $item){
            $content    = get_transient("sim-git-$item");
            // if not in transient
            if($content === false){
                try{
                    $file   = $this->client->api('repo')->contents()->show($author, $repo, $item.'.md');
                    
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

                    $content    = '';
                }

                // Store for 24 hours
                set_transient( "sim-git-$item", $content, DAY_IN_SECONDS );
            }

            if(!empty($content)){
                // do not use h2 for layout purposes
                $content    = str_replace('h4', 'h5', trim($content));
                $content    = str_replace('h3', 'h4', trim($content));
                $content    = str_replace('h2', 'h3', trim($content));
                $res->sections[$item]    = str_replace('h2', 'h3', trim($content));
            }
        }

        // Add meta's
        $res->version 			= $release['tag_name'];
        $res->last_updated 		= \Date(DATEFORMAT, strtotime($release['published_at']));
        $res->author            = $res->Author;
        $res->requires          = $res->RequiresWP;
        $res->requires_php      = $res->RequiresPhp;
        $res->homepage          = $res->PluginURI;
        $res->slug              = $args->slug;

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
     * @param   string  $pluginPath     The fullpath to the plugin main file
     *
     * @return  object                  Version information
     */
    public function pluginVersionInfo($pluginPath){
        $plugin = explode('/', $pluginPath)[0];

        if( !function_exists('get_plugin_data') ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $pluginVersion  = get_plugin_data($pluginPath)['Version'];

        $release		= $this->getLatestRelease();

        if(is_wp_error($release)){
            return $release;
        }

        $gitVersion     = $release['tag_name'];

        $item			= (object) array(
            'slug'          => $plugin,
            'new_version'   => $pluginVersion,
            'url'           => 'https://api.github.com/repos/Tsjippy/sim-plugin',
            'package'       => '',
            'plugin'		=> SIM\PLUGIN
        );

        if(version_compare($gitVersion, $pluginVersion) && !empty($release['assets'][0]['browser_download_url'])){
            $item->new_version	= $gitVersion;
            $item->package		= $release['assets'][0]['browser_download_url'];
        }

        return $item;
    }
}