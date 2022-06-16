async function hideColumn(cell){
	// Hide the column
	var table		= cell.closest('table');
	var tableRows	= table.rows;
	for (let i = 0; i < tableRows.length; i++) {
		tableRows[i].cells[cell.cellIndex].classList.add('hidden')
	}

	//show the reset button

	cell.closest('.form-table-wrapper').querySelector('.reset-col-vis').classList.remove('hidden');

	// store as preference
	var formData	= new FormData();
	formData.append('formid', table.dataset.formid);
	formData.append('column_name', cell.id);
	
	var response	= await FormSubmit.fetchRestApi('forms/save_table_prefs', formData);
}

async function showHiddenColumns(target){
	//hiden the reset button
	target.closest('.form-table-wrapper').querySelector('.reset-col-vis').classList.add('hidden');

	// Show the columns again
	var table		= target.closest('.form-table-wrapper').querySelector('table');
	table.querySelectorAll('th.hidden, td.hidden').forEach(el=>el.classList.remove('hidden'));

	// store as preference
	var formData	= new FormData();
	formData.append('formid', table.dataset.formid);

	var response	= await FormSubmit.fetchRestApi('forms/delete_table_prefs', formData);

	if(response){
		Main.displayMessage(response);
		location.reload();
	}
}

async function saveColumnSettings(target){
	var response = await FormSubmit.submitForm(target, 'forms/save_column_settings');

	if(response){
		Main.displayMessage(response);
		location.reload();
	}
}

async function saveTableSettings(target){
	var response = await FormSubmit.submitForm(target, 'forms/save_table_settings');

	if(response){
		Main.displayMessage(response);
	}
}

