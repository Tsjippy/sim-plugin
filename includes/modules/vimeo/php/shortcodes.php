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

	ob_start();
	?>
	<div class="vimeo-wrapper">
		<div class='vimeo-embed-container' style='background:url(<?php echo LOADERIMAGEURL;?>) center center no-repeat;'>
			<iframe title='' loading='lazy' src='https://player.vimeo.com/video/<?php echo $vimeoId; ?>' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
		</div>
	</div>
	<?php
	return ob_get_clean();
}