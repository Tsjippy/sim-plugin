var reorderingBusy, formWrapper, formElementWrapper, modal;

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
				var editor = tinyMCE.get(el.id);
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

	var formId					= target.dataset.formid;
	if(formId == undefined){
		if(target.closest('.form_element_wrapper') != null){
			formId = target.closest('.form_element_wrapper').dataset.formid;
		}else{
			formId = document.querySelector('input[type=hidden][name="formid"').value;
		}
	}
	
	clearFormInputs();
	
	if(formElementWrapper != null){
		modal.querySelector('[name="insertafter"]').value = formElementWrapper.dataset.priority;
	}
	
	modal.querySelector('[name="submit_form_element"]').textContent = modal.querySelector('[name="submit_form_element"]').textContent.replace('Update','Add');
	
	modal.querySelector('.element_conditions_wrapper').innerHTML = '<img src="'+sim.loadingGif+'" style="display:block; margin:0px auto 0px auto;">';
	
	modal.classList.remove('hidden');

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

	var elementId		= formElementWrapper.dataset.id;
	var formId			= target.closest('.form_element_wrapper').dataset.formid;
	modal.querySelector('[name="element_id"]').value = formElementWrapper.dataset.id;
	modal.originalhtml	= target.outerHTML;

	var editButton		= target.outerHTML;

	var loader			= Main.showLoader(target);

	loader.querySelector('.loadergif').style.margin = '5px 19px 0px 19px';
	
	var formData = new FormData();
	formData.append('elementid', elementId);
	formData.append('formid', formId);
	
	var response = await FormSubmit.fetchRestApi('forms/request_form_element', formData);

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
		
		modal.classList.remove('hidden');
		
		//show edit button again
		loader.outerHTML	= editButton;
	}
}

async function addFormElement(target){
	console.log(target);
	var form		= target.closest('form');
	var response	= await FormSubmit.submitForm(target, 'forms/add_form_element');

	if(response){
		if(form.querySelector('[name="element_id"]').value == ''){
			//First clear any previous input
			clearFormInputs();

			var referenceNode		= document.querySelector('.form_elements .clicked');
			if(referenceNode == null){
				window.location.href = window.location.href+'&formbuilder=true';
			}
			referenceNode.classList.remove('clicked');
			referenceNode.closest('.form_element_wrapper').insertAdjacentHTML('afterEnd', response.html)
			
			fixElementNumbering(form);
			
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
		var formData = new FormData();
		formData.append('formid', el.closest('.form_element_wrapper').dataset.formid);
		formData.append('elementid', el.closest('.form_element_wrapper').dataset.id);
		formData.append('new_width', widthPercentage);
		
		var response = await FormSubmit.fetchRestApi('forms/edit_formfield_width', formData);

		if(response){
			Main.hideModals();

			Main.displayMessage(response);
		}
	}
}

async function removeElement(target){
	var parent			= target.parentNode;
	var elementWrapper	= target.closest('.form_element_wrapper');
	var formId			= elementWrapper.dataset.formid;
	var elementIndex 	= elementWrapper.dataset.id;
	var form			= target.closest('form');

	Main.showLoader(target);
	var loader			= parent.querySelector('.loadergif');
	loader.style.paddingRight = '10px';
	loader.classList.remove('loadergif');

	var formData = new FormData();
	formData.append('formid', formId);

	formData.append('elementindex', elementIndex);
	
	var response = await FormSubmit.fetchRestApi('forms/remove_element', formData);

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

		var oldIndex	= parseInt(event.item.dataset.priority);

		fixElementNumbering(event.item.closest('form'));
		
		var difference = event.newIndex-event.oldIndex

		var formData = new FormData();
		formData.append('formid', event.item.dataset.formid);
		formData.append('old_index', oldIndex);
		formData.append('new_index',(oldIndex + difference));
		
		var response	= await FormSubmit.fetchRestApi('forms/reorder_form_elements', formData);

		if(response){
			reorderingBusy = false;

			Main.displayMessage(response);
		}
	}else{
		Swal.fire({
			icon: 'error',
			title: 'ordering already in progress, please wait',
			confirmButtonColor: "#bd2919",
		});
	}
}

async function saveFormConditions(target){
	var response	= await FormSubmit.submitForm(target, 'forms/save_element_conditions');

	if(response){
		Main.hideModals();

		Main.displayMessage(response);
	}
}

async function saveFormSettings(target){
	var response	= await FormSubmit.submitForm(target, 'forms/save_form_settings');

	if(response){
		target.closest('.submit_wrapper').querySelector('.loadergif').classList.add('hidden');

		Main.displayMessage(response);
	}
}

