import {
	closeMobileMenu,
    preparePublicKeyCredentials,
    preparePublicKeyOptions,
	fetchRestApi
} from './shared.js';

//Add an event listener to the login or register button
console.log("Login.js loaded");

//show loader
async function requestLogin(){
	//hide everything
	document.querySelectorAll('.authenticator_wrapper:not(.hidden)').forEach(el=>{
		el.classList.add('hidden');
		el.classList.add('current-method');
	});
	
	//show login message
	document.getElementById('logging_in_wrapper').classList.remove('hidden');

	var form 		= document.getElementById('loginform');
	var formData	= new FormData(form);
	form.querySelectorAll('.hidden [required]').forEach(el=>{el.required = false});
	var validity	= form.reportValidity();
	//if not valid return
	if(!validity){
		return;
	}

	await Main.waitForInternet();

	var response	= await fetchRestApi('request_login', formData);

	if(response){
		document.querySelector('#logging_in_wrapper .status_message').textContent='Succesfully logged in, redirecting...';

		if(!response.startsWith('http')){
			location.reload();
		}else{
			location.href = response;
		}
	}else{
		document.getElementById('logging_in_wrapper').classList.add('hidden');

		document.querySelector('.current-method').classList.remove('hidden');
	}
}

// Send request to start webauthn
async function verifyWebauthn(methods){	
	//show webauthn messages
	document.getElementById('webauthn_wrapper').classList.remove('hidden');

	var username	= document.getElementById('username').value;

	try{
		var formData			= new FormData();
		formData.append('username', username);

		var response			= await fetchRestApi('auth_start', formData);
		if(!response){
			throw new Error('auth_start failed');
		}

		var publicKey			= preparePublicKeyOptions(response);
		console.log(publicKey);

		// Update message
		document.querySelector('#webauthn_wrapper .status_message').textContent	= 'Waiting for biometric';

		// Verify on device
		var credentials			= await navigator.credentials.get({	publicKey });

		// Update message
		document.querySelector('#webauthn_wrapper .status_message').textContent	= 'Verifying...';

		// Verify on the server
		const publicKeyCredential 	= preparePublicKeyCredentials(credentials);
		formData					= new FormData();
		formData.append('publicKeyCredential', JSON.stringify(publicKeyCredential));
		response					= await fetchRestApi('auth_finish', formData);
		if(!response){
			throw new Error('auth_finish failed');
		}

		//authentication success
		requestLogin();
	}catch (error){
		if(document.getElementById('logging_in_wrapper').classList.add('hidden'));

		//authentication failed
		document.querySelector('#webauthn_wrapper').classList.add('hidden');

		if(methods.length == 1){
			showMessage('Authentication failed, please setup an additional login factor.');
			requestLogin();
		}else{
			var message;
			if(error['message'] == "No authenticator available"){
				message = "No biometric login for this device found. <br>Give verification code.";
			}else{
				message = 'Web authentication failed, please give verification code.';
				message += '<button type="button" class="button small" id="retry_webauthn" style="float:right;margin-top:-20px;">Retry</button>';
				console.error('Authentication failure: '+error['message']);
			}
			showMessage(message);

			//Show other 2fa fields
			showTwoFaFields(methods);
		}
	}
}

// Request email code for 2fa login
async function requestEmailCode(){
	//add new one
	var loader				= "<img id='loader' src='"+sim.loadingGif+"' style='height:30px;margin-top:-6px;float:right;'>";
	showMessage(`Sending e-mail... ${loader}`);

	var username	= document.getElementById('username').value;
	var formData	= new FormData();
	formData.append('username',username);

	var response	= await fetchRestApi('request_email_code', formData, false);
	
	if(response){
		showMessage(response);
	}else{
		showMessage(`Sending e-mail failed`);
	}
}

