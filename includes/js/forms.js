/* fetch(simnigeria.ajax_url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Cache-Control': 'no-cache',
    },
    body: formData
}).then(response => response.json())
.then(response => console.log(response))
.catch(err => console.log(err)); */
function sendAJAX(formData, form = null){
	/* for(var pair of formData.entries()) {
		console.log(pair[0]+ ', '+ pair[1]);
	} */
	 
	responsdata = '';
	
	var request = new XMLHttpRequest();

	request.open('POST', simnigeria.ajax_url, true);

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
	
	request.onerror = function() {
		// Connection error
	};
	request.send(formData);
};

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

function show_modal(modal_id){
	var modal = document.getElementById(modal_id+"_modal");
	
	if(modal != null){
		modal.classList.remove('hidden');
	}	
}

function hide_modals(){
	document.querySelectorAll('.modal:not(.hidden)').forEach(modal=>modal.classList.add('hidden'));
}

function remove_default_select(el){
	Array.from(el.options).forEach(function(option){
		option.defaultSelected = false;
	});
}

function clone_node(original_node, clear=true){
	//First remove any nice selects
	original_node.querySelectorAll('select').forEach(select => {
		//remove defaults if it has changed
		if(select.options[select.selectedIndex].defaultSelected == false){
			//remove all default selected
			remove_default_select(select);
		}
		
		//Then add a new default selected if needed
		if(select.selectedOptions.length>0){
			select.selectedOptions[0].defaultSelected = true;
		}
		
		//remove the original
		select._niceselect.destroy()
	});
	
	//also remove any tinymce's
	if(typeof(tinymce) != 'undefined'){
		tinymce_settings = [];
		original_node.querySelectorAll('.wp-editor-area').forEach(el =>{
			var tn = tinymce.get(el.id);
			if(tn != null){
				tinymce_settings[el.id] = tn.settings
				tn.save();
				tn.remove();
			}
		});
	}
	
	//make a clone
	newnode = original_node.cloneNode(true);
	
	//Then add niceselects again after cloning took place
	original_node.querySelectorAll('select').forEach(select => {
		select._niceselect = NiceSelect.bind(select,{searchable: true});
		if(select.value == ''){
			select._niceselect.clear();
		}
	});
	
	//add tinymce's again
	original_node.querySelectorAll('.wp-editor-area').forEach(el =>{
		tinymce.init(tinymce_settings[el.id]);
	});
	
	//clear values in the clone
	if(clear == true){
		newnode.querySelectorAll('input,select,textarea').forEach(input => {
			if(input.type == 'checkbox' || input.type == 'radio'){
				input.checked = false;
			}else if(input.type != 'hidden'){
				input.value = "";
			}
			
			//if this is a select
			if(input.type == "select-one"){
				//remove any defaults
				remove_default_select(input);
				
				input._niceselect = NiceSelect.bind(input,{searchable: true});
				input._niceselect.clear();
			}
		});
	}else{
		newnode.querySelectorAll('select').forEach(select => {
			select._niceselect = NiceSelect.bind(select,{searchable: true});
		});
	}
	
	return newnode;
}

function copy_form_input(original_node){
	newnode = clone_node(original_node);
	
	//Find the node number
	var nodenr = parseInt(original_node.dataset.divid)+1;
	
	if(original_node.getAttribute("data-type") != null){
		var datatype = original_node.getAttribute("data-type").replace('[','\\[').replace(']','\\]');
		re = new RegExp('('+datatype+'?\\]?\\[).*[0-9](.*)',"g");
	}else{
		re = new RegExp('(.*)[0-9](.*)',"g");
	}
	
	//update the data index
	newnode.querySelectorAll('.upload-files').forEach(function(uploadbutton){
		uploadbutton.dataset.index = nodenr;
	})
	
	//Clear contents of any document preview divs.
	newnode.querySelectorAll('.documentpreview').forEach(function(previewdiv){
		previewdiv.innerHTML = '';
	});

	//Select
	var i = 0;
	newnode.querySelectorAll('select').forEach(select => {
		//Find the value of the select we have cloned
		previous_val = original_node.getElementsByTagName('select')[i].selectedIndex;
		
		//Hide the value in the clone
		select.options[previous_val].style.display = 'none';
		
		//Add nice select
		select._niceselect.update();
		
		select.nextSibling.querySelector('.current').textContent = 'Select an option';
		
		i++;
	});
	
	//Add remove buttons if they are not there
	if(original_node.querySelector('.remove') == null){
 		var add_element 	= newnode.querySelector('.add');
		var element_class 	= add_element.className.replace('add','remove');
		var id 				= add_element.id.replace('add','remove');
		var content 		= add_element.textContent.replace('Add','Remove this').replace('an','').replace('+','-');
		
		var html = '<button type="button" class="'+element_class+'" id="'+id+'" style="flex: 1;">'+content+'</button>';
		
		//Add minus button to the first div
		original_node.querySelector('.buttonwrapper').insertAdjacentHTML('beforeend',html);
		//Add minus button to the second div
		newnode.querySelector('.buttonwrapper').insertAdjacentHTML('beforeend',html)
	}
	
	//Insert the clone
	original_node.parentNode.insertBefore(newnode, original_node.nextSibling);
	
	//add tinymce's can only be done when node is inserted and id is unique
	newnode.querySelectorAll('.wp-editor-area').forEach(el =>{
		tinymce.init({selector:"#"+el.id});
	});
	
	return newnode;
}

