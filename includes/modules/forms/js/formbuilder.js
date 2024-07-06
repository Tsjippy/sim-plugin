var reorderingBusy, formWrapper, formElementWrapper, modal;

console.log("Formbuilder.js loaded");

/* FUNCTIONS */
function clearFormInputs(){
	try {
		//Loop to clear the modalform
		modal.querySelectorAll('input:not([type=hidden]), select, textarea, [name=insertafter], [name=element_id]').forEach(function(el){
			if(el.type == 'checkbox'){
				el.checked = false;
			}else{
				el.value = '';
			}

			if(el.type == 'textarea' && el.id != ''){
				let editor = tinyMCE.get(el.id);
				if(editor != null){
					editor.setContent('');
				}
			}
			
			if(el.type == "select-one"){
				FormFunctions.removeDefaultSelect(el);
				
				if(el._niceselect != undefined){
					el._niceselect.clear();
					el._niceselect.update();
				}
			}
		});
		
		hideConditionalfields(modal.querySelector('form'));
	}
	catch(err) {
		console.error(err);
	}
}

function fixElementNumbering(form){
	form.querySelectorAll('.form_element_wrapper').forEach((el, index)=>{
		el.dataset.priority = index+1;
	});
}

// add new element
async function showEmptyModal(target){
	target.classList.add('clicked');

	let formId					= target.dataset.formid;
	if(formId == undefined){
		if(target.closest('.form_element_wrapper') != null){
			formId = target.closest('.form_element_wrapper').dataset.formid;
		}else{
			formId = document.querySelector('input[type=hidden][name="formid"').value;
		}
	}
	
	clearFormInputs();

	// Hide all
	modal.querySelectorAll(".shouldhide").forEach(el=>el.classList.replace('shouldhide', 'hidden'))
	
	if(formElementWrapper != null){
		modal.querySelector('[name="insertafter"]').value = formElementWrapper.dataset.priority;
	}
	
	modal.querySelector('[name="submit_form_element"]').textContent = modal.querySelector('[name="submit_form_element"]').textContent.replace('Change','Add');
	
	modal.querySelector('.element_conditions_wrapper').innerHTML = `<img src="${sim.loadingGif}" style="display:block; margin:0px auto 0px auto;">`;
	
	Main.showModal(modal);

	var formData = new FormData();
	formData.append('elementid', '-1');
	formData.append('formid', formId);
	var response = await FormSubmit.fetchRestApi('forms/request_form_conditions_html', formData);

	//fill the element conditions tab
	modal.querySelector('.element_conditions_wrapper').innerHTML = response;

	// Add nice selects
	modal.querySelectorAll('.condition_select').forEach(function(select){
		select._niceselect = NiceSelect.bind(select,{searchable: true});
	});
}

//edit existing element
async function requestEditElementData(target){
	target.classList.add('clicked');

	let elementId		= formElementWrapper.dataset.id;
	let formId			= target.closest('.form_element_wrapper').dataset.formid;
	modal.querySelector('[name="element_id"]').value = formElementWrapper.dataset.id;
	modal.originalhtml	= target.outerHTML;

	let editButton		= target.outerHTML;

	let loader			= Main.showLoader(target);

	loader.querySelector('.loadergif').style.margin = '5px 19px 0px 19px';
	
	let formData = new FormData();
	formData.append('elementid', elementId);
	formData.append('formid', formId);
	
	let response = await FormSubmit.fetchRestApi('forms/request_form_element', formData);

	if(response){
		//fill the form after we have clicked the edit button
		if(modal.querySelector('[name="add_form_element_form"]') != null){
			modal.querySelector('[name="add_form_element_form"]').innerHTML = response.elementForm;

			//activate tiny mce's
			modal.querySelectorAll('.wp-editor-area').forEach(el =>{
				window.tinyMCE.execCommand('mceAddEditor',false, el.id);
			});
		}

		showCondionalFields(modal.querySelector('[name="formfield[type]"]').value, modal.querySelector('[name="add_form_element_form"]'));

		//fill the element conditions tab
		modal.querySelector('.element_conditions_wrapper').innerHTML = response.conditionsHtml;

		// Add nice selects
		modal.querySelectorAll('select').forEach(function(select){
			select._niceselect = NiceSelect.bind(select,{searchable: true});
		});
		
		Main.showModal(modal);
		
		//show edit button again
		loader.outerHTML	= editButton;
	}
}

