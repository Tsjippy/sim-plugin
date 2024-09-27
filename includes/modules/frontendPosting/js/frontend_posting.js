import './../../locations/js/user_location.js';
import { addStyles } from './../../../js/imports.js';

console.log("Frontendposting.js loaded");

async function confirmPostDelete( event, type='delete' ) {
	let url;
	let target	= event.target;
	event.preventDefault();
	parent = target.closest('#frontend_upload_form');

	let options = {
		title: 'Are you sure?',
		text: `Are you sure you want to ${type} this ${document.querySelector('[name="post_type"]').value}}?`,
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: `Yes, ${type} it!`
	};

	if(document.fullscreenElement != null){
		options['target']	= document.fullscreenElement;
	}

	var confirmed = await Swal.fire(options);

	if (confirmed.isConfirmed) {
		let postId = target.dataset.post_id;
		target.closest('div').querySelector('.loadergif').classList.remove('hidden');
	
		var formData = new FormData();
		formData.append('post_id', postId);

		if(type == 'delete'){
			url 	= 'frontend_posting/remove_post'
		}else{
			url 	= 'frontend_posting/archive_post'
		}
		
		var response = await FormSubmit.fetchRestApi(url, formData);

		if(response){
			Main.displayMessage(response);
		}
	}
}

async function refreshPostLock(){
	var postId = document.querySelector('[name="post_id"]');

	if(postId != null && postId.value != ''){
		var formData	= new FormData();
		formData.append('postid', postId.value);
		FormSubmit.fetchRestApi('frontend_posting/refresh_post_lock', formData);
	}
}

async function deletePostLock(){
	var postId = document.querySelector('[name="post_id"]');

	if(postId != null && postId.value != ''){
		var formData	= new FormData();
		formData.append('postid', postId.value);
		await FormSubmit.fetchRestApi('frontend_posting/delete_post_lock', formData);
	}
}

// Strores a change of post type on the server
async function changePostType(target){
	var response = await FormSubmit.submitForm(target, 'frontend_posting/change_post_type');

	if(response){
		Main.displayMessage(response);
	}
}

// Switches the available fields on post type change
function switchforms(target){
	var postType;
	var parent 			= document.getElementById('frontend_upload_form');

	if(target == null){
		postType		= location.search.replace('?type=', '');
	}else{
		postType 		= target.value;
	}
	
	document.querySelector('#postform [name="post_type"]').value 	= postType;
	
	//Change button text
	parent.querySelectorAll('.replaceposttype').forEach(function(el){
		el.textContent = postType;
	});
	
	//Show the lite elements if there is no content
	if(tinymce.activeEditor == null || tinymce.activeEditor.getContent() == ""){
		parent.querySelectorAll('.lite').forEach(function(el){
			el.classList.remove('hidden');
		});
		
		//show the button to show all fields again
		parent.querySelector('#showallfields').classList.add('hidden');
	}

	parent.querySelectorAll('#wp-post_content-media-buttons, .advancedpublishoptions, #advancedpublishoptionsbutton').forEach(el=>el.classList.remove('hidden'));
	
	//Show all page options
	parent.querySelectorAll(`.property.${postType}`).forEach(el=>el.classList.remove('hidden'));

	//Hide other options
	parent.querySelectorAll(`.property:not(.${postType})`).forEach(el=>el.classList.add('hidden'));

	if(postType == 'attachment') {
		// tick the box to always include the url
		parent.querySelector('[name="signal_url"]').checked=true;
	}
}

function showallfields(target){
	var parent = target.closest('#frontend_upload_form');
	if(parent.querySelector("#showallfields").classList.contains('show')){
		//Show the lite elements if the button is clicked
		parent.querySelectorAll('.lite').forEach(function(el){
			el.classList.remove('hidden');
		});
		
		target.classList.remove('show');
		target.classList.add('shouldhide');
		
		target.textContent = "Only show required fields" 
	}else{
		//Hide the lite elements if the button is clicked
		parent.querySelectorAll('.lite').forEach(function(el){
			el.classList.add('hidden');
		});
		
		target.classList.add('show');
		target.classList.remove('shouldhide');
		
		target.textContent = "Show all fields" 
	}
}

function addFeaturedImage(event) {
	event.preventDefault();
	
	var parent = event.target.closest('#frontend_upload_form');

	let file_frame = wp.media.frames.file_frame = wp.media({
		title: 'Select featured image' ,
		button: {
			text: 'Save featured image',
		},
		multiple: false
	});

	file_frame.on( 'select', function() {
		//Get the selected image
		var attachment = file_frame.state().get('selection').first().toJSON();
		
		//Store the id
		parent.querySelector('[name="post_image_id"]').value = attachment.id;
		
		//Show the image
	
		document.getElementById('featured-image-div').classList.remove('hidden');

		var imgdiv = document.getElementById('featured_image_wrapper');
		
		//If already an image set, remove it
		if(imgdiv.querySelector('img') != null){
			imgdiv.querySelector('img').remove()
		}
		
		imgdiv.insertAdjacentHTML('beforeEnd','<img width="150" height="150" src="'+attachment.url+'">');
		
		//Change button text
		parent.querySelectorAll('[name="add-featured-image"]' ).forEach(function(element){
			element.innerHTML = element.innerHTML.replace("Add",'Replace');
		});
	});

	file_frame.open();
}

