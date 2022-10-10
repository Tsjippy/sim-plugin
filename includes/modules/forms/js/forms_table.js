async function hideColumn(cell){
	// Hide the column
	var table		= cell.closest('table');
	var tableRows	= table.rows;
	for (const element of tableRows) {
		element.cells[cell.cellIndex].classList.add('hidden')
	}

	//show the reset button

	cell.closest('.table-wrapper').querySelector('.reset-col-vis').classList.remove('hidden');

	// store as preference
	var formData	= new FormData();
	formData.append('formid', table.dataset.formid);
	formData.append('column_name', cell.id);
	
	await FormSubmit.fetchRestApi('forms/save_table_prefs', formData);
}

async function showHiddenColumns(target){
	//hiden the reset button
	target.closest('.table-wrapper').querySelector('.reset-col-vis').classList.add('hidden');

	// Show the columns again
	let table		= target.closest('.table-wrapper').querySelector('table');
	table.querySelectorAll('th.hidden, td.hidden').forEach(el=>el.classList.remove('hidden'));

	// store as preference
	let formData	= new FormData();
	formData.append('formid', table.dataset.formid);

	let response	= await FormSubmit.fetchRestApi('forms/delete_table_prefs', formData);

	if(response){
		Main.displayMessage(response);
		location.reload();
	}
}

async function saveColumnSettings(target){
	let response = await FormSubmit.submitForm(target, 'forms/save_column_settings');

	if(response){
		Main.displayMessage(response);
		location.reload();
	}
}

async function saveTableSettings(target){
	let response = await FormSubmit.submitForm(target, 'forms/save_table_settings');

	if(response){
		Main.displayMessage(response);
	}
}

async function askConfirmation(text){
	let result = await Swal.fire({
		title: 'Are you sure?',
		text: `Are you sure you want to ${text} this?`,
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: "#bd2919",
		cancelButtonColor: '#d33',
		confirmButtonText: `Yes, ${text} it!`
	});

	return result.isConfirmed;
}

async function removeSubmission(target){
	if(await askConfirmation('delete')){		
		let submissionId	= target.closest('tr').dataset.id;
		let table			= target.closest('table');

		let formData = new FormData();
		formData.append('submissionid', submissionId);
		
		//display loading gif
		Main.showLoader(target);

		let response	= await FormSubmit.fetchRestApi('forms/remove_submission', formData);

		if(response){
			table.querySelectorAll(`.table-row[data-id="${submissionId}"]`).forEach(
				row=>row.remove()
			);
		}
	}
}

async function archiveSubmission(target){	
	let table			= target.closest('table');
	let tableRow		= target.closest('tr');
	let submissionId	= tableRow.dataset.id;
	let showSwal		= true;
	let action			= target.value;
	let response;

	let formData 		= new FormData();
	formData.append('formid', table.dataset.formid);
	formData.append('submissionid', submissionId);
	formData.append('action', action);
	
	// Ask whether to archive one piece or the whole
	if(tableRow.dataset.subid != undefined){
		showSwal = false;
		
		response = await Swal.fire({
			title: `What do you want to ${action}?`,
			text: `Do you want to ${action} just this one or the whole request?`,
			icon: 'question',
			showDenyButton: true,
			showCancelButton: true,
			confirmButtonText: 'Just this one',
			denyButtonText: 'The whole request',
			confirmButtonColor: "#bd2919",
		});

		if (response.isConfirmed) {
			formData.append('subid', tableRow.dataset.subid);
		}
		
		// skip if denied
		if(response.isDismissed){
			return;
		}
	}
	
	if(showSwal){
		if(!await askConfirmation(action)){
			return;
		}
	}

	//display loading gif
	Main.showLoader(target);
	
	response	= await FormSubmit.fetchRestApi('forms/archive_submission', formData);

	if(response){
		const params = new Proxy(new URLSearchParams(window.location.search), {
			get: (searchParams, prop) => searchParams.get(prop),
		});

		// Create a custom event so others can listen to it.
		// Used by bookings uploads
		const event = new Event('submissionArchived');
		
		// Delete all
		if(formData.get('subid') == null){
			table.querySelectorAll(`[data-id="${submissionId}"]`).forEach(row=>{
				row.dispatchEvent(event);

				// just change the button name
				if(params.archived == 'true'){
					let loader = row.querySelector(`.loaderwrapper`);
					changeArchiveButton(loader, action);
				}else{
					row.remove();
				}
			});
		// Only delete subid
		}else{
			table.querySelectorAll(`.table-row[data-id="${submissionId}"][data-subid="${tableRow.dataset.subid}"]`).forEach(row=>{
				row.dispatchEvent(event);
				
				// just change the button name
				if(params.archived == 'true'){
					let loader = row.querySelector('.loaderwrapper');
					changeArchiveButton(loader, action);
				}else{
					row.remove();
				}
			}
			);
		}
	}
}