async function addFormElement(target){
	let form		= target.closest('form');
	let response	= await FormSubmit.submitForm(target, 'forms/add_form_element');

	if(response){
		if(form.querySelector('[name="element_id"]').value == ''){
			//First clear any previous input
			clearFormInputs();

			let referenceNode		= document.querySelector('.form_elements .clicked');
			if(referenceNode == null){
				window.location.href = window.location.href+'&formbuilder=true';
			}
			referenceNode.classList.remove('clicked');
			referenceNode.closest('.form_element_wrapper').insertAdjacentHTML('afterEnd', response.html)
			
			fixElementNumbering(referenceNode.closest('form'));
			
			//add resize listener
			form.querySelectorAll('.resizer').forEach(el=>{resizeOb.observe(el);});
		}else{
			//Runs after an element update
			document.querySelector('.form_elements .clicked').closest('.form_element_wrapper').outerHTML = response.html;
		}

		Main.hideModals();

		Main.displayMessage(response.message);
	}
}

async function sendElementSize(el, widthPercentage){
	if(widthPercentage != el.dataset.widthpercentage && Math.abs(widthPercentage - el.dataset.widthpercentage)>5){
		el.dataset.widthpercentage = widthPercentage;
		
		//send new width over AJAX
		let formData = new FormData();
		formData.append('formid', el.closest('.form_element_wrapper').dataset.formid);
		formData.append('elementid', el.closest('.form_element_wrapper').dataset.id);
		formData.append('new_width', widthPercentage);
		
		let response = await FormSubmit.fetchRestApi('forms/edit_formfield_width', formData);

		if(response){
			Main.hideModals();

			Main.displayMessage(response);
		}
	}
}

async function removeElement(target){
	let parent			= target.parentNode;
	let elementWrapper	= target.closest('.form_element_wrapper');
	let formId			= elementWrapper.dataset.formid;
	let elementIndex 	= elementWrapper.dataset.id;
	let form			= target.closest('form');

	Main.showLoader(target);
	let loader			= parent.querySelector('.loadergif');
	loader.style.paddingRight = '10px';
	loader.classList.remove('loadergif');

	let formData = new FormData();
	formData.append('formid', formId);

	formData.append('elementindex', elementIndex);
	
	let response = await FormSubmit.fetchRestApi('forms/remove_element', formData);

	if(response){
		//remove the formelement row
		elementWrapper.remove();
		
		fixElementNumbering(form);

		Main.displayMessage(response);
	}
}

 //Fires after element reorder
async function reorderformelements(event){
	if(!reorderingBusy){
		reorderingBusy = true;

		let oldIndex	= parseInt(event.item.dataset.priority);

		fixElementNumbering(event.item.closest('form'));
		
		let difference = event.newIndex-event.oldIndex

		let formData = new FormData();
		formData.append('form_id', event.item.dataset.formid);
		formData.append('el_id', event.item.dataset.id);
		formData.append('old_index', oldIndex);
		formData.append('new_index', (oldIndex + difference));
		
		let response	= await FormSubmit.fetchRestApi('forms/reorder_form_elements', formData);

		if(response){
			reorderingBusy = false;

			Main.displayMessage(response);
		}
	}else{
		let options = {
			icon: 'error',
			title: 'ordering already in progress, please wait',
			confirmButtonColor: "#bd2919",
		};

		if(document.fullscreenElement != null){
			options['target']	= document.fullscreenElement;
		}

		Swal.fire(options);
	}
}

async function saveFormConditions(target){
	let response	= await FormSubmit.submitForm(target, 'forms/save_element_conditions');

	if(response){
		Main.hideModals();

		Main.displayMessage(response);
	}
}

async function saveFormSettings(target){
	let response	= await FormSubmit.submitForm(target, 'forms/save_form_settings');

	if(response){
		target.closest('.submit_wrapper').querySelector('.loadergif').classList.add('hidden');

		Main.displayMessage(response);
	}
}

