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
				formFunctions.removeDefaultSelect(el);
				
				if(el._niceselect != undefined){
					el._niceselect.clear();
					el._niceselect.update();
				}
			}
		});
		
		hide_conditionalfields(modal.querySelector('form'));
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

function addWarningConditionsForm(response){
	if(modal.querySelector('#warning_conditions_form') == null) return;
	try{
		modal.querySelector('#warning_conditions_form').innerHTML = response.warning_conditions;

		modal.querySelectorAll('#warning_conditions_form select').forEach(function(select){
			select._niceselect = NiceSelect.bind(select,{searchable: true});
		});
	}catch(err) {
		console.error(err);
	}
}

function addConditionsForm(response){
	//fill the element conditions tab
	modal.querySelector('.element_conditions_wrapper').innerHTML = response;

	// Add nice selects
	modal.querySelectorAll('.condition_select').forEach(function(select){
		select._niceselect = NiceSelect.bind(select,{searchable: true});
	});
}

// add new element
async function showEmptyModal(target){
	target.classList.add('clicked');

	var formid					= target.dataset.formid;
	if(formid == undefined){
		formid = target.closest('.form_element_wrapper').dataset.formid;
	}
	
	clearFormInputs();
	
	if(form_element_wrapper != null){
		modal.querySelector('[name="insertafter"]').value = form_element_wrapper.dataset.priority;
	}
	
	modal.querySelector('[name="submit_form_element"]').textContent = modal.querySelector('[name="submit_form_element"]').textContent.replace('Update','Add');
	
	modal.querySelector('.element_conditions_wrapper').innerHTML = '<img src="'+sim.loading_gif+'" style="display:block; margin:0px auto 0px auto;">';
	
	modal.classList.remove('hidden');

	var formdata = new FormData();
	formdata.append('elementid','-1');
	formdata.append('formid', formid);
	var response = await fetchRestApi('forms/request_form_conditions_html', formdata);

	addConditionsForm(response);
}

//edit existing element
async function requestEditElementData(target){
	target.classList.add('clicked');

	var elementid					= form_element_wrapper.dataset.id;
	var formid						= target.closest('.form_element_wrapper').dataset.formid;
	modal.querySelector('[name="element_id"]').value = form_element_wrapper.dataset.id;
	modal.originalhtml = target.outerHTML;
	var parent			= target.parentNode;
	showLoader(target);
	parent.querySelector('.loadergif').style.margin = '5px 19px 0px 19px';
	
	var formdata = new FormData();
	formdata.append('elementid',elementid);
	formdata.append('formid',formid);
	
	var response = await fetchRestApi('forms/request_form_element', formdata);

	if(response){
		//fill the form after we have clicked the edit button
		clearFormInputs();

		addConditionsForm(response.conditions_html);

		addWarningConditionsForm(response.warning_conditions);

		//parse data
		var original = response.original;
		var form = modal.querySelector('form');
		show_condional_fields(original['type'],form);
		//loop over all elements in the modal
		modal.querySelectorAll('.formbuilder').forEach(function(el){
			if(el.name != null){
				//get the elements name
				var name = el.name.replace('formfield[','').replace(']','');
				
				//Check if there is a value for this element
				if(original[name] != undefined && original[name] != ''){						
					if(el.type == 'checkbox'){
						if(original[name]==true){
							el.checked = true;
						}
						
						/* if(el.name == "formfield[multiple]"){
							multiple_changed(el);
							form.querySelector('select.formbuilder.array').classList.replace('hidden', 'hide');
						} */
					}else{
						//set the value
						el.value = original[name];
					}
					
					if(el.type == "select-one"){
						if(el.selectedOptions.length > 0){
							el.selectedOptions[0].defaultSelected = true;

							if(el._niceselect != 'undefined'){
								el._niceselect.update();
							}
						}
					}
					
					if(el.classList.contains('wp-editor-area')){
						tinyMCE.get(el.id).setContent(original[name]);
					}
				}
			}
		});
		
		modal.querySelector('[name="element_id"]').value = elementid;
		modal.querySelector('[name="submit_form_element"]').textContent = modal.querySelector('[name="submit_form_element"]').textContent.replace('Add','Update');		
		modal.classList.remove('hidden');
		
		//show edit button again
		var loader 			= document.querySelector(`.form_element_wrapper[data-id="${elementid}"][data-formid="${formid}"] .loadergif`);
		var template		= document.createElement('template');
		template.innerHTML	= modal.originalhtml;
		var node			= template.content.firstChild;
		
		loader.parentNode.replaceChild(node, loader);
	}
}

