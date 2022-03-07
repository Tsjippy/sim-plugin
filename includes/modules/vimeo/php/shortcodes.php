<?php
namespace SIM\VIMEO;
use SIM;

//shortcode to display vimeo video's
add_shortcode("vimeo_video",function ($atts){
	// Load css
	wp_enqueue_style( 'vimeo_style');

	ob_start();

	$vimeo_id	= $atts['id'];
	?>
	<div class='vimeo-embed-container'>
		<iframe src='https://player.vimeo.com/video/<?php echo $vimeo_id; ?>' frameborder='0' webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
	</div>
	<?php
	return ob_get_clean();
});