async function saveFormEmails(target){
	let response	= await FormSubmit.submitForm(target, 'forms/save_form_emails');

	if(response){
		target.closest('.submit_wrapper').querySelector('.loadergif').classList.add('hidden');

		Main.displayMessage(response);
	}
}

//listen to element size changes
var doit;
const resizeOb = new ResizeObserver(function(entries) {
	let element = entries[0].target;
	if(element.parentNode != undefined){
		var width	= entries[0].contentRect.width
		var widthPercentage = Math.round(width/element.parentNode.offsetWidth * 100);
		
		//Show percentage on screen
		let el	= element.querySelector('.widthpercentage');
		if(widthPercentage <99){
			el.textContent = widthPercentage + '%';
		}else if(el != null){
			el.textContent = '';
		}
		
		clearTimeout(doit);
		doit = setTimeout(sendElementSize, 500, element, widthPercentage);
	}
});

//show conditional fields based on on the element type
function showCondionalFields(type, form){
	hideConditionalfields(form);

	form.querySelectorAll(`.elementoption:not(.${type}, .reverse), .elementoption.not${type}`).forEach(el=>el.classList.replace('shouldhide', 'hidden'));
	form.querySelectorAll(`.elementoption.${type}`).forEach(el=>el.classList.replace('hidden', 'shouldhide'));

	switch(type) {
		case 'button':
		case 'formstep':
		case 'label':
			form.querySelector('[name="labeltext"] .elementtype').textContent = type;
			break;
		case 'info':
			form.querySelector('[name="infotext"] .type').textContent = 'infobox';
			break;
		case 'hcaptcha':
		case 'recaptcha':
		case 'turnstile':
			modal.querySelectorAll(".shouldhide").forEach(el=>el.classList.replace('shouldhide', 'hidden'));
			modal.querySelectorAll(`[name="elementname"]`).forEach(el=>el.classList.replace('hidden', 'shouldhide'));
			break;
		case 'radio':
		case 'select':
		case 'checkbox':
		case 'datalist':
			break;
		case 'p':
			form.querySelector('[name="infotext"] .type').textContent = 'Paragraph';
			break;
		case 'php':
			break;
		case 'file':
		case 'image':
			form.querySelectorAll('.filetype').forEach(el=>{el.textContent = type;});
			break;
		default:
			break;
	} 
}

function hideConditionalfields(form){
	form.querySelectorAll(`.elementoption.reverse`).forEach(el=>el.classList.replace('hidden', 'shouldhide'));
}

function showOrHideIds(target){

	const url 		= new URL(window.location);

	if(target.dataset.action == 'show'){
		url.searchParams.set('showid', 'true');

		formWrapper.querySelectorAll('.elid.hidden').forEach(el=>el.classList.remove('hidden'));
		
		target.textContent 		= 'Hide ids';
		target.dataset.action	= 'hide';
	}else{
		url.searchParams.delete('showid');

		formWrapper.querySelectorAll('.elid:not(.hidden)').forEach(el=>el.classList.add('hidden'));
		
		target.textContent 		= 'Show ids';
		target.dataset.action	= 'show';
	}
	
	window.history.pushState({}, '', url);
}

function showOrHideName(target){
	const url 		= new URL(window.location);

	if(target.dataset.action == 'show'){
		url.searchParams.set('showname', 'true');

		formWrapper.querySelectorAll('.elname.hidden').forEach(el=>el.classList.remove('hidden'));
		
		target.textContent 		= 'Hide names';
		target.dataset.action	= 'hide';
	}else{
		url.searchParams.delete('showid');

		formWrapper.querySelectorAll('.elname:not(.hidden)').forEach(el=>el.classList.add('hidden'));
		
		target.textContent 		= 'Show names';
		target.dataset.action	= 'show';
	}

	window.history.pushState({}, '', url);
}

function maybeRemoveElement(target){
	if(typeof(Swal)=='undefined'){
		removeElement(target);
	}else{
		let options = {
			title: 'Are you sure?',
			text: "This will remove this element",
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#3085d6',
			cancelButtonColor: '#d33',
			confirmButtonText: 'Yes, delete it!'
		}

		if(document.fullscreenElement != null){
			options['target']	= document.fullscreenElement;
		}

		Swal.fire(options).then((result) => {
			if (result.isConfirmed) {
				removeElement(target);
			}
		})
	}
}

