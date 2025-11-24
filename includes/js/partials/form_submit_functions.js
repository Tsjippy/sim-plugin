export function formReset(form){
	//reset form to the default
	form.reset();

	//hide loaders
	form.querySelectorAll('.loader-wrapper').forEach(el=>el.classList.add('hidden'));

	//hide button again if needed
	form.querySelectorAll('.multi-step-controls .form-submit').forEach(el=>el.classList.add('hidden'));
	
	//fix required fields
	form.querySelectorAll('[required]').forEach(el=>{el.required = false;});
	
	//empty any fileuploads
	form.querySelectorAll('.file_upload').forEach(el=>el.value='');

	//show hidden file uploads
	form.querySelectorAll('.upload-div.hidden').forEach(el=>el.classList.remove('hidden'));
	
	//remove doc previews
	document.querySelectorAll('.document-preview .document').forEach(el=>el.remove());
	
	if(form.querySelector('.formstep') != null){
		//mark all circles as not active anymore
		form.querySelectorAll('.multi-step-controls .step.finish').forEach(el=>el.classList.remove('finish'));
	
		//go back to the first part of the form in case of multistep
		FormFunctions.showFormStep(0, form);
	}
}

/**
 * 
 * @param {node} wrapper 	The Wrapper element of the inputs to be prepared
 */
export function prepareForValidation(wrapper){
	// make all inputs required that should be
	wrapper.querySelectorAll('.required input, .required textarea, .required select').forEach(el => el.required = true);

	//get all hidden required inputs and unrequire them
	wrapper.querySelectorAll('.hidden [required], select[required], .nice-select-search[required], .step-hidden [required]').forEach(el=>{el.required = false});

	// Get all multi-text inputs with a value and unrequire the main element
	wrapper.querySelectorAll(`.list-selection-list > .list-selection:first-child`).forEach(list => list.closest('.option-wrapper').querySelector(`input[type='text']`).required = false);

	// Get all file uploads with a value and unrequire them
	wrapper.querySelectorAll('.required input[type="file"]').forEach(el => {
		let elementWrapper	= el.closest('.input-wrapper');

		// this file input has already files
		if(elementWrapper.querySelector(`.document-preview input[type="hidden"]`) != null){
			el.required	= false;
		}
	});

	// enable disabled fields so it gets included and warnings are shown
	wrapper.querySelectorAll('[disabled][required]').forEach( el=>{
		el.disabled	= false;
		el.classList.add('was-disabled'); 
	});
}

export async function submitForm(target, url, extraData=''){
	let form		= target.closest('form');
	let validity 	= true;
	
	prepareForValidation(form);

	validity	= form.reportValidity();

	if(!validity){
		form.querySelectorAll(':invalid').forEach(el=>{
			if(el.validationMessage != undefined){
				Main.displayMessage(`${el.name} has an error:\n${el.validationMessage}`, 'error').then((value)=>{
					form.querySelectorAll('.formstep:not(.step-hidden)').forEach(formstep => formstep.classList.add('step-hidden'));

					if(el.closest('.step-hidden') != null){
						el.closest('.step-hidden').classList.remove('step-hidden');
					}

					el.focus();

					el.scrollIntoView();

					el.focus();
				});
			}
		});

		return false;
	}

	// Disable the submit button
	target.disabled	= true;

	/**
	 * Adjust the submit button text
	 */
	let buttonText 		= target.innerHTML;

	// get the first word
	let text			= buttonText.split(' ')[0];
	let vowels			= ['a', 'e', 'i', 'o', 'u'];
	let lastChar		= text.charAt(text.length - 1);
	let secondLatChar	= text.charAt(text.length - 2);
	
	if(lastChar == 'e'){
		// replace ie with y
		if(secondLatChar == 'i'){
			text		= text.substring(0, text.length - 2)+'y';
		}
		
		// remove the e
		else{
			text		= text.substring(0, text.length - 1);
		}
	}

	// duplicate the last letter if needed
	else if(
		secondLatChar != lastChar &&
		vowels.includes(secondLatChar) &&
		!vowels.includes(lastChar)
	){
		text			= text + text.substring(text.length - 1, text.length);
	}

	text				= text+'ing...';

	target.innerHTML	= Main.showLoader(null, false, 20, text, true, true);
	
	//save any tinymce forms
	if (typeof tinymce !== 'undefined') {
		tinymce.get().forEach((tn)=>{
			// only save when the visual tab is active
			if(!tn.hidden){
				tn.save()
			}
		});
	}

	// change the name of multiselects if needed
	document.querySelectorAll('select[multiple]:not([name$=\\[\\]])').forEach(el => {
		console.error(`Multi select ${el.name} should have [] at the end. I have fixed it for now`);
		el.name	= el.name+'[]';
	})
	
	let formData = new FormData(form);
	
	if(extraData != ''){
		formData.append('extra', extraData);
	}

	// disable fields again
	form.querySelectorAll('.was-disabled').forEach( el=>{
		el.disabled	= true;
		el.classList.remove('was-disabled'); 
	});

	if(form.dataset.addEmpty == true){
		//also append at least one off all checkboxes
		form.querySelectorAll('input[type="checkbox"]:not(:checked)').forEach(checkbox=>{
			//if no checkbox with this name exist yet
			if(!formData.has(checkbox.name)){
				formData.append(checkbox.name,'');
			}
		});
	}

	// also add get params
	try{
		location['search'].split('?')[1].split('&').forEach(param=>{
			let split	= param.split('=');
			if(split[0] != 'formbuilder' && split[0] != 'main-tab' && split[0] != 'second-tab'){
				formData.append(split[0], split[1]);
			}

		});
	}catch{
		//pass
	};

	let response = await fetchRestApi(url, formData);

	// Reset button
	target.innerHTML	= buttonText;
	target.disabled		= false;

	if(form.dataset.reset != true){
		markComplete();
	}

	return response;
}