async function addFormElement(target){
	var form		= target.closest('form');
	var response	= await submitForm(target, 'forms/add_formfield');

	if(response){
		if(form.querySelector('[name="element_id"]').value == ''){
			//First clear any previous input
			clearFormInputs();
			
			//Add the new element to the form, first convert the received html to nodes
			var template		= document.createElement('template');
			template.innerHTML	= response.html;
			var node			= template.content.firstChild;
			
			//insert the node
			referenceNode = document.querySelector('.form_elements .clicked');
			referenceNode.classList.remove('clicked');
			referenceNode.closest('.form_element_wrapper').parentNode.insertBefore(node, referenceNode.closest('.form_element_wrapper').nextSibling);
			
			fixElementNumbering(form);
			
			//add resize listener
			resize_ob.observe(node);
		}else{
			//Runs after an element update
			document.querySelector('.form_elements .clicked').closest('.form_element_wrapper').outerHTML = response.html;
		}

		hide_modals();

		display_message(response.message);
	}
}

async function sendElementSize(el, widthpercentage){
	if(widthpercentage != el.dataset.widthpercentage && Math.abs(widthpercentage - el.dataset.widthpercentage)>5){
		el.dataset.widthpercentage = widthpercentage;
		
		//send new width over AJAX
		var remove_form_element_nonce = document.querySelector('[name="remove_form_element_nonce"]').value;
		var formdata = new FormData();
		formdata.append('formid',el.closest('.form_element_wrapper').dataset.formid);
		formdata.append('elementid',el.closest('.form_element_wrapper').dataset.id);
		formdata.append('new_width',widthpercentage);
		
		response = await fetchRestApi('forms/edit_formfield_width', formdata);

		if(response){
			hide_modals();

			display_message(response);
		}
	}
}

async function remove_element(target){
	var parent			= target.parentNode;
	var elementwrapper	= target.closest('.form_element_wrapper');
	var formid			= elementwrapper.dataset.formid;
	var elementindex 	= elementwrapper.dataset.id;
	var form			= target.closest('form');

	showLoader(target);
	var loader			= parent.querySelector('.loadergif');
	loader.style.paddingRight = '10px';
	loader.classList.remove('loadergif');

	var formdata = new FormData();
	formdata.append('formid',formid);

	formdata.append('elementindex',elementindex);
	
	response = await fetchRestApi('forms/remove_element', formdata);

	if(response){
		//remove the formelement row
		elementwrapper.remove();
		
		fixElementNumbering(form);

		display_message(response);
	}
}

 //Fires after element reorder
async function reorderformelements(event){
	if(reordering_busy == false){
		reordering_busy = true;

		var old_index	= parseInt(event.item.dataset.priority);

		fixElementNumbering(event.item.closest('form'));
		
		var difference = event.newIndex-event.oldIndex

		var formdata = new FormData();
		formdata.append('formid',event.item.dataset.formid);
		formdata.append('old_index', old_index);
		formdata.append('new_index',(old_index+difference));
		
		var response	= await fetchRestApi('forms/reorder_form_elements', formdata);

		if(response){
			reordering_busy = false;

			display_message(response);
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
	var response	= await submitForm(target, 'forms/save_element_conditions');

	if(response){
		hide_modals();

		display_message(response);
	}
}

async function saveFormSettings(target){
	var response	= await submitForm(target, 'forms/save_form_settings');

	if(response){
		target.closest('.submit_wrapper').querySelector('.loadergif').classList.add('hidden');

		display_message(response);
	}
}

async function saveFormEmails(target){
	var response	= await submitForm(target, 'forms/save_form_emails');

	if(response){
		target.closest('.submit_wrapper').querySelector('.loadergif').classList.add('hidden');

		display_message(response);
	}
}

//listen to element size changes
var doit;
const resize_ob = new ResizeObserver(function(entries) {
	var element = entries[0].target;
	if(element.parentNode != undefined){
		var width	= entries[0].contentRect.width
		var widthpercentage = Math.round(width/element.parentNode.offsetWidth*100);
		
		//Show percentage on screen
		var el	= element.querySelector('.widthpercentage');
		if(widthpercentage <99){
			el.textContent = widthpercentage+'%';
		}else if(el != null){
			el.textContent = '';
		}
		
		clearTimeout(doit);
		doit = setTimeout(sendElementSize, 500,element,widthpercentage);
	}
});



//show conditional fields based on on the element type
function show_condional_fields(type, form){
	hide_conditionalfields(form);
	
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
			//console.log(type);
	} 
}

function hide_conditionalfields(form){
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

function show_or_hide_controls(target){
	if(target.dataset.action == 'show'){
		formwrapper.querySelectorAll('.formfieldbutton').forEach(function(el){
			el.classList.remove('hidden');
			target.textContent	= 'Hide form edit controls';
			target.dataset.action	= 'hide';
		});
		
		formwrapper.querySelectorAll('.resizer').forEach(function(el){
			el.classList.add('show');
		});
		
		//hide submit button
		formwrapper.querySelector('[name="submit_form"]').classList.add('hidden');
		
	}else{
		formwrapper.querySelectorAll('.formfieldbutton').forEach(function(el){
			el.classList.add('hidden');
			target.textContent = 'Show form edit controls';
			target.dataset.action	= 'show';
		});
		
		formwrapper.querySelectorAll('.resizer').forEach(function(el){
			el.classList.remove('show');
		});
		
		//show submit button
		formwrapper.querySelector('[name="submit_form"]').classList.remove('hidden');
		}
}

function maybe_remove_element(target){
	if(typeof(Swal)=='undefined'){
		remove_element(target);
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
				remove_element(target);
			}
		})
	}
}

