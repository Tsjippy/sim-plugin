<?php
namespace SIM\VIMEO;
use SIM;

//shortcode to display vimeo video's
add_shortcode("vimeo_video", function($atts){
	return showVimeoVideo($atts['id']);
});

function showVimeoVideo($vimeoId){
	// Load css
	wp_enqueue_style( 'vimeo_style');
	wp_enqueue_script('sim_vimeo_shortcode_script');

	ob_start();
	?>
	<div class="vimeo-wrapper">
		<div class="loaderwrapper" style="margin:auto; width:fit-content;">
			<img src="<?php echo LOADERIMAGEURL;?>" loading='lazy' style="max-height: 100px;"><br>
			<b>Loading Vimeo video</b>
		</div>

		<div class='vimeo-embed-container'>
			<iframe src='https://player.vimeo.com/video/<?php echo $vimeoId; ?>' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen onload = "showVimeoIframe(this)" style="display:none;"></iframe>
		</div>
	</div>
	<?php
	return ob_get_clean();
}