<?php
namespace SIM\VIMEO;
use SIM;
use Vimeo\Vimeo;

//shortcode to display vimeo video's
add_shortcode("vimeo_video", function($atts){
	return showVimeoVideo($atts['id']);
});

function showVimeoVideo($vimeoId){

	// Load css
	wp_enqueue_style( 'vimeo_style');

	$vimeoApi	= new VimeoApi();
	$html	= $vimeoApi->getEmbedHtml($vimeoId);

	ob_start();
	?>
	<div class="vimeo-wrapper">
		<div class='vimeo-embed-container' style='background:url(<?php echo LOADERIMAGEURL;?>) center center no-repeat;'>
			<?php echo $html;?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

add_filter( 'wp_video_shortcode', function($output, $atts, $video, $postId){
	$vimeoId	= get_post_meta($postId, 'vimeo_id', true);

	if(!is_numeric($vimeoId)){
		return $output;
	}

	return showVimeoVideo($vimeoId);
}, 10, 4 );