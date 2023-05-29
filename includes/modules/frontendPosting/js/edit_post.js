import { addStyles } from './../../../js/imports.js';

console.log("Edit post.js loaded");

let editPostSwitch = async function (event){
	let button	= event.target;
	let wrapper	= button.closest('main').querySelector('.content-wrapper');
	wrapper.classList.add('hidden');

	let formData    = new FormData();
    let postId      = button.dataset.id;
    formData.append('postid', postId);

	const url 		= new URL(edit_post_url);
	url.searchParams.set('post_id', postId);

	window.history.pushState({}, '', url);

	let loader	= Main.showLoader(button, true, 'Requesting form...');

	let response = await FormSubmit.fetchRestApi('frontend_posting/post_edit', formData);

	if(response){
		wrapper.innerHTML	= response.html;

		addStyles(response, document);	// runs also the afterScriptsLoaded function
	}else{
		loader.outerHTML	= button.outerHTML;
	}
}

document.addEventListener("DOMContentLoaded", function() {
	
	document.querySelectorAll('#page-edit.hidden').forEach(el=>{
		el.classList.remove('hidden');
	});
});

document.addEventListener("click", function(ev) {	
	if(ev.target.id == 'page-edit'){
		editPostSwitch(ev);
	}
});

// after scripts have been loaded over AJAX
document.addEventListener("scriptsloaded", function() {
	document.querySelectorAll('#frontend_upload_form').forEach(el=>el.classList.remove('hidden'));

	document.querySelectorAll('.content-wrapper').forEach(el=>el.classList.remove('hidden'));
	document.querySelectorAll('.loaderwrapper:not(.hidden)').forEach(el=>el.remove());
});