function show_or_hide_condition_fields(target){
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

function add_condition_rule(target){
	var row				= target.closest('.rule_row');
	var activebutton	= row.querySelector('.active');
		
	//there was alreay an next rule and we clicked on the button which was no active
	if(activebutton != null && !target.classList.contains('active')){		
		if(activebutton.textContent == 'AND'){
			var current		= 'AND';
			var opposite	= 'OR';
		}else{
			var current		= 'OR';
			var opposite	= 'AND';
		}
		
		Swal.fire({
			title: 'What do you want to do?',
			showDenyButton: true,
			showCancelButton: true,
			confirmButtonText: 'Change '+current+' to '+opposite,
			denyButtonText: 'Add a new rule',
		}).then((result) => {
			//swap and/or
			if (result.isConfirmed) {
				//make other button inactive
				activebutton.classList.remove('active');
				
				//make the button active
				target.classList.add('active');
				
				//store action 
				row.querySelector('.combinator').value = target.textContent;
			//add new rule after this one
			}else if (result.isDenied) {
				addrulerow(row);
			}
		});
	//add new rule at the end
	}else{
		addrulerow(row);
		
		//make the button active
		target.classList.add('active');
		
		//store action 
		row.querySelector('.combinator').value = target.textContent;
	}
}

function addrulerow(row){
	//Insert a new rule row
	var clone		= formFunctions.cloneNode(row);
	
	var cloneindex	= parseInt(row.dataset.rule_index) + 1;

	clone.dataset.rule_index = cloneindex;

	row.parentNode.insertBefore(clone, row.nextSibling);
	
	fix_rule_numbering(row.closest('.condition_row'));
}

function add_condition(target){
	var row = target.closest('.condition_row');
	
	if(target.classList.contains('opposite')){
		clone = formFunctions.cloneNode(row, false);
	}else{
		clone = formFunctions.cloneNode(row);
	}
	
	var cloneindex	= parseInt(row.dataset.condition_index) + 1;

	clone.dataset.condition_index = cloneindex;
	
	if(!target.classList.contains('opposite')){
		clone.querySelectorAll('.active').forEach(function(el){
			el.classList.remove('active');
		});
	}
	
	if(target.classList.contains('opposite')){
		//Set values to opposite
		clone.querySelectorAll('.element_condition').forEach(function(el){
			if(el.tagName == 'SELECT' && el.classList.contains('equation')){
				//remove all default selected
				formFunctions.removeDefaultSelect(el);
				
				//get the original value which was lost during cloning
				var original_select = row.querySelector('.equation');
				var selindex = original_select.selectedIndex;
				
				if(selindex != false){
					//if odd the select the next one
					if(selindex % 2){
						el.options[(selindex+1)].defaultSelected = true;
					}else{
						el.options[(selindex-1)].defaultSelected = true;
					}
				}
				
				//reflect changes in niceselect
				el._niceselect.update();
			}else if(el.type == 'radio' && el.classList.contains('element_condition') && (el.value == 'show' || el.value == 'hide')){
				if(el.checked == false){
					el.checked = true;
				}else{
					el.checked = false;
				}
			}
		});
	}
	
	
	//store radio values
	var radio_values = {};
	row.querySelectorAll('input[type="radio"]:checked').forEach(input=>{
		radio_values[input.value]	= input.value;
	});
		
	//insert in page
	row.parentNode.insertBefore(clone, row.nextSibling);
	
	if(!target.classList.contains('opposite')){
		//remove unnecessy rulerows works only after html insert
		var rule_count = clone.querySelectorAll('.rule_row').length;
		clone.querySelectorAll('.rule_row').forEach(function(rulerow){
			//only keep the last rule (as that one has the + button)
			if(rulerow.dataset.rule_index != rule_count-1){
				rulerow.remove();
			}else{
				fix_rule_numbering(rulerow.closest('.condition_row'));
			}
		});
	}
	
	//fix numbering
	fix_condition_numbering();
	
	//fix radio's as they get unselected due to same names on insert
	row.querySelectorAll('input[type="radio"]').forEach(input=>{
		if(radio_values[input.value] != undefined){
			input.checked = true;
		}
	});
	
	
	//hide button
	target.style.display = 'none';
}

function remove_condition_rule(target){
	var conditionrow = target.closest('.condition_row');
	
	//count rule rows in this condition row
	if(conditionrow.querySelectorAll('.rule_row').length > 1){
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
				var rule_row		= target.closest('.rule_row');
				
				//get the current row index
				var rule_row_index	= rule_row.dataset.rule_index;
				
				//Get previous row
				var prev_row		= conditionrow.querySelector('[data-rule_index="'+(rule_row_index-1)+'"]')
				
				if(prev_row != null){
					//remove the active class from row above
					prev_row.querySelectorAll('.active').forEach(el=>el.classList.remove('active'));
					
					//clear the hidden input
					prev_row.querySelector('.combinator').value	= '';
				}
				
				target.closest('.rule_row').remove();
				
				fix_rule_numbering(conditionrow);
			} else if (result.isDenied) {
				conditionrow.remove();
				fix_condition_numbering();
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
				conditionrow.remove();
				fix_condition_numbering();
			}
		});
	}
}

