<?php
namespace SIM\VIMEO;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'sync_vimeo_action', 'SIM\VIMEO\vimeoSync');
    add_action( 'createVimeoThumbnails', 'SIM\VIMEO\createVimeoThumbnails');
});

function scheduleTasks(){
    SIM\scheduleTask('createVimeoThumbnails', 'daily');
    if(SIM\getModuleOption('vimeo', 'sync')){
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
        if(!$vimeoVideos) return;

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
    }
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	wp_clear_scheduled_hook( 'createVimeoThumbnails' );
	wp_clear_scheduled_hook( 'sync_vimeo_action' );
}, 10, 2);