function showOrHideConditionFields(target){
	//show default conditional field
	target.closest('.rule_row').querySelector('[name*="conditional_value"]').classList.remove('hidden');
	
	//hide extra fields
	target.closest('.rule_row').querySelectorAll('.equation_2, .conditional_field_2').forEach(function(el){
		el.classList.add('hidden');
	});
	
	//show both extra fields
	if(target.dataset.value == '-' || target.dataset.value == '+'){
		target.closest('.rule_row').querySelectorAll('.equation_2, .conditional_field_2').forEach(function(el){
			el.classList.remove('hidden');
		});
	//show extra conditional field
	}else if(target.dataset.value != undefined && target.dataset.value.includes('value')){
		target.closest('.rule_row').querySelector('.conditional_field_2').classList.remove('hidden');

		//hide normal conditional value field
		target.closest('.rule_row').querySelector('[name*="conditional_value"]').classList.add('hidden');
	}else if(target.dataset.value != undefined && (
			target.dataset.value.includes('changed') || 
			target.dataset.value.includes('clicked') ||
			target.dataset.value.includes('visible') ||
			target.dataset.value.includes('invisible') ||
			target.dataset.value.includes('checked')
	)){
		target.closest('.rule_row').querySelector('[name*="conditional_value"]').classList.add('hidden');
	}
}

async function addConditionRule(target){
	let condition		= target.closest('.condition_row');
	let row				= target.closest('.rule_row');
	let activeButton	= row.querySelector('.active');
		
	
	if(
		(
			activeButton == null && 													// there is no active button
			condition.querySelectorAll('.rule_row').length > 1 && 						// but there are more than one rules
			row.dataset.rule_index != condition.querySelectorAll('.rule_row').length -1	// and this is not the last rule
		) ||
		(
			activeButton != null && 				// there is an active button
			!target.classList.contains('active')	// We clicked on the button which was not active
		)
	){	
		let current		= 'OR';
		let opposite	= 'AND';
		let makeActive	= true;
		
		if(activeButton != null){
			if(activeButton.textContent == 'AND'){
				current		= 'AND';
				opposite	= 'OR';
			}

			let options = {
				title: 'What do you want to do?',
				showDenyButton: true,
				showCancelButton: true,
				confirmButtonText: `Change ${current} to ${opposite}`,
				denyButtonText: 'Add a new rule',
			};
	
			if(document.fullscreenElement != null){
				options['target']	= document.fullscreenElement;
			}
			
			result	= await Swal.fire(options)

			//swap and/or
			if (result.isConfirmed) {
				//make other button inactive
				activeButton.classList.remove('active');
			//add new rule after this one
			}else if (result.isDenied) {
				addRuleRow(row);
				makeActive	= false;
			}
		}

		if(makeActive){
			//make the button active
			target.classList.add('active');
			
			//store action 
			row.querySelector('.combinator').value = target.textContent;
		}
		
			
	//add new rule at the end
	}else{
		addRuleRow(row);
		
		//make the button active
		target.classList.add('active');
		
		//store action 
		row.querySelector('.combinator').value = target.textContent;
	}
}

function addRuleRow(row){
	//Insert a new rule row
	let clone		= FormFunctions.cloneNode(row);
	
	let cloneIndex	= parseInt(row.dataset.rule_index) + 1;

	clone.dataset.rule_index = cloneIndex;

	row.parentNode.insertBefore(clone, row.nextSibling);
	
	fixRuleNumbering(row.closest('.condition_row'));
}

function addOppositeCondition(clone, target){
	//Set values to opposite
	clone.querySelectorAll('.element_condition').forEach(function(el){
		if(el.tagName == 'SELECT' && el.classList.contains('equation')){
			//remove all default selected
			FormFunctions.removeDefaultSelect(el);
			
			//get the original value which was lost during cloning
			let originalSelect 	= target.closest('.condition_row').querySelector('.equation');
			let selIndex		= originalSelect.selectedIndex;
			
			if(selIndex){
				//if odd the select the next one
				if(selIndex % 2){
					el.options[(selIndex + 1)].defaultSelected = true;
				}else{
					el.options[(selIndex - 1)].defaultSelected = true;
				}
			}
			
			//reflect changes in niceselect
			el._niceselect.update();
		}else if(el.type == 'radio' && el.classList.contains('element_condition') && (el.value == 'show' || el.value == 'hide')){
			if(!el.checked){
				el.checked = true;
			}else{
				el.checked = false;
			}
		}
	});

	return clone;
}

