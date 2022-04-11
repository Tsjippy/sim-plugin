console.log('formsubmit loaded');

function formReset(form){
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
		currentTab = 0;
		showTab(currentTab,form);
	}
}

async function submitForm(form, url){	
	var validity = true;
	
	//first get all hidden required inputs and unrequire them
	form.querySelectorAll('.hidden [required]').forEach(el=>{el.required = false});
	
	validity	= form.reportValidity();

	//only continue if valid
	if(validity){
		//Display loader
		form.querySelectorAll('.submit_wrapper .loadergif').forEach(
			function(loader){
				loader.classList.remove('hidden');
			}
		);
		
		//save any tinymce forms
		if (typeof tinymce !== 'undefined') {
			tinymce.get().forEach((tn)=>tn.save());
		}
		
		formdata = new FormData(form);

		if(form.dataset.addempty == 'true'){
			//also append at least one off all checkboxes
			form.querySelectorAll('input[type="checkbox"]:not(:checked)').forEach(checkbox=>{
				//if no checkbox with this name exist yet
				if(!formdata.has(checkbox.name)){
					formdata.append(checkbox.name,'');
				}
			});
		}

		return await fetchRestApi(url, formdata);
	}else{
		return false;
	}
}

async function fetchRestApi(url, formdata){
	formdata.append('_wpnonce', sim.restnonce);

	var result = await fetch(
		sim.base_url+'/wp-json/sim/v1/'+url,
		{
			method: 'POST',
			credentials: 'same-origin',
			body: formdata
		}
	);

	var response	= await result.json();

	if(result.ok){
		return response;
	}else{
		console.error(response);
		display_message(response.message, 'error');
		return false;
	};
}