function fix_numbering(clone_divs_wrapper){
	clone_divs_wrapper.querySelectorAll('.clone_div').forEach((clone, index)=>{
		//Update the new number	
		//DIV
		if(clone.id != ''){
			clone.id = clone.id.replace(re, '$1'+index+'$2');
		}
		
		if(clone.dataset.divid  != null){
			clone.dataset.divid = index;
		}
		
		//Update the title
		clone.querySelectorAll('h3').forEach(title => {
			title.textContent = title.textContent.replace(/([0-9])/g, index+1);
		});
		
		//Update the label
		clone.querySelectorAll('label, legend').forEach(label => {
			label.innerHTML = label.innerHTML.replace(/ ([0-9])/g, ' '+(index+1));
		});
		
		//Update the elements
		clone.querySelectorAll('input,select,textarea').forEach(input => {
			//Do not copy nice selects
			if(!input.classList.contains('nice-select-search')){
				//Update the id
				if(input.id != '' && input.id != undefined){
					input.id = input.id.replace(re, '$1'+index+'$2');
				}
				//Update the name
				if(input.name != '' && input.name != undefined){
					input.name = input.name.replace(re, '$1'+index+'$2');
				}
				
				//Reset the select to the default
				if(input.type == 'button'){
					input.value = input.value.replace(/[0-9]/g, index);
				}
			}
		});
	})
}

function remove_node(target){
	var node			= target.closest(".clone_div");
	var parent_node		= node.closest('.clone_divs_wrapper');
	var all_clone_divs	= parent_node.querySelectorAll('.clone_div');
	
	//Check if we are removing the last element
	if(all_clone_divs[all_clone_divs.length-1] == node){
		var add_element = node.querySelector(".add");
		
		//Move the add button one up
		var prev = node.previousElementSibling;
		prev.querySelector('div').appendChild(add_element);
	}
	
	//Check which number to update
	if(node.getAttribute("data-type") != null){
		var datatype = node.getAttribute("data-type").replace('[','\\[').replace(']','\\]');
		re = new RegExp('('+datatype+'?\\]?\\[).*[0-9](.*)',"g");
	}else{
		re = new RegExp('(.*)[0-9](.*)',"g");
	}
	
	//Remove the node
	node.remove();
	
	//update the collection
	all_clone_divs	= parent_node.querySelectorAll('.clone_div');
	
	//If there is only one div remaining, remove the remove button
	if(all_clone_divs.length == 1){
		var remove_element = parent_node.querySelector('.remove');
		remove_element.remove();
	}
	
	//Loop over all the remaining nodes.
	nodenr = parseInt(all_clone_divs[0].dataset.divid);
	all_clone_divs.forEach(function(clonenode){
		if(clonenode.dataset.type == 'understudies'){
			//update the data index
			clonenode.querySelectorAll('.upload-files').forEach(function(uploadbutton){
				uploadbutton.dataset.index = nodenr;
			})
		}
			
		//DIV
		if(clonenode.id != ''){
			clonenode.id = clonenode.id.replace(re, '$1'+nodenr+'$2');
		}
		
		clonenode.dataset.divid = nodenr;
		
		//Update the title
		clonenode.querySelectorAll('h3').forEach(title => {
			title.textContent = title.textContent.replace(/[0-9]/g, nodenr);
		});
		
		//Update the label
		clonenode.querySelectorAll('label').forEach(label => {
			if(all_clone_divs[0].dataset.divid == 0){
				labelnr = nodenr + 1;
			}else{
				labelnr = nodenr;
			}
			label.innerHTML = label.innerHTML.replace(/ ([0-9])/g, function($1) {return ' '+labelnr});
		});
		
		//Update the elements
		clonenode.querySelectorAll('input,select,textarea').forEach(input => {
			if(input.id != ''){
				//Update the id
				input.id = input.id.replace(re, '$1'+nodenr+'$2');
			}
			//Update the name
			input.name = input.name.replace(re, '$1'+nodenr+'$2');
		});	

		nodenr += 1;
	})
}

/* 
	FUNCTIONS USED BY DYNAMIC FORMS JS
 */