// Check if a valid username and password is submitted
async function verifyCreds(){
	var username	= document.getElementById('username').value;
	var password	= document.getElementById('password').value;

	// Check if the fields are filled
	if(username != '' && password != ''){
		document.querySelector('#check_cred_wrapper .loadergif').classList.remove('hidden');
	}else{
		showMessage('Please give an username and password!');
		return;
	}

	// Make sure we have a internet connection
	await Main.waitForInternet();

	var formData	= new FormData();
	formData.append('username',username);
	formData.append('password',password);

	var response	= await fetchRestApi('check_cred', formData);

 	if(response){
		if(response == 'false') {
			showMessage('Invalid login, try again');

			// Password reset form
			document.getElementById('captcha-form').classList.remove('hidden');
			
			// hide loader
			document.querySelector('#check_cred_wrapper .loadergif').classList.add('hidden');
		} else {
			addMethods(response);
		}
	}
}

//request password reset e-mail
async function resetPassword(target){
	var username	= document.getElementById('username').value;

	if(username == ''){
		Main.displayMessage('Specify your username first','error');
		return;
	}
	let captchaForm	= document.getElementById('captcha-form');
	//check if captcha visible
	if(captchaForm.classList.contains('hidden')){
		if(captcha.querySelector('iframe') == null){
			Main.displayMessage('Captcha failed to load, please refresh the page','error');
		}

		//show captcha
		captchaForm.classList.remove('hidden');

		//change button text
		target.text	= 'Send password reset request';
	}else{
		target.classList.add('hidden');
		var loader = Main.showLoader(target, false, 'Sending e-mail...   ');

		var formData	= new FormData();
		formData.append('username',username);
		
		var response	= await fetchRestApi('request_pwd_reset', formData);

		if (response) {
			Main.displayMessage(response,'success');
		}

		loader.remove();
		target.classList.remove('hidden');
	}
}

// request a new user account
async function requestAccount(target){
	var form 		= target.closest('form');

	// Show loader
	form.querySelector('.loadergif').classList.remove('hidden');

	var formData	= new FormData(form);

	var response	= await fetchRestApi('request_user_account', formData);
	
	if(response){
		Main.displayMessage(response);
	}

	// reset form 
	form.reset();

	// Hide loader
	form.querySelector('.loadergif').classList.add('hidden');
}

function showMessage(message){
	document.querySelector("#login_wrapper .message").innerHTML= DOMPurify.sanitize(message);
}

let webauthnSupported	= false;
let abortController;
let abortSignal;
let modal;

async function processCredential(credential){
	if (credential) {
		let username = String.fromCodePoint(...new Uint8Array(credential.response.userHandle));
		console.log(username);

		// Verify on the server
		const publicKeyCredential 	= preparePublicKeyCredentials(credential);
		formData					= new FormData();
		formData.append('publicKeyCredential', JSON.stringify(publicKeyCredential));
		response					= await fetchRestApi('auth_finish', formData);
		if(!response){
			throw new Error('auth_finish failed');
		}

		//authentication success
		requestLogin();

	} else {
		console.log("Credential returned null");

		document.getElementById('usercred_wrapper').classList.remove('hidden');
		document.getElementById('webauthn_wrapper').classList.add('hidden');
	}
}

