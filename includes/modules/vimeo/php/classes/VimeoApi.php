<?php
namespace SIM\VIMEO;
use SIM;
use GuzzleHttp;
use WP;
use WP_Error;

require_once( MODULE_PATH  . 'lib/vendor/autoload.php');

if(!class_exists(__NAMESPACE__.'\VimeoApi')){
    class VimeoApi{
        public function __construct(){
            global $Modules;

            if ( ! class_exists( '\Vimeo\Vimeo' ) ) {
                $error = __( 'Vimeo not loaded', 'sim' );
                SIM\printArray($error);
                return false;
            }

            $settings               = $Modules[MODULE_SLUG];

            if(!empty($settings['client_id']) && !empty($settings['client_secret']) && !empty($settings['access_token'])){
                $this->clientId		    = $settings['client_id'];
                $this->clientSecret	    = $settings['client_secret'];
                $this->accessToken      = $settings['access_token'];
                $this->filesDir         = WP_CONTENT_DIR.'/vimeo_files';
                $this->picturesDir      = $this->filesDir."/thumbnails/";
                $this->backupDir        = $this->filesDir."/backup/";

                $this->api = new \Vimeo\Vimeo($this->clientId, $this->clientSecret, $this->accessToken);
            }
        }

        /**
         *
         * Checks if we are online and have a connection to Vimeo
         *
         * @return   string      'online' or 'offline'
         *
        **/
        public function isConnected(){
            $this->status = get_transient( 'vimeo_connected' );
            if ( $this->status === false || $this->status == 'offline' || empty($this->status)) {
                try {
                    if($this->api == null){
                        $this->api = new \Vimeo\Vimeo($this->clientId, $this->clientSecret, $this->accessToken);
                    }
                    $response = $this->api->request( '/oauth/verify', [], 'GET' );

                    $this->status       = 'online';
                    $this->license      = $response['body']['user']['account'];
                    if($response['status'] != 200){
                        $this->status   = 'offline';
                        $error          = $response['body']['error'];
                        error_log($error);
                    }
                }catch ( \Exception $e ) {
                    $this->status   = 'offline';
                    $error          = $e;
                    error_log($error);
                }

                set_transient( 'vimeo_connected', $this->status, 120 );
            }
            return $this->status;
        }

        /**
         *
         * Logs in to Vimeo and get an authorized url for use during authoraziation
         *
         * @param    string     $clientId       Vimeo client id
         * @param    string     $clientSecret   Vimeo client secret
         * @param    string     $url            Optional url
         * @return   string     Theauthorized url
         *
        **/
        public function getAuthorizeUrl($clientId, $clientSecret, $url=null){
            if($url == null){
                $redirectUri   =  admin_url( "admin.php?page=".$_GET["page"] );
            }

            $scopes = array(
                'create',
                'interact',
                'private',
                'edit',
                'upload',
                'delete',
                'public',
                'video_files'
            );

            $state  = mt_rand(1000000000,9999999999);
            update_option('vimeo_state', $state);

            $api = new \Vimeo\Vimeo($clientId, $clientSecret);

            return $api->buildAuthorizationEndpoint($redirectUri, $scopes, $state);
        }

        /**
         *
         * Store the Vimeo Accestoken
         *
         * @param    string     $clientId       Vimeo client id
         * @param    string     $clientSecret   Vimeo client secret
         * @param    string     $code           code retrieved from Vimeo website
         * @param    string     $redirectUri    Url to redirect to
         * @return   string     Acces token to Vimeo
         *
        **/
        public function storeAccessToken($clientId, $clientSecret, $code, $redirectUri){
            $api    = new \Vimeo\Vimeo($clientId, $clientSecret);
            $token  = $api->accessToken($code, $redirectUri);
            $api->setToken($token['body']['access_token']);

            return $token['body']['access_token'];
        }

        /**
         *
         * Gets a vimeo id from a post id
         *
         * @param    int        $postId     Wordpress post id
         * @return   int|false              Vimeo video id
         *
        **/
        public function getVimeoId($postId){
            $vimeoId		= get_post_meta($postId, 'vimeo_id', true);
            if(is_numeric($vimeoId)){
                return $vimeoId;
            }else{
                return false;
            }
        }

        /**
         *
         * Gets a vimeo id from a post id
         *
         * @param    int        $vimeId     Vimeo video id
         * @return   WP_Post|WP_Error       Wordpress post
         *
        **/
        public function getPost($vimeoId){
            // Get the post for this video
            $posts = get_posts(array(
                'numberposts'   => -1,
                'post_type'     => 'attachment',
                'meta_key'      => 'vimeo_id',
                'meta_value'    => $vimeoId
            ));

            if(empty($posts)){
                return new WP_Error('vimeo'," No post found for this video");
            }

            return $posts[0];
        }

        /**
         *
         * Retrieves all videos on Vimeo
         *
         * @return   array      An Array containing all the videos on Vimeo
         *
        **/
        public function getUploadedVideos() {
            $videos = array();

            if ( $this->isConnected() ) {
                $query  = array(
                    'fields'   => 'uri,name',
                    'filter'   => 'embeddable',
                );

                $response   = $this->api->request( '/me/videos', $query, 'GET' );
                $total      = $response['body']['total'];

                if ( $response['status'] === 200 ) {
                    $videos = array_merge( $videos, $response['body']['data'] );

                    $queryParams = array();
                    //check if we got a paged response
                    if ( isset( $response['body']['paging']['last'] ) ) {
                        // parse the url to get the last page number
                        wp_parse_str( $response['body']['paging']['last'], $queryParams );
                    }

                    //last page with video's
                    $lastPage = isset( $queryParams['page'] ) ? $queryParams['page'] : 1;

                    // get the how many more queries we can do
                    $remaining = null;
                    if ( isset( $response['headers']['x-ratelimit-remaining'] ) ) {
                        $remaining = $response['headers']['x-ratelimit-remaining'];
                    }

                    //loop over all the pages and add the video's
                    if ( ! is_null( $remaining ) && $remaining > 5 && $lastPage > 1 ) {
                        for ( $i = 2; $i <= $lastPage; $i ++ ) {
                            $query['page'] = $i;
                            $response = $this->api->request( '/me/videos', $query, 'GET' );
                            if ( isset( $response['status'] ) && $response['status'] === 200 ) {
                                $videos = array_merge( $videos, $response['body']['data'] );
                                if ( $response['headers']['x-ratelimit-remaining'] < 5 ) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if($total != count($videos)){
                return false;
            }
            return $videos;
        }

        /**
         *
         * Deletes a video from Vimeo
         *
         * @param    int     $postId    Wordpress post id
         *
        **/
        public function deleteVimeoVideo($postId){
            if ( $this->isConnected() ) {
                $vimeoId   = $this->getVimeoId($postId);

                if(!is_numeric($vimeoId)){
                    return false;
                }

                //Deleting video on vimeo
                if($_SERVER['HTTP_HOST'] != 'localhost'){
                    wp_mail('enharmsen@gmail.com' , 'Video deleted', 'Hi Ewald<br><br>'.wp_get_current_user()->display_name.' just deleted the video with id '.$vimeoId);
                    /* $response = $this->api->request( "/videos/$vimeo_id", [], 'DELETE' );

                    if(isset($response['body']['error'])){
                        return $response['body']['error'];
                    } */
                }

                SIM\printArray("Succesfully deleted video with id $vimeoId from Vimeo");

                //delete thumbnail
                $path   = get_post_meta($postId, 'thumbnail', true);
                unlink($path);
            }
        }

        /**
         *
         * Retrieves an upload url from Vimeo
         *
         * @param    string     $path The full path to the file to be uploaded
         * @param    string     $mime The mimetype of the file
         * @return   array      An Array containing the upload url, WP post id and Vimeo video id
         *
        **/
        public function createVimeoVideo($path, $mime){
            //we should only do this via REST
            if(!defined('REST_REQUEST')){
                return false;
            }

            //remove extension
            $title		    = pathinfo($path, PATHINFO_FILENAME);
            $uploadLink     = '';
            $size		    = $_POST['file_size'];
            if(!is_numeric($size)){
                return new WP_Error('vimeo','No filesize given');
            }

            if ( $this->isConnected()) {
                $params=[
                    "upload" => [
                        "approach"	=> "tus",
                        "size"		=> $size
                    ],
                    "name"			=> $title,
                ];

                $response		= $this->api->request('/me/videos?fields=uri,upload', $params, 'POST');

                if(isset($response['body']['error'])){
                    return new WP_Error('vimeo', $response['body']['error']);
                }

                $uploadLink     = $response['body']['upload']['upload_link'];

                $vimeoId		= str_replace('/videos/','',$response['body']['uri']);
            }else{
                return new WP_Error('vimeo','No internet');
            }

            if(!is_numeric($vimeoId)){
                return new WP_Error('vimeo','Something went wrong');
            }

            $attachmentId  = $this->createVimeoPost($title, $mime, $vimeoId);

            add_post_meta($attachmentId, '_wp_attached_file', 'Uploading to vimeo');

            //store upload link in case of failed upload and we want to resume
            add_post_meta($attachmentId, 'vimeo_upload_data', [
                'url'       => $uploadLink,
                'filename'  => $path,
                'vimeo_id'  => $vimeoId
            ]);

            return [
                'upload_link'	=> $uploadLink,
                'post_id'		=> $attachmentId,
                'vimeo_id'      => $vimeoId
            ];
        }

        /**
         *
         * Creates a Wordpress attachment containing the Vimeo video
         *
         * @param    string     $title      The title of the video
         * @param    string     $mime       The mimetype of the file
         * @param    int        $vimeoId    The Vimeo id
         * @return   id                     The id of the created attachment
         *
        **/
        public function createVimeoPost($title, $mime, $vimeoId){
            $args = array(
                'post_title'   		=> $title,
                'post_name'   		=> str_replace(' ', '-', $title),
                'post_content' 		=> '',
                'post_status'  		=> 'publish',
                'post_type'    		=> 'attachment',
                'post_author'  		=> is_user_logged_in() ? get_current_user_id() : 0,
                'post_mime_type'	=> $mime
            );

            $attachmentId = wp_insert_post( $args );

            //add to wp library
            update_post_meta($attachmentId, 'vimeo_id', $vimeoId);
            update_post_meta($attachmentId, '_wp_attached_file', $title);

            return $attachmentId;
        }

        /**
         *
         * Retrieves an upload url from Vimeo
         *
         * @param    int     $postId    WP_Post id
         * @return   array              The vimeo response
         *
        **/
        public function upload($postId){
            // only continue if not on vimeo yet
            if(!is_numeric($postId) || is_numeric($this->getVimeoId($postId))){
                return false;
            }

            $path   = get_attached_file($postId);
            if(!file_exists($path)){
                return false;
            }

            $post   = get_post($postId);

            try{
                $response = $this->api->upload($path, [
                    'name'          => $post->post_title,
                    'description'   => $post->post_content
                ]);
            }catch(\Exception $e) {
                SIM\printArray('Unable to upload: '.$e->getMessage());
            }

            update_post_meta($postId, 'vimeo_id', str_replace('/videos/', '', $response['body']['uri']));

            return $response;
        }

        /**
         *
         * Saves the backup video path to post meta
         *
         * @param    int     $postId    WP_Post id
         * @return   int|bool           meta key id if a new one got created
         *
        **/
        public function saveVideoPath($postId, $filePath){
            if(!is_numeric($postId)){
                return false;
            }

            return update_post_meta($postId, 'video_path', $filePath);
        }

        /**
         *
         * Retrieves the backup video path
         *
         * @param    int     $postId    WP_Post id
         * @return   string|false       Path to the video file
         *
        **/
        public function getVideoPath($postId){
            if(!is_numeric($postId)){
                return false;
            }

            $filePath   = get_post_meta($postId, 'video_path', true);

            if(empty($filePath)){
                return false;
            }

            return $filePath;
        }

        /**
         *
         * Updates the metadata of an video on Vimeo
         *
         * @param    int     $postId    WP_Post id
         * @param    array   $data      Meta data to be updated
         *
        **/
        public function updateMeta($postId, $data){
            $vimeoId   = $this->getVimeoId($postId);

            // Save meta on vimeo
            if($vimeoId && $this->isConnected()){
                $this->api->request("/videos/$vimeoId", $data, 'PATCH');
            }

            // If we are updating the title
            if(!empty($data['name'])){
                update_post_meta($postId, '_wp_attached_file', $data['name']);

                // Get current file path
                $filePath = $this->getVideoPath($postId);

                if($filePath){
                    //Rename backup file
                    $newFilePath    = dirname($filePath)."/{$vimeoId}_{$data['name']}.mp4";
                    rename($filePath, $newFilePath);

                    // Save new filepath in post meta
                    $this->saveVideoPath($postId, $newFilePath);
                }
            }
        }

        /**
         *
         * Hides a vimeo video
         *
         * @param    int        $postId    WP_Post id
         *
        **/
        public function hideVimeoVideo( $postId) {
            //Hide the video from vimeo
            try {
                $vimeoId   = $this->getVimeoId($postId);

                if(!is_numeric($vimeoId) || !$this->isConnected()){
                    return false;
                }

                $this->api->request( "/videos/$vimeoId", array(
                    'privacy' => array(
                        'view' => "disable"
                    )
                ), 'PATCH' );
            } catch ( \Exception $e ) {
                SIM\printArray( 'Hide Vimeo video: ' . $e->getMessage() );
            }
        }

        /**
         *
         * Retrieves the path of the thumbnail of a Vimeo video
         *
         * @param    int        $postId     WP_Post id
         * @return   string|WP_Error|false  Thumbnail path
         *
        **/
        public function getThumbnail($postId){
            $thumbnail  = get_post_meta($postId, 'thumbnail', true);

            if(file_exists($thumbnail)){
                return $thumbnail;
            }

            //no thumbnail found, create one
            $vimeoId   = $this->getVimeoId($postId);

            if($vimeoId && $this->isConnected()){
                //Get thumbnails
                $data	= $this->api->request("/videos/$vimeoId/pictures?sizes=48x64", [], 'GET')['body']['data'][0];
                if($data['active']){
                    $iconUrl   = $data['base_link'].'.webp';

                    $result = $this->downloadFromVimeo($iconUrl, $postId);
                    if(is_wp_error($result)){
                        $result = $result->error_data['vimeo']['path'];
                    }
                    update_post_meta($postId, 'thumbnail', $result);

                    return $result;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }

        /**
         *
         * Tries to download a vimeo video to the server
         * Sends an e-mail to the site admin if that fails
         *
         * @param    int        $postId     WP_Post id
         *
        **/
        public function downloadVideo($postId){
            $vimeoId    = $this->getVimeoId($postId);
            $response	= $this->api->request("/videos/$vimeoId", [], 'GET');

            if(isset($response['download'])){
                $response['download']['quality'];
                $url = $response['download']['link'];
            }else{
                $adminUrl   = admin_url("admin.php?page=sim_vimeo&vimeoid=$vimeoId");
                $message    = "Hi admin,<br><br>";
                $message    .= "Please provide me with a link to download the Vimeo video with id $vimeoId to a local backup folder on your website.<br>";
                $message    .= "Use <a href='$adminUrl'>this page</a> to provide me the download link.<br>";
                $message    .= "Get the download link from Vimeo on <a href='https://vimeo.com/manage/$vimeoId/advanced'>this page</a>.<br>";

                wp_mail(get_option('admin_email'), 'Please backup this Vimeo Video', $message);
            }
        }

        /**
         *
         * Retrieves an upload url from Vimeo
         *
         * @param    string                 $url        The url to the Vimeo
         * @param    string                 $postID     The WP_Post id
         * @return   string|false|WP_Error              An Array containing the upload url, WP post id and Vimeo video id
         *
        **/
        public function downloadFromVimeo($url, $postId) {
            $extension  = pathinfo(parse_url($url)['path'], PATHINFO_EXTENSION);
            if($extension == 'webp'){
                $path       = $this->picturesDir;
                $filename   = $this->getVimeoId($postId);
            }else{
                $path       = $this->backupDir;

                $filename   = $this->getVimeoId($postId)."_".get_the_title($postId);

                if(empty($extension )){
                    $extension = 'mp4';
                }
            }

            // Create folder if it does not exist
            if (!file_exists($path)) {
                SIM\printArray("Creating folder at $path");
                if(!mkdir($path, 0755, true)){
                    SIM\printArray("Creating folder in $path failed!");
                    return false;
                }
            }

            $filePath  = str_replace('\\', '/', $path.$filename.'.'.$extension);

            // save filepath in meta
            if($extension == 'mp4'){
                $this->saveVideoPath($postId, $filePath);
            }

            if (file_exists($filePath)) {
                return new WP_Error('vimeo', "The video is already downloaded", ['path' => $filePath]);
            }

            $client = new GuzzleHttp\Client();
            try{
                $client->request(
                    'GET',
                    $url,
                    array('sink' => $filePath)
                );

                return $filePath;
            }catch (\GuzzleHttp\Exception\ClientException $e) {
                unlink($filePath);

                if($e->getResponse()->getReasonPhrase() == 'Gone'){
                    return new WP_Error('vimeo', "The link has expired, please get a new one");
                }
                return new WP_Error('vimeo', $e->getResponse()->getReasonPhrase());
            }
        }
    }
}
