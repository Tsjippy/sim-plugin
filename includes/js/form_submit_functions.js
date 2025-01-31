export function formReset(form){
	//reset form to the default
	form.reset();

	//hide loaders
	form.querySelectorAll('.loadergif').forEach(el=>el.classList.add('hidden'));

	//hide button again if needed
	form.querySelectorAll('.multistepcontrols .form_submit').forEach(el=>el.classList.add('hidden'));
	
	//fix required fields
	form.querySelectorAll('[required]').forEach(el=>{el.required = false;});
	
	//empty any fileuploads
	form.querySelectorAll('.file_upload').forEach(el=>el.value='');

	//show hidden file uploads
	form.querySelectorAll('.upload_div.hidden').forEach(el=>el.classList.remove('hidden'));
	
	//Reset nice selects
	form.querySelectorAll('select').forEach(el=>el._niceselect.update());
	
	//remove doc previews
	document.querySelectorAll('.documentpreview .document').forEach(el=>el.remove());
	
	if(form.querySelector('.formstep') != null){
		//mark all circles as not active anymore
		form.querySelectorAll('.multistepcontrols .step.finish').forEach(el=>el.classList.remove('finish'));
	
		//go back to the first part of the form in case of multistep
		FormFunctions.showTab(0,form);
	}
}

export async function submitForm(target, url, extraData=''){
	let form		= target.closest('form');
	let validity 	= true;
	
	//get all hidden required inputs and unrequire them
	form.querySelectorAll('.hidden [required], select[required], .nice-select-search[required], .stephidden [required]').forEach(el=>{el.required = false});

	// enable disabled fields so it gets included and warnings are shown
	form.querySelectorAll('[disabled][required]').forEach( el=>{
		el.disabled	= false;
		el.classList.add('was-disabled'); 
	});

	validity	= form.reportValidity();

	//only continue if valid
	if(validity){
		//Display loader
		target.closest('.submit_wrapper').querySelectorAll('.loadergif').forEach(loader=>loader.classList.remove('hidden'));
		
		//save any tinymce forms
		if (typeof tinymce !== 'undefined') {
			tinymce.get().forEach((tn)=>{
				// only save when the visual tab is active
				if(!tn.hidden){
					tn.save()
				}
			});
		}
		
		let formData = new FormData(form);
		
		if(extraData != ''){
			formData.append('extra', extraData);
		}

		// disable fields again
		form.querySelectorAll('.was-disabled').forEach( el=>{
			el.disabled	= true;
			el.classList.remove('was-disabled'); 
		});

		if(form.dataset.addempty == 'true'){
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
				if(split[0] != 'formbuilder' && split[0] != 'main_tab' && split[0] != 'second_tab'){
					formData.append(split[0], split[1]);
				}

			});
		}catch{
			//pass
		}

		// store values of multi-selects
		document.querySelectorAll('select[multiple]').forEach(el => {
			formData.set(el.name, Array.from(el.selectedOptions,e=>e.value));
		});

		let response = await fetchRestApi(url, formData);

		form.querySelectorAll('.submit_wrapper .loadergif').forEach(loader => loader.classList.add('hidden'));

		if(form.dataset.reset != 'true'){
			markComplete();
		}

		return response;
	}else{
		form.querySelectorAll(':invalid').forEach(el=>{
			if(el.validationMessage != undefined){
				Main.displayMessage(`${el.name} has an error:\n${el.validationMessage}`, 'error').then((value)=>{
					form.querySelectorAll('.formstep:not(.stephidden)').forEach(formstep=>formstep.classList.add('stephidden'));

					el.closest('.stephidden').classList.remove('stephidden');

					el.focus();

					el.scrollIntoView();

					el.focus();
				});
			}
		});

		return false;
	}
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
			console.error(json);
			if(json.data == null){
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