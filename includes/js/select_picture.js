import { displayMessage } from './imports.js';

console.log("select picture.js loaded");
function selectImage(event, type='') {
	event.preventDefault();

	let frame = wp.media.frames.frame = wp.media({
		title: 'Select image' ,
		button: {
			text: 'Save image',
		},
		multiple: false,
		library: {
			type: 'image'
		},
	});

	// check file type
	console.log(type)
	if(type != ''){
		frame.on( 'selection:toggle', function(ev) {
			let selection	= frame.state().get('selection').first();
	
			if(selection != undefined){
				let attachment = selection.toJSON();

				if(attachment.subtype != type){
					displayMessage(`Please select an image with the ${type} extension!`, 'error');
					document.querySelectorAll('.swal2-container').forEach(el=>el.style.zIndex= 999999);

					selection.destroy();
				}
			}
		})
	}

	frame.on( 'select', function() {
		//Get the selected image
		let attachment = frame.state().get('selection').first().toJSON();

		let parent		= button.closest('.picture_selector_wrapper');
		
		//Store the id
		parent.querySelector('.image_attachment_id').value = attachment.id;
		
		//Show the image
		let imgdiv = parent.querySelector('.image-preview-wrapper');
		imgdiv.querySelector('img').src= attachment.url;
		imgdiv.classList.remove('hidden');
		
		//Change button text
		parent.querySelector('.select_image_button' ).innerHTML = parent.querySelector('.select_image_button' ).innerHTML.replace("Add", 'Replace');
	});

	frame.open();
}

var button = '';
window.addEventListener('click', event=>{
	if(event.target.classList.contains('select_image_button')){
		event.preventDefault();
		button		= event.target;

		let type = '';

		if(button.dataset.type != undefined){
			type	= button.dataset.type;
		}
		selectImage(event, type);
	}
})