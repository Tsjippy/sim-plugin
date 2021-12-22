console.log("Fingerprint.js loaded");

// Import the registration hook
import useRegistration from './node_modules/@web-auth/webauthn-helper/src/useRegistration.js';

// Create your register function.
// By default the urls are "/register" and "/register/options"
// but you can change those urls if needed.
const register = useRegistration(
  {
    actionUrl: simnigeria.base_url+'/wp-json/simnigeria/v1/store_fingerprint',
    optionsUrl: simnigeria.base_url+'/wp-json/simnigeria/v1/fingerprint_options',
    actionHeader:{'X-WP-Nonce': simnigeria.restnonce}
  },
  {
    'X-WP-Nonce': simnigeria.restnonce
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

 	fetch(simnigeria.ajax_url, {
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
    var loader_html = `<div id="loader_wrapper" style='margin-bottom:20px;'><span class="message">Please authenticate...</span><img class="loadergif" src="${simnigeria.loading_gif}" height="30px;"></div>`;
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
})