import { addStyles } from './../../../js/imports.js';

async function loadTab(tab){

	let formData    = new FormData();

	let params		= new URLSearchParams(window.location.search);
	if(params.get('userid') != null){
		formData.append('userid', params.get('userid'));
	}else{
    	formData.append('userid', sim.userId);
	}

	formData.append('tabname', tab.id.replace('_info', ''));

	let response = await FormSubmit.fetchRestApi('user_management/get_userpage_tab', formData);

	if(response){
		tab.querySelector('.loader-wrapper').innerHTML	= response.html;

		// after scripts have been loaded over AJAX
		tab.addEventListener("scriptsloaded", function(event) {
			event.target.querySelectorAll('.loader-wrapper.hidden').forEach(el=>el.classList.remove('hidden'));

			event.target.querySelectorAll('.tabloader').forEach(el=>el.remove());
		});

		addStyles(response, tab);	// runs also the afterScriptsLoaded function
	}else{
		console.error(tab);
	}
}

document.addEventListener("DOMContentLoaded", function() {

	// only load when the loader image is still there
	document.querySelectorAll(`.loader-wrapper.loading`).forEach(loader => {
		loader.classList.remove('loading');
		setTimeout(loadTab, 100, loader.parentNode);
	});
});


console.log('user page js loaded');