async function askConfirmation(text){
	var result = await Swal.fire({
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
	if(askConfirmation('delete')){		
		var submissionId	= target.closest('tr').dataset.id;
		var table			= target.closest('table');

		var formData = new FormData();
		formData.append('submissionid', submissionId);
		
		//display loading gif
		Main.showLoader(target);

		var response	= await FormSubmit.fetchRestApi('forms/remove_submission', formData);

		if(response){
			table.querySelectorAll(`.table-row[data-id="${submissionId}"]`).forEach(
				row=>row.remove()
			);
		}
	}
};

async function archiveSubmission(target){	
	var table			= target.closest('table');
	var tableRow		= target.closest('tr');
	var submissionId	= tableRow.dataset.id;
	var showSwal		= true;
	var action			= target.value;

	var formData 		= new FormData();
	formData.append('formid', table.dataset.formid);
	formData.append('submissionid', submissionId);
	formData.append('action', action);
	
	// Ask whether to archive one piece or the whole
	if(tableRow.dataset.subid != undefined){
		showSwal = false;
		
		var response = await Swal.fire({
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
		var confirmed = askConfirmation(action);

		if(!confirmed){
			return;
		}
	}

	//display loading gif
	Main.showLoader(target);
	
	var response	= await FormSubmit.fetchRestApi('forms/archive_submission', formData);

	if(response){
		const params = new Proxy(new URLSearchParams(window.location.search), {
			get: (searchParams, prop) => searchParams.get(prop),
		});
		
		// Delete all
		if(formData.get('subid') == null){
			table.querySelectorAll(`.table-row[data-id="${submissionId}"]`).forEach(row=>{
				// just change the button name
				if(params.archived == 'true'){
					var loader = row.querySelector('.loaderwrapper, .'+action);
					changeArchiveButton(loader, action);
				}else{
					row.remove();
				}
			});
		// Only delete subid
		}else{
			table.querySelectorAll(`.table-row[data-id="${submissionId}"][data-subid="${tableRow.dataset.subid}"]`).forEach(row=>{
				// just change the button name
				if(params.archived == 'true'){
					var loader = row.querySelector('.loaderwrapper');
					changeArchiveButton(loader, action);
				}else{
					row.remove();
				}
			}
			);
		}
	}
};

function changeArchiveButton(loader, action){
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
	var table			= target.closest('table');
	var formId			= table.dataset.formid;
    var submissionId	= target.closest('tr').dataset.id;
	var subId			= target.dataset.subid;
    var cellId			= target.dataset.id
	oldText				= target.textContent;
    
    Main.showLoader(target.firstChild);
	
	target.dataset.oldtext	 	= oldText;

	var formData = new FormData();
    formData.append('formid', formId);
    formData.append('submissionid', submissionId);
	formData.append('subid', subId);
    formData.append('fieldname', cellId);

	var response	= await FormSubmit.fetchRestApi('forms/get_input_html', formData);

	if(response){
		target.innerHTML	 = response;

		addFormsTableInputEventListeners(target);

		//add a listener for clicks outside the cell
		document.addEventListener('click', outsideFormsTableClicked);
	}
}

function addFormsTableInputEventListeners(cell){
	var inputs		= cell.querySelectorAll('input,select,textarea');

	// get old value
	var oldValue	= JSON.parse(cell.dataset.oldvalue);

	// make it an array if not an array
	if(!Array.isArray(oldValue)){
		oldValue = [oldValue];
	}
		
	inputs.forEach((inputNode, index)=>{
		var val	= oldValue[index];

		if(inputNode.type == 'checkbox' || inputNode.type == 'radio'){
			if(inputNode.value == val.trim()){
				inputNode.checked = true;
			}
		}else if(inputNode.type == 'select'){
			inputNode.querySelector('option[value="'+val+'"]').selected = true;
		}else{
			inputNode.value	= val;
		}
		
		if(inputNode.type == 'select-one'){
			inputNode._niceselect = NiceSelect.bind(inputNode,{searchable: true});
		}
		
		//Add a keyboard
		inputNode.addEventListener("keyup", function(event){
			if (event.keyCode === 13) {
				processFormsTableInput(event.target);
			}
		});
		
		//add a listener for clicks outside the cell
		document.addEventListener('click', outsideFormsTableClicked);
		
		if(inputNode.type != 'checkbox' || inputs.length == 1){
			if(inputNode.type == 'date'){
				inputNode.addEventListener("blur", function(event){
					//only process if we added a value
					if(event.target.value != ''){
						processFormsTableInput(event.target);
					}
				});
			}else{
				inputNode.addEventListener("change", function(event){
					//only process if we added a value
					if(event.target.value != ''){
						processFormsTableInput(event.target);
					}
				});
			}
			
			inputNode.focus();
		}
	});
}

function outsideFormsTableClicked(event){
	if(event.target.closest('td') == null || event.target.closest('td').dataset.oldtext == null){
		//remove as soon as we come here
		this.removeEventListener("click", arguments.callee);
		processFormsTableInput(document.querySelector('[data-oldtext]'));
	}
}

//function to get the temp input value and save it over AJAX
var running = false;
async function processFormsTableInput(target){	
	if(running == target){
		return;
	}
	running = target;
	
	setTimeout(function(){ running = false;}, 500);	

	var table			= target.closest('table');
	var formId			= table.dataset.formid;
	var cell			= target.closest('td');
    var cell_id			= cell.dataset.id
	var value			= FormFunctions.getFieldValue(target,false);
	var submission_id	= target.closest('tr').dataset.id;
	var sub_id			= cell.dataset.subid;
	
	//remove all event listeners
	document.removeEventListener("click", outsideFormsTableClicked);
	
	//Only update when needed
	if (value != JSON.parse(cell.dataset.oldvalue)){
		Main.showLoader(cell.firstChild);
		
		// Submit new value and receive the filtered value back
		var formData = new FormData();
		formData.append('formid', formId);
		formData.append('submissionid',submission_id);
		formData.append('subid',sub_id);
		formData.append('fieldname',cell_id);
		formData.append('newvalue',value);
		
		var response	= await FormSubmit.fetchRestApi('forms/edit_value', formData);
	
		if(response){
			var value = response.newvalue;
			//Replace the input element with its value
			if(value == "") value = "X";
	
			//Update all occurences of this field
			if(sub_id == null){
				var target	= table.querySelectorAll(`tr[data-id="${submission_id}"] td[data-id="${cell_id}"]`);
				target.forEach(cell=>{
					cell.innerHTML = value;
				});
			}else{
				cell.innerHTML = value;
			}
	
			Main.displayMessage(response.message)
		}
	}else{
		console.log(value)
		cell.innerHTML = cell.dataset.oldtext;
	}

	delete target.dataset.oldtext;
}

document.addEventListener("click", event=>{
	var target = event.target;

	if(target.name == 'submit_column_setting'){
		saveColumnSettings(target);
	}

	if(target.name == 'submit_table_setting'){
		saveTableSettings(target);
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
	
	//show auto archive fields
	if(target.name == 'form_settings[autoarchive]'){
		el = target.closest('.table_rights_wrapper').querySelector('.autoarchivelogic');
		if(target.value == 'true'){
			el.classList.remove('hidden');
		}else{
			el.classList.add('hidden');
		}
	}
	
	//Open settings modal
	if(target.classList.contains('edit_formshortcode_settings')){
		document.querySelector('.modal.form_shortcode_settings').classList.remove('hidden');
	}
	
	//Edit data
	var td = target.closest('td');
	if(target.matches('td.edit_forms_table') && target.dataset.oldtext == null){
		event.stopPropagation();
		getInputHtml(target);
	}else if(td != null  && target.dataset.oldtext == null && td.matches('td.edit_forms_table') && target.tagName != 'INPUT' && target.tagName != 'A' && target.tagName != 'TEXTAREA' && !target.closest('.nice-select') ){
		event.stopPropagation();
		getInputHtml(target.closest('td'));
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
});

document.addEventListener("DOMContentLoaded",function() {
	console.log("Formstable.js loaded");
	
	if(typeof(Sortable) != 'undefined'){
		//Make the sortable_column_settings_rows div sortable
		var options = {
			handle: '.movecontrol',
			animation: 150,
		};

		document.querySelectorAll('.sortable_column_settings_rows').forEach(el=>{
			Sortable.create(el, options);
		});
	}
});

document.addEventListener('change', event=>{
	var target = event.target;

	if(target.id == 'sim-forms-selector'){
		document.querySelectorAll('.main_form_wrapper').forEach(el=>{
			el.classList.add('hidden');
		});
		document.getElementById(target.value).classList.remove('hidden');

		// position table
		SimTableFunctions.positionTable();
	}
});