let startConditionalRequest = async () => {
	if (window.PublicKeyCredential.isConditionalMediationAvailable) {
		console.log("Conditional UI is understood by the browser");
		if (!await window.PublicKeyCredential.isConditionalMediationAvailable()) {
			console.log("Conditional UI is understood by your browser but not available");
			return;
		}
	} else {
		if (!navigator.credentials.conditionalMediationSupported) {
			console.log("Your browser does not implement Conditional UI (are you running the right chrome/safari version with the right flags?)");
			return;
		} else {
			console.log("This browser understand the old version of Conditional UI feature detection");
		}
	}

	openLoginModal();

	document.getElementById('usercred_wrapper').classList.add('hidden');
	document.getElementById('webauthn_wrapper').classList.remove('hidden');

	showMessage('Performing automatic passkey login');

	abortController = new AbortController();
	abortSignal 	= abortController.signal;

	try {
		let formData			= new FormData();
		formData.append('username', '');

		let response			= await fetchRestApi('auth_start', formData);
		if(!response){
			throw new Error('auth_start failed');
		}

		let publicKey			= preparePublicKeyOptions(response);
		console.log(publicKey);

		let credentialPromise = navigator.credentials.get({
			mediation: 'conditional',
			publicKey: {
				challenge: publicKey.challenge,
			}
		}).then(credential=>processCredential(credential)).catch((err) => {
			console.error(err);
			return "default response";
		});

		const onTimeout = new Promise((resolve, reject) => {
			setTimeout(resolve, 500, false);
		});

		let credential	= await Promise.race([credentialPromise, onTimeout]);

		processCredential(credential);
	} catch (error) {
		document.getElementById('usercred_wrapper').classList.remove('hidden');
		document.getElementById('webauthn_wrapper').classList.add('hidden');

		showMessage('Passkey login failed, try using your username and password');

		if (error.name == "AbortError") {
			console.log("request aborted");
			return;
		}
		console.log(error);
	}
}

function checkWebauthnAvailable(){
	if (window.PublicKeyCredential) {
		PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then((available) => {
			if (available) {
				console.log("Supported.");
				webauthnSupported = true;

				startConditionalRequest();
			} else {
				console.log("WebAuthn supported, Platform Authenticator not supported.");
			}
		})
		.catch((err) => {
			console.error("Something went wrong.");
			console.error(err);
		});
	} else {
		console.log("Not supported.");
	}
}

// Display the form for the 2fa email or authenticator code
function showTwoFaFields(methods){
	if(methods.includes('email')){
		requestEmailCode();
	}

	//show 2fa fields
	for(const element of methods){
		if(element == 'webauthn'){
			//do not show webauthn
			continue;
		}
		var wrapper	= document.getElementById(element+'_wrapper');
		if(wrapper != null){
			wrapper.classList.remove('hidden');
			wrapper.querySelectorAll('input').forEach(el=>window.setTimeout(() => el.focus(), 0));
		}
	}

	//enable login button
	document.querySelector("#login_button").disabled			= '';
	//show login button
	document.querySelector('#submit_login_wrapper').classList.remove('hidden');
}

function addMethods(result){
	document.querySelector('#check_cred_wrapper .loadergif').classList.add('hidden');
	
	if(typeof(result) == 'string' && result){
		//hide login form
		document.querySelectorAll("#usercred_wrapper, #captcha-form").forEach(el=>el.classList.add('hidden'));

		document.getElementById('logging_in_wrapper').classList.remove('hidden');

		if(location.href != result){
			//redirect to the returned webpage
			location.href	= result;
		}else{
			//close login modal
			Main.hideModals();
		}
	}else if(!result){
		//incorrect creds add message, but only once
		showMessage('Invalid username or password!');
	}else if(typeof(result) == 'object'){
		//hide cred fields
		document.querySelectorAll("#usercred_wrapper, #captcha-form").forEach(el=>el.classList.add('hidden'));

		//hide messsages
		showMessage('');

		if(result.find(element => element == 'webauthn')){
			if(webauthnSupported){
				//correct creds and webauthn enabled
				verifyWebauthn(result);
			}else if(result.length == 1){
				showMessage('You do not have a valid second login method for this device, please add one.');
				requestLogin();
			}else{
				showTwoFaFields(result);
			}
		}else{
			//correct creds and 2fa enabled
			showTwoFaFields(result);
		}
	}else{
		//something went wrong, reload the page
		location.reload();
	}
}

document.addEventListener('keypress', function (e) {
    if (e.key === 'Enter' && document.querySelector("#usercred_wrapper") != null){
		if(!document.querySelector("#usercred_wrapper").classList.contains('hidden')) {
			verifyCreds();
		}else if(!document.querySelector("#submit_login_wrapper").classList.contains('hidden')){
			requestLogin();
		}
	}
});

