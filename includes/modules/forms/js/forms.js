import { addStyles } from './../../../js/imports.js';
import { getFieldValue } from  '../../../js/field_value.js';
export{ getFieldValue };

console.log('Forms.js is loaded');

async function saveFormInput(target){
	let form		= target.closest('form');

	// make all inputs required if needed
	form.querySelectorAll('.required:not(hidden) input, .required:not(hidden) textarea, .required:not(hidden) select').forEach(el=>{
		// do not make nice select inputs nor file uploads required
		if(el.closest('div.nice-select') == null && (el.type != 'file' || el.closest('.file_upload_wrap').querySelector('.documentpreview input') == null)){
			el.required	= true;
		}
	});

	let response	= await FormSubmit.submitForm(target, 'forms/save_form_input');

	if(response){
		target.closest('.submit_wrapper').querySelector('.loadergif').classList.add('hidden');

		Main.displayMessage(response);

		if(form.dataset.reset == 'true'){
			FormSubmit.formReset(form);
		}
	}
}

export function removeDefaultSelect(el){
	Array.from(el.options).forEach(function(option){
		option.defaultSelected = false;
	});
}

let tinymceSettings = [];
function prepareForCloning(originalNode){
	//First remove any nice selects
	originalNode.querySelectorAll('select').forEach(select => {
		//remove defaults if it has changed
		if(select.selectedIndex != -1){
			if(!select.options[select.selectedIndex].defaultSelected){
				//remove all default selected
				removeDefaultSelect(select);
			}
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
		originalNode.querySelectorAll('.wp-editor-area').forEach(el =>{
			let tn = tinymce.get(el.id);
			if(tn != null){
				tinymceSettings[el.id] = tn.settings
				tn.save();
				tn.remove();
			}
		});
	}
}

export function cloneNode(originalNode, clear=true){
	prepareForCloning(originalNode);
	
	//make a clone
	let newNode = originalNode.cloneNode(true);
	
	//Then add niceselects again after cloning took place
	originalNode.querySelectorAll('select').forEach(select => {
		select._niceselect = NiceSelect.bind(select,{searchable: true});
		if(select.value == ''){
			select._niceselect.clear();
		}
	});
	
	//add tinymce's again
	originalNode.querySelectorAll('.wp-editor-area').forEach(el =>{
		if(tinymceSettings[el.id] != undefined){
			tinymce.init(tinymceSettings[el.id]);
		}
	});
	
	//clear values in the clone
	if(clear){
		newNode.querySelectorAll('input,select,textarea').forEach(input => {
			if(input.type == 'checkbox' || input.type == 'radio'){
				input.checked = false;
			}else if(input.type != 'hidden'){
				input.value = "";
			}
			
			//if this is a select
			if(input.type == "select-one"){
				//remove any defaults
				removeDefaultSelect(input);
				
				input._niceselect = NiceSelect.bind(input,{searchable: true});
				input._niceselect.clear();
			}
		});
	}else{
		newNode.querySelectorAll('select').forEach(select => {
			select._niceselect = NiceSelect.bind(select,{searchable: true});
		});
	}
	
	return newNode;
}

export function copyFormInput(originalNode){
	let newNode = cloneNode(originalNode);
	
	//update the data index
	newNode.querySelectorAll('.upload-files').forEach(function(uploadButton){
		uploadButton.dataset.index = nodeNr;
	})
	
	//Clear contents of any document preview divs.
	newNode.querySelectorAll('.documentpreview').forEach(function(previewDiv){
		previewDiv.innerHTML = '';
	});

	//Select
	let i = 0;
	newNode.querySelectorAll('select').forEach(select => {
		//Find the value of the select we have cloned
		let previousVal = originalNode.getElementsByTagName('select')[i].selectedIndex;
		
		//Hide the value in the clone
		if(select.options[previousVal] != undefined){
			select.options[previousVal].style.display = 'none';
		}else{
			console.log(select);
			console.log(select.options);
			console.log(previousVal);
		}
		
		//Add nice select
		if(select._niceselect == undefined){
			console.error(select);
			select._niceselect = NiceSelect.bind(select, {searchable: true});
		}else{
			select._niceselect.update();
			select.nextSibling.querySelector('.current').textContent = 'Select an option';
		}
		
		i++;
	});
	
	//Add remove buttons if they are not there
	if(originalNode.querySelector('.remove') == null && newNode.querySelector('.remove') == null){
 		let html = `<button type="button" class="remove button" style="flex: 1;">-</button>`;

		//Add minus button to the first div
		originalNode.querySelector('.buttonwrapper').insertAdjacentHTML('beforeend', html);

		//Add minus button to the second div
		newNode.querySelector('.buttonwrapper').insertAdjacentHTML('beforeend', html)
	}	
	
	//Insert the clone
	originalNode.parentNode.insertBefore(newNode, originalNode.nextSibling);
	
	return newNode;
}

export function fixNumbering(cloneDivsWrapper){
	cloneDivsWrapper.querySelectorAll(':scope > .clone_div').forEach((clone, index)=>{
		//Update the new number	
		//DIV
		if(clone.id != ''){
			clone.id = clone.id.replace(/[0-9]+(?!.*[0-9])/, index);
		}
		
		if(clone.dataset.divid  != null){
			clone.dataset.divid = index;
		}
		
		//Update the title
		clone.querySelectorAll('h3, h4, legend :first-child').forEach(el => {
			el.textContent = el.textContent.replace(/[0-9]+(?!.*[0-9])/, index+1);
		});
		
		//Update the legend
		/* clone.querySelectorAll('legend').forEach(legend => {
			legend.textContent = legend.textContent.replace(/[0-9]+(?!.*[0-9])/, ' '+(index+1));
		}); */
		
		//Update the elements
		clone.querySelectorAll('input,select,textarea').forEach(input => {
			//Do not copy nice selects
			if(!input.classList.contains('nice-select-search')){
				//Update the id
				if(input.id != '' && input.id != undefined){
					input.id = input.id.replace(/[0-9]+(?!.*[0-9])/, index);
				}
				//Update the name
				if(input.name != '' && input.name != undefined){
					input.name = input.name.replace(/[0-9]+(?!.*[0-9])/, index);
				}
				
				//Reset the select to the default
				if(input.type == 'button'){
					input.value = input.value.replace(/[0-9]+(?!.*[0-9])/, index);
				}
			}
		});
	})
}

export function removeNode(target){
	let node			= target.closest(".clone_div");
	let parentNode		= node.closest('.clone_divs_wrapper');
	let allCloneDivs	= parentNode.querySelectorAll('.clone_div');
	
	//Check if we are removing the last element
	if(allCloneDivs[allCloneDivs.length-1] == node){
		let addElement = node.querySelector(".add");
		
		//Move the add button one up
		let prev = node.previousElementSibling;
		if(prev.querySelector('.buttonwrapper .add') == null){
			prev.querySelector('.buttonwrapper').appendChild(addElement);
		}
	}
	
	//Remove the node
	node.remove();

	//If there is only one div remaining, remove the remove button
	if(parentNode.querySelectorAll('.clone_div').length == 1){
		let removeElement = parentNode.querySelector('.remove');
		removeElement.remove();
	}

	fixNumbering(parentNode)
}

/* 
	FUNCTIONS USED BY DYNAMIC FORMS JS
 */
export function tidyMultiInputs(){
	//remove unnecessary buttons on inputs with multiple values
	document.querySelectorAll('.clone_divs_wrapper').forEach( function(div){
		let cloneDivArr	= div.querySelectorAll(':scope > .clone_div');
		
		if(cloneDivArr.length == 1){
			cloneDivArr[0].querySelectorAll('.remove').forEach(el=>el.remove());
		}
		
		cloneDivArr.forEach(function(cloneDiv, index, array){
			//update dataset
			cloneDiv.dataset.divid = index;
			
			//remove add button for all but the last
			if(index != array.length - 1){
				// Select all add buttons but not the any nested buttons
				cloneDiv.querySelectorAll('.add:not(:scope .clone_divs_wrapper .add)').forEach(el=>el.remove());
			}
		})
	});
}

export function updateMultiStepControls(form){
	// get active formsteps amount
	let formsteps			= form.querySelectorAll('.formstep');
	let visibleFormsteps	= form.querySelectorAll('.formstep:not(.hidden)');
	let stepIndicators		= form.querySelectorAll('.multistepcontrols_wrapper .step');

	// update step circles amount
	for(let x = visibleFormsteps.length; x < formsteps.length; x++){
		stepIndicators[x].classList.add('hidden');
	}

	// check if this is the last visible
	let activeFormstep		= form.querySelector('.formstep:not(.stephidden)');
	if(visibleFormsteps[visibleFormsteps.length-1] == activeFormstep){
		// make the submit button visible
		form.querySelector('.nextBtn').classList.add('hidden');
		form.querySelector('.form_submit ').classList.remove('hidden');
	}else{
		form.querySelector('.nextBtn').classList.remove('hidden');
		form.querySelector('.form_submit ').classList.add('hidden');
	}
}
	
//show a next form step
export function showTab(n, form) {
	if(typeof(form) != 'undefined'){
		if(n == 0){
			let loader = form.querySelector('.formsteploader');
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
		let x = form.getElementsByClassName("formstep");
		
		if(x.length == 0){
			return;
		}
		
		//scroll back to top
		let y = x[n].offsetTop - document.querySelector("#masthead").offsetHeight
		window.scrollTo({ top: y, behavior: 'auto'});
		
		//show
		x[n].classList.remove('stephidden');

		// This function removes the "active" class of all steps...
		form.querySelectorAll(".step.active").forEach(el=>{el.classList.remove('active');});

		//... and adds the "active" class to the current step:
		x = form.getElementsByClassName("step");
		try{
			x[n].classList.add("active");
		}catch(err) {
			console.log(x);
			console.log(n);
		  	console.error(err.message);
		}
		

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
export function nextPrev(n) {
	// This function will figure out which tab to display
	let x 				= form.querySelectorAll(".formstep");
	let stepIndicators	= form.querySelectorAll(".step");
	let currentTab		= 0;
	let valid			= true;

	// Find the current active tab
	x.forEach((el, index)=>{if(!el.matches('.stephidden')){currentTab = index}});
	
	//Check validity of this step if going forward
	if(n>0){
		// Report validity of each required field
		let elements	= x[currentTab].querySelectorAll('.required:not(.hidden) input, .required:not(.hidden) textarea, .required:not(.hidden) select');
		for(const element of elements) {
			if(
				element.closest('.hidden')	== null	&&
				element.closest('div.nice-select') == null && 
				(
					element.type != 'file' || 
					element.closest('.file_upload_wrap').querySelector('.documentpreview input') == null
				)
			){
				element.required	= true;
				valid				= element.reportValidity();
				if(!valid){
					break;
				}
			}
		}

		if(!valid) return;
		
		//mark the last step as finished
		stepIndicators[currentTab].classList.add("finish");
	}else{
		//mark the last step as unfinished
		stepIndicators[currentTab].classList.remove("finish");
	}
	
	//loop over all the formsteps to hide stepindicators of them if needed
	Array.from(x).forEach((formstep,index) =>{
		if(formstep.classList.contains('hidden')){
			//hide the corresponding circle
			stepIndicators[index].classList.add('hidden');
		}
	});

	// Increase or decrease the current tab by 1:
	currentTab = currentTab + n;
	
	//check if the next tab is hidden
	while(x[currentTab].classList.contains('hidden')){
		//go to the next tab
		currentTab = currentTab + n;

		if (currentTab >= x.length) {
			break;
		}
	}
	
	// if you have reached the end of the form... :
	if (currentTab >= x.length) {
		return false;
	}
	// Otherwise, display the correct tab:
	showTab(currentTab,form);

	return true;
}

export function changeFieldValue(selector, value, functionRef, form, addition='', forceValue=false){
	if(value == undefined){
		return;
	}

	let name		= '';
	let target		= '';

	if(selector instanceof Element){
		target		= selector;
		name		= target.name;
		if(target.id == ''){
			selector	= `[name^="${target.name}" i]`;
		}else{
			selector	= `[id^=${target.id}]`;
		}
		
	}else{
		target 		= form.querySelector(selector);

		try{
			name		= target.name;
		}catch{
			console.log(target);
		}
	}

	let oldValue	= getFieldValue(target, form, false, value);
	// nothing to change
	if(oldValue == value){
		return;
	}

	// Check if we are dealing with a multi input field
	if(target == null){
		let targets 	= form.querySelectorAll(`.clone_div [name^="${name}" i]`);
		if(targets.length === 0){
			return;
		}else if(targets.length == 1){
			target	= targets[0];
			targets	= '';
		}else{
			target	= targets[0];

			targets.forEach((el, index) =>{
				if(index == 0){
					changeFieldValue(el, '', '', form);
				}else{
					removeNode(el);
				}
			});
		}
	}

	// calculate the new value
	if(addition != ''){
		// check if a date
		if (/\d{4}-\d{2}-\d{2}/.test(value)){
			let date	= new Date(value);
			date.setDate(date.getDate() + parseInt(addition));
			value	= date.toISOString().split('T')[0];
		}else{
			value	= value + parseInt(addition);
		}
	}
	
	if(target.type == 'radio' || target.type == 'checkbox'){
		// uncheck all
		if(value == ''){
			if(selector != ''){
				form.querySelectorAll(selector).forEach(el=>el.checked=false);
			}
		}else{
			// Check if the current target is the one we need to check
			if(target.value.toLowerCase() == value.toLowerCase()){
				target.checked = true;
			}else{
				// find the element with the given value and check it
				let targets = form.querySelectorAll(`[name="${name}" i]`);
				for (const element of targets) {
					if(element.value.toLowerCase() == value.toLowerCase()){
						element.checked = true;
					}
				}
			}
		}
	//the target has a list attached to it
	}else if(target.type == 'date'){
		target.value = value;
		
		// convert a date to the right format
		if (!/\d{4}-\d{2}-\d{2}/.test(value)){
			let splitted = '';
			if(value.split('-').length == 3){
				splitted	= value.split('-');
			}else if(value.split('/').length == 3){
				splitted	= value.split('/');
			}

			if(splitted != ''){
				let year;
				let month;
				let day;
				splitted.forEach(nr=>{
					if(nr.length == 4){
						year	= nr;
					}else if(nr.length == 2){
						if(nr > 12){
							day	= nr;
						}else{
							// does not have a value yet
							if(month == undefined){
								month	= nr;
							}else{
								day	= nr;
							}
						}
					}
				});

				if(day != undefined && month != undefined && year != undefined){
					target.value = `${year}-${month}-${day}`;
				}
			}
		}
	}else if(target.list != null){
		let dataListOption = target.list.querySelector(`[data-value="${value}" i]`);

		//we found a match
		if(dataListOption != null){
			// We found a cloned field, add as many inputs as needed
			if(target.closest('.clone_div') != null){
				// mark the existing ones for deletion, we can delete right now as we need to copy the existing ones first
				target.closest('.clone_divs_wrapper').querySelectorAll('.clone_div').forEach(el=>el.classList.add('shouldremove'));

				let clone;
				dataListOption.value.split(';').forEach(val=>{
					clone = copyFormInput(target.closest('.clone_div'));

					clone.classList.remove('shouldremove');

					changeFieldValue(clone.querySelector(target.tagName), val, '', form, '', true);
				});

				fixNumbering(target.closest('.clone_divs_wrapper'));

				// delete the old ones
				target.closest('.clone_divs_wrapper').querySelectorAll('.shouldremove').forEach(el=>el.remove());
			}else{
				target.value = dataListOption.value;
			}
		// We did not find a match, we are filling in the given value
		}else if(forceValue){
			target.value = value;
		// We did not find a match, empty value
		}else{
			target.value = '';
		}
	}else{
		target.value = value;
	}
	
	//create a new event
	let evt = new Event('input');
	//attach the target
	target.dispatchEvent(evt);
	
	//run the originating function with this event
	if(typeof functionRef == 'function'){
		functionRef(target);
	}
}

export function changeVisibility(action, el, functionRef){
	let wrapper	= el.closest('.inputwrapper');
	if(wrapper == null){
		wrapper	= el
	}
	
	if(action == 'add'){
		if(wrapper.matches('.hidden')){
			return;
		}
		wrapper.classList.add('hidden');
	}else{
		if(!wrapper.matches('.hidden')){
			return;
		}
		wrapper.classList.remove('hidden');
	}
	//form.querySelector("[name='$conditionalFieldName']").closest('.hidden') == null;

	//create a new event
	let evt = new Event('input');
	//attach the target
	wrapper.dispatchEvent(evt);
	
	//run the originating function with this event
	if(typeof functionRef == 'function'){
		functionRef(el);
	}
}

export function changeFieldProperty(selector, att, value, functionRef, form, addition=''){
	//first change the value
	let target = form.querySelector(selector);

	// calculate the new value
	if(addition != ''){
		// check if a date
		if (/\d{4}-\d{2}-\d{2}/.test(value)){
			let date	= new Date(value);
			date.setDate(date.getDate() + parseInt(addition));
			value	= date.toISOString().split('T')[0];
		}else{
			value	= value + parseInt(addition);
		}
	}

	target[att] = value;
	
	//create a new event
	let evt = new Event('input');

	//attach the target
	target.dispatchEvent(evt);

	//run the originating function with this event
	functionRef(target);
}

async function formbuilderSwitch(target){
	let wrapper	= target.closest('.sim-form-wrapper');
	let button	= target.outerHTML;

	let formData = new FormData();
	let formId;

	const url 		= new URL(window.location);
	if(target.matches('.formbuilder-switch')){
		formData.append('formbuilder', true);
		url.searchParams.set('formbuilder', true);

		formId	= wrapper.querySelector('form.sim-form-wrapper').dataset.formid;
	}else{
		url.searchParams.delete('formbuilder');
		formId	= wrapper.querySelector('[name="formid"]').value;
	}
	window.history.pushState({}, '', url);

	formData.append('formid', formId);

	let loader	= Main.showLoader(target, false, 'Requesting form...');
	wrapper.innerHTML	= loader.outerHTML;

	let response = await FormSubmit.fetchRestApi('forms/form_builder', formData);

	if(response){
		wrapper.innerHTML	= response.html;

		addStyles(response, document);

		// Activate tinyMce's again
	/* 	wrapper.querySelectorAll('.wp-editor-area').forEach(el =>{
			window.tinyMCE.execCommand('mceAddEditor', false, el.id);
		});

		wrapper.querySelectorAll('select').forEach(function(select){
			select._niceselect = NiceSelect.bind(select, {searchable: true});
		}); */
	}else{
		loader.outerHTML	= button;
	}
}

async function requestNewFormResults(target){
	let wrapper		= target.closest('.form.table-wrapper');
	let button		= target.outerHTML;

	let formData 	= new FormData();
	let formId		= wrapper.querySelector('.sim-table.form-data-table').dataset.formid;
	let shortcodeId	= wrapper.querySelector('.sim-table.form-data-table').dataset.shortcodeid;

	formData.append('formid', formId);
	formData.append('shortcode_id', shortcodeId);

	const url 		= new URL(window.location);
	if(url.searchParams.get('onlyown')){
		formData.append('onlyown', true);
	}

	if(url.searchParams.get('all')){
		formData.append('all', true);
	}

	if(url.searchParams.get('archived')){
		formData.append('archived', true);
	}

	let loader	= Main.showLoader(target, false, 'Requesting form results...');
	wrapper.innerHTML	= loader.outerHTML;

	let response = await FormSubmit.fetchRestApi('forms/show_form_results', formData);

	if(response){
		wrapper.innerHTML	= response;
	}else{
		loader.outerHTML	= button;
	}
}

async function archivedEntriesSwitch(target){
	const url 		= new URL(window.location);
	if(target.matches('.archive-switch-show')){
		url.searchParams.set('archived', true);
	}else{
		url.searchParams.delete('archived');
	}
	window.history.pushState({}, '', url);

	requestNewFormResults(target);
}

async function onlyOwnSwitch(target){
	const url 		= new URL(window.location);
	if(target.matches('.onlyown-switch-on')){
		url.searchParams.set('onlyown', true);
		url.searchParams.delete('all', true);
	}else{
		url.searchParams.set('all', true);
		url.searchParams.delete('onlyown');
	}
	window.history.pushState({}, '', url);

	requestNewFormResults(target);
}

//we are online again
window.addEventListener('online', function(){
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

document.addEventListener('click', function(event) {
	let target = event.target;
	
	//add element
	if(target.matches('.add')){
		let newNode = copyFormInput(target.closest(".clone_div"));

		fixNumbering(target.closest('.clone_divs_wrapper'));

		//add tinymce's can only be done when node is inserted and id is unique
		newNode.querySelectorAll('.wp-editor-area').forEach(el =>{
			window.tinyMCE.execCommand('mceAddEditor',false, el.id);
		});

		target.remove();
	}
	
	//remove element
	if(target.matches('.remove')){
		//Remove node clicked
		removeNode(target);
	}

	if(target.matches('.sim-form-wrapper [name="submit_form"]')){
		event.stopPropagation();
		
		saveFormInput(target);
	}

	if(target.matches('.formbuilder-switch') || target.matches('.formbuilder-switch-back')){
		formbuilderSwitch(target);
	}

	if(target.matches('.archive-switch-hide') || target.matches('.archive-switch-show')){
		archivedEntriesSwitch(target);
	}

	if(target.matches('.onlyown-switch-all') || target.matches('.onlyown-switch-on')){
		onlyOwnSwitch(target);
	}
});

document.addEventListener('change', ev=>{
	// select all elements with a datalist attached
	if(ev.target.matches('input[list]') && ev.target.name.includes('[')){
		let el		= ev.target.list.querySelector(`[value="${ev.target.value}" i]`);

		if(el != null){
			// find the dataset value of the given element value
			let value	= el.dataset.value;

			if(value != undefined){
				// change the value to create extra inputs if necessary
				changeFieldValue(ev.target, value, '', ev.target.closest('form'));
			}
		}
	}
})