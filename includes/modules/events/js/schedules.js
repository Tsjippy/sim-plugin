/**
 *
 * Submits the new schedule form via rest API and adds the new schedule to the screen.
 *
 * @listens to button click if button has name add_schedule
 *
 * @param {Object} target     The clicked button.
 *
 * @return nothing
*/ 
async function addSchedule(target){
	var response = await FormSubmit.submitForm(target, 'events/add_schedule');

	if(response){
		target.closest('.schedules_wrapper').outerHTML=response.html;
		
		Main.displayMessage(response.message);

		addSelectable();
	}
}

function ShowPublishScheduleModal(target){
	var modal			= target.closest('.schedules_div').querySelector('.publish_schedule');
	modal.querySelector('[name="schedule_id"]').value = target.dataset["schedule_id"];
	if(target.dataset.target != null){
		modal.querySelector('[name="schedule_target"]').value = target.dataset.target;
		modal.querySelector('[name="publish_schedule"]').click();
		Main.showLoader(target,true);
	}else{
		modal.classList.remove('hidden');
	}
}

async function publishSchedule(target){
	var response	= await FormSubmit.submitForm(target, 'events/publish_schedule');

	if(response){
		document.querySelectorAll('.schedule_actions .loadergif').forEach(el=>el.classList.add('hidden'));
		
		Main.hideModals();

		Main.displayMessage(response);
	}
}

// Removes an existing schedule
async function removeSchedule(target){
	var scheduleId		= target.dataset["schedule_id"];
	var text 			= "Are you sure you want to remove this schedule";
	var formData 		= new FormData();
	formData.append('schedule_id', scheduleId);

	var confirmed		= await checkConfirmation(text);
	if(confirmed){
		var response	= await FormSubmit.fetchRestApi('events/remove_schedule', formData);

		if(response){
			Main.displayMessage(response);

			document.querySelector('.schedules_wrapper .loaderwrapper:not(.hidden)').remove();
		}
	}
}

function applyRowSpan(target, count){
	if(count > 1){
		var row		= target.closest('tr');
		var index	= target.cellIndex;
		//Loop over the next cells to add the hidden attribute
		for (var i = 1; i < count; i++) {
			row = row.nextElementSibling;
			row.cells[index].classList.add('hidden');
		}
		target.rowSpan	= count;
	}
}

function removeRowSpan(target){
	document.querySelectorAll('.ui-selected').forEach(el=>el.classList.remove('ui-selected', 'active', 'hidden'));
	target.rowSpan = 1;
}

//shows the modal to select a user as host
function showAddHostModal(){
	target.classList.add('active');
	var table											= target.closest('table');
	var cell											= target.closest('td');
	var date											= table.rows[0].cells[cell.cellIndex].dataset.isodate;
	var startTime										= target.closest('tr').dataset.starttime;
	var modal											= target.closest('.schedules_wrapper').querySelector('[name="add_host"]');
	// Clear
	modal.querySelectorAll('input:not([type="hidden"])').forEach(el=>el.value='');

	// Add new values
	modal.querySelector('[name="schedule_id"]').value	= table.dataset["id"];
	modal.querySelector('[name="date"]').value			= date;
	modal.querySelector('[name="starttime"]').value		= startTime;

	modal.classList.remove('hidden');
}

// Add a new host when the host form is submitted
async function addHost(target){
	var response 	= await FormSubmit.submitForm(target, 'events/add_host');

	if(response){
		addHostHtml(response);
	}
}

// Add current user as host
async function addCurrentUserAsHost(target){
	var table			= target.closest('table');
	var cell			= target.closest('td');
	var scheduleOwner	= table.dataset.target;
	var dateStr			= table.rows[0].cells[cell.cellIndex].dataset.date;
	
	var text 			= `Please confirm you want to be the host for ${scheduleOwner} on ${dateStr}`;
	var confirmed		= await checkConfirmation(text);

	if(confirmed){
		target.classList.add('active');

		var formData		= loadHostFormdata(target);

		var response	= await FormSubmit.fetchRestApi('events/add_host', formData);

		if(response){
			addHostHtml(response);
		}
	}
}

