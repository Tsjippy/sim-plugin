<?php
namespace SIM\VIMEO;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'sync_vimeo_action', __NAMESPACE__.'\vimeoSync');
    add_action( 'createVimeoThumbnails', __NAMESPACE__.'\createVimeoThumbnails');
});

function scheduleTasks(){
    SIM\scheduleTask('createVimeoThumbnails', 'daily');
    if(SIM\getModuleOption(MODULE_SLUG, 'sync')){
        SIM\scheduleTask('sync_vimeo_action', 'daily');
    }
}

//create local thumbnails
function createVimeoThumbnails(){

	$args = array(
		'post_type'  	=> 'attachment',
		'numberposts'	=> -1,
		'meta_query'	=> array(
			array(
				'key'   => 'vimeo_id'
			),
			array(
                'key' => 'thumbnail',
                'compare' => 'NOT EXISTS'
            )
		)
	);
	$posts = get_posts( $args );

	if(!empty($posts)){
		$vimeoApi	= new VimeoApi();
		foreach($posts as $post){
			$vimeoApi->getThumbnail($post->ID);
		}
	}
}

//sync local db with vimeo.com
function vimeoSync(){

    $vimeoApi	= new VimeoApi();
    
    if ( $vimeoApi->isConnected() ) {
        $vimeoVideos	= $vimeoApi->getUploadedVideos();
        if(!$vimeoVideos){
            return;
        }

        $indexedVideos  = [];

        // use the vimeo id as the index for easy finding
        foreach($vimeoVideos as $vimeoVideo){
            $vimeoId                    = str_replace('/videos/', '', $vimeoVideo['uri']);

            $indexedVideos[$vimeoId]    = $vimeoVideo;
        }

        // update the cache
        update_option('sim-vimeo-videos', $indexedVideos);

        $args = array(
            'post_type'  	=> 'attachment',
            'numberposts'	=> -1,
            'meta_query'	=> array(
                array(
                    'key'   => 'vimeo_id'
                )
            )
        );
        $posts = get_posts( $args );

        $localVideos	= [];
        $onlineVideos	= [];

        //Build the local videos array
        foreach($posts as $post){
            $vimeoId	= get_post_meta($post->ID, 'vimeo_id',true);
            if(is_numeric($vimeoId)){
                $localVideos[$vimeoId]	= $post->ID;
            }
        }

        //Build online video's array
        foreach($vimeoVideos as $vimeoVideo){
            $vimeoId				= str_replace('/videos/', '', $vimeoVideo['uri']);
            $onlineVideos[$vimeoId]	= html_entity_decode($vimeoVideo['name']);
        }

        //remove any local video which does not exist on vimeo
        foreach(array_diff_key($localVideos, $onlineVideos) as $vimeoId=>$postId){
            $vimeoId		= get_post_meta($postId, 'vimeo_id', true);
            SIM\printArray("Deleting video with vimeo id $vimeoId");
            wp_delete_post($postId);
        }

        //add any video which does not exist locally
        foreach(array_diff_key($onlineVideos, $localVideos) as $vimeoId => $videoName){
            $vimeoApi->createVimeoPost( $videoName, 'video/mp4', $vimeoId);
        }

        // Backup any video who is not yet backed up
        $files      = glob($vimeoApi->backupDir.'*.mp4');
        foreach(array_keys($onlineVideos) as $vimeoId){
            // If the video does not exist locally
            if(empty(preg_grep("~$vimeoId~", $files))){
                $post   = $vimeoApi->getPost($vimeoId);
                $vimeoApi->downloadVideo($post->ID);
            }
        }
    }
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	wp_clear_scheduled_hook( 'createVimeoThumbnails' );
	wp_clear_scheduled_hook( 'sync_vimeo_action' );
});
