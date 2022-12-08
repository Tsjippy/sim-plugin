<?php
namespace SIM\EMBEDPAGE;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/embedPage/build',
		array(
			'render_callback' => __NAMESPACE__.'\displayEmbedBlock',
		)
	);

	register_block_type(
		__DIR__ . '/embedExternalPage/build',
		array(
			'render_callback' => __NAMESPACE__.'\externalblock',
		)
	);
});

function displayEmbedBlock($attributes){
	$page	= json_decode($attributes['page']);
	if(isset($page->ID)){
		return displayPageContents($page->ID, $attributes['hide']);
	}
}

function externalblock($attributes){
	if(!empty($attributes['url'])){
		// check if embedable
		$url 	= $attributes['url'];
		$header	= get_headers($url, 1);
		if(in_array($header["x-frame-options"], ['DENY', 'SAMEORIGIN', 'ALLOW-FROM'])){
			?>
			<script>
				document.addEventListener('mousemove', location.href='<?php echo $url;?>');
			</script>
			<?php
			return "Redirection to $url";
		}else{
			return "<iframe src='$url' sandbox=''></iframe>";
		}
	}
}