// Show the new host in the schedule
function addHostHtml(response){
	var targetCell			= document.querySelector('td.active');
	targetCell.classList.remove('active');
	targetCell.outerHTML	= response.html;

	Main.hideModals();

	Main.displayMessage(response.message);

	addSelectable();
}

// Remove a host
async function removeHost(target){
	var formData		= loadHostFormdata(target);

	var table			= target.closest('table');
	var scheduleOwner	= table.dataset.target;
	var cell			= target.closest('td');
	var dateStr			= table.rows[0].cells[cell.cellIndex].dataset.date;
	var text;

	if(target.classList.contains('orientation')){
		text 	= `Are you sure you want to remove this orientation session`;
	}else if(target.classList.contains('admin')){
		text 	= `Please confirm host removal for ${scheduleOwner} on ${dateStr}`;
	}else{
		text 	= `Please confirm you do not want to be the host for ${scheduleOwner} on ${dateStr} anymore`;
	}

	var confirmed	= await checkConfirmation(text);

	if(confirmed){
		Main.showLoader(cell.firstChild);

		var response = await FormSubmit.fetchRestApi('events/remove_host', formData);

		//Remove cell class
		cell.classList.remove('selected');
		cell.classList.remove('ui-selected');

		//Set cell text
		cell.innerHTML	= 'Available';
		var index		= cell.cellIndex;
		var row			= cell.closest('tr');

		//only remove rowspan if the first cell of a row has no rowspan
		if(row.cells[0].rowSpan==1){
			//Loop over the next cells to remove the hidden attribute
			for (var i = 1; i < target.rowSpan; i++) {
				row = row.nextElementSibling;
				row.cells[index].classList.remove('hidden');
			}

			//Reset the rowSpan
			target.rowSpan = 1;
		}

		Main.displayMessage(response);
	}
}

function showTimeslotModal(selected){
	var modal 			= document.querySelector('[name="add_session"]');
	var firstCell		= selected[0].node;
	var lastCell		= selected[selected.length-1].node;

	firstCell.classList.add('active');
	let rowCount		= document.querySelectorAll('.ui-selected').length;
	applyRowSpan(firstCell, rowCount);
	Main.showLoader(firstCell.firstChild);

	var endTime			= lastCell.closest('tr').dataset.endtime;
	var table			= firstCell.closest('table');
	var date			= table.rows[0].cells[firstCell.cellIndex].dataset.isodate;
	
	// Clear
	modal.querySelectorAll('input:not([type="hidden"])').forEach(el=>el.value='')

	//Fill the modal values
	modal.querySelector('[name="schedule_id"]').value		= table.dataset["id"];
	modal.querySelector('[name="date"]').value				= date;
	modal.querySelector('[name="olddate"]').value 			= date;
	modal.querySelector('[name="starttime"]').value			= firstCell.closest('tr').dataset.starttime;
	modal.querySelector('[name="endtime"]').value			= endTime;
	modal.querySelector('[name="add_timeslot"]').classList.remove('add_schedule_row');
	modal.querySelector('[name="add_timeslot"]').classList.add('update_schedule');
	
	modal.classList.remove('hidden');
}