function addCondition(target){
	let clone;
	let row = target.closest('.condition_row');
	
	if(target.classList.contains('opposite')){
		clone = FormFunctions.cloneNode(row, false);
		clone = addOppositeCondition(clone, target);
	}else{
		clone = FormFunctions.cloneNode(row);
	}
	
	let cloneIndex	= parseInt(row.dataset.condition_index) + 1;

	clone.dataset.condition_index = cloneIndex;
	
	if(!target.classList.contains('opposite')){
		clone.querySelectorAll('.active').forEach(function(el){
			el.classList.remove('active');
		});
	}	
	
	//store radio values
	let radioValues = {};
	row.querySelectorAll('input[type="radio"]:checked').forEach(input=>{
		radioValues[input.value]	= input.value;
	});
		
	//insert in page
	row.parentNode.insertBefore(clone, row.nextSibling);
	
	if(!target.classList.contains('opposite')){
		//remove unnecessy rulerows works only after html insert
		let ruleCount = clone.querySelectorAll('.rule_row').length;
		clone.querySelectorAll('.rule_row').forEach(function(ruleRow){
			//only keep the last rule (as that one has the + button)
			if(ruleRow.dataset.rule_index != ruleCount - 1){
				ruleRow.remove();
			}else{
				fixRuleNumbering(ruleRow.closest('.condition_row'));
			}
		});
	}
	
	//fix numbering
	fixConditionNumbering();
	
	//fix radio's as they get unselected due to same names on insert
	row.querySelectorAll('input[type="radio"]').forEach(input=>{
		if(radioValues[input.value] != undefined){
			input.checked = true;
		}
	});
	
	
	//hide button
	target.style.display = 'none';
}

function removeConditionRule(target){
	let conditionRow = target.closest('.condition_row');
	
	//count rule rows in this condition row
	if(conditionRow.querySelectorAll('.rule_row').length > 1){
		let options = {
			title: 'What do you want to remove?',
			showDenyButton: true,
			showCancelButton: true,
			confirmButtonText: `One condition rule`,
			denyButtonText: `The whole condition`,
			confirmButtonColor: "#bd2919",
		};

		if(document.fullscreenElement != null){
			options['target']	= document.fullscreenElement;
		}

		Swal.fire(options).then((result) => {
			//remove a rule rowe
			if (result.isConfirmed) {
				//get the current row
				let ruleRow		= target.closest('.rule_row');
				
				//get the current row index
				let ruleRowIndex	= parseInt(ruleRow.dataset.rule_index);
				
				//Get previous row
				let prevRow		= conditionRow.querySelector('[data-rule_index="'+(ruleRowIndex - 1)+'"]');
				
				if(prevRow != null){
					//remove the active class from row above if there is no row after this one
					if(conditionRow.querySelector(`[data-rule_index="${ruleRowIndex + 1}"]`) == null){
						prevRow.querySelectorAll('.active').forEach(el=>el.classList.remove('active'));
					}
					
					//clear the hidden input
					prevRow.querySelector('.combinator').value	= '';
				}
				
				target.closest('.rule_row').remove();
				
				fixRuleNumbering(conditionRow);
			} else if (result.isDenied) {
				conditionRow.remove();
				fixConditionNumbering();
			}
		});
	}else{
		let options = {
			title: 'Are you sure?',
			text: "This will remove this condition",
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: "#bd2919",
			cancelButtonColor: '#d33',
			confirmButtonText: 'Yes, delete it!'
		};

		if(document.fullscreenElement != null){
			options['target']	= document.fullscreenElement;
		}

		Swal.fire(options).then((result) => {
			if (result.isConfirmed) {
				conditionRow.remove();
				fixConditionNumbering();
			}
		});
	}
}

