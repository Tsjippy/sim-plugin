window['saved2fa'] = function(result,responsdata){
	if (result.status >= 200 && result.status < 400) {
        document.querySelectorAll('[id^="setup-"]:not(.hidden)').forEach(el=>el.classList.add('hidden'));
	}
}

function check_webauthn_available(){
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

document.addEventListener("DOMContentLoaded",function() {
	console.log("2fa.js loaded");

	//hide the webauthn table if not possible
	var el = document.getElementById('webauthn_wrapper');
	if(el != null){
		check_webauthn_available();
	}
	
});

document.addEventListener("click",function(event) {
	var target	= event.target;

	if(target.classList.contains('twofa_option_checkbox')){
		//hide all options
		document.querySelectorAll('.twofa_option').forEach(el=>el.classList.add('hidden'));

		var wrapper	= document.getElementById('setup-'+target.value);
		wrapper.classList.remove('hidden');

		if (isMobileDevice()){
			wrapper.querySelectorAll('.mobile.hidden').forEach(el=>el.classList.remove('hidden'));
		}else{
			wrapper.querySelectorAll('.desktop.hidden').forEach(el=>el.classList.remove('hidden'));
		}

		target.closest('form').querySelector('.form_submit').classList.remove('hidden');
	}

	if(target.id=='enable_email_2fa'){

	}
});