function catChanged(target){
	var parentId = target.closest('.infobox').dataset.parent;
	
	var parentDiv = target.closest('.categories');
	
	if(target.checked){
		//An recipetype is just selected, find all element with its value as parent attribute
		parentDiv.querySelectorAll("[data-parent='"+target.value+"']").forEach(el=>{
			//Make this subcategory visible
			el.classList.remove('hidden');
			
			//Show the label
			parentDiv.querySelector('#subcategorylabel').classList.remove('hidden');
		});
		
	//If we just deselected a parent category
	}else if(parentId == undefined){
		//Hide the label if there is no category visible anymore
		if(parentDiv.querySelector('.childtypes input[type="checkbox"]:checked') == null){
			parentDiv.querySelector('#subcategorylabel').classList.add('hidden');
		
			//An recipetype is just deselected, find all element with its value as parent attribute
			parentDiv.querySelectorAll("[data-parent='"+target.value+"']").forEach(el=>{
				//Make this subcategory invisible
				el.classList.add('hidden');
			});
		}
	}
}

async function addCatType(target){
	let parentDiv, parentData;
	let response	= await FormSubmit.submitForm(target, 'frontend_posting/add_category');

	if(response){
		//Get the newly added category parent id
		let parentCat  	= target.closest('form').querySelector('[name="cat_parent"]').value;
		let postType	= target.closest('form').querySelector('[name="post_type"]').value;
		let catName		= target.closest('form').querySelector('[name="cat_name"]').value;
		
		//No parent category
		if(parentCat == ''){
			parentDiv		= document.getElementById(postType+'_parenttypes');
			parentData		= '';
		//There is a parent
		}else{
			parentDiv	 	= document.getElementById(postType+'_childtypes');
			parentData		= `data-parent="${parentCat}"`;
			
			//Select parent if it is not checked already
			let parent = document.querySelector(`.${postType}type[value="${parentCat}"]`);
			if(!parent.checked){
				parent.click();
			}
		}
		
		//Add the new category as checkbox
		let html = `
		<div class="infobox" ${parentData}>
			<input type="checkbox" class="${postType}type" id="${postType}type[]" value="${response.id}" checked>
			<label class="option-label category-select">${catName}</label>
		</div>
		`
		parentDiv.insertAdjacentHTML('afterBegin', html);
		Main.hideModals();

		Main.displayMessage(`Succesfully added the ${catName} category`);
	}
}

async function submitPost(target){
	let response	= await FormSubmit.submitForm(target, 'frontend_posting/submit_post');
	
	if(response){
		// If no html found, reload the page
		if(!response.html || !response.js){
			Main.displayMessage(response.message+response.data);

			location.href	= response.url;
			return;
		}	

		// Update the url
		const url 		= new URL(response.url);
		window.history.pushState({}, '', url);

		let div	= document.getElementById('page-title-image');

		// Update the header image if there is one
		if(response.picture){
			if( div == null ){
				div	= document.createElement('div');
				div.id	= 'page-title-image';
			}
			div.style.backgroundImage	= `url("${response.picture}")`;
		}

		document.querySelector('main').innerHTML	= response.html;

		addStyles(response, document);

		// Scroll page to top
		window.scrollTo(0,0);
		
		console.log('scrolling')

		document.getElementById('page-edit').classList.remove('hidden');

		Main.displayMessage(response.message);
	}
}

//Retrieve file contents over AJAX
async function readFileContents(attachmentId){
	let formData	= new FormData();
	formData.append('attachment_id', attachmentId);

	let response	= await FormSubmit.fetchRestApi('frontend_posting/get_attachment_contents', formData);

	if(response){
		// Get current content
		let content	= tinyMCE.activeEditor.getContent();
		
		//Add contents to editor
		tinymce.activeEditor.setContent(content+response);

		Swal.close();
	}
}

/**
 * Checks every 0.1 second if wp.media.frame is available.
 * If available adds an on insert function
 */
function insertMediaContents(){
	setTimeout(
		function(){
			if(wp.media.frame == undefined){
				insertMediaContents();
				return;
			}
			wp.media.frame.on('insert', function() {
				let selection = wp.media.frame.state().get('selection').first().toJSON(); 
				if (['.txt', '.doc', '.rtf'].some(v => selection.url.includes(v))) {
					let options = {
						title: 'Question',
						text: 'Do you want to insert the contents of this file into the post?',
						icon: 'question',
						showCancelButton: true,
						confirmButtonColor: "#bd2919",
						cancelButtonColor: '#d33',
						confirmButtonText: 'Yes please',
						cancelButtonText: 'No thanks',
						hideClass: {
							popup: '',                     // disable popup fade-out animation
						},
					};

					if(document.fullscreenElement != null){
						options['target']	= document.fullscreenElement;
					}

					Swal.fire(options).then((result) => {
						if (result.isConfirmed) {
							let options = {
								title: 'Please wait...',
								html: "<IMG src='"+sim.loadingGif+"' width=100 height=100>",
								showConfirmButton: false,
								showClass: {
									backdrop: 'swal2-noanimation', // disable backdrop animation
									popup: '',                     // disable popup animation
									icon: ''                       // disable icon animation
								},
								
							};

							if(document.fullscreenElement != null){
								options['target']	= document.fullscreenElement;
							}

							Swal.fire(options);

							readFileContents(selection.id);
						}
					});
				}
			});
		},
		100
	);
}

