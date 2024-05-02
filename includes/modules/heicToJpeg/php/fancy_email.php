<?php
namespace SIM\HEICTOJPEG;
use SIM;

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != 'fancyemail'){
		return $optionsHtml;
	}

    ?>
    <br>
	<label>
		<input type='checkbox' name='convert-heic' value='true' <?php if(isset($settings['convert-heic'])){echo 'checked';}?>>
		Convert attached .heic files to jpeg
	</label>
    <?php

    return $optionsHtml.ob_get_clean();
}, 20);