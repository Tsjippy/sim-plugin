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
		tab.querySelector('.wrapper').innerHTML	= response.html;

		// after scripts have been loaded over AJAX
		tab.addEventListener("scriptsloaded", function(event) {
			console.log(event.target);

			event.target.querySelector('.wrapper.hidden').classList.remove('hidden');

			event.target.querySelector('.tabloader').remove();
		});

		addStyles(response, tab);	// runs also the afterScriptsLoaded function
	}else{
		console.error(tab);
	}
}

document.addEventListener("DOMContentLoaded", function() {

	// only load when the loader image is still there
	document.querySelectorAll(`.wrapper.loading`).forEach(loader => {
		loader.classList.remove('loading');
		setTimeout(loadTab, 1000, loader.parentNode);
	});
});


console.log('user page js loaded');