/**
 * Set the default values to the current values so to not trigger a page leave warning
 */
export function markComplete(){

	// pending
	document.querySelectorAll('[data-pending]').forEach(el=>{
		el.removeAttribute('data-pending');
	});

	// check all inputs
	document.querySelectorAll('form input:not([type=radio], [type=checkbox]), form textarea').forEach(el=>{
		el.defaultValue = el.value
	});

	// check all checkboxes and radio
	document.querySelectorAll('form input[type=radio], form input[type=checkbox]').forEach(el=>{
		if(el.checked){
			el.defaultChecked = true; 
		}
	});

	// check all dropdowns
	document.querySelectorAll('form select').forEach(el=>{
		if(el.selectedIndex != -1 && el.options[el.selectedIndex] != undefined){
			el.options[el.selectedIndex].defaultSelected	= true;
		}
	});
}

export async function fetchRestApi(url, formData='', showErrors=true){
	if(formData == ''){
		formData	= new FormData();
	}
	
	formData.append('_wpnonce', sim.restNonce);
	let result;
	try{
		result = await fetch(
			`${sim.baseUrl}/wp-json${sim.restApiPrefix}/${url}`,
			{
				method: 'POST',
				credentials: 'same-origin',
				body: formData
			}
		);
	}catch(error){
		console.error(error);
	}
	
	let response	= await result.text();
	try{
		let json		= JSON.parse(response);

		if(result.ok){
			return json;
		}else if(json.code == 'rest_cookie_invalid_nonce'){
			Main.displayMessage('Please refresh the page and try again!', 'error');
			return false;
		}else{
			if(json.data == null || json.data.status == 403){
				Main.displayMessage(json.message);
			}else{
				Main.displayMessage(json.message+"\n"+JSON.stringify(json.data), 'error');
			}
			return false;
		}
	}catch(error){
		console.error(error);
		console.error(result);
		console.error(response);

		if(result.ok){
			if(showErrors){
				Main.displayMessage(`Problem parsing the json, refresh the page or try again.`, 'error');
			}
			console.error(`${sim.baseUrl}/wp-json${sim.restApiPrefix}/${url}` );
			console.error(response);
		}else{
			let modal	= `<div id='response-details' class="modal hidden" style='z-index:9999999999;'>`;
				modal	+= `<div class="modal-content" style='max-width: min(1000px,100%);'>`
					modal	+= `<span class="close">&times;</span>`;
					modal	+= response;
				modal	+= `</div>`;
			modal	+= `</div>`;

			document.querySelector('body').insertAdjacentHTML('afterBegin', modal);
			Main.displayMessage(`Error loading url ${sim.baseUrl}/wp-json${sim.restApiPrefix}/${url}<br><button class='button small' id='error-details' onclick='(function(){ document.getElementById("response-details").classList.remove("hidden"); })();'>Details</button><br><div class='hidden response-message'>${response}</div>`, 'error');
		}
		
		return false;
	}
}