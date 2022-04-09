<?php
namespace SIM\VIMEO;
use SIM;

//shortcode to display vimeo video's
add_shortcode("vimeo_video", function($atts){
	return show_vimeo_video($atts['id']);
});

function show_vimeo_video($vimeo_id){
	// Load css
	wp_enqueue_style( 'vimeo_style');

	ob_start();
	?>
	<div class="vimeo-wrapper">
		<div class="loaderwrapper" style="margin:auto; width:fit-content;">
			<img src="<?php echo LOADERIMAGEURL;?>" style="max-height: 100px;"><br>
			<b>Loading Vimeo video</b>
		</div>

		<div class='vimeo-embed-container'>
			<iframe src='https://player.vimeo.com/video/<?php echo $vimeo_id; ?>' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen onload = "this.closest('.vimeo-wrapper').querySelector('.loaderwrapper').remove();this.style.display='block';" style="display:none;"></iframe>
		</div>
	</div>
	<?php
	return ob_get_clean();
}