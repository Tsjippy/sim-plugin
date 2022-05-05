<?php
namespace SIM\VIMEO;
use SIM;
use GuzzleHttp;
use WP_Error;

if(!class_exists(__NAMESPACE__.'\VimeoApi')){
    class VimeoApi{
        function __construct(){
            global $Modules;

            if ( ! class_exists( '\Vimeo\Vimeo' ) ) {
                $error = __( 'Vimeo not loaded', 'sim' );
                SIM\print_array($error);
                return false;
            }

            $settings               = $Modules['vimeo'];
            $this->client_id		= $settings['client_id'];
            $this->client_secret	= $settings['client_secret'];
            $this->access_token     = $settings['access_token'];
            $this->files_dir        = WP_CONTENT_DIR.'/vimeo_files';
            $this->pictures_dir     = $this->files_dir."/thumbnails/";
            $this->backup_dir       = $this->files_dir."/backup/";

            $this->api = new \Vimeo\Vimeo($this->client_id, $this->client_secret, $this->access_token);

            $this->is_connected();
        }

        function is_connected(){
            $this->status = get_transient( 'vimeo_connected' );
            if ( $this->status === false or $this->status == 'offline' or empty($this->status)) {
                try {
                    if($this->api == null)  $this->api = new \Vimeo\Vimeo($this->client_id, $this->client_secret, $this->access_token);
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

        function get_authorize_url($client_id, $client_secret, $url=null){
            if($url==null){
                $redirect_uri   =  admin_url( "admin.php?page=".$_GET["page"] );
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
        
            $api = new \Vimeo\Vimeo($client_id, $client_secret);
        
            //$token = $api->clientCredentials($scopes);
        
            $url = $api->buildAuthorizationEndpoint($redirect_uri, $scopes, $state);
        
            return $url;
        }
        
        function store_accesstoken($client_id, $client_secret, $code, $redirect_uri){
            $api = new \Vimeo\Vimeo($client_id, $client_secret);
            $token = $api->accessToken($code, $redirect_uri);
            $api->setToken($token['body']['access_token']);
        
            return $token['body']['access_token'];
        }

        function getVimeoId($postId){
            $vimeoId		= get_post_meta($postId, 'vimeo_id', true);
            if(is_numeric($vimeoId)){
                return $vimeoId;
            }else{
                return false;
            }
        }

        function get_uploaded_videos() {
            $videos = array();

            if ( $this->is_connected() ) {
                $query  = array(
                    'fields'   => 'uri,name',
                    'filter'   => 'embeddable',
                );
            
                $response      = $this->api->request( '/me/videos', $query, 'GET' );
            
                if ( $response['status'] === 200 ) {
                    $videos = array_merge( $videos, $response['body']['data'] );

                    $query_params = array();
                    //check if we got a paged response
                    if ( isset( $response['body']['paging']['last'] ) ) {
                        //add the last page number to the query params
                        wp_parse_str( $response['body']['paging']['last'], $query_params );
                    }

                    //last page with video's
                    $last_page = isset( $query_params['page'] ) ? $query_params['page'] : 1;

                    $remaining = null;
                    if ( isset( $response['headers']['x-ratelimit-remaining'] ) ) {
                        $remaining = $response['headers']['x-ratelimit-remaining'];
                    }

                    //loop over all the pages and add the video's
                    if ( ! is_null( $remaining ) && $remaining > 5 && $last_page > 1 ) {
                        for ( $i = 2; $i <= $last_page; $i ++ ) {
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
        
            return $videos;
        }

        function deleteVimeoVideo($post_id){  
            if ( $this->is_connected() ) {
                $vimeo_id   = $this->getVimeoId($post_id);

                if(!is_numeric($vimeo_id)) return false;

                //Deleting video on vimeo
                if($_SERVER['HTTP_HOST'] != 'localhost'){
                    wp_mail('enharmsen@gmail.com' , 'Video deleted', 'Hi Ewald<br><br>'.wp_get_current_user()->display_name.' just deleted the video with id '.$vimeo_id);
                    /* $response = $this->api->request( "/videos/$vimeo_id", [], 'DELETE' );
        
                    if(isset($response['body']['error'])){
                        return $response['body']['error'];
                    } */
                }

                SIM\print_array("Succesfully deleted video with id $vimeo_id from Vimeo");

                //delete thumbnail
                $path   = get_post_meta($post_id, 'thumbnail', true);
                unlink($path);
            }
        }

        function create_vimeo_video($file_name, $mime){
            //we should only do this via AJAX
            if(!defined('REST_REQUEST')){
                return false;
            }
        
            //remove extension
            $title		    = pathinfo($file_name, PATHINFO_FILENAME);
            $upload_link    = '';
            $size		    = $_POST['file_size'];
            if(!is_numeric($size)){
                return new WP_Error('vimeo','No filesize given');
            }
                
            if ( $this->is_connected()) {
                $params=[
                    "upload" => [
                        "approach"	=> "tus",
                        "size"		=> $size
                    ],
                    "name"			=> $title,
                ];

                $response		= $this->api->request('/me/videos?fields=uri,upload', $params, 'POST');

                $upload_link	= $response['body']['upload']['upload_link'];

                $vimeo_id		= str_replace('/videos/','',$response['body']['uri']);
            }else{
                return new WP_Error('vimeo','No internet');
            }
            
            if(!is_numeric($vimeo_id)){
                return new WP_Error('vimeo','Something went wrong');
            } 
        
            $attachment_id  = $this->create_vimeo_post($title, $mime, $vimeo_id);
            
            add_post_meta($attachment_id, '_wp_attached_file', 'Uploading to vimeo');
            
            //store upload link in case of failed upload and we want to resume
            add_post_meta($attachment_id, 'vimeo_upload_data', [
                'url'       => $upload_link, 
                'filename'  => $file_name,
                'vimeo_id'  => $vimeo_id
            ]);
            
            return [
                'upload_link'	=> $upload_link,
                'post_id'		=> $attachment_id,
                'vimeo_id'      => $vimeo_id
            ];
        }

        function create_vimeo_post($title, $mime, $vimeo_id){
            $args = array(
                'post_title'   		=> $title,
                'post_name'   		=> str_replace(' ', '-', $title),
                'post_content' 		=> '',
                'post_status'  		=> 'publish',
                'post_type'    		=> 'attachment',
                'post_author'  		=> is_user_logged_in() ? get_current_user_id() : 0,
                'post_mime_type'	=> $mime
            );
        
            $attachment_id = wp_insert_post( $args );
        
            //add to wp library
            update_post_meta($attachment_id, 'vimeo_id', $vimeo_id);
            update_post_meta($attachment_id, '_wp_attached_file', $title);

            return $attachment_id;
        }

        function upload($post_id){
            if(!is_numeric($post_id)) return false;

            $path   = get_attached_file($post_id);
            if(!file_exists($path)) return false;

            $post   = get_post($post_id);

            try{
                $response = $this->api->upload($path, [
                    'name'          => $post->post_title,
                    'description'   => $post->post_content
                ]);
            }catch(\Exception $e) {
                SIM\print_array('Unable to upload: '.$e->getMessage());
            }

            update_post_meta($post_id, 'vimeo_id', str_replace('/videos/', '', $response['body']['uri']));

            return $response;
        }

        function update_meta($post_id, $data){
            $vimeo_id   = $this->getVimeoId($post_id);

            if($vimeo_id and $this->is_connected()){
                $response = $this->api->request("/videos/$vimeo_id", $data, 'PATCH');
            }
        }

        //hide on vimeo
        function hide_vimeo_video( $post_id) {
            //Hide the video from vimeo
            try {
                $vimeo_id   = $this->getVimeoId($post_id);

                if(!is_numeric($vimeo_id) or !$this->is_connected()) return false;

                $response 	= $this->api->request( "/videos/$vimeo_id", array(
                    'privacy' => array(
                        'view' => "disable"
                    )
                ), 'PATCH' );
            } catch ( \Exception $e ) {
                SIM\print_array( 'Hide Vimeo video: ' . $e->getMessage() );
            }
        }

        function get_thumbnail($post_id){
            $thumbnail  = get_post_meta($post_id, 'thumbnail', true);

            if(file_exists($thumbnail)) return $thumbnail;

            //no thumbnail found, create one
            $vimeo_id   = $this->getVimeoId($post_id);

            if($vimeo_id and $this->is_connected()){
                //Get thumbnails
                $data	= $this->api->request("/videos/$vimeo_id/pictures?sizes=48x64", [], 'GET')['body']['data'][0];
                if($data['active']){
                    $icon_url   = $data['base_link'].'.webp';

                    $result = $this->downloadFromVimeo($icon_url, $vimeo_id);
                    if(is_wp_error($result)){
                        $result = $result->error_data['vimeo']['path'];
                    }
                    update_post_meta($post_id, 'thumbnail', $result);

                    return $result;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }

        function download_video($post_id){
            $vimeo_id   = $this->getVimeoId($post_id);
            $response	= $this->api->request("/videos/$vimeo_id", [], 'GET');

            if(isset($response['download'])){
                $response['download']['quality'];
                $url = $response['download']['link'];
            }else{
                $admin_url  = admin_url("admin.php?page=sim_vimeo&vimeoid=$vimeo_id");
                $message    = "Hi admin,<br><br>";
                $message    .= "Please provide me with a link to download the Vimeo video with id $vimeo_id to a local backup folder on your website.<br>";
                $message    .= "Use <a href='$admin_url'>this page</a> to provide me the download link.<br>";
                $message    .= "Get the download link from Vimeo on <a href='https://vimeo.com/manage/$vimeo_id/advanced'>this page</a>.<br>";
                
                wp_mail(get_option('admin_email'), 'Please backup this Vimeo Video', $message);
            }
        }

        function downloadFromVimeo($url, $filename) {
            $extension  = pathinfo(parse_url($url)['path'], PATHINFO_EXTENSION);
            if($extension == 'webp'){
                $path   = $this->pictures_dir;
            }else{
                $path   = $this->backup_dir;
            }

            if(empty($extension )) $extension = 'mp4';

            // Create folder if it does not exist
            if (!file_exists($path)) {
                SIM\print_array("Creating folder at $path");
                if(!mkdir($path, 0755, true)){
                    SIM\print_array("Creating folder in $path failed!");
                    return false;
                }
            }

            $file_path  = str_replace('\\', '/', $path.$filename.'.'.$extension);

            if (file_exists($file_path)) {
                return new WP_Error('vimeo', "The video is already downloaded", ['path' => $file_path]);
            }

            $client = new GuzzleHttp\Client();
            try{
                $client->request(
                    'GET',
                    $url,
                    array('sink' => $file_path)
                );

                return $file_path;
            }catch (\GuzzleHttp\Exception\ClientException $e) {
                unlink($file_path);

                if($e->getResponse()->getReasonPhrase() == 'Gone'){
                    return new WP_Error('vimeo', "The link has expired, please get a new one");
                }
                return $e->getResponse()->getReasonPhrase();
            }
        }
    }
}