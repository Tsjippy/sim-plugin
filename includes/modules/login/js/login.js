import {close_mobile_menu} from './shared.js';

//Add an event listener to the login or register button
console.log("Login.js loaded");

// Import the login hook
import useLogin from './node_modules/@web-auth/webauthn-helper/src/useLogin.js';

// function to run webauthn login
const request_webauthn = useLogin(
	{
		actionUrl: sim.base_url+'/wp-json/sim/v1/auth_finish',
		optionsUrl: sim.base_url+'/wp-json/sim/v1/auth_start',
		actionHeader:{'X-WP-Nonce': sim.restnonce}
	},
	{
		'X-WP-Nonce': sim.restnonce
	}
);

function show_message(message){
	document.querySelector("#login_wrapper .message").innerHTML= message;
}

let webauthn_supported	= false;
function check_webauthn_available(){
	if (window.PublicKeyCredential) {
		PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then((available) => {
			if (available) {
				console.log("Supported.");
				webauthn_supported = true;
			} else {
				console.log("WebAuthn supported, Platform Authenticator not supported.");
			}
		})
		.catch((err) => console.log("Something went wrong."));
	} else {
		console.log("Not supported.");
	}
}

function show_2fa_code_fields(methods){
	if(methods.includes('email')){
		request_email_code();
	}

	//show 2fa fields
	for(var x=0; x<methods.length; x++){
		if(methods[x] == 'webauthn'){
			//do not show webauthn
			continue;
		}
		var wrapper	= document.getElementById(methods[x]+'_wrapper');
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

function verify_webauthn(methods){	
	//show webauthn messages
	document.getElementById('webauthn_wrapper').classList.remove('hidden');

	//initiate login
	request_webauthn({
		user_login: document.getElementById('username').value,
	})
	.then((response) => {
		//authentication success
		request_login();
	})
	.catch((error) => {
		if(document.getElementById('logging_in_wrapper').classList.add('hidden'));

		//authentication failed
		document.querySelector('#webauthn_wrapper').classList.add('hidden');

		if(methods.length == 1){
			show_message('Authentication failed, please setup an additional login factor.');
			request_login();
		}else{
			if(error['message'] == "No authenticator available"){
				var message = "No biometric login for this device found. <br>Give verification code.";
			}else{
				var message = 'Web authentication failed, please give verification code.';
				message += '<button type="button" class="button small" id="retry_webauthn" style="float:right;margin-top:-20px;">Retry</button>';
				console.error('Authentication failure: '+error['message']);
			}
			show_message(message);

			//Show other 2fa fields
			show_2fa_code_fields(methods);
		}
	})
	;
}

function add_methods(result){
	document.querySelector('#check_cred_wrapper .loadergif').classList.add('hidden');
	
	if(typeof(result) == 'string' && result != false){
		//hide login form
		document.querySelectorAll("#usercred_wrapper, #login_nav").forEach(el=>el.classList.add('hidden'));

		document.getElementById('logging_in_wrapper').classList.remove('hidden');

		if(location.href != result){
			//redirect to the returned webpage
			location.href	= result;
		}else{
			//close login modal
			hide_modals();
		}
	}else if(result == false){
		//incorrect creds add message, but only once
		if(document.querySelector('.invalidcred') == null){
			show_message('Invalid username or password!');
		}
	}else if(typeof(result) == 'object'){
		//hide cred fields
		document.querySelectorAll("#usercred_wrapper, #login_nav").forEach(el=>el.classList.add('hidden'));

		//hide messsages
		show_message('');

		if(result.find(element => element == 'webauthn')){
			if(webauthn_supported){
				//correct creds and webauthn enabled
				verify_webauthn(result);
			}else if(result.length == 1){
				show_message('You do not have a valid second login method for this device, please add one.');
				request_login();
			}else{
				show_2fa_code_fields(result);
			}
		}else{
			//correct creds and 2fa enabled
			show_2fa_code_fields(result);
		}
	}else{
		//something went wrong, reload the page
		location.reload();
	}
}

async function request_email_code(){
	//add new one
	var loader				= "<img id='loader' src='"+sim.loading_gif+"' style='height:30px;margin-top:-6px;float:right;'>";
	show_message(`Sending e-mail... ${loader}`);

	var username	= document.getElementById('username').value;
	var formData	= new FormData();
	formData.append('action','request_email_code');
	formData.append('username',username);

 	var response = await fetch(sim.ajax_url, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData
	});

	var message = await response.text();
	
	if(response.ok){
		show_message(message);
	}else{
		display_message(message, 'error');
	}
}

async function verify_creds(){
	var username	= document.getElementById('username').value;
	var password	= document.getElementById('password').value;
	if(username != '' && password != ''){
		document.querySelector('#check_cred_wrapper .loadergif').classList.remove('hidden');
	}else{
		if(document.querySelector('.missingcred') == null){
			show_message('Please give an username and password!');
		}
		return;
	}

	await waitForInternet();

	var formData	= new FormData();
	formData.append('action','check_cred');
	formData.append('username',username);
	formData.append('password',password);

 	var response = await fetch(sim.ajax_url, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData
	});

	var text = await response.text();
	
	if(response.ok){
		try {
			var methods = JSON.parse(text);
			add_methods(methods);
		} catch (e) {
			location.href=text;
		}
	}else{
		display_message(text, 'error');
		hide_modals();
	}
}

