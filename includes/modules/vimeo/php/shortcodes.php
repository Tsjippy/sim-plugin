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
	<script>
		if( typeof(showVimeoIframe) =='undefined' ){
			function showVimeoIframe(iframe){
				var loaderWrapper	= iframe.closest('.vimeo-wrapper').querySelector('.loaderwrapper');
				if(loaderWrapper != null){
					loaderWrapper.remove();
				}
				
				iframe.style.display='block';
			}
		}
	</script>
	<div class="vimeo-wrapper">
		<div class="loaderwrapper" style="margin:auto; width:fit-content;">
			<img src="<?php echo LOADERIMAGEURL;?>" loading='lazy' style="max-height: 100px;" alt=''><br>
			<strong>Loading Vimeo video</strong>
		</div>

		<div class='vimeo-embed-container'>
			<iframe title='' loading='lazy' src='https://player.vimeo.com/video/<?php echo $vimeoId; ?>' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen onload = "showVimeoIframe(this)" ></iframe>
		</div>
	</div>
	<?php
	return ob_get_clean();
}