function fixRuleNumbering(conditionRow){
	let ruleRows	= conditionRow.querySelectorAll('.element_conditions_wrapper .rule_row');
	let i = 0;
	
	//loop over all rules in the condition
	ruleRows.forEach(ruleRow =>{
		ruleRow.dataset.rule_index = i;
		
		ruleRow.querySelectorAll('.element_condition').forEach(function(el){
			//fix numbering
			if(el.id != undefined){
				el.id = el.id.replace(/([0-9]+\]\[rules\]\[)[0-9]+/g,"$1"+i);
			}
				
			if(el.name != undefined){
				el.name = el.name.replace(/([0-9]+\]\[rules\]\[)[0-9]+/g,"$1"+i);
			}
		});
		
		i++;
	});
}

function fixConditionNumbering(){
	let conditionRows	= modal.querySelectorAll('.element_conditions_wrapper .condition_row');
	for (let i = 0; i < conditionRows.length; i++) {
		conditionRows[i].dataset.condition_index = i;
		
		conditionRows[i].querySelectorAll('.element_condition').forEach(function(el){
			//fix numbering
			if(el.id != undefined){
				el.id = el.id.replace(/[0-9]+(\]\[rules\]\[[0-9]+)/g,i+"$1")
				el.id.replace(/[0-9]+(\]\[action\])/g,i+"$1");
				el.id = el.id.replace(/(element_conditions\[)[0-9]+\]\[+/g,"$1"+i+'][');
			}
			if(el.name != undefined){
				el.name = el.name.replace(/[0-9]+(\]\[rules\]\[[0-9]+)/g,i+"$1");
				el.name = el.name.replace(/[0-9]+(\]\[action\])/g,i+"$1");
				el.name = el.name.replace(/(element_conditions\[)[0-9]+\]\[+/g,"$1"+i+'][');
			}
		});
	}
}

function focusFirst(){
	modal.scrollTo(0,0);
	
	console.log('scrolling')
	modal.querySelector('[name="add_form_element_form"] .nice-select').focus();
}

reorderingBusy = false;
document.addEventListener("DOMContentLoaded",function() {

	//Make the form_elements div sortable
	let options = {
		handle: '.movecontrol',
		animation: 150,
		onEnd: reorderformelements
	};
	
	document.querySelectorAll('.form_elements').forEach(el=>{Sortable.create(el, options);});
	
	//Listen to resize events on form_element_wrappers
	document.querySelectorAll('.resizer').forEach(el=>{resizeOb.observe(el);});
});

function fromEmailClicked(target){
	let div1 = target.closest('.clone_div').querySelector('.emailfromfixed');
	let div2 = target.closest('.clone_div').querySelector('.emailfromconditional');

	if(target.value == 'fixed'){
		div1.classList.remove('hidden');
		div2.classList.add('hidden');
	}else{
		div2.classList.remove('hidden');
		div1.classList.add('hidden');
	}
}

function toEmailClicked(target){
	let div1 = target.closest('.clone_div').querySelector('.emailtofixed');
	let div2 = target.closest('.clone_div').querySelector('.emailtoconditional');

	if(target.value == 'fixed'){
		div1.classList.remove('hidden');
		div2.classList.add('hidden');
	}else{
		div2.classList.remove('hidden');
		div1.classList.add('hidden');
	}
}

function placeholderSelect(target){
	let value = '';
	if(target.classList.contains('placeholders')){
		value = target.textContent;
	}else if(target.value != ''){
		value = target.value;
		target.selectedIndex = '0';
	}
	
	if(value != ''){
		let options = {
			icon: 'success',
			title: 'Copied '+value,
			showConfirmButton: false,
			timer: 1500
		};

		if(document.fullscreenElement != null){
			options['target']	= document.fullscreenElement;
		}

		Swal.fire(options);

		navigator.clipboard.writeText(value);
	}
}

function copyWarningCondition(target){
	//copy the row
	let newNode = FormFunctions.cloneNode(target.closest('.warning_conditions'));

	target.closest('.conditions_wrapper').insertAdjacentElement('beforeEnd', newNode);

	//add the active class
	target.classList.add('active');

	//store the value
	target.closest('.warning_conditions').querySelector('.combinator').value = target.value;

	fixWarningConditionNumbering(target.closest('.conditions_wrapper'));
}