//show loader
async function request_login(){
	//hide everything
	document.querySelectorAll('.authenticator_wrapper:not(.hidden)').forEach(el=>el.classList.add('hidden'));
	
	//show login messages
	document.getElementById('logging_in_wrapper').classList.remove('hidden');

	var form 		= document.getElementById('loginform');
	var formData	= new FormData(form);
	form.querySelectorAll('.hidden [required]').forEach(el=>{el.required = false});
	form.reportValidity();
	//if not valid return
	if(form.checkValidity() == false){
		return;
	}

	await waitForInternet();

	var response = await fetch(sim.ajax_url, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData
	});

	var message = await response.text();

	if(response.ok){
		document.querySelector('#logging_in_wrapper .status_message').textContent='Succesfully logged in, redirecting...';
		
		if(message == 'Login successful'){
			location.reload();
		}else{
			location.href = message;
		}
	}else{
		document.querySelectorAll('.authenticator_wrapper input').forEach(el=>{
			if(el.value != ''){
				el.closest('.authenticator_wrapper').classList.remove('hidden');
				el.value	= '';
			}
		})
		document.getElementById('logging_in_wrapper').classList.add('hidden');
		document.getElementById('logging_in_wrapper').classList.add('hidden');
		show_message(message,'error');
	}
};

document.addEventListener('keypress', function (e) {
    if (e.key === 'Enter'){
		if(!document.querySelector("#usercred_wrapper").classList.contains('hidden')) {
			verify_creds(e);
		}else if(!document.querySelector("#submit_login_wrapper").classList.contains('hidden')){
			request_login();
		}
	}
});

function toggle_pwd_view(ev){
	if(ev.target.tagName == 'IMG'){
		var target	= ev.target.parentNode;
	}else{
		var target	= ev.target;
	}

	if(target.dataset.toggle == '0'){
		target.title								= 'Hide password';
		target.dataset.toggle						= '1';
		target.innerHTML							= target.innerHTML.replace('invisible', 'visible');
		document.getElementById('password').type	= 'text';
	}else{
		target.title								= 'Show password';
		target.dataset.toggle						= '0';
		target.innerHTML							= target.innerHTML.replace('visible', 'invisible');
		document.getElementById('password').type	= 'password';
	}
};

function open_login_modal(){
	waitforcaptcha();

	close_mobile_menu();

	//prevent page scrolling
	document.querySelector('body').style.overflowY = 'hidden';

	var modal	= document.getElementById('login_modal');

	//reset form
	modal.querySelectorAll('form > div:not(.hidden)').forEach(el=>el.classList.add('hidden'));
	modal.querySelector('#usercred_wrapper').classList.remove('hidden');

	modal.classList.remove('hidden');

	window.setTimeout(() => modal.querySelector('#username').focus(), 0);
}

var observer = new MutationObserver(function(mutations) {
	mutations.forEach(function(mutation) {
		if (mutation.attributeName === "data-hcaptcha-response" && document.querySelector('.h-captcha iframe').dataset.hcaptchaResponse.length > 1000) {
			reset_password(document.querySelector('#login_nav>a'));
		}
	});
});

function waitforcaptcha(){
	setTimeout(function() {
		var iframe	= document.querySelector('.h-captcha iframe');
		if(iframe	== null){
			waitforcaptcha();
		}else{
			observer.observe(iframe, {
				attributes: true
			});
		}
	}, 1000);
}

//request password reset e-mail
async function reset_password(target){
	var username	= document.getElementById('username').value;

	if(username == ''){
		display_message('Specify your username first','error');
		return;
	}
	var captcha	= target.previousElementSibling;
	//check if captcha visible
	if(captcha.classList.contains('hidden')){
		if(captcha.querySelector('iframe') == null){
			display_message('Captcha failed to load, please refresh the page','error');
		}

		//show captcha
		captcha.classList.remove('hidden');

		//change button text
		target.text	= 'Send password reset request';
	}else{
		target.classList.add('hidden');
		var loader = showLoader(target, false, 'Sending e-mail...   ');

		var formData	= new FormData(target.closest('form'));
		formData.append('action','request_pwd_reset');
		formData.append('username',username);

		var response = await fetch(sim.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		});

		var message = await response.text();

		if (response.ok) {
			display_message(message,'success');
		}else{
			display_message(message,'error');
		}

		loader.remove();
		target.classList.remove('hidden');
	}
};

//check if the current device supports webauthn
check_webauthn_available();

document.addEventListener("click", function(event){
	var target = event.target;

	if(target.id == "login_button"){
		request_login();
	}else if(target.id == 'check_cred'){
		verify_creds();
	}else if(target.id == 'toggle_pwd_view' || target.parentNode.id == 'toggle_pwd_view'){
		toggle_pwd_view(event);
	}else if(target.id == 'login' || target.parentNode.id == 'login'){
		open_login_modal();
	}else if(target.id == "lost_pwd_link"){
		reset_password(target);
	}else if(target.id == 'retry_webauthn'){
		show_message('');
		verify_webauthn([]);
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