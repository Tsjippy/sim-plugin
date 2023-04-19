<?php
namespace SIM\VIMEO;
use SIM;

add_filter('render_block', function($blockContent, $block){
    global $post;

    // Video block with a vimeo url
    if($block['blockName'] == 'core/video'){
        // Find vimeo id
        $vimeoId    = get_post_meta($post->ID, 'vimeo_id', true);

        if(is_numeric($vimeoId)){
            return showVimeoVideo($vimeoId);
        }
    }

    return $blockContent;
}, 999999999, 2);


//change the default output for a local video to a vimeo iframe
add_filter( 'render_block', function( $blockContent,  $block ){
	//if this is a video block
	if($block['blockName'] == 'core/video'){
		$postId		= $block['attrs']['id'];
		$vimeoId	= get_post_meta($postId, 'vimeo_id', true);

		//if this video is an vimeo video
		if(is_numeric($vimeoId)){
			$vimeoApi	= new VimeoApi();
			$html	= $vimeoApi->getEmbedHtml($vimeoId);

			//return a vimeo block
			ob_start();
			?>
			<figure class="wp-block-embed is-type-video is-provider-vimeo wp-block-embed-vimeo wp-embed-aspect-16-9 wp-has-aspect-ratio">
				<div class="wp-block-embed__wrapper">
					<?php echo $html;?>
				</div>
			</figure>
			<?php

            return ob_get_clean();
        }
	}

	return $blockContent;
}, 10, 2);
