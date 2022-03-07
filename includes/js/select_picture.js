function selectImage(event) {
	event.preventDefault();

	// if the file_frame has already been created, just reuse it
	if ( typeof file_frame == 'object' ) {
		file_frame.open();
		return;
	} 

	file_frame = wp.media.frames.file_frame = wp.media({
		title: 'Select image' ,
		button: {
			text: 'Save image',
		},
		multiple: false
	});

	file_frame.on( 'select', function(help) {
		//Get the selected image
		attachment = file_frame.state().get('selection').first().toJSON();

		parent		= button.closest('.picture_selector_wrapper');
		
		//Store the id
		parent.querySelector('.image_attachment_id').value = attachment.id;
		
		//Show the image
		var imgdiv = parent.querySelector('.image-preview-wrapper');
		imgdiv.querySelector('img').src= attachment.url;
		imgdiv.classList.remove('hidden');
		
		//Change button text
		parent.querySelector('.select_image_button' ).innerHTML = el.innerHTML.replace("Add",'Replace');
	});

	file_frame.open();
}

var button = '';
window.addEventListener('click', event=>{
	if(event.target.classList.contains('select_image_button')){
		button		= event.target;
		selectImage(event);
	}
})