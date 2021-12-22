/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};

;// CONCATENATED MODULE: ./node_modules/@web-auth/webauthn-helper/src/common.js
// Predefined fetch function
const fetchEndpoint = (data, url, header) => {
  return fetch(
      url,
      {
        method: 'POST',
        credentials: 'same-origin',
        redirect: 'error',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          ...header
        },
        body: JSON.stringify(data),
      }
  );
} 


// Decodes a Base64Url string
const base64UrlDecode = (input) => {
  input = input
      .replace(/-/g, '+')
      .replace(/_/g, '/');

  const pad = input.length % 4;
  if (pad) {
    if (pad === 1) {
      throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
    }
    input += new Array(5-pad).join('=');
  }

  return window.atob(input);
};

// Converts an array of bytes into a Base64Url string
const arrayToBase64String = (a) => btoa(String.fromCharCode(...a));

// Prepares the public key options object returned by the Webauthn Framework
const preparePublicKeyOptions = publicKey => {
  //Convert challenge from Base64Url string to Uint8Array
  publicKey.challenge = Uint8Array.from(
      base64UrlDecode(publicKey.challenge),
      c => c.charCodeAt(0)
  );

  //Convert the user ID from Base64 string to Uint8Array
  if (publicKey.user !== undefined) {
    publicKey.user = {
      ...publicKey.user,
      id: Uint8Array.from(
          window.atob(publicKey.user.id),
          c => c.charCodeAt(0)
      ),
    };
  }

  //If excludeCredentials is defined, we convert all IDs to Uint8Array
  if (publicKey.excludeCredentials !== undefined) {
    publicKey.excludeCredentials = publicKey.excludeCredentials.map(
        data => {
          return {
            ...data,
            id: Uint8Array.from(
                base64UrlDecode(data.id),
                c => c.charCodeAt(0)
            ),
          };
        }
    );
  }

  if (publicKey.allowCredentials !== undefined) {
    publicKey.allowCredentials = publicKey.allowCredentials.map(
        data => {
          return {
            ...data,
            id: Uint8Array.from(
                base64UrlDecode(data.id),
                c => c.charCodeAt(0)
            ),
          };
        }
    );
  }

  return publicKey;
};

// Prepares the public key credentials object returned by the authenticator
const preparePublicKeyCredentials = data => {
  const publicKeyCredential = {
    id: data.id,
    type: data.type,
    rawId: arrayToBase64String(new Uint8Array(data.rawId)),
    response: {
      clientDataJSON: arrayToBase64String(
          new Uint8Array(data.response.clientDataJSON)
      ),
    },
  };

  if (data.response.attestationObject !== undefined) {
    publicKeyCredential.response.attestationObject = arrayToBase64String(
        new Uint8Array(data.response.attestationObject)
    );
  }

  if (data.response.authenticatorData !== undefined) {
    publicKeyCredential.response.authenticatorData = arrayToBase64String(
        new Uint8Array(data.response.authenticatorData)
    );
  }

  if (data.response.signature !== undefined) {
    publicKeyCredential.response.signature = arrayToBase64String(
        new Uint8Array(data.response.signature)
    );
  }

  if (data.response.userHandle !== undefined) {
    publicKeyCredential.response.userHandle = arrayToBase64String(
        new Uint8Array(data.response.userHandle)
    );
  }

  return publicKeyCredential;
};

;// CONCATENATED MODULE: ./node_modules/@web-auth/webauthn-helper/src/useRegistration.js


const useRegistration = ({actionUrl = '/register', actionHeader = {}, optionsUrl = '/register/options'}, optionsHeader = {}) => {
    return async (data) => {
        const optionsResponse = await fetchEndpoint(data, optionsUrl, optionsHeader);
        const json = await optionsResponse.json();
        const publicKey = preparePublicKeyOptions(json);
        const credentials = await navigator.credentials.create({publicKey});
		document.querySelector('#loader_wrapper .message').textContent  = 'Saving authenticator...';
        const publicKeyCredential = preparePublicKeyCredentials(credentials);
        const actionResponse = await fetchEndpoint(publicKeyCredential, actionUrl, actionHeader);
        if (! actionResponse.ok) {
            throw actionResponse;
        }
        const responseBody = await actionResponse.text();

        return responseBody !== '' ? JSON.parse(responseBody) : responseBody;
    };
};

/* harmony default export */ const src_useRegistration = (useRegistration);

;// CONCATENATED MODULE: ./register_fingerprint.js
console.log("Fingerprint.js loaded");

// Import the registration hook


// Create your register function.
// By default the urls are "/register" and "/register/options"
// but you can change those urls if needed.
const register = src_useRegistration(
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
/******/ })()
;