export function togglePassworView(ev){
	var target	= ev.target;

	if(ev.target.tagName == 'IMG'){
		target	= ev.target.parentNode;
	}

	if(target.dataset.toggle == '0'){
		target.title								= 'Hide password';
		target.dataset.toggle						= '1';
		target.innerHTML							= target.innerHTML.replace('invisible', 'visible');
		target.closest('.password').querySelector('input[type="password"]').type	= 'text';
	}else{
		target.title								= 'Show password';
		target.dataset.toggle						= '0';
		target.innerHTML							= target.innerHTML.replace('visible', 'invisible');
		target.closest('.password').querySelector('input[type="text"]').type	= 'password';
	}
}

// Show the modal with the login form
function openLoginModal(){
	// Allready load the captcha for password reset so it is available when we need it
	//waitForCaptcha();

	// Make sure the menu is closed
	closeMobileMenu();

	//prevent page scrolling
	document.querySelector('body').style.overflowY = 'hidden';

	modal	= document.getElementById('login_modal');

	//reset form
	modal.querySelectorAll('form > div:not(.hidden)').forEach(el=>el.classList.add('hidden'));
	modal.querySelector('#usercred_wrapper').classList.remove('hidden');

	modal.classList.remove('hidden');

	window.setTimeout(() => modal.querySelector('#username').focus(), 0);
}

var observer = new MutationObserver(function(mutations) {
	mutations.forEach(function(mutation) {
		if (mutation.attributeName === "data-hcaptcha-response" && document.querySelector('.h-captcha iframe').dataset.hcaptchaResponse.length > 1000) {
			resetPassword(document.querySelector('#captcha-form>a'));
		}
	});
});

function waitForCaptcha(){
	// Do not try to load if there is not even a h-captcha element
	if(document.querySelector('.h-captcha') == null) return;

	// Wait till the iframe is loaded before we attach the observer
	setTimeout(function() {
		var iframe	= document.querySelector('.h-captcha iframe');
		if(iframe	== null){
			waitForCaptcha();
		}else{
			observer.observe(iframe, {
				attributes: true
			});
		}
	}, 1000);
}

document.addEventListener('DOMContentLoaded', () => {
	//check if the current browser supports webauthn
	checkWebauthnAvailable();
});

document.addEventListener("click", function(event){
	var target = event.target;

	if(target.matches('.login')){
		// Show modal with login form
		openLoginModal();
	}else if(target.id == 'check_cred'){
		// Check if a valid username and password is submitted
		verifyCreds();
	}else if(target.id == "login_button"){
		// Submit the login form when averything is ok
		requestLogin();
	}else if(target.closest('.toggle_pwd_view') != null){
		togglePassworView(event);
	}else if(target.id == "lost_pwd_link"){
		resetPassword(target);
	}else if(target.id == 'retry_webauthn'){
		showMessage('');
		verifyWebauthn([]);
	}else if(target.name == 'request_account'){
		requestAccount(target);
	}
});

//prevent zoom in on login form on a iphone
const addMaximumScaleToMetaViewport = () => {
	const el = document.querySelector('meta[name=viewport]');
  
	if (el !== null) {
	  let content = el.getAttribute('content');
	  let re = /maximum\-scale=[0-9\.]+/g;
  
	  if (re.test(content)) {
		  content = content.replace(re, 'maximum-scale=1.0');
	  } else {
		  content = [content, 'maximum-scale=1.0'].join(', ')
	  }
  
	  el.setAttribute('content', content);
	}
};

const checkIsIOS = () =>/iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

if (checkIsIOS()) {
	addMaximumScaleToMetaViewport();
}

document.querySelectorAll('.login').forEach(el=>{
	el.classList.remove('hidden');
});