async function checkForDuplicate(target){
	document.querySelectorAll('#post-title-warning').forEach(el=>el.remove());

	if(target.value == ''){
		return;
	}

	let formData	= new FormData();
	formData.append('title', target.value);
	formData.append('type', target.closest('form').querySelector('[name="post_type"]').value);
	formData.append('exclude', target.closest('form').querySelector('[name="post_id"]').value);

	let response	= await FormSubmit.fetchRestApi('frontend_posting/check_duplicate', formData);

	if(response){
		target.insertAdjacentHTML('afterEnd', response['html']);
		Main.displayMessage(response['warning'], 'warning');
	}
}

document.addEventListener("DOMContentLoaded", function() {
	
	if(window['frontendLoaded'] == undefined){
		window['frontendLoaded']	= true;

		//Update minutes
		let minutes = document.querySelector("#minutes");
		if(minutes != null){
			setInterval(function() {
				minutes.innerHTML = parseInt(minutes.innerHTML)+1;
			}, 60000);
		}
		
		refreshPostLock();
		
		//Refresh the post lock every minute
		setInterval(refreshPostLock, 60000);
		
		// run js if we open a specific post type
		if(location.search.includes('?type') || location.search.includes('?post_id')){
			switchforms(document.querySelector('#postform [name="post_type"]'));
		}

		// Listen to insert media actions
		insertMediaContents();
	}
});

//Remove post lock when move away from the page
window.onbeforeunload = function(){
	if(document.getElementById('post_lock_warning') == null){
		deletePostLock();
	}
};

document.addEventListener("click", event =>{
	let target = event.target;

	if(target.name == 'submit_post' || (target.parentNode != null && target.parentNode.name == 'submit_post')){
		submitPost(target);
	}else if(target.name == 'draft_post' || (target.parentNode != null && target.parentNode.name == 'draft_post')){
		//change action value
		target.closest('form').querySelector('[name="post_status"]').value = 'draft';
		
		submitPost(target);
	}else if(target.name == 'publish_post'){
		//change action value
		target.closest('form').querySelector('[name="post_status"]').value = 'publish';
		
		submitPost(target);
	}else if(target.name == 'delete_post'){
		confirmPostDelete(event, 'delete');
	}else if(target.name == 'archive_post'){
		confirmPostDelete(event, 'archive');
	}else if(target.name == 'change_post_type'){
		changePostType(target);
	}else if(target.name == "add-featured-image"){
		addFeaturedImage(event);
	}else if(target.id == 'showallfields'){
		showallfields(target);
	}else if(target.id == 'advancedpublishoptionsbutton'){
		let div = target.closest('form').querySelector('.advancedpublishoptions');
		if(div.classList.contains('hidden')){
			div.classList.remove('hidden');
			target.querySelector('span').textContent = 'Hide';
		}else{
			div.classList.add('hidden');
			target.querySelector('span').textContent = 'Show';
		}
	}
	
	if(target.matches('.remove_featured_image')){
		document.querySelector('[name="post_image_id"]').value	= 0;
		
		let parent	= document.getElementById('featured-image-div');
		parent.classList.add('hidden');
		parent.querySelector('img').remove();

		document.querySelector('[name="add-featured-image"]').textContent = 'Add featured image';
	}
	
	// SHow add category modal
	if(target.classList.contains('add_cat')){
		document.getElementById('add_'+target.dataset.type+'_type').classList.remove('hidden');
	}

	if(target.matches('.add_category .form_submit')){
		addCatType(target);
	}

	if(target.matches('button.show-diff')){
		target.parentNode.querySelector('.post-diff-wrapper').classList.toggle('hidden');
	}
});

document.addEventListener('change', event=>{
	let target = event.target;

	//listen to change of post type
	if(target.id == 'post_type_selector'){
		switchforms(target);

		// title is not empty
		if(	target.closest('#frontend_upload_form').querySelector('[name="post_title"]').value != ''){
			checkForDuplicate(target.closest('#frontend_upload_form').querySelector('[name="post_title"]'));
		}
	}else if(target.name == 'post_title'){
		checkForDuplicate(target);
	}

	if(target.list != null){
		//find the relevant datalist option
		let datalist_op = target.list.querySelector(`[value='${target.value}' i]`);
		if(datalist_op != null){
			let value = datalist_op.dataset.value;

			let valEl	= target.parentNode.querySelector('.datalistvalue');
			if(valEl != null){
				valEl.value	= value;
			}
		}
	}
	
	//listen for parent type select
	if(target.classList.contains('parent_cat')){
		catChanged(target);
	}
});