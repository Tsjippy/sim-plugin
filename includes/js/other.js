function mark_as_read(target){
	if(target.dataset.postid != undefined){
		showLoader(target);
		
		formData = new FormData();
		formData.append('action','mark_page_as_read');
		formData.append('userid',target.dataset.userid);
		formData.append('postid',target.dataset.postid);
		sendAJAX(formData);
	}
}

function show_modal(modal_id){
	var modal = document.getElementById(modal_id+"_modal");
	
	if(modal != null){
		modal.classList.remove('hidden');
	}	
}

function hide_modals(){
	document.querySelectorAll('.modal:not(.hidden)').forEach(modal=>modal.classList.add('hidden'));
}

document.addEventListener('click',function(event) {
	var target = event.target;
		
	//Process the click on a mark as read button
	if(target.matches(".mark_as_read")){
		mark_as_read(target);
	}

    	//close modal if clicked outside of modal
	if(target.closest('.modal-content') == null && target.closest('.swal2-container') == null && target.tagName=='DIV'){
		hide_modals();
	}

	//submit form via AJAX
	if((target.parentNode != null && (target.parentNode.matches('.form_submit') || target.matches('.form_submit')))){
		//Submit the form
		submit_form(event); 
	}
});

function form_reset(form){
	//reset form to the default
	if(form.dataset.reset == 'true'){
		form.reset();
	
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
}

//userid to url
document.querySelectorAll('[name="user_selection"]').forEach(el=>el.addEventListener('change',function(){
	var current_url = window.location.href.replace(location.hash,'');
	if (current_url.includes("userid=")){
		new_url = current_url.replace(/userid=[0-9]+/g,"userid="+this.value);
	}else{
		new_url=current_url;
		if (current_url.includes("?")){
			new_url += "&";
		}else{
			new_url += "?";
		}
		new_url += "userid="+this.value;
	}
	window.location.href = new_url+location.hash;
}));

/* fetch(sim.ajax_url, {
    method: 'POST',
    credentials: 'same-origin',
    body: formData
}).then(response => response.json())
.then(response => console.log(response))
.catch(err => console.log(err)); */
function sendAJAX(formData, form = null){
	for(var pair of formData.entries()) {
		console.log(pair[0]+ ', '+ pair[1]);
	}
	 
	responsdata = '';
	
	var request = new XMLHttpRequest();

	request.open('POST', sim.ajax_url, true);

	request.onload = function () {
		try {
			responsdata = JSON.parse(this.response);
		} catch (e) {
			//no encoded response
			console.log(e);
			console.log(this.response);
		}
				
		if (this.status >= 200 && this.status < 400) {
			// If successful
			if(typeof(responsdata) == 'undefined' || responsdata == ''){
				if(this.response != ''){
					var message = this.response;
				}
			}else{
				if(responsdata.message != ''){
					var message = responsdata.message;
				}
			}
			
			display_message(message, 'success', false);
		}else {
			// If fail
			console.log(this.response);
			display_message(this.response, 'error');
		}
		
		//If the response data is json encoded and includes a callback name
		if(typeof(responsdata) != undefined){
			if(responsdata.callback != undefined){
				try {
					console.log("Running "+responsdata['callback']);
					window[responsdata['callback']](this,responsdata);
				} catch (e) {
					//console.log(e);
					//console.log("No function named "+responsdata['callback']+" found");
				}
			}
			if(responsdata.redirect != undefined){
				location.href	=	responsdata.redirect;
			}
		}
		
		//hide loader
		document.querySelectorAll(".loadergif:not(.hidden)").forEach(loader=>{
			if(!loader.parentNode.classList.contains('hidden')){
				loader.classList.add('hidden');
			}
		});
		
		if(form != null && this.status >= 200 && this.status < 400){
			form_reset(form);
		}
	};
	
	request.send(formData);
};

function submit_form(event){
	//prevent default submission
	event.preventDefault();
	
	var form = event.target.closest('form');
	
	var validity = true;
	
	if(form != null){
		//first get all hidden required inputs and unrequire them
		form.querySelectorAll('.hidden [required]').forEach(el=>{el.required = false});
		
		form.reportValidity();
		
		validity = form.checkValidity();
	
		//only continue if valid
		if(validity){
			//Disable button
			var save_button = event.target;
			if(save_button.tagName != "BUTTON"){
				var save_button = event.target.parentNode;
			}
			
			//Display loader
			save_button.parentNode.querySelectorAll('.loadergif').forEach(
				function(loader){
					loader.classList.remove('hidden');
				}
			);
			
			var oldvalue = save_button.innerHTML;
			save_button.innerHTML = "Saving changes...";
			
			//save any tinymce forms
			if (typeof tinymce !== 'undefined') {
				tinymce.get().forEach((tn)=>tn.save());
			}
			
			formData = new FormData(form);

			if(form.dataset.addempty == 'true'){
				//also append at least one off all checkboxes
				form.querySelectorAll('input[type="checkbox"]:not(:checked)').forEach(checkbox=>{
					//if no checkbox with this name exist yet
					if(!formData.has(checkbox.name)){
						formData.append(checkbox.name,'');
					}
				});
			}

			//Send Ajax
			sendAJAX(formData,form);
			
			save_button.innerHTML = oldvalue;
		}
	}else{
		console.log('form not found');
	}
}