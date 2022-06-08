// Import the registration hook
import {
    preparePublicKeyCredentials,
    preparePublicKeyOptions,
	fetchRestApi
} from './shared.js';

async function saveTwofaSettings(target){
	// Show loader
	var loader	= target.closest('.submit_wrapper').querySelector('.loadergif');
	loader.classList.remove('hidden');

	var form		= target.closest('form');
	var formData	= new FormData(form);
	var response 	= await fetchRestApi('save_2fa_settings', formData);

	if(response){
		form.querySelectorAll('[id^="setup-"]:not(.hidden)').forEach(el=>el.classList.add('hidden'));

		Main.displayMessage(response);
	}

	loader.classList.add('hidden');
}

function checkWebauthnAvailable(){
	if (window.PublicKeyCredential) {
		PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then((available) => {
			if (available) {
				console.log("Supported.");
				document.getElementById('webauthn_wrapper').classList.remove('hidden');
			} else {
				console.log("WebAuthn supported, Platform Authenticator not supported.");
			}
		})
		.catch((err) => console.log("Something went wrong."));
	} else {
		console.log("Not supported.");
	}
}

function showTwofaSetup(target) {
	//hide all options
	document.querySelectorAll('.twofa_option').forEach(el=>el.classList.add('hidden'));

	// Show setup for this method
	var wrapper	= document.getElementById('setup-'+target.value);
	wrapper.classList.remove('hidden');

	if (Main.isMobileDevice()){
		wrapper.querySelectorAll('.mobile.hidden').forEach(el=>el.classList.remove('hidden'));
	}else{
		wrapper.querySelectorAll('.desktop.hidden').forEach(el=>el.classList.remove('hidden'));
	}

	//Show submit button
	target.closest('form').querySelector('.form_submit').classList.remove('hidden');
}

async function removeWebAuthenticator(target){
	var table   = target.closest('table');
	var row     = target.closest('tr');

	var formData	= new FormData();
	formData.append('key',target.dataset.key);

	Main.showLoader(target, true);

	var response 	= await fetchRestApi('remove_web_authenticator', formData);

	if(response){
		if(table.rows.length==2){
			table.remove();
		}else{
			row.remove();
		}

		Main.displayMessage(response);
	}
}

//Start registration with button click
async function registerBiometric(target){
    var identifier  = target.closest('#webauthn_wrapper').querySelector('[name="identifier"]').value;
    if(identifier == ''){
		Main.displayMessage('Please specify a device name', 'error');
      return;
    }

    //show loader
    document.getElementById('add_webauthn').classList.add('hidden');
    var loaderHtml = `<div id="loader_wrapper" style='margin-bottom:20px;'><span class="message"></span><img class="loadergif" src="${sim.loadingGif}" height="30px;"></div>`;
    document.getElementById('add_webauthn').insertAdjacentHTML('afterEnd', loaderHtml);
	var message		= document.querySelector('#loader_wrapper .message');

	try{
		// Get biometric challenge
		var formData			= new FormData();
		formData.append('identifier', identifier);
		var response			= await fetchRestApi('fingerprint_options', formData);
		if(!response){
			throw 'Options retrieval failed';
		}
		var publicKey 			= preparePublicKeyOptions(response);

		// Update the message
		message.textContent  	= 'Please authenticate...';

		// Ask user to verify
		var credentials 		= await navigator.credentials.create({publicKey});

		// Update the message
		message.textContent  	= 'Saving authenticator...';

		// Store result
		var publicKeyCredential = preparePublicKeyCredentials(credentials);
		
		var formData			= new FormData();
		formData.append('publicKeyCredential', JSON.stringify(publicKeyCredential));
		var response			= await fetchRestApi('store_fingerprint', formData);
		if(!response){
			throw 'Storing biometric failed';
		}

		var wrapper 			= document.getElementById('webautn_devices_wrapper');
		if(wrapper == null){
			//add authenthn table
			document.getElementById('webauthn_wrapper').insertAdjacentHTML('beforeEnd', response);
		}else{
			//update authenthn table
			wrapper.outerHTML = response;
		}
  
		//labels for use
		setTableLabel();
  
		Main.displayMessage('Registration success');
	}catch(error){
		document.getElementById('add_webauthn').classList.remove('hidden');
		console.error(error);
	}

    document.querySelector('#loader_wrapper').remove();
}

document.addEventListener("DOMContentLoaded",function() {
	console.log("2fa.js loaded");

	//hide the webauthn table if not possible
	var el = document.getElementById('webauthn_wrapper');
	if(el != null){
		checkWebauthnAvailable();
	}	
});

document.addEventListener('click', ev =>{
	var target = ev.target;

	if(target.name == '2fa_methods[]'){
		showTwofaSetup(target);
	}

	if(target.matches('.remove_webauthn')){
		removeWebAuthenticator(target);
	}
	
	if(target.id == 'add_fingerprint'){
		registerBiometric(target);
	}

	if(target.name == 'save2fa'){
		saveTwofaSettings(target);
	}
})