async function editTimeSlot(selected){
	var answer = await Swal.fire({
		title: 'What do you want to do?',
		text: "Do you want to edit or remove this timeslot?",
		showDenyButton: true,
		showCancelButton: true,
		confirmButtonText: 'Edit timeslot',
		denyButtonText: 'Remove timeslot',
	});
	
	var firstCell	= selected[0].node;

	//swap and/or
	if (answer.isConfirmed) {
		// Show modal and prefill the default fields
		showTimeslotModal(selected);

		// also prefill the other fields
		var modal 		= document.querySelector('[name="add_session"]');
		var endRow		= target.closest('tr').rowIndex + target.rowSpan - 1;
		var endTime		= target.closest('table').rows[endRow].dataset.firstCell;			
		modal.querySelector('[name="subject"]').value			= firstCell.querySelector('.subject').textContent;
		modal.querySelector('[name="endtime"]').value			= endTime;

		//Fill locationfield
		var locationtText = firstCell.querySelector('.location').textContent;
		if(locationtText != 'Add location'){
			modal.querySelector('[name="location"]').value			= locationtText;
		}
		
		//Fill userfield
		var userid = firstCell.dataset.host;
		if(userid != null){
			let el = modal.querySelector('[name="host"]');
			if(el != null){
				el.value	= userid;
			}
			el.selectedOptions[0].defaultSelected=true;
			el._niceselect.update();
			el.selectedOptions[0].defaultSelected=false;
		}
		
		//change button text
		modal.querySelector('[name="add_timeslot"]').textContent	= 'Update time slot';
	//add new rule after this one
	}else if (answer.isDenied) {
		removeHost(firstCell);
	}
}

// Show the modal to add a recipe keyword
function showRecipeModal(target){
	target.classList.add('active');

	var table			= target.closest('table');
	var heading			= table.tHead.rows[0];
	var cell			= target.closest('td');
	var date			= heading.cells[cell.cellIndex].dataset.isodate;
	var startTime		= target.closest('tr').dataset.starttime;

	//Fill the modal with the values of the clickes schedule
	var recipeModal 	= document.querySelector('[name="recipe_keyword_modal"]');

	FormSubmit.formReset(recipeModal.querySelector('form'));

	recipeModal.querySelector('[name="schedule_id"]').value 	= table.dataset["id"];
	recipeModal.querySelector('[name="date"]').value 			= date;
	recipeModal.querySelector('[name="starttime"]').value 		= startTime;

	if(target.textContent != 'Enter recipe keyword'){
		recipeModal.querySelector('[name="recipe_keyword"]').value	= target.textContent;
		document.querySelector('[name="add_recipe_keyword"]').textContent	= 'Update recipe keywords';
	}
	
	recipeModal.classList.remove('hidden');
}

//submit the recipe form
async function submitRecipe(target){
	var response = await FormSubmit.submitForm(target, 'events/add_menu');

	document.querySelector('.active').textContent	= target.closest('form').querySelector('[name="recipe_keyword"]').value;

	document.querySelector('.active').classList.remove('active');

	Main.hideModals();

	Main.displayMessage(response);
}

function loadHostFormdata(target){
	var table			= target.closest('table');
	var scheduleId		= table.dataset["id"];
	var heading			= table.tHead.rows[0];
	var cell			= target.closest('td');
	var date			= heading.cells[cell.cellIndex].dataset.isodate;
	var startTime		= target.closest('tr').dataset.starttime;

	var formData		= new FormData();
	formData.append('date',date);
	if(cell.dataset.host != null){
		formData.append('host', cell.dataset.host);
	}else{
		formData.append('host_id', sim.userId);
	}
	
	formData.append('starttime', startTime);
	formData.append('schedule_id', scheduleId);

	return formData;
}

async function checkConfirmation(text){
	var result = await Swal.fire({
		title: 'Are you sure?',
		text: text+"?",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: "#bd2919",
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes'
	});
	
		if (result.isConfirmed) {
			document.querySelectorAll('.modal:not(.hidden)').forEach(modal=>modal.classList.add('hidden'));
			//display loading gif
			if(target.classList.contains('remove_schedule')){
				Main.showLoader(target.closest('.schedules_div'));
			}else{
				Main.showLoader(target.firstChild);
			}
			
			return true;
		}

		return false;
}

//hide empty rows on mobile
function hideRows() {
	//only run for smaller screens
	if (window.innerWidth > 800) return false;
	
	//Loop over all tables
	document.querySelectorAll('.sim-table tr').forEach(function(tr){
		var emptyRow = true;
		
		//DO not hide lunch or dinner rows
		if(tr.dataset.starttime != 'dinner' && tr.dataset.starttime != 'lunch'){
			//loop over all table cells to see if there is a cell with content
			tr.querySelectorAll('td').forEach(function(cell,index){
				if(index>0 && cell.textContent != 'Available' && cell.textContent != ' '){
					emptyRow = false;
				}
			});
			
			//If non of the cells have content
			if(emptyRow ){
				//hide the row
				tr.classList.add('hidden');
			}
		}
	});
}