function changeArchiveButton(loader, action){
	let text;

	if(action == 'archive'){
		action 	= 'unarchive';
		text	= 'Unarchive';
	}else{
		action 	= 'archive';
		text	= 'Archive';
	}
	loader.outerHTML = `<button class="${action} button forms_table_action" name="${action}_action" value="${action}">${text}</button>`;
}

async function getInputHtml(target){
	let table			= target.closest('table');

	// First make sure we have processed all others
	document.querySelectorAll('td.active').forEach(td=>{
		if(td != target){
			processFormsTableInput(td);
		}
	});

	// There can only be one active cell per page
	target.classList.add('active');

	let formId			= table.dataset.formid;
    let submissionId	= target.closest('tr').dataset.id;
	let subId			= target.dataset.subid;
    let cellId			= target.dataset.id
	let oldText			= target.textContent;
    
    Main.showLoader(target.firstChild);
	
	target.dataset.oldtext	 	= oldText;

	let formData = new FormData();
    formData.append('formid', formId);
    formData.append('submissionid', submissionId);
	formData.append('subid', subId);
    formData.append('fieldname', cellId);

	let response	= await FormSubmit.fetchRestApi('forms/get_input_html', formData);

	if(response){
		target.innerHTML	 = response;

		addFormsTableInputEventListeners(target);

		target.querySelectorAll('.file_upload_wrap').forEach(el=>el.addEventListener('uploadfinished', uploadFinished));
	}else{
		target.innerHTML	= target.dataset.oldtext;
		target.classList.remove('active');
	}
}

function addFormsTableInputEventListeners(cell){
	let inputs		= cell.querySelectorAll('input,select,textarea');
		
	inputs.forEach((inputNode)=>{
		if(inputNode.type == 'select-one'){
			inputNode._niceselect = NiceSelect.bind(inputNode, {searchable: true});
		}
		
		if(inputNode.type != 'checkbox' || inputs.length == 1){

			if(inputNode.type == 'date'){
				inputNode.addEventListener("blur", processFormsTableInput);
			}
			
			inputNode.focus();
		}
	});
}

function uploadFinished(event){
	if(event.target.closest('td') != null){
		//remove as soon as we come here
		document.removeEventListener('uploadfinished', uploadFinished);
		processFormsTableInput(document.querySelector('[data-oldtext]'));
	}
}

//function to get the temp input value and save it over AJAX
var running = false;
async function processFormsTableInput(target){
	// target is an event
	if(target.target != undefined){
		target = target.target;
	}
	
	if(running == target || target.value == ''){
		return;
	}
	running = target;
	
	setTimeout(function(){ running = false;}, 500);	

	let table			= target.closest('table');
	let formId			= table.dataset.formid;
	let cell			= target.closest('td');
	cell.classList.remove('active');
    let cellId			= cell.dataset.id
	let value			= FormFunctions.getFieldValue(target, cell, false);
	let submissionId	= target.closest('tr').dataset.id;
	let subId			= cell.dataset.subid;

	//Only update when needed
	if (value != JSON.parse(cell.dataset.oldvalue)){
		Main.showLoader(cell.firstChild);
		
		// Submit new value and receive the filtered value back
		let formData = new FormData();
		formData.append('formid', formId);
		formData.append('submissionid', submissionId);
		if(subId != undefined){
			formData.append('subid', subId);
		}
		formData.append('fieldname', cellId);
		formData.append('newvalue', JSON.stringify(value));
		
		let response	= await FormSubmit.fetchRestApi('forms/edit_value', formData);
	
		if(response){
			let newValue = response.newvalue;
			
			if(typeof(newValue) == 'string'){
				newValue.replace('_', ' ');
			}

			//Replace the input element with its value
			if(newValue == "") newValue = "X";
	
			//Update all occurences of this field
			if(subId == null){
				let targets	= table.querySelectorAll(`tr[data-id="${submissionId}"] td[data-id="${cellId}"]`);
				targets.forEach(td=>{td.innerHTML = newValue;});
			}else{
				cell.innerHTML = newValue;
			}
	
			Main.displayMessage(response.message)
		}
	}else{
		cell.innerHTML = cell.dataset.oldtext;
	}

	addFormsTableInputEventListeners(target);

	if(target.dataset.oldtext != undefined){
		delete target.dataset.oldtext;
	}
}