async function saveFormEmails(target){
	var response	= await FormSubmit.submitForm(target, 'forms/save_form_emails');

	if(response){
		target.closest('.submit_wrapper').querySelector('.loadergif').classList.add('hidden');

		Main.displayMessage(response);
	}
}

//listen to element size changes
var doit;
const resizeOb = new ResizeObserver(function(entries) {
	var element = entries[0].target;
	if(element.parentNode != undefined){
		var width	= entries[0].contentRect.width
		var widthPercentage = Math.round(width/element.parentNode.offsetWidth * 100);
		
		//Show percentage on screen
		var el	= element.querySelector('.widthpercentage');
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
	
	switch(type) {
		case 'button':
		case 'formstep':
		case 'label':
			form.querySelector('[name="labeltext"]').classList.replace('hidden', 'hide');
			form.querySelector('[name="labeltext"] .elementtype').textContent = type;
			
			form.querySelectorAll('.labelhide').forEach(function(el){
				el.classList.replace('hide','hidden');
			});
			
			break;
		case 'info':
			form.querySelector('[name="infotext"]').classList.replace('hidden', 'hide');
			form.querySelector('[name="infotext"] .type').textContent = 'infobox';
			break;
		case 'radio':
		case 'select':
		case 'checkbox':
		case 'datalist':
			//show manual options field
			form.querySelector('[name="valuelist"]').classList.replace('hidden', 'hide');
			
			//show option array select
			form.querySelector('[name="select_options"]').classList.replace('hidden', 'hide');

			//show selected option select
			form.querySelector('[name="defaults"]').classList.replace('hidden', 'hide');
			
			if(type != 'select'){
				form.querySelector('[name="multiple"]').classList.replace('hide','hidden');
			}
			
			break;
		case 'p':
			form.querySelectorAll('[name="labeltext"],[name="wrap"],[name="multiple"],[name="defaults"],[name="elementoptions"]').forEach(div=>div.classList.add('hidden'))
			form.querySelector('[name="infotext"]').classList.replace('hidden', 'hide');
			form.querySelector('[name="infotext"] .type').textContent = 'Paragraph';
			break;
		case 'php':
			form.querySelectorAll('[name="elementname"],[name="infotext"],[name="labeltext"],[name="wrap"],[name="multiple"],[name="defaults"],[name="elementoptions"]').forEach(div=>div.classList.add('hidden'))
			form.querySelector('[name="functionname"]').classList.replace('hidden', 'hide');
			break;
		case 'file':
		case 'image':
			form.querySelectorAll('[name="functionname"],[name="infotext"],[name="labeltext"],[name="wrap"],[name="defaults"]').forEach(div=>div.classList.add('hidden'))
			form.querySelector('[name="upload-options"]').classList.replace('hidden', 'hide');

			form.querySelectorAll('.filetype').forEach(el=>{el.textContent = type;});
			break;
		default:
			break;
	} 
}

function hideConditionalfields(form){
	//hide all fields that should be hidden
	form.querySelectorAll('.hide').forEach(function(el){
		el.classList.replace('hide', 'hidden');
	});
	
	//show the default value selector
	//form.querySelectorAll('.nice-select.formbuilder.flat').forEach(el=>{el.classList.replace('hidden', 'hide')});
	//form.querySelector('select.formbuilder.flat').classList.replace('hidden', 'hide');
	
	//show the multiple
	form.querySelector('[name="multiple"]').classList.replace('hidden', 'hide');

	form.querySelectorAll('.labelhide').forEach(function(el){
		el.classList.replace('hidden','hide');
	});
}

function showOrHideControls(target){
	if(target.dataset.action == 'show'){
		formWrapper.querySelectorAll('.formfieldbutton').forEach(function(el){
			el.classList.remove('hidden');
			target.textContent		= 'Hide form edit controls';
			target.dataset.action	= 'hide';
		});
		
		formWrapper.querySelectorAll('.resizer').forEach(function(el){
			el.classList.add('show');
		});
		
		//hide submit button
		formWrapper.querySelector('[name="submit_form"]').classList.add('hidden');
		
	}else{
		formWrapper.querySelectorAll('.formfieldbutton').forEach(function(el){
			el.classList.add('hidden');
			target.textContent 		= 'Show form edit controls';
			target.dataset.action	= 'show';
		});
		
		formWrapper.querySelectorAll('.resizer').forEach(function(el){
			el.classList.remove('show');
		});
		
		//show submit button
		formWrapper.querySelector('[name="submit_form"]').classList.remove('hidden');
	}
}

function maybeRemoveElement(target){
	if(typeof(Swal)=='undefined'){
		removeElement(target);
	}else{
		Swal.fire({
			title: 'Are you sure?',
			text: "This will remove this element",
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#3085d6',
			cancelButtonColor: '#d33',
			confirmButtonText: 'Yes, delete it!'
		}).then((result) => {
			if (result.isConfirmed) {
				removeElement(target);
			}
		})
	}
}

function showOrHideConditionFields(target){
	//showdefault conditional field
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
			target.dataset.value.includes('checked')
		)){
		target.closest('.rule_row').querySelector('[name*="conditional_value"]').classList.add('hidden');
	}
}

