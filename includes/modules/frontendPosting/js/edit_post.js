import { callback, addStyles } from './../../../js/ajax_import.js';

let editPostSwitch = async function (event){
	let afterScriptsLoadedFinal	= function (){
		document.getElementById('frontend_upload_form').classList.remove('hidden');

		wrapper.classList.remove('hidden');
		loader.remove();
	}

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
		callback	= afterScriptsLoadedFinal;

		addStyles(response);
	}else{
		loader.outerHTML	= button.outerHTML;
	}
}

document.addEventListener("DOMContentLoaded", function() {
	console.log("Edit post.js loaded");
	
	document.querySelectorAll('#page-edit').forEach(el=>{
		el.classList.remove('hidden');
	});
});

document.addEventListener("click", function(ev) {	
	if(ev.target.id == 'page-edit'){
		editPostSwitch(ev);
	}
});