function addSelectable(){
	//Add selectable on non-mobile devices
	//if(!isMobileDevice()){
		//loop over all the schedule tables
		document.querySelectorAll('.sim-table.schedule').forEach(function(table){
			if(table._selectable != undefined){
				table._selectable.destroy();
			}

			//Load selectable and attach it to the table
			table._selectable = new Selectable({
			   filter:		".orientation.admin",
			   appendTo:	table.querySelector("tbody"),
			   lasso: {
					border: "none",
					backgroundColor: "none"
				}
			});
			
			//Run the function afterSelect when selection is final
			table._selectable.on('end', afterSelect);
		});
	//}

	hideRows();
}

document.addEventListener("DOMContentLoaded",function() {
	console.log("Schedule.js loaded");
	
	addSelectable();
});

document.addEventListener('click',function(event){
	target = event.target;

	if(target.name == 'add_schedule'){
		event.stopPropagation();

		addSchedule(target);
	}else if(target.name == 'publish_schedule'){
		event.stopPropagation();

		publishSchedule(target);
	}else if(target.name == 'add_recipe_keyword'){
		event.stopPropagation();

		submitRecipe(target);
	}else if(target.name == 'add_host' || target.name == 'add_timeslot'){
		event.stopPropagation();
		
		addHost(target);
	}else if(target.matches('.close') && target.closest('.modal') != null && target.closest('.modal').attributes.name.value == 'add_session'){
		removeRowSpan(document.querySelector('td.active'));
	}
	
	if(target.classList.contains('keyword')){
		showRecipeModal(target);
	}

	//publish the schedule
	if(target.classList.contains('publish')){
		event.stopPropagation();

		ShowPublishScheduleModal(target);
	}else if(target.classList.contains('remove_schedule')){
		event.stopPropagation();

		removeSchedule(target);
	}
});

document.addEventListener('change', function(event){

	target = event.target;

	//Direct table actions
	if(target.name=='target_name'){
		var userid = target.list.querySelector(`[value='${target.value}' i]`).dataset.value;

		target.closest('form').querySelector('[name="target_id"]').value	= userid;
	}else if(target.name == 'host'){
		let host	= target.list.querySelector(`[value="${target.value}"]`);
		let userId	= '';
		if(host != null){
			userId	= host.dataset.userid;
		}

		target.closest('form').querySelector('[name="host_id"]').value	= userId;
	}
});

function checkIfValidSelection(target, selected, e){
	//we have selected something and there are no open modals
	var openModals	= document.querySelectorAll('.modal:not(.hidden)');
	if((selected.length > 0 || target.classList.contains('orientation')) && (openModals == null || openModals.length == 0) ){	
		//check if selection is valid
		var columnNr	= selected[0].node.cellIndex;
		var firstCell	= selected[0].node;

		for (const selection of selected){
			if(columnNr != selection.node.cellIndex){
				Swal.fire({
					icon: 'error',
					title: "You can not select times on multiple days!",
					confirmButtonColor: "#bd2919",
				});
				e.target.closest('.sim-table')._selectable.clear();
				return;
			}
		}
		
		//We are dealing with a cell with a value
		if(firstCell.classList.contains('selected')){
			editTimeSlot(selected);
		}else{
			showTimeslotModal(selected);
		}
	}
}

//Do something with selected items
function afterSelect(e, selected, _unselected){
	target = e.target;

	//remove a meal host
	if(target.matches('.meal.selected.own, .meal.selected.admin')){
		removeHost(target);
	//Add a host
	}else if(target.matches('.meal.admin')){
		showAddHostModal();
	//orientation slot
	}else if(target.matches('.meal:not(.admin, .selected)')){
		addCurrentUserAsHost(target);
	}else{
		checkIfValidSelection(target, selected, e); 
	}
}