function removeWarningCondition(target){
	let condition	= target.closest('.warning_conditions');

	// Remove the active class of the previous conditions
	if(condition.nextElementSibling == null){
		condition.previousElementSibling.querySelector('.active').classList.remove('active');

		//clear the value
		condition.previousElementSibling.querySelector('.combinator').value='';
	}

	// remove the condition
	condition.remove();

	fixWarningConditionNumbering(target.closest('.conditions_wrapper'));
}

//Catch click events
window.addEventListener("click", event => {
	let target = event.target;
	
	formWrapper				= target.closest('.sim-form-wrapper');
	formElementWrapper		= target.closest('.form_element_wrapper');
	
	if(formWrapper != null){
		modal = formWrapper.querySelector('.add_form_element_modal');
	}
	
	/* ELEMENT ACTIONS */
	
	//Show form edit controls
	if (target.name == 'showid'){
		showOrHideIds(target);
	}else if (target.name == 'showname'){
		showOrHideName(target);
	}else if(target.name == 'submit_form_element'){
		event.stopPropagation();
		addFormElement(target);
	}else if(target.name == 'submit_form_condition'){
		saveFormConditions(target);
	}else if(target.name == 'submit_form_setting'){
		saveFormSettings(target);
	}else if(target.name == 'submit_form_emails'){
		saveFormEmails(target);
	}else if(target.name == 'autoarchive'){
		let el = target.closest('.formsettings_wrapper').querySelector('.autoarchivelogic');
		if(target.value == 'true'){
			el.classList.remove('hidden');
		}else{
			el.classList.add('hidden');
		}
	}else if(target.name == 'save_in_meta'){
		target.closest('.sim-form.builder').querySelector('.recurring-submissions').classList.toggle('hidden');
	}else if(target.name == 'formfield[mandatory]' && target.checked){
		target.closest('div').querySelector('[name="formfield[recommended]"]').checked=true;
	}
	
	//request form values via AJAX
	if (target.classList.contains('edit_form_element')){
		requestEditElementData(target);
	}
	
	if (target.classList.contains('remove_form_element')){
		maybeRemoveElement(target);
	}

	//open the modal to add an element
	if (target.classList.contains('add_form_element') || target.name == 'createform'){
		showEmptyModal(target);
	}
	
	//actions on element type select
	if (target.closest('.elementtype') != null){
		showCondionalFields(target.dataset.value, target.closest('form'));
		//if label type is selected, wrap by default
		if(target.dataset.value == 'label'){
			target.closest('form').querySelector('[name="formfield[wrap]"]').checked	= true;
		}
	}
	
	/* ELEMENT CONDITION ACTIONS */
	//actions on condition equation select
	if (target.closest('.equation') != null){
		showOrHideConditionFields(target);
	}
	
	//add new conditions_rule
	if (target.classList.contains('and_rule') || target.classList.contains('or_rule')){
		addConditionRule(target);
	}
	
	//add new condition row
	if (target.classList.contains('add_condition')){
		addCondition(target);
	}
	
	//remove  condition row
	if (target.classList.contains('remove_condition')){
		removeConditionRule(target);
	}
	
	//show copy fields
	if (target.classList.contains('showcopyfields')){
		if(target.checked){
			target.closest('.copyfieldswrapper').querySelector('.copyfields').classList.remove('hidden');
		}else{
			target.closest('.copyfieldswrapper').querySelector('.copyfields').classList.add('hidden');
		}
	}
	
	if(target.classList.contains('emailtrigger')){
		let el = target.closest('.clone_div').querySelector('.conditionalfield-wrapper');
		if(target.value == 'fieldchanged'){
			el.classList.remove('hidden');
		}else{
			el.classList.add('hidden');
		}

		el = target.closest('.clone_div').querySelector('.conditionalfields-wrapper');
		if(target.value == 'fieldschanged'){
			el.classList.remove('hidden');
		}else{
			el.classList.add('hidden');
		}

		el = target.closest('.clone_div').querySelector('.submitted-type');
		if(target.value == 'submittedcond'){
			el.classList.remove('hidden');
		}else{
			el.classList.add('hidden');
		}
	}
	
	if(target.classList.contains('fromemail')){
		fromEmailClicked(target);
	}
	
	if(target.classList.contains('emailto')){
		toEmailClicked(target);
	}
	
	if(target.classList.contains('placeholderselect') || target.classList.contains('placeholders')){
		placeholderSelect(target);
	}

	//copy warning_conditions row
	if(target.matches('.warn_cond')){
		copyWarningCondition(target);
	}

	if(target.matches('.remove_warn_cond')){
		removeWarningCondition(target)
	}

	if(target.matches('.builder-permissions-rights-form')){
		target.closest('div').querySelector('.permission-wrapper').classList.toggle('hidden');
	}
});