function tidy_multi_inputs(){
	//remove unnecessary buttons on inputs with multiple values
	document.querySelectorAll('.clone_divs_wrapper').forEach(function(div){
		var clone_div_arr	= div.querySelectorAll('.clone_div');
		
		if(clone_div_arr.length == 1){
			clone_div_arr[0].querySelectorAll('.remove').forEach(el=>el.remove());
		}
		
		clone_div_arr.forEach(function(clone_div, index, array){
			//update dataset
			clone_div.dataset.divid = index;
			
			//remove add button for all but the last
			if(index != array.length - 1){
				clone_div.querySelectorAll('.add').forEach(el=>el.remove());
			}
		})
	});
}
	
//show a next form step
function showTab(n,form) {
	if(typeof(form) != 'undefined'){
		if(n == 0){
			var loader = form.querySelector('.formsteploader');
			//hide loader
			if(loader != null){
				loader.classList.add('hidden');
			
				//show form controls
				form.querySelector('.multistepcontrols').classList.remove('hidden');
			}
		}
		
		//hide all formsteps
		form.querySelectorAll('.formstep:not(.stephidden)').forEach(step=>step.classList.add('stephidden'));
		
		// Show the specified formstep of the form ...
		var x = form.getElementsByClassName("formstep");
		
		if(x.length == 0){
			return;
		}
		
		//scroll back to top
		var y = x[n].offsetTop - document.querySelector("#masthead").offsetHeight
		window.scrollTo({ top: y, behavior: 'auto'});
		
		//show
		x[n].classList.remove('stephidden');

		// This function removes the "active" class of all steps...
		form.querySelectorAll(".step.active").forEach(el=>{el.classList.remove('active');});

		//... and adds the "active" class to the current step:
		var x = form.getElementsByClassName("step");
		x[n].classList.add("active");

		// ... and fix the Previous/Next buttons:
		if (n == 0) {
			form.querySelector('[name="prevBtn"]').classList.add('hidden');
		} else {
			form.querySelector('[name="prevBtn"]').classList.remove('hidden');
		}

		if (n == (x.length - 1)) {
			form.querySelector('[name="nextBtn"]').classList.add('hidden');
			form.querySelector('.form_submit').classList.remove('hidden');
		} else {
			form.querySelector('[name="nextBtn"]').classList.remove('hidden');
			form.querySelector('.form_submit').classList.add('hidden');
		}
	}else{
		console.log('no form defined');
	}
		
}

//next form step clicked
function nextPrev(n) {
	// This function will figure out which tab to display
	var x 				= form.getElementsByClassName("formstep");
	var stepindicators	= form.getElementsByClassName("step");
	
	//reset required fields
	form.querySelectorAll('[required]').forEach(el=>{el.required = false;});
	
	//Check validity of this step if going forward
	if(n>0){
		//make all required fields of this step required
		x[currentTab].querySelectorAll('.required:not(.hidden) input, .required:not(.hidden) textarea, .required:not(.hidden) select').forEach(el=>{el.required = true});
		//report validity to the user
		form.reportValidity();
		//if not valid return
		if(form.checkValidity() == false){
			return;
		}
		
		//mark the last step as finished
		stepindicators[currentTab].classList.add("finish");
	}else{
		//mark the last step as unfinished
		stepindicators[currentTab].classList.remove("finish");
	}
	
	//loop over all the formsteps to hide stepindicators of them if needed
	Array.from(x).forEach((formstep,index) =>{
		if(formstep.classList.contains('hidden')){
			//hide the corresponding circle
			stepindicators[index].classList.add('hidden');
		}
	});

	// Increase or decrease the current tab by 1:
	currentTab = currentTab + n;
	
	//check if the next tab is hidden
	while(x[currentTab].classList.contains('hidden')){
		//go to the next tab
		currentTab = currentTab + n;
	}
	
	// if you have reached the end of the form... :
	if (currentTab >= x.length) {
		return false;
	}
	// Otherwise, display the correct tab:
	showTab(currentTab,form);
}