function fix_rule_numbering(condition_row){
	var rulerows	= condition_row.querySelectorAll('.element_conditions_wrapper .rule_row');
	var i = 0;
	
	//loop over all rules in the condition
	rulerows.forEach(rulerow =>{
		rulerow.dataset.rule_index = i;
		
		rulerow.querySelectorAll('.element_condition').forEach(function(el){
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

function fix_condition_numbering(){
	var conditionrows	= modal.querySelectorAll('.element_conditions_wrapper .condition_row');
	for (let i = 0; i < conditionrows.length; i++) {
		conditionrows[i].dataset.condition_index = i;
		
		conditionrows[i].querySelectorAll('.element_condition').forEach(function(el){
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

function focus_first(){
	modal.scrollTo(0,0);
	modal.querySelector('[name="add_form_element_form"] .nice-select').focus();
}

reordering_busy = false;

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
	document.querySelectorAll('.resizer').forEach(el=>{resize_ob.observe(el);});
});

//Catch click events
window.addEventListener("click", event => {
	var target = event.target;
	
	formwrapper				= target.closest('.sim_form.wrapper');
	form_element_wrapper	= target.closest('.form_element_wrapper');
	
	if(formwrapper != null){
		modal = formwrapper.querySelector('.add_form_element_modal');
	}
	
	/* ELEMENT ACTIONS */
	
	//Show form edit controls
	if (target.name == 'editform'){
		show_or_hide_controls(target);
	}
	
	//open the modal to add an element
	if (target.classList.contains('add_form_element') || target.name == 'createform'){
		showEmptyModal(target);
	}
	
	if(target.name == 'submit_form_element'){
		addFormElement(target);
	}

	if(target.name == 'submit_form_condition'){
		saveFormConditions(target);
	}

	if(target.name == 'submit_form_setting'){
		saveFormSettings(target);
	}

	if(target.name == 'submit_form_emails'){
		saveFormEmails(target);
	}
	
	//request form values via AJAX
	if (target.classList.contains('edit_form_element')){
		requestEditElementData(target);
	}
	
	if (target.classList.contains('remove_form_element')){
		maybe_remove_element(target);
	}
	
	//actions on element type select
	if (target.closest('.elementtype') != null){
		show_condional_fields(target.dataset.value, target.closest('form'));
		//if label type is selected, wrap by default
		if(target.dataset.value == 'label'){
			target.closest('form').querySelector('[name="formfield[wrap]"]').checked	= true;
		}
	}
	
	/* ELEMENT CONDITION ACTIONS */
	//actions on condition equation select
	if (target.closest('.equation') != null){
		show_or_hide_condition_fields(target);
	}
	
	//add new conditions_rule
	if (target.classList.contains('and_rule') || target.classList.contains('or_rule')){
		add_condition_rule(target);
	}
	
	//add new condition row
	if (target.classList.contains('add_condition')){
		add_condition(target);
	}
	
	//remove  condition row
	if (target.classList.contains('remove_condition')){
		remove_condition_rule(target);
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
		el = target.closest('.clone_div').querySelector('.emailfieldcondition');
		if(target.value == 'fieldchanged'){
			el.classList.remove('hidden');
		}else{
			el.classList.add('hidden');
		}
	}
	
	if(target.classList.contains('fromemail')){
		div1 = target.closest('.clone_div').querySelector('.emailfromfixed');
		div2 = target.closest('.clone_div').querySelector('.emailfromconditional');
		if(target.value == 'fixed'){
			div1.classList.remove('hidden');
			div2.classList.add('hidden');
		}else{
			div2.classList.remove('hidden');
			div1.classList.add('hidden');
		}
	}
	
	if(target.classList.contains('emailto')){
		div1 = target.closest('.clone_div').querySelector('.emailtofixed');
		div2 = target.closest('.clone_div').querySelector('.emailtoconditional');
		if(target.value == 'fixed'){
			div1.classList.remove('hidden');
			div2.classList.add('hidden');
		}else{
			div2.classList.remove('hidden');
			div1.classList.add('hidden');
		}
	}
	
	if(target.classList.contains('placeholderselect') || target.classList.contains('placeholders')){
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
	
	//show auto archive fields
	if(target.name == 'settings[autoarchive]'){
		el = target.closest('.formsettings_wrapper').querySelector('.autoarchivelogic');
		if(target.value == 'true'){
			el.classList.remove('hidden');
		}else{
			el.classList.add('hidden');
		}
	}

	if(target.name == 'settings[save_in_meta]'){
		target.closest('.sim_form.builder').querySelector('.submit_others_form_wrapper').classList.toggle('hidden');
	}

	if(target.name == 'formfield[mandatory]' && target.checked){
		target.closest('div').querySelector('[name="formfield[recommended]"]').checked=true;
	}

	//copy warning_conditions row
	if(target.matches('.warn_cond')){
		//copy the row
		var new_node = formFunctions.cloneNode(target.closest('.warning_conditions'));

		document.getElementById('conditions_wrapper').insertAdjacentElement('beforeEnd', new_node);

		//add the active class
		target.classList.add('active');

		//store the value
		target.closest('.warning_conditions').querySelector('.combinator').value = target.value;

		fix_warning_condition_numbering();
	}

	if(target.matches('.remove_warn_cond')){
		target.closest('.warning_conditions').remove();
		fix_warning_condition_numbering();
	}
	
});

window.addEventListener('change', ev=>{
	if(ev.target.matches('.meta_key')){
		//if this option has a keys data value
		var meta_indexes	= ev.target.list.querySelector("[value='"+ev.target.value+"' i]").dataset.keys;
		if(meta_indexes != null){
			parent	= ev.target.closest('.warning_conditions').querySelector('.index_wrapper');
			//show the data key selector
			parent.classList.remove('hidden');
			
			console.log(parent);
			//remove all options and add new ones
			var datalist	= parent.querySelector('.meta_key_index_list');
			console.log(datalist);
			for(var i=1; i<datalist.options.length; i++) {
				datalist.options[i].remove();
			}

			//add the new options
			meta_indexes.split(',').forEach(key=>{
				var opt			= document.createElement('option');
				opt.value 		= key;
				datalist.appendChild(opt);
			});
		}
	}
});

function fix_warning_condition_numbering(){
	var warning_conditions	= document.querySelectorAll('#conditions_wrapper .warning_conditions');
	var i = 0;
	//loop over all rules in the condition
	warning_conditions.forEach(condition =>{
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