function addConditionRule(target){
	var row				= target.closest('.rule_row');
	var activeButton	= row.querySelector('.active');
		
	//there was alreay an next rule and we clicked on the button which was no active
	if(activeButton != null && !target.classList.contains('active')){	
		var current		= 'OR';
		var opposite	= 'AND';	
		if(activeButton.textContent == 'AND'){
			current		= 'AND';
			opposite	= 'OR';
		}
		
		Swal.fire({
			title: 'What do you want to do?',
			showDenyButton: true,
			showCancelButton: true,
			confirmButtonText: `Change ${current} to ${opposite}`,
			denyButtonText: 'Add a new rule',
		}).then((result) => {
			//swap and/or
			if (result.isConfirmed) {
				//make other button inactive
				activeButton.classList.remove('active');
				
				//make the button active
				target.classList.add('active');
				
				//store action 
				row.querySelector('.combinator').value = target.textContent;
			//add new rule after this one
			}else if (result.isDenied) {
				addRuleRow(row);
			}
		});
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
	var clone		= FormFunctions.cloneNode(row);
	
	var cloneIndex	= parseInt(row.dataset.rule_index) + 1;

	clone.dataset.rule_index = cloneIndex;

	row.parentNode.insertBefore(clone, row.nextSibling);
	
	fixRuleNumbering(row.closest('.condition_row'));
}

function addOppositeCondition(clone){
	//Set values to opposite
	clone.querySelectorAll('.element_condition').forEach(function(el){
		if(el.tagName == 'SELECT' && el.classList.contains('equation')){
			//remove all default selected
			FormFunctions.removeDefaultSelect(el);
			
			//get the original value which was lost during cloning
			var originalSelect 	= row.querySelector('.equation');
			var selIndex		= originalSelect.selectedIndex;
			
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
	var clone;
	var row = target.closest('.condition_row');
	
	if(target.classList.contains('opposite')){
		clone = FormFunctions.cloneNode(row, false);
		clone = addOppositeCondition(clone);
	}else{
		clone = FormFunctions.cloneNode(row);
	}
	
	var cloneIndex	= parseInt(row.dataset.condition_index) + 1;

	clone.dataset.condition_index = cloneIndex;
	
	if(!target.classList.contains('opposite')){
		clone.querySelectorAll('.active').forEach(function(el){
			el.classList.remove('active');
		});
	}	
	
	//store radio values
	var radioValues = {};
	row.querySelectorAll('input[type="radio"]:checked').forEach(input=>{
		radioValues[input.value]	= input.value;
	});
		
	//insert in page
	row.parentNode.insertBefore(clone, row.nextSibling);
	
	if(!target.classList.contains('opposite')){
		//remove unnecessy rulerows works only after html insert
		var ruleCount = clone.querySelectorAll('.rule_row').length;
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
	var conditionRow = target.closest('.condition_row');
	
	//count rule rows in this condition row
	if(conditionRow.querySelectorAll('.rule_row').length > 1){
		Swal.fire({
			title: 'What do you want to remove?',
			showDenyButton: true,
			showCancelButton: true,
			confirmButtonText: `One condition rule`,
			denyButtonText: `The whole condition`,
			confirmButtonColor: "#bd2919",
		}).then((result) => {
			//remove a rule rowe
			if (result.isConfirmed) {
				//get the current row
				var ruleRow		= target.closest('.rule_row');
				
				//get the current row index
				var ruleRowIndex	= ruleRow.dataset.rule_index;
				
				//Get previous row
				var prevRow		= conditionrow.querySelector('[data-rule_index="'+(ruleRowIndex - 1)+'"]')
				
				if(prevRow != null){
					//remove the active class from row above
					prevRow.querySelectorAll('.active').forEach(el=>el.classList.remove('active'));
					
					//clear the hidden input
					prevRow.querySelector('.combinator').value	= '';
				}
				
				target.closest('.rule_row').remove();
				
				fixRuleNumbering(conditionRow);
			} else if (result.isDenied) {
				conditionrow.remove();
				fixConditionNumbering();
			}
		});
	}else{
		Swal.fire({
			title: 'Are you sure?',
			text: "This will remove this condition",
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: "#bd2919",
			cancelButtonColor: '#d33',
			confirmButtonText: 'Yes, delete it!'
		}).then((result) => {
			if (result.isConfirmed) {
				conditionRow.remove();
				fixConditionNumbering();
			}
		});
	}
}

function fixRuleNumbering(conditionRow){
	var ruleRows	= conditionRow.querySelectorAll('.element_conditions_wrapper .rule_row');
	var i = 0;
	
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
	var conditionRows	= modal.querySelectorAll('.element_conditions_wrapper .condition_row');
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
	modal.querySelector('[name="add_form_element_form"] .nice-select').focus();
}

reorderingBusy = false;
document.addEventListener("DOMContentLoaded",function() {
	console.log("Formbuilder.js loaded");

	//Make the form_elements div sortable
	var options = {
		handle: '.movecontrol',
		animation: 150,
		onEnd: reorderformelements
	};
	
	document.querySelectorAll('.form_elements').forEach(el=>{Sortable.create(el, options);});
	
	//Listen to resize events on form_element_wrappers
	document.querySelectorAll('.resizer').forEach(el=>{resizeOb.observe(el);});
});

function fromEmailClicked(target){
	var div1 = target.closest('.clone_div').querySelector('.emailfromfixed');
	var div2 = target.closest('.clone_div').querySelector('.emailfromconditional');

	if(target.value == 'fixed'){
		div1.classList.remove('hidden');
		div2.classList.add('hidden');
	}else{
		div2.classList.remove('hidden');
		div1.classList.add('hidden');
	}
}

function toEmailClicked(target){
	var div1 = target.closest('.clone_div').querySelector('.emailtofixed');
	var div2 = target.closest('.clone_div').querySelector('.emailtoconditional');

	if(target.value == 'fixed'){
		div1.classList.remove('hidden');
		div2.classList.add('hidden');
	}else{
		div2.classList.remove('hidden');
		div1.classList.add('hidden');
	}
}

function placeholderSelect(target){
	var value = '';
	if(target.classList.contains('placeholders')){
		value = target.textContent;
	}else if(target.value != ''){
		value = target.value;
		target.selectedIndex = '0';
	}
	
	if(value != ''){
		Swal.fire({
			icon: 'success',
			title: 'Copied '+value,
			showConfirmButton: false,
			timer: 1500
		})
		navigator.clipboard.writeText(value);
	}
}

function copyWarningCondition(target){
	//copy the row
	var newNode = FormFunctions.cloneNode(target.closest('.warning_conditions'));

	target.closest('.conditions_wrapper').insertAdjacentElement('beforeEnd', newNode);

	//add the active class
	target.classList.add('active');

	//store the value
	target.closest('.warning_conditions').querySelector('.combinator').value = target.value;

	fixWarningConditionNumbering(target.closest('.conditions_wrapper'));
}

function removeWarningCondition(target){
	var condition	= target.closest('.warning_conditions');

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
	var target = event.target;
	
	formWrapper				= target.closest('.sim_form.wrapper');
	formElementWrapper		= target.closest('.form_element_wrapper');
	
	if(formWrapper != null){
		modal = formWrapper.querySelector('.add_form_element_modal');
	}
	
	/* ELEMENT ACTIONS */
	
	//Show form edit controls
	if (target.name == 'editform'){
		showOrHideControls(target);
	}else if(target.name == 'submit_form_element'){
		event.stopPropagation();
		addFormElement(target);
	}else if(target.name == 'submit_form_condition'){
		saveFormConditions(target);
	}else if(target.name == 'submit_form_setting'){
		saveFormSettings(target);
	}else if(target.name == 'submit_form_emails'){
		saveFormEmails(target);
	}else if(target.name == 'settings[autoarchive]'){
		let el = target.closest('.formsettings_wrapper').querySelector('.autoarchivelogic');
		if(target.value == 'true'){
			el.classList.remove('hidden');
		}else{
			el.classList.add('hidden');
		}
	}else if(target.name == 'settings[save_in_meta]'){
		target.closest('.sim_form.builder').querySelector('.submit_others_form_wrapper').classList.toggle('hidden');
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
		let el = target.closest('.clone_div').querySelector('.emailfieldcondition');
		if(target.value == 'fieldchanged'){
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
	
});

window.addEventListener('change', ev=>{
	if(ev.target.matches('.meta_key')){
		//if this option has a keys data value
		var metaIndexes	= ev.target.list.querySelector("[value='"+ev.target.value+"' i]").dataset.keys;
		if(metaIndexes != null){
			parent	= ev.target.closest('.warning_conditions').querySelector('.index_wrapper');
			//show the data key selector
			parent.classList.remove('hidden');
			
			//remove all options and add new ones
			var datalist	= parent.querySelector('.meta_key_index_list');
			for(var i=1; i<datalist.options.length; i++) {
				datalist.options[i].remove();
			}

			//add the new options
			metaIndexes.split(',').forEach(key=>{
				var opt			= document.createElement('option');
				opt.value 		= key;
				datalist.appendChild(opt);
			});
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