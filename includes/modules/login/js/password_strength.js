import {
	fetchRestApi
} from './shared.js';

function checkPassStrength() {
	var pass1 		= document.querySelector('[name="pass1"]');
	var pass2 		= document.querySelector('[name="pass2"]');
	var indicator1	= document.querySelector('#pass-strength-result1');
	var indicator2	= document.querySelector('#pass-strength-result2');

	var pwsL10n = {
		empty: "Strength indicator",
		short: "Improve this password, maybe add special characters",
		bad: "Keep going!",
		good: "Allmost there, make it a little bit stronger",
		strong: "Well done!",
		mismatch: "Mismatch"
	};

	if (pass1.value == '') {
		indicator1.textContent = pwsL10n.empty;
		indicator1.classList.add('hidden');
	}else{
		updateIndicator(indicator1);
	}
	
	if (pass2.value == '') {
		indicator2.textContent = pwsL10n.empty;
		indicator2.classList.add('hidden');
	}else{
		updateIndicator(indicator2);
		
		//hide password strength indicator1
		if (pass1.value != '') {
			indicator1.classList.add('hidden');
		}
	}
}

function updateIndicator(indicator){
	indicator.classList.remove('short,bad,good,strong');

	var pass1 		= document.querySelector('[name="pass1"]');
	var pass2 		= document.querySelector('[name="pass2"]');
	var strength	= wp.passwordStrength.meter(pass1.value, wp.passwordStrength.userInputDisallowedList(), pass2.value);

	switch (strength) {
		case 2:
			indicator.classList.add('bad');
			indicator.textContent = pwsL10n.bad;
			break;
		case 3:
			indicator.classList.add('good');
			indicator.textContent = pwsL10n.good;
			break;
		case 4:
			indicator.classList.add('strong');
			indicator.textContent = pwsL10n.strong;
			break;
		case 5:
			indicator.classList.add('short');
			indicator.textContent = pwsL10n.mismatch;
			break;
		default:
			indicator.classList.add('short');
			indicator.textContent = pwsL10n['short'];
	}
	
	indicator.classList.remove('hidden');
}

async function submitPasswordChange(event){
	var form	= event.target.closest('form');

	// show loader
	form.querySelector('.submit_wrapper .loadergif').classList.remove('hidden');

	var formData	= new FormData(form);

	var response	= await fetchRestApi('update_password', formData);

	if(response){
		Main.displayMessage(response.message);

		// redirect to login again
		location.href	= response.redirect
	}

	// hide loader
	form.querySelector('.submit_wrapper .loadergif').classList.add('hidden');
}
					
document.addEventListener("DOMContentLoaded",function() {
	console.log('Password strength.js loaded');
	
	document.querySelectorAll('.changepass').forEach(el=>el.addEventListener("keyup",checkPassStrength));

	
	document.querySelectorAll('[name="update_password"]').forEach(el=>el.addEventListener("click",submitPasswordChange));
});