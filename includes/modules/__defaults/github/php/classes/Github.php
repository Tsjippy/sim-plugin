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
     * @param	string	$package	The github package name
     * @param   bool    $force      Whether to skip the cached result. Default false
     *
     * @return	array	Array containing information about the latest release
     */
    public function getLatestRelease($author='tsjippy', $package=PLUGINNAME, $force=false){
        if(isset($_GET['update']) || $force){
            $release	= false;
        }else{
            //check db version
            $release    = get_transient("$author-$package");
        }
        
        // if not in transient
        if(!$release){
            try{
                $release 	    = $this->client->api('repo')->releases()->latest($author, $package);
            } catch (ApiLimitExceedException $e) {
                SIM\printArray('Rate limit reached, please try again in an hour');
                return new \WP_Error('update', 'Rate limit reached, please try again in an hour');
            }catch(\Exception $exception){
                SIM\printArray($exception);
                if($exception->getMessage() == 'Not Found'){
                    // authenticate
                    $this->authenticate();
                    
                    // rerun
                    return $this->getLatestRelease($author, $package);
                }else{
                    return new \WP_Error('update', $exception->getMessage());
                }
            }            

            //printArray($release);
            $this->client->removeCache();
            
            // Store for 1 hours
            set_transient( "$author-$package", $release, HOUR_IN_SECONDS );
        }
        return $release;
    }

    /**
     * Downloads and unzips the latest release from a given github location to a given path
     *
     * @param	string	$author		The github author. Default 'Tsjippy'
     * @param	string	$package	The github package name
     * @param	string	$path		The destination path
     * 
     * @return	true|WP_Error       True on success, WP_Error object on failure
     */
    public function downloadFromGithub($author='Tsjippy', $package, $path){
        // Get latest release info
        $release	= $this->getLatestRelease($author, $package, true);

        if(is_wp_error($release)){
            return $release;
        }

        // download latest release
        $zipContent = $this->client->api('repo')->releases()->assets()->show($author, $package, $release['assets'][0]['id'], true);
        
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
     * Parses plugininfo from github
     *
     * @param   string  $pluginFilePath     The main file of the plugin you want to have info of
     */
    public function pluginData($pluginFilePath){
        if( ! function_exists('get_plugin_data') ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $pluginData = get_plugin_data( $pluginFilePath, false, true );

        $res 					= (object)$pluginData;

        $release				= $this->getLatestRelease();
        if(is_wp_error($release)){
            return $res;
        }

        $res->Version 			= $release['tag_name'];
        $res->last_updated 		= \Date(DATEFORMAT, strtotime($release['published_at']));

        $description    		= get_transient('sim-git-description');

        // if not in transient
        if(!$description){
            $description    = base64_decode($github->client->api('repo')->contents()->readme('Tsjippy', PLUGINNAME)['content']);
            // Store for 24 hours
            set_transient( 'sim-git-description', $description, DAY_IN_SECONDS );
        }

        $changelog    = get_transient('sim-git-changelog');
        // if not in transient
        if(!$changelog){
            $changelog	= base64_decode($github->client->api('repo')->contents()->show('Tsjippy', PLUGINNAME, 'CHANGELOG.md')['content']);
            
            //convert to html
            $parser 	= new \Michelf\MarkdownExtra;
            $changelog	= $parser->transform($changelog);
            
            // Store for 24 hours
            set_transient( 'sim-git-changelog', $changelog, DAY_IN_SECONDS );
        }
            
        $res->sections = array(
            'description' 	=> $description,
            'changelog' 	=> $changelog
        );

        $res->plugin_uri        = $res->PluginURI;
        $res->author_profile    = $res->AuthorURI;
        $res->text_domain       = $res->TextDomain;
        $res->domain_path       = $res->DomainPath;
        $res->requires_wp       = $res->RequiresWP;
        $res->requires_php      = $res->RequiresPHP;
        $res->update_uri        = $res->UpdateURI;
        $res->requires_plugins  = (array)$res->RequiresPlugins;
        $res->author_name       = $res->authorName;
        $res->homepage          = $res->PluginURI;
        
        foreach($res as $key=>$value){
            $newKey = strtolower($key);
            $res->$newKey   = $value;
        }
        return $res;
    }
}