console.log('formsubmit loaded');

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

export async function submitForm(target, url){
	let form		= target.closest('form');
	let validity 	= true;
	
	//get all hidden required inputs and unrequire them
	form.querySelectorAll('.hidden [required], select[required]').forEach(el=>{el.required = false});

	// Unhide required selects so the warning can be shown
	form.querySelectorAll('select[required]').forEach( el=>el.style.display	= 'block' );

	// enable disabled fields so it gets included and warnings are shown
	form.querySelectorAll('[disabled][required]').forEach( el=>{
		el.disabled	= false;
		el.classList.add('was-disabled'); 
	});

	validity	= form.reportValidity();

	// Hide again
	form.querySelectorAll('select[required]').forEach(el=>el.style.display	= 'none');

	//only continue if valid
	if(validity){
		//Display loader
		target.closest('.submit_wrapper').querySelectorAll('.loadergif').forEach(loader=>loader.classList.remove('hidden'));
		
		//save any tinymce forms
		if (typeof tinymce !== 'undefined') {
			tinymce.get().forEach((tn)=>tn.save());
		}
		
		let formData = new FormData(form);

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

		let response = await fetchRestApi(url, formData);

		form.querySelectorAll('.submit_wrapper .loadergif').forEach(loader => loader.classList.add('hidden'));

		return response;
	}else{
		return false;
	}
}

export async function fetchRestApi(url, formData){
	formData.append('_wpnonce', sim.restNonce);

	try{
		let result = await fetch(
			`${sim.baseUrl}/wp-json/sim/v2/${url}`,
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
			Main.displayMessage(json.message, 'error');
			return false;
		}
	}catch(error){
		console.error(error);
		console.error(result);

		if(result.ok){
			Main.displayMessage(`Problem parsing the json, refresh the page or try again.`, 'error');
			console.error(response);
		}else{
			Main.displayMessage(`Url ${sim.baseUrl}/wp-json${sim.restApiPrefix}/${url} not found`, 'error');
		}
		
		return false;
	}
}