function get_field_value(orgname, checkdatalist=true, comparevalue=null){
	//name is not a name but a node
	if(orgname instanceof Element){
		el			= orgname;		
		//check if valid input type
		if(el.tagName != 'INPUT' && el.tagName != 'TEXTAREA' && el.tagName != 'SELECT' && el.closest('.nice-select-dropdown') == null){
			el = el.querySelector('input, select, textarea');
		}
		if(el == null){
			el			= orgname;
		}
		name		= el.name;
	}else{
		name		= orgname;
		el			= form.querySelector('[name=\"'+name+'\" i]');
	}
	
	if(el != null){
		var value = '';
		if(el.type == 'radio'){
			el		= form.querySelector('[name=\"'+name+'\" i]:checked');
			
			//There is no radio selected currently
			if(el == null){
				//return an empty value
				return '';
			}
		}
		
		if(el.type == 'checkbox'){
			//we are dealing with a specific checkbox
			if(orgname.type == 'checkbox' ){
				if(el.checked){
					return el.value;
				}else{
					return '';
				}
			//we should return all checked checkboxes
			}else{
				//we should find the checkbox with this value and check if it is checked
				if(comparevalue != null){
					els		= form.querySelector('[name=\"'+name+'\" i][value="'+comparevalue+'" i]:checked');
					if(els != null){
						value = comparevalue;
					}
				//no compare value give just return all checked values
				}else{
					els		= form.querySelectorAll('[name=\"'+name+'\" i]:checked');
					
					els.forEach(el=>{
						if(value != ''){
							value += ', ';
						}
						value += el.value;
					});
				}
				
				
			}
		//get the value of niceselect
		}else if(el.closest('.nice-select-dropdown') != null && el.dataset.value != undefined){
			value = el.dataset.value
		//value of datalist
		}else if(el.list != null && checkdatalist){
			var orig_input = el.list.querySelector("[value='"+el.value+"' i]");
			
			if(orig_input == null){
				value =  el.value;
			}else{
				value = orig_input.dataset.value;
				if(value == ''){
					value =  el.value;
				}
			}
		}else if(el.type == 'checkbox'){
			if(el.checked){
				value = el.value;
			}else{
				value = '';
			}
		}else if(el.value != null){
			value = el.value;
		}
		
		return value;
	}else{
		console.trace();
		console.log("cannot find element with name "+name);
	}
}

function change_field_value(orgname, value){
	if(orgname instanceof Element){
		var name	= orgname.name;
		var target	= orgname;
	}else{
		var name = orgname;
		//get the target
		var target = form.querySelector('[name="'+name+'" i]');
	}
	
	if(target.type == 'radio' || target.type == 'checkbox'){
		if(!orgname instanceof Element){
			targets = form.querySelectorAll('[name="'+name+'" i]');
			for (let i = 0; i < targets.length; i++) {
				if(targets[i].value.toLowerCase() == value.toLowerCase()){
					target = targets[i];
					break;
				}
			}
		}
		
		if(target.value.toLowerCase() == value.toLowerCase()){
			target.checked = true;
		}
	//the target has a list attached to it
	}else if(target.list != null){
		var datalistoption = target.list.querySelector('[data-value="'+value+'" i]');
		//we found a match
		if(datalistoption != null){
			target.value = datalistoption.value;
		}else{
			target.value = value;
		}
	}else{
		target.value = value;
	}
	
	prev_el = '';
	//create a new event
	evt = new Event('input');
	//attach the target
	target.dispatchEvent(evt);
	
	//run the originating function with this event
	window[arguments.callee.caller.name](target);
}

function change_field_property(name, att, value){
	//first change the value
	var target = form.querySelector('[name="'+name+'" i]');
	
	form.querySelector('[name="'+name+'"]')[att] = value;
	
	prev_el = '';
	//create a new event
	evt = new Event('input');
	//attach the target
	target.dispatchEvent(evt);
	//run the originating function with this event
	window[arguments.callee.caller.name](target);
}

//Main code
document.addEventListener("DOMContentLoaded",function() {
	console.log('Forms.js is loaded');
});

//we are online again
window.addEventListener('online',function(){
	document.querySelectorAll('.form_submit').forEach(btn=>{
		btn.disabled = false
		btn.querySelectorAll('.offline').forEach(el=>el.remove());
	});
});

//prevent form submit when offline
window.addEventListener('offline',function(){
	document.querySelectorAll('.form_submit').forEach(btn=>{
		btn.disabled = true;
		if(btn.querySelector('.online') == null){
			btn.innerHTML = '<div class="online">'+btn.innerHTML+'</div>';
		}
		btn.innerHTML += '<div class="offline">You are offline</div>'
	});
});

document.addEventListener('click',function(event) {
	var target = event.target;
	//submit form via AJAX
	if((target.parentNode != null && (target.parentNode.matches('.form_submit') || target.matches('.form_submit')))){
		//Submit the form
		submit_form(event); 
	}
		
	//Process the click on a mark as read button
	if(target.matches(".mark_as_read")){
		mark_as_read(target);
	}
	
	//add element
	if(target.matches('.add')){
		copy_form_input(target.closest(".clone_div"));

		fix_numbering(target.closest('.clone_divs_wrapper'));

		target.remove();
	}
	
	//remove element
	if(target.matches('.remove')){
		var wrapper	= target.closest('.clone_divs_wrapper');
		//Remove node clicked
		remove_node(target); 

		fix_numbering(wrapper);
	}
	
	//close modal if clicked outside of modal
	if(target.closest('.modal-content') == null && target.closest('.swal2-container') == null && target.tagName=='DIV'){
		hide_modals();
	}
});

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