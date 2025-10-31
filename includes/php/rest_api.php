<?php
namespace SIM;

add_action( 'rest_api_init', __NAMESPACE__.'\restApiInit');
function restApiInit() {
	register_rest_route( 
		RESTAPIPREFIX, 
		'/fetch_image_edit_modal', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> __NAMESPACE__.'\fetchImageEditModal',
			'permission_callback' 	=> '__return_true',
		)
	);
}

function fetchImageEditModal(){

	$basePicturesUrl	= plugins_url('../pictures/', __DIR__);

	ob_start();

	?>
	<div id="edit-image-modal" class="modal edit-image hidden">
		<!-- Modal content -->
		<div class="modal-content">
			<span id="modal-close" class="close">&times;</span>

			<div class="image-edit-container">
				<h4>Edit your image</h4>
				<div class="image-edit-wrapper">
					<div class="editor-panel">
						<div class="filter">
							<label class="title">Filters</label>
							<div class="options">
								<button id="brightness" type="button" class="active">Brightness</button>
								<button id="saturation" type="button">Saturation</button>
								<button id="inversion" type="button">Inversion</button>
								<button id="grayscale" type="button">Grayscale</button>
							</div>
							<div class="slider">
								<div class="filter-info">
									<p class="name">Brighteness</p>
									<p class="value">100%</p>
								</div>
								<input type="range" value="100" min="0" max="200">
							</div>
						</div>
						<div class="rotate">
							<label class="title">Rotate</label>
							<div class="options">
								<button id="left" type="button"><img src='<?php echo $basePicturesUrl;?>rotate-left-solid.svg' alt='rotate left'></button>
								<button id="right" type="button"><img src='<?php echo $basePicturesUrl;?>rotate-right-solid.svg' alt='rotate right'></i></button>
								<button id="horizontal" type="button"><img src='<?php echo $basePicturesUrl;?>reflect-vertical.svg' alt='reflect vertical'></button>
								<button id="vertical" type="button"><img src='<?php echo $basePicturesUrl;?>reflect-horizontal.svg' alt='reflect horizontal'></button>
							</div>
						</div>
					</div>
					<div class="preview-img">
						<img src="" alt="preview-img" class='hidden'>
						<div class="break"></div>
						<div class="zoom" style='margin-top:10px;'>
							Zoom<br>
							<input class='image-zoom' type="range" value="50" min="0" max="100">
							<output>50</output>%
						</div>
					</div>
					
				</div>
				<div class="controls">
					<button type="button" class="reset-filter">Reset Filters</button>
					<div class="row">
						<input type="file" class="file-input" accept="image/*" hidden>
						<button type="button" class="choose-img">Change Image</button>
						<button type="button" class="save-img">Save Image</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php

	return ob_get_clean();
}