window.addEventListener('change', ev=>{
	let target	= ev.target;
	if(target.matches('.meta_key')){
		//if this option has a keys data value
		let metaIndexes	= target.list.querySelector(`[value='${target.value}' i]`);
		if(metaIndexes != null && metaIndexes.dataset.keys != undefined){
			parent	= target.closest('.warning_conditions').querySelector('.index_wrapper');
			//show the data key selector
			parent.classList.remove('hidden');
			
			//remove all options and add new ones
			let datalist	= parent.querySelector('.meta_key_index_list');
			for(let i=1; i<datalist.options.length; i++) {
				datalist.options[i].remove();
			}

			//add the new options
			metaIndexes.split(',').forEach(key=>{
				let opt			= document.createElement('option');
				opt.value 		= key;
				datalist.appendChild(opt);
			});
		}
	}else if(target.matches(".condition_row [name*='[property_value]']")){
		let selectedElement	= document.querySelector(`.form_element_wrapper[data-id="${target.value}"]`);
		if(selectedElement == null){
			return;
		}

		let selectedElementType	= selectedElement.dataset.type;

		// if select element is of number type
		if(selectedElementType == 'number'){
			target.closest('.condition_form').querySelectorAll('.addition.hidden').forEach(el=>el.classList.remove('hidden'));
		}else{
			target.closest('.condition_form').querySelectorAll('.addition:not(.hidden)').forEach(el=>el.classList.add('hidden'));
		}
		
		// if select element is of date type
		if(selectedElementType == 'date'){
			target.closest('.condition_form').querySelectorAll('.addition .days.hidden').forEach(el=>el.classList.remove('hidden'));
		}else{
			target.closest('.condition_form').querySelectorAll('.addition .days:not(.hidden)').forEach(el=>el.classList.add('hidden'));
		}
	}else if(target.matches("[name*='[submittedtrigger][equation]']")){
		let parent	= target.closest('.submitted-trigger-type');
		let static	= parent.querySelector('.staticvalue');
		let dynamic	= parent.querySelector('div.dynamicvalue');

		if(dynamic != null){
		
			if(target.value == '==' || target.value == '!=' || target.value == '>' || target.value == '<'){
				static.classList.remove('hidden');
				dynamic.classList.add('hidden');
			}else if(target.value == 'checked' || target.value == '!checked'){
				dynamic.classList.add('hidden');
				static.classList.add('hidden');
			}else{
				dynamic.classList.remove('hidden');
				static.classList.add('hidden');
			}
		}
	}else if(target.name != undefined && target.name.includes('[equation')){
		let warningsConditions	= target.closest('.warning_conditions');

		if(warningsConditions != null){
			if( target.value == 'submitted'){
				warningsConditions.querySelector(`[name*='[conditional_value]']`).classList.add('hidden');
			}else{
				warningsConditions.querySelector(`[name*='[conditional_value]']`).classList.remove('hidden');
			}
		}
	}
});

function fixWarningConditionNumbering(parent){
	var warningConditions	= parent.querySelectorAll('.warning_conditions');
	var i = 0;
	//loop over all rules in the condition
	warningConditions.forEach(condition =>{
		condition.dataset.index = i;
		
		condition.querySelectorAll('.warning_condition').forEach(function(el){
			//fix numbering
			if(el.name != undefined){
				el.name	= el.name.replace(/([0-9])+/g,i);
			}

			if(el.id != undefined){
				el.id	= el.id.replace(/([0-9])+/g,i);
			}

			if(el.list != undefined){
				el.setAttribute('list', el.list.id.replace(/([0-9])+/g,i));
			}
		});
		
		i++;
	});
}

