async function saveFormInput(target){
	let form		= target.closest('form');
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
		tinymce.init(tinymceSettings[el.id]);
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
		select.options[previousVal].style.display = 'none';
		
		//Add nice select
		select._niceselect.update();
		
		select.nextSibling.querySelector('.current').textContent = 'Select an option';
		
		i++;
	});
	
	//Add remove buttons if they are not there
	if(originalNode.querySelector('.remove') == null){
 		let addElement 		= newNode.querySelector('.add');
		let elementClass 	= addElement.className.replace('add','remove');
		let id 				= addElement.id.replace('add','remove');
		let content 		= addElement.textContent.replace('Add','Remove this').replace('an','').replace('+','-');
		
		let html = `<button type="button" class="${elementClass}" id="${id}" style="flex: 1;">${content}</button>`;
		
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
			clone.id = clone.id.replace(/(\d)/g, index);
		}
		
		if(clone.dataset.divid  != null){
			clone.dataset.divid = index;
		}
		
		//Update the title
		clone.querySelectorAll('h3, h4').forEach(title => {
			title.textContent = title.textContent.replace(/(\d)/g, index+1);
		});
		
		//Update the legend
		clone.querySelectorAll('legend').forEach(legend => {
			legend.innerHTML = legend.innerHTML.replace(/ (\d)/g, ' '+(index+1));
		});
		
		//Update the elements
		clone.querySelectorAll('input,select,textarea').forEach(input => {
			//Do not copy nice selects
			if(!input.classList.contains('nice-select-search')){
				//Update the id
				if(input.id != '' && input.id != undefined){
					input.id = input.id.replace(/(\d)/g, index);
				}
				//Update the name
				if(input.name != '' && input.name != undefined){
					input.name = input.name.replace(/(\d)/g, index);
				}
				
				//Reset the select to the default
				if(input.type == 'button'){
					input.value = input.value.replace(/\d/g, index);
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
		prev.querySelector('.buttonwrapper').appendChild(addElement);
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
	document.querySelectorAll('.clone_divs_wrapper').forEach(function(div){
		let cloneDivArr	= div.querySelectorAll('.clone_div');
		
		if(cloneDivArr.length == 1){
			cloneDivArr[0].querySelectorAll('.remove').forEach(el=>el.remove());
		}
		
		cloneDivArr.forEach(function(cloneDiv, index, array){
			//update dataset
			cloneDiv.dataset.divid = index;
			
			//remove add button for all but the last
			if(index != array.length - 1){
				cloneDiv.querySelectorAll('.add').forEach(el=>el.remove());
			}
		})
	});
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
export function nextPrev(n) {
	// This function will figure out which tab to display
	let x 				= form.querySelectorAll(".formstep");
	let stepIndicators	= form.querySelectorAll(".step");
	let currentTab		= 0;
	let valid;

	// Find the current active tab
	x.forEach((el, index)=>{if(!el.matches('.stephidden')){currentTab = index}});
	
	//Check validity of this step if going forward
	if(n>0){
		// Report validity of each required field
		let elements	= x[currentTab].querySelectorAll('.required:not(.hidden) input, .required:not(.hidden) textarea, .required:not(.hidden) select');
		for(const element of elements) {
			element.required		= true;
			valid		= element.reportValidity();
			if(!valid){
				break;
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
	}
	
	// if you have reached the end of the form... :
	if (currentTab >= x.length) {
		return false;
	}
	// Otherwise, display the correct tab:
	showTab(currentTab,form);
}

function getRadioValue(form, name){
	let el	= form.querySelector(`[name='${name}' i]:checked`);

	//There is no radio selected currently
	if(el == null){
		//return an empty value
		return '';
	}

	return el.value;
}

function getCheckboxValue(form, name, compareValue, orgName){
	let value		= '';
	let elements	= '';

	//we are dealing with a specific checkbox
	if(orgName.type == 'checkbox' ){
		if(orgName.checked){
			return orgName.value;
		}
		
		return '';
	}

	//we should find the checkbox with this value and check if it is checked
	if(compareValue != null){
		elements	= form.querySelector(`[name='${name}' i][value="${compareValue}" i]:checked`);
		if(elements != null){
			value = compareValue;
		}
	//no compare value give just return all checked values
	}else{
		elements	= form.querySelectorAll(`[name="${name}" i]:checked`);
		
		elements.forEach(el=>{
			if(value != ''){
				value += ', ';
			}
			value += el.value;
		});
	}

	return value;
}

function getDataListValue(el){
	let value		= '';
	let origInput 	= el.list.querySelector("[value='"+el.value+"' i]");
			
	if(origInput == null){
		value =  el.value;
	}else{
		value = origInput.dataset.value;
		if(value == ''){
			value =  el.value;
		}
	}

	return value;
}

export function getFieldValue(orgName, form, checkDatalist=true, compareValue=null, lowercase=false){
	let el		= ''; 
	let name	= '';
	let value 	= '';

	//name is not a name but a node
	if(orgName instanceof Element){
		el			= orgName;		
		//check if valid input type
		if(el.tagName != 'INPUT' && el.tagName != 'TEXTAREA' && el.tagName != 'SELECT' && el.closest('.nice-select-dropdown') == null){
			el = el.querySelector('input, select, textarea');
		}
		if(el == null){
			el			= orgName;
		}
		name		= el.name;
	}else{
		name		= orgName;
		el			= form.querySelector(`[name='${name}' i]`);
	}
	
	if(el == null){
		console.trace();
		console.log("cannot find element with name "+name);

		return value;
	}

	if(el.type == 'radio'){
		value	= getRadioValue(form, name);
	}else if(el.type == 'checkbox'){
		value	= getCheckboxValue(form, name, compareValue, orgName);	
	}else if(el.closest('.nice-select-dropdown') != null && el.dataset.value != undefined){
		//nice select
		value = el.dataset.value
	}else if(el.list != null && checkDatalist){
		value =  getDataListValue(el);
	}else if(el.value != null && el.value != 'undefined'){
		value = el.value;
	}
	
	if(lowercase){
		return value.toLowerCase();
	}
	return value;
}

function findcheckboxTarget(form, name, value){
	let targets = form.querySelectorAll(`[name="${name}" i]`);
	for (const element of targets) {
		if(element.value.toLowerCase() == value.toLowerCase()){
			return element;
		}
	}
}

export function changeFieldValue(orgName, value, functionRef, form){
	let name	= '';
	let target	= '';

	if(orgName instanceof Element){
		name	= orgName.name;
		target	= orgName;
	}else{
		name 	= orgName;
		//get the target
		target 	= form.querySelector(`[name="${name}" i]`);
	}
	
	if(target.type == 'radio' || target.type == 'checkbox'){
		if(!(orgName instanceof Element)){
			target	= findcheckboxTarget(form, name, value);
		}
		
		if(target.value.toLowerCase() == value.toLowerCase()){
			target.checked = true;
		}
	//the target has a list attached to it
	}else if(target.list != null){
		let dataListOption = target.list.querySelector(`[data-value="${value}" i]`);

		//we found a match
		if(dataListOption != null){
			target.value = dataListOption.value;
		}else{
			target.value = value;
		}
	}else{
		target.value = value;
	}
	
	//create a new event
	let evt = new Event('input');
	//attach the target
	target.dispatchEvent(evt);
	
	//run the originating function with this event
	functionRef(target);
}

export function changeFieldProperty(name, att, value, functionRef, form){
	//first change the value
	let target = form.querySelector(`[name="${name}" i]`);
	
	form.querySelector(`[name="${name}"]`)[att] = value;
	
	//create a new event
	let evt = new Event('input');

	//attach the target
	target.dispatchEvent(evt);

	//run the originating function with this event
	functionRef(target);
}

async function formbuilderSwitch(target){
	let wrapper	= target.closest('.sim_form.wrapper');
	let button	= target.outerHTML;

	let formData = new FormData();
	let formId;

	const url 		= new URL(window.location);
	if(target.matches('.formbuilder-switch')){
		formData.append('formbuilder', true);
		url.searchParams.set('formbuilder', true);

		formId	= wrapper.querySelector('form.sim_form').dataset.formid;
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
		wrapper.innerHTML	= response;

		// Activate tinyMce's again
		wrapper.querySelectorAll('.wp-editor-area').forEach(el =>{
			window.tinyMCE.execCommand('mceAddEditor',false, el.id);
		})
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
	formData.append('id', shortcodeId);

	const url 		= new URL(window.location);
	if(url.searchParams.get('onlyown')){
		formData.append('onlyown', true);
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
	}else{
		url.searchParams.delete('onlyown');
	}
	window.history.pushState({}, '', url);

	requestNewFormResults(target);
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

	if(target.matches('.sim_form [name="submit_form"]')){
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