// Import the registration hook
import useRegistration from './node_modules/@web-auth/webauthn-helper/src/useRegistration.js';

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

	// We clicked a 2fa method option (authenticator or e-mail)
	if(target.classList.contains('twofa_option_checkbox')){
		//hide all options
		document.querySelectorAll('.twofa_option').forEach(el=>el.classList.add('hidden'));

		// Show setup for this method
		var wrapper	= document.getElementById('setup-'+target.value);
		wrapper.classList.remove('hidden');

		if (isMobileDevice()){
			wrapper.querySelectorAll('.mobile.hidden').forEach(el=>el.classList.remove('hidden'));
		}else{
			wrapper.querySelectorAll('.desktop.hidden').forEach(el=>el.classList.remove('hidden'));
		}

		//Show submit button
		target.closest('form').querySelector('.form_submit').classList.remove('hidden');
	}
});

// Register a new fingerprint
const register = useRegistration(
  {
    actionUrl: sim.base_url+'/wp-json/sim/v1/store_fingerprint',
    optionsUrl: sim.base_url+'/wp-json/sim/v1/fingerprint_options',
    actionHeader:{'X-WP-Nonce': sim.restnonce}
  },
  {
    'X-WP-Nonce': sim.restnonce
  }
);

function remove_web_authenticator(event){
	var target  = event.target;
	var table   = target.closest('table');
	var row     = target.closest('tr');

	var formData	= new FormData();
	formData.append('action','remove_web_authenticator');
	formData.append('key',target.dataset.key);

	showLoader(target, true);

	fetch(sim.ajax_url, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData
	})
	.then((response) => {
		if(response.ok){
			if(table.rows.length==2){
			table.remove();
			}else{
			row.remove();
			}
		}
		response.text();
	})
	.then((response) => display_message(response))
}

//Start registration with button click
var el = document.querySelector("#add_fingerprint")
if(el != null){
  el.addEventListener('click', function(event){
    var identifier  = event.target.closest('#webauthn_wrapper').querySelector('[name="identifier"]').value;
    if(identifier == ''){
      display_message('Please specify a device name', 'error');
      return;
    }

    //show loader
    document.getElementById('add_webauthn').classList.add('hidden');
    var loader_html = `<div id="loader_wrapper" style='margin-bottom:20px;'><span class="message">Please authenticate...</span><img class="loadergif" src="${sim.loading_gif}" height="30px;"></div>`;
    document.getElementById('add_webauthn').insertAdjacentHTML('afterEnd',loader_html);

    register({
      identifier: identifier,
    })
    //registering succesfull
    .then((response) => {
      var wrapper = document.getElementById('webautn_devices_wrapper');

      //add authenthn table
      if(wrapper == null){
        document.getElementById('webauthn_wrapper').insertAdjacentHTML('beforeEnd',response)
      }else{
        wrapper.outerHTML = response;
      }

      //labels for use
      setTableLabel();

      display_message('Registration success', 'success');
    })
    .catch((error) => {
      document.getElementById('add_webauthn').classList.remove('hidden');
      display_message('Registration failure: '+error['message'], 'error'); 
      console.error('Registration failure: '+error['message']) }
    )
    .finally(()=>{
      //remove loader
      document.querySelector('#loader_wrapper').remove();
    })
  });
}

document.querySelectorAll('.remove_webauthn').forEach(el=>{
  el.addEventListener('click',remove_web_authenticator);
});