document.addEventListener("click", event=>{
	let target = event.target;

	if(target.name == 'submit_column_setting'){
		saveColumnSettings(target);
	}else if(target.name == 'submit_table_setting'){
		saveTableSettings(target);
	}else if(target.name == 'form_settings[autoarchive]'){
		//show auto archive fields
		let el = target.closest('.table_rights_wrapper').querySelector('.autoarchivelogic');
		if(target.value == 'true'){
			el.classList.remove('hidden');
		}else{
			el.classList.add('hidden');
		}
	}

	//Actions
	if(target.matches('.delete.forms_table_action')){
		removeSubmission(target);
	}

	if(target.matches('.archive.forms_table_action, .unarchive.forms_table_action')){
		archiveSubmission(target);
	}

	if(target.matches('.print.forms_table_action')){
		window.location.href = window.location.href.split('?')[0]+"?print=true&table_id="+table.dataset.id+"&submission_id="+table_row.querySelector("[id='id' i]").textContent;
	}
	
	//Open settings modal
	if(target.classList.contains('edit_formshortcode_settings')){
		document.querySelector('.modal.form_shortcode_settings').classList.remove('hidden');
	}
	
	//Edit data
	let td = target.closest('td');
	if(td && !td.matches('.active')){
		if( target.matches('td.edit_forms_table')){
			event.stopPropagation();
			getInputHtml(target);
		}else if(td.matches('td.edit_forms_table') && target.tagName != 'INPUT' && target.tagName != 'A' && target.tagName != 'TEXTAREA' && !target.closest('.nice-select') ){
			event.stopPropagation();
			getInputHtml(target.closest('td'));
		}
	}
	
	//Hide column
	if(target.classList.contains('visibilityicon')){
		if(target.tagName == 'SPAN'){
			target = target.querySelector('img');
		}

		// Table itself
		if(target.parentNode.matches('th')){
			hideColumn(target.parentNode);
		// Table settings
		}else{
			if(target.classList.contains('visible')){
				target.classList.replace('visible','invisible');
				target.src	= target.src.replace('visible.png','invisible.png');
				target.closest('.column_setting_wrapper').querySelector('.visibilitytype').value = 'hide';
			}else{
				target.classList.replace('invisible','visible');
				target.src	= target.src.replace('invisible.png','visible.png');
				target.closest('.column_setting_wrapper').querySelector('.visibilitytype').value = 'show';
			}
		}
	}

	if(target.matches('.reset-col-vis')){
		showHiddenColumns(target);
	}

	// If we clicked somewhere and there is an active cell
	let activeCell	= document.querySelector('td.active');
	if(activeCell != null && td == null){
		processFormsTableInput(activeCell);
	}

	if(target.matches('.permissins-rights-form')){
		target.closest('div').querySelector('.permission-wrapper').classList.toggle('hidden');
	}
});

document.addEventListener("DOMContentLoaded", function() {
	console.log("Formstable.js loaded");
	
	if(typeof(Sortable) != 'undefined'){
		//Make the sortable_column_settings_rows div sortable
		let options = {
			handle: '.movecontrol',
			animation: 150,
		};

		document.querySelectorAll('.sortable_column_settings_rows').forEach(el=>{
			Sortable.create(el, options);
		});
	}
});

document.addEventListener('change', event=>{
	let target = event.target;

	if(target.id == 'sim-forms-selector'){
		document.querySelectorAll('.main_form_wrapper').forEach(el=>{
			el.classList.add('hidden');
		});

		document.getElementById(target.value).classList.remove('hidden');

		// position table
		SimTableFunctions.positionTable();
	}

	if(target.closest('td.active') != null && target.type != 'file' && target.type != 'date'){
		processFormsTableInput(target);
	}

});

//Add a keyboard listener
document.addEventListener("keyup", function(event){
	if (['Enter', 'NumpadEnter'].includes(event.key) && keysPressed.Shift == undefined) {

		let activeCell	= document.querySelector('td.active');
		if(activeCell != null){
			processFormsTableInput(activeCell);
		}
	}

	delete keysPressed[event.key];
});


// Keep track of which keys are pressed
let keysPressed = {};
document.addEventListener('keydown', (event) => {
   keysPressed[event.key] = true;
});