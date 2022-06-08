var re			= '';

async function saveFormInput(target){
	var form		= target.closest('form');
	var response	= await FormSubmit.submitForm(target, 'forms/save_form_input');

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

export function cloneNode(originalNode, clear=true){
	//First remove any nice selects
	originalNode.querySelectorAll('select').forEach(select => {
		//remove defaults if it has changed
		if(select.selectedIndex != -1){
			if(select.options[select.selectedIndex].defaultSelected == false){
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
		var tinymceSettings = [];
		originalNode.querySelectorAll('.wp-editor-area').forEach(el =>{
			var tn = tinymce.get(el.id);
			if(tn != null){
				tinymceSettings[el.id] = tn.settings
				tn.save();
				tn.remove();
			}
		});
	}
	
	//make a clone
	var newNode = originalNode.cloneNode(true);
	
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
	if(clear == true){
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
	var newNode = cloneNode(originalNode);
	
	//update the data index
	newNode.querySelectorAll('.upload-files').forEach(function(uploadButton){
		uploadButton.dataset.index = nodeNr;
	})
	
	//Clear contents of any document preview divs.
	newNode.querySelectorAll('.documentpreview').forEach(function(previewdiv){
		previewDiv.innerHTML = '';
	});

	//Select
	var i = 0;
	newNode.querySelectorAll('select').forEach(select => {
		//Find the value of the select we have cloned
		var previousVal = originalNode.getElementsByTagName('select')[i].selectedIndex;
		
		//Hide the value in the clone
		select.options[previousVal].style.display = 'none';
		
		//Add nice select
		select._niceselect.update();
		
		select.nextSibling.querySelector('.current').textContent = 'Select an option';
		
		i++;
	});
	
	//Add remove buttons if they are not there
	if(originalNode.querySelector('.remove') == null){
 		var addElement 		= newNode.querySelector('.add');
		var elementClass 	= addElement.className.replace('add','remove');
		var id 				= addElement.id.replace('add','remove');
		var content 		= addElement.textContent.replace('Add','Remove this').replace('an','').replace('+','-');
		
		var html = `<button type="button" class="${elementClass}" id="${id}" style="flex: 1;">${content}</button>`;
		
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
	var re	= new RegExp('(.*)[0-9](.*)',"g");

	cloneDivsWrapper.querySelectorAll(':scope > .clone_div').forEach((clone, index)=>{
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

function removeNode(target){
	var node			= target.closest(".clone_div");
	var parentNode		= node.closest('.clone_divs_wrapper');
	var allCloneDivs	= parentNode.querySelectorAll('.clone_div');
	
	//Check if we are removing the last element
	if(allCloneDivs[allCloneDivs.length-1] == node){
		var addElement = node.querySelector(".add");
		
		//Move the add button one up
		var prev = node.previousElementSibling;
		prev.querySelector('div').appendChild(addElement);
	}
	
	//Check which number to update
	if(node.getAttribute("data-type") != null){
		var dataType	= node.getAttribute("data-type").replace('[','\\[').replace(']','\\]');
		re 				= new RegExp('('+dataType+'?\\]?\\[).*[0-9](.*)',"g");
	}else{
		re				= new RegExp('(.*)[0-9](.*)',"g");
	}
	
	//Remove the node
	node.remove();
	
	//update the collection
	allCloneDivs	= parentNode.querySelectorAll('.clone_div');
	
	//If there is only one div remaining, remove the remove button
	if(allCloneDivs.length == 1){
		var removeElement = parentNode.querySelector('.remove');
		removeElement.remove();
	}
	
	//Loop over all the remaining nodes.
	var nodeNr = parseInt(allCloneDivs[0].dataset.divid);
	allCloneDivs.forEach(function(cloneNode){
		if(cloneNode.dataset.type == 'understudies'){
			//update the data index
			cloneNode.querySelectorAll('.upload-files').forEach(function(uploadButton){
				uploadButton.dataset.index = nodeNr;
			})
		}
			
		//DIV
		if(cloneNode.id != ''){
			cloneNode.id = cloneNode.id.replace(re, '$1'+nodeNr+'$2');
		}
		
		cloneNode.dataset.divid = nodeNr;
		
		//Update the title
		cloneNode.querySelectorAll('h3').forEach(title => {
			title.textContent = title.textContent.replace(/[0-9]/g, nodeNr);
		});
		
		//Update the label
		cloneNode.querySelectorAll('label').forEach(label => {
			if(allCloneDivs[0].dataset.divid == 0){
				labelNr = nodeNr + 1;
			}else{
				labelNr = nodeNr;
			}
			label.innerHTML = label.innerHTML.replace(/ ([0-9])/g, function($1) {return ' '+labelNr});
		});
		
		//Update the elements
		cloneNode.querySelectorAll('input,select,textarea').forEach(input => {
			if(input.id != ''){
				//Update the id
				input.id = input.id.replace(re, '$1'+nodeNr+'$2');
			}
			//Update the name
			input.name = input.name.replace(re, '$1'+nodeNr+'$2');
		});	

		nodeNr += 1;
	})
}

/* 
	FUNCTIONS USED BY DYNAMIC FORMS JS
 */
export function tidyMultiInputs(){
	//remove unnecessary buttons on inputs with multiple values
	document.querySelectorAll('.clone_divs_wrapper').forEach(function(div){
		var cloneDivArr	= div.querySelectorAll('.clone_div');
		
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
export function nextPrev(n) {
	// This function will figure out which tab to display
	var x 				= form.querySelectorAll(".formstep");
	var stepIndicators	= form.querySelectorAll(".step");
	var currentTab		= 0;
	var valid;

	// Find the current active tab
	x.forEach((el, index)=>{if(!el.matches('.stephidden')){currentTab = index}});
	
	//Check validity of this step if going forward
	if(n>0){
		// Report validity of each required field
		var elements	= x[currentTab].querySelectorAll('.required:not(.hidden) input, .required:not(.hidden) textarea, .required:not(.hidden) select');
		for(var i = 0; i < elements.length; i++) {
			elements[i].required		= true;
			valid		= elements[i].reportValidity();
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

export function getFieldValue(orgName, checkDatalist=true, compareValue=null, lowercase=false){
	//name is not a name but a node
	if(orgName instanceof Element){
		var el			= orgName;		
		//check if valid input type
		if(el.tagName != 'INPUT' && el.tagName != 'TEXTAREA' && el.tagName != 'SELECT' && el.closest('.nice-select-dropdown') == null){
			el = el.querySelector('input, select, textarea');
		}
		if(el == null){
			el			= orgName;
		}
		var name		= el.name;
	}else{
		var name		= orgName;
		var el			= form.querySelector('[name=\"'+name+'\" i]');
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
			if(orgName.type == 'checkbox' ){
				if(el.checked){
					return el.value;
				}else{
					return '';
				}
			//we should return all checked checkboxes
			}else{
				//we should find the checkbox with this value and check if it is checked
				if(compareValue != null){
					var els		= form.querySelector(`[name='${name}' i][value="${compareValue}" i]:checked`);
					if(els != null){
						value = compareValue;
					}
				//no compare value give just return all checked values
				}else{
					var els		= form.querySelectorAll(`[name="${name}" i]:checked`);
					
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
		}else if(el.list != null && checkDatalist){
			var origInput = el.list.querySelector("[value='"+el.value+"' i]");
			
			if(origInput == null){
				value =  el.value;
			}else{
				value = origInput.dataset.value;
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
		}else if(el.value != null && el.value != 'undefined'){
			value = el.value;
		}
		
		if(lowercase){
			return value.toLowerCase();
		}
		return value;
	}else{
		console.trace();
		console.log("cannot find element with name "+name);
	}
}

export function changeFieldValue(orgName, value, function_ref){
	if(orgName instanceof Element){
		var name	= orgName.name;
		var target	= orgName;
	}else{
		var name = orgName;
		//get the target
		var target = form.querySelector(`[name="${name}" i]`);
	}
	
	if(target.type == 'radio' || target.type == 'checkbox'){
		if(!(orgName instanceof Element)){
			targets = form.querySelectorAll(`[name="${name}" i]`);
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
		var datalistoption = target.list.querySelector(`[data-value="${value}" i]`);
		//we found a match
		if(datalistoption != null){
			target.value = datalistoption.value;
		}else{
			target.value = value;
		}
	}else{
		target.value = value;
	}
	
	//create a new event
	var evt = new Event('input');
	//attach the target
	target.dispatchEvent(evt);
	
	//run the originating function with this event
	//window[arguments.callee.caller.name](target);
	function_ref(target);
}

export function changeFieldProperty(name, att, value){
	//first change the value
	var target = form.querySelector(`[name="${name}" i]`);
	
	form.querySelector(`[name="${name}"]`)[att] = value;
	
	//create a new event
	var evt = new Event('input');
	//attach the target
	target.dispatchEvent(evt);
	//run the originating function with this event
	function_ref(target);
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
	
	//add element
	if(target.matches('.add')){
		var newNode = copyFormInput(target.closest(".clone_div"));

		fixNumbering(target.closest('.clone_divs_wrapper'));

		//add tinymce's can only be done when node is inserted and id is unique
		newNode.querySelectorAll('.wp-editor-area').forEach(el =>{
			window.tinyMCE.execCommand('mceAddEditor',false, el.id);
		});

		target.remove();
	}
	
	//remove element
	if(target.matches('.remove')){
		var wrapper	= target.closest('.clone_divs_wrapper');
		//Remove node clicked
		removeNode(target); 

		fixNumbering(wrapper);
	}

	if(target.matches('.sim_form [name="submit_form"]')){
		event.stopPropagation();
		
		saveFormInput(target);
	}
});