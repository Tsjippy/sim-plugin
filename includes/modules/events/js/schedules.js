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
		Main.showModal(modal);
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
function showAddHostModal(target){
	let table											= target.closest('table');
	target.classList.add('active');
	let modal											= document.querySelector('[name="add_host"]');
	if(table != null){
		let cell											= target.closest('td');
		let date											= table.rows[0].cells[cell.cellIndex].dataset.isodate;
		let startTime										= target.closest('tr').dataset.starttime;
		modal.querySelector('[name="date"]').value			= date;
		modal.querySelector('[name="starttime"]').value		= startTime;
	}

	// Clear
	modal.querySelectorAll('input:not([type="hidden"], [type="radio"])').forEach(el=>el.value='');

	// Add new values
	modal.querySelector('[name="schedule_id"]').value	= target.closest('.schedules_div').dataset.id;

	modal.classList.remove('hidden');
}

// Add a new host/ updates an existing entry when the host form is submitted
async function addHost(target){
	let form	= target.closest('form');
	let oldtime	= form.querySelector('[name="oldtime"]').value;
	if(oldtime != ''){
		// remove the old event
		let cell	= document.querySelector('td.active');
		if(cell != null){
			cell.classList.remove('ui-selected', 'active', 'selected');
			cell.removeAttribute('rowspan');

			let date		= form.querySelector('[name="date"]').value;
			let time		= form.querySelector('[name="starttime"]').value;
			let columnNr	= cell.closest('table').tHead.querySelector(`[data-isodate="${date}"]`).cellIndex;
			// Add the new one
			let newCell		= cell.closest('table').querySelector(`tr[data-starttime="${time}"]`).cells[columnNr];
			newCell.classList.add('ui-selected', 'active', 'selected');
			newCell.innerHTML	= cell.innerHTML;

			cell.innerHTML	= 'Available';
		}
	}

	var response 	= await FormSubmit.submitForm(target, 'events/add_host');

	if(response){
		addHostHtml(response);
	}
}

async function addHostMobile(target){
	var response 	= await FormSubmit.submitForm(target, 'events/add_host');

	if(response){
		Main.displayMessage(response);
	}

	Main.hideModals();
}

// Add current user as host
async function addCurrentUserAsHost(target){
	var table			= target.closest('table');

	let scheduleOwner, dateStr;

	if(table != null){
		var cell		= target.closest('td');
		scheduleOwner	= table.closest('.schedules_div').dataset.target;;
		dateStr			= table.rows[0].cells[cell.cellIndex].dataset.date;
	}else{
		scheduleOwner	= target.closest('.schedules_div').dataset.target;;
		dateStr			= target.dataset.date;
	}
	
	var text 			= `Please confirm you want to be the host for ${scheduleOwner} on ${dateStr}`;
	var confirmed		= await checkConfirmation(text);

	if(confirmed){

		var formData		= loadHostFormdata(target);

		target.classList.add('active');

		if(target.closest('.session-wrapper-mobile ') != null){
			target.closest('.session-wrapper-mobile ').classList.add('active');
		}
		if(table == null){
			Main.showLoader(target);
		}

		var response	= await FormSubmit.fetchRestApi('events/add_host', formData);

		if(response){
			addHostHtml(response);
		}
	}
}

// Show the new host in the schedule
function addHostHtml(response){
	var targetCell			= document.querySelector('.active');

	targetCell.classList.remove('active');
	targetCell.outerHTML	= response.html;

	Main.displayMessage(response.message);

	addSelectable();

	Main.hideModals();
}

// Remove a host
async function removeHost(target){
	let formData, scheduleOwner, cell, dateStr, text, wrapper, confirmed;

	var table			= target.closest('table');
	if(table != null){
		formData		= loadHostFormdata(target);
		scheduleOwner	= table.closest('.schedules_div').dataset.target;
		cell			= target.closest('td');
		dateStr			= table.rows[0].cells[cell.cellIndex].dataset.date;
	}else{
		console.log(target)
		formData		= loadHostFormdata(target);
		scheduleOwner	= target.closest('.schedules_div').dataset.target;
		dateStr			= target.dataset.date;
	}

	if(target.classList.contains('orientation')){
		text 	= `Are you sure you want to remove this orientation session`;
	}else if(target.classList.contains('admin')){
		text 	= `Please confirm host removal for ${scheduleOwner} on ${dateStr}`;
	}else{
		text 	= `Please confirm you do not want to be the host for ${scheduleOwner} on ${dateStr} anymore`;
	}

	confirmed	= await checkConfirmation(text);

	if(confirmed){
		if(table != null){
			Main.showLoader(cell.firstChild);
		}else{
			wrapper	= target.closest('.session-wrapper-mobile');
			Main.showLoader(target);
		}

		var response = await FormSubmit.fetchRestApi('events/remove_host', formData);

		if(table != null){
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
		}else{
			wrapper.remove();
		}

		Main.displayMessage(response);
	}
}

function showTimeslotModal(selected=''){
	let firstCell, date, startTime, endTime, hostId, oldTime, subject, location, hostName, eventId;
	let modal 			= document.querySelector('[name="add_session"]');

	// Clear
	modal.querySelectorAll('input:not([type="hidden"], [type="checkbox"], [type="radio"])').forEach(el=>el.value='');

	if(selected[0] == undefined){
		firstCell	= selected;
		date		= firstCell.dataset.isodate;
		startTime	= firstCell.dataset.starttime;
		endTime		= firstCell.dataset.endtime;

		modal.querySelector('[name="schedule_id"]').value		= selected.closest('.schedules_div').dataset.id;
	}else{
		firstCell		= selected[0].node;
		let lastCell		= selected[selected.length-1].node;
		let rowCount		= document.querySelectorAll('.ui-selected').length;
		applyRowSpan(firstCell, rowCount);

		// Only show loader when the cell is empty
		if(!firstCell.matches('.selected')){
			Main.showLoader(firstCell.firstChild);
		}

		startTime	= firstCell.closest('tr').dataset.starttime;

		endTime		= firstCell.dataset.endtime;
		if(endTime == undefined){
			endTime	= lastCell.closest('tr').dataset.endtime;
		}
		let table			= firstCell.closest('table');
		date			= table.rows[0].cells[firstCell.cellIndex].dataset.isodate;
	}

	hostId			= firstCell.dataset.host_id;
	oldTime			= firstCell.dataset.old_time;
	subject			= firstCell.dataset.subject;
	location		= firstCell.dataset.location;
	hostName		= firstCell.dataset.host;
	eventId			= firstCell.dataset.event_id;
	
	if(target.closest('.day-wrapper-mobile') != null){
		target.closest('.day-wrapper-mobile').classList.add('active');
	}else{
		firstCell.classList.add('active');
	}

	//Fill the modal values
	modal.querySelector('[name="date"]').value				= date;
	modal.querySelector('[name="starttime"]').value			= startTime;
	modal.querySelector('[name="endtime"]').value			= endTime;
	modal.querySelector('[name="add_timeslot"]').classList.remove('add_schedule_row');
	modal.querySelector('[name="add_timeslot"]').classList.add('update_schedule');
	if(startTime != undefined){
		modal.querySelector('[name="oldtime"]').value		= startTime;
	}
	if(date != undefined){
		modal.querySelector('[name="olddate"]').value 		= date;
	}
	if(eventId != undefined){
		modal.querySelector('[name="event-id"]').value		= eventId;
	}
	if(hostId	!= undefined){
		modal.querySelector('[name="host_id"]').value		= hostId;
	}
	if(subject	!= undefined){
		modal.querySelector('[name="subject"]').value			= subject;
	}
	if(location	!= undefined){
		modal.querySelector('[name="location"]').value			= location;
	}
	if(hostName	!= undefined){
		modal.querySelector('[name="host"]').value				= hostName;
	}
	
	Main.showModal(modal);
}

async function editTimeSlot(selected){
	let options	= {
		title: 'What do you want to do?',
		text: "Do you want to edit or remove this timeslot?",
		showDenyButton: true,
		showCancelButton: true,
		confirmButtonText: 'Edit timeslot',
		denyButtonText: 'Remove timeslot',
	}

	if(document.fullscreenElement != null){
		options['target']	= document.fullscreenElement;
	}

	var answer = await Swal.fire(options);

	let firstCell;
	if(selected[0] == undefined){
		firstCell	= selected;
	}else{
		firstCell	= selected[0].node;
	}

	//swap and/or
	if (answer.isConfirmed) {
		// Show modal and prefill the default fields
		showTimeslotModal(selected);
		
		//change button text
		var modal 			= document.querySelector('[name="add_session"]');
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
	
	Main.showModal(recipeModal);
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

	let scheduleId, heading, cell, date, startTime, host;

	if(table == null){
		scheduleId		= target.closest('.schedules_div').dataset["id"];
		startTime		= target.dataset.starttime;
		host			= target.dataset.host_id;
		date			= target.dataset.isodate
	}else{
		scheduleId		= table.closest('.schedules_div').dataset["id"];
		heading			= table.tHead.rows[0];
		cell			= target.closest('td');
		date			= heading.cells[cell.cellIndex].dataset.isodate;
		startTime		= target.closest('tr').dataset.starttime;
		host			= cell.dataset.host;
	}

	var formData		= new FormData();
	formData.append('date', date);
	if(host != null){
		formData.append('host_id', host);
	}else{
		formData.append('host_id', sim.userId);
	}
	
	formData.append('starttime', startTime);
	formData.append('schedule_id', scheduleId);

	return formData;
}

async function checkConfirmation(text){
	let options = {
		title: 'Are you sure?',
		text: text+"?",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: "#bd2919",
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes'
	};

	if(document.fullscreenElement != null){
		options['target']	= document.fullscreenElement;
	}

	var result = await Swal.fire(options);
	
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
	//loop over all the schedule tables
	document.querySelectorAll('.sim-table.schedule').forEach(function(table){
		//Add selectable on non-mobile devices or if it is only a lunch and dinner schedule
		if(!Main.isMobileDevice() || table.rows.length < 7){
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
		}
	});

	hideRows();
}

function showEditScheduleModal(target){
	let modal	= document.getElementById('edit_schedule_modal');
	let wrapper	= target.closest('.schedules_div');
	let table	= wrapper.querySelector('table.schedule');

	modal.querySelector(`[name="schedule_id"]`).value		= table.dataset.id;
	modal.querySelector(`[name="target_id"]`).value			= table.dataset.target_id;
	modal.querySelector(`[name="target_name"]`).value		= table.dataset.target;
	modal.querySelector(`[name="schedule_info"]`).value		= wrapper.querySelector('.table_title.sub-title').textContent;
	modal.querySelector(`[name="startdate"]`).value			= table.tHead.querySelector('tr').cells[1].dataset.isodate;
	modal.querySelector(`[name="enddate"]`).value			= table.tHead.querySelector('tr').cells[table.tHead.querySelector('tr').cells.length-1].dataset.isodate;
	modal.querySelector(`[name="skiplunch"]`).checked		= table.dataset.lunch;
	modal.querySelector(`[name="skiporientation"]`).checked	= table.rows.length<3;

	Main.showModal(modal);
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
	}else if(target.name == 'add-session'){
		event.stopPropagation();
		showTimeslotModal(target);
	}else if(target.name == 'add-host'){
		event.stopPropagation();

		Main.showModal(target.closest('.schedules_div').querySelector('.add-host-mobile-wrapper'));
	}else if (target.name == 'add_host_mobile' ){
		event.stopPropagation();
		addHostMobile(target)
	}else if(target.closest('.remove-host-mobile') != null){
		event.stopPropagation();
		removeHost(target.closest('.remove-host-mobile'));
	}else if(target.closest('.edit-session-mobile') != null){
		editTimeSlot(target.closest('.edit-session-mobile'));
	}else if(target.closest('.add-me-as-host') != null){
		addCurrentUserAsHost(target.closest('.add-me-as-host'));
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
	}else if(target.matches('.edit_schedule')){
		event.stopPropagation();

		showEditScheduleModal(target);
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
	}else if(target.name == 'starttime' && target.type == 'radio'){
		if(target.value == '12:00'){
			target.closest('form').querySelector('.lunch.select-wrapper').classList.remove('hidden');
			target.closest('form').querySelector('.diner.select-wrapper').classList.add('hidden');
		}else{
			target.closest('form').querySelector('.diner.select-wrapper').classList.remove('hidden');
			target.closest('form').querySelector('.lunch.select-wrapper').classList.add('hidden');
		}
	}

	if(target.matches('[name="add_session"] .time')){
		// get the active cell
		let cell		= document.querySelector('td.active');

		if(cell == null){
			return;
		}
		let table		= cell.closest('table');

		// validate the time
		if(
			(target.name == 'starttime' && table.querySelector(`tr[data-starttime="${target.value}"]`) == null) ||
			(target.name == 'endtime' && table.querySelector(`tr[data-endtime="${target.value}"]`) == null) 
		){
			return;
		}

		let form		= target.closest('form');
		let d			= new Date();
		let starttime	= form.querySelector('[name="starttime"]').value.split(':');
		starttime		= new Date(d.getFullYear(), 0, 1, starttime[0], starttime[1]);
		let endtime		= form.querySelector('[name="endtime"]').value.split(':');
		endtime			= new Date(d.getFullYear(), 0, 1, endtime[0], endtime[1]);
		// calculate the quarters between end and starttime
		let rowSpan		= (endtime-starttime)/1000/60/15

		// starttime changed
		if(starttime != target.value){
			// adjust the current cell
			cell.removeAttribute('rowspan');
			cell.classList.remove('ui-selected', 'active');

			// find the new cell
			let newRow	= cell.closest('table').querySelector('tr[data-starttime="'+form.querySelector('[name="starttime"]').value+'"]');
			let newCell	= newRow.cells[cell.cellIndex];

			newCell.innerHTML	= cell.innerHTML;
			newCell.classList.add('ui-selected', 'active');
			applyRowSpan(newCell, rowSpan);
		// endtime changed
		}else{
			applyRowSpan(cell, rowSpan);
		}
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
				let options = {
					icon: 'error',
					title: "You can not select times on multiple days!",
					confirmButtonColor: "#bd2919",
				};

				if(document.fullscreenElement != null){
					options['target']	= document.fullscreenElement;
				}

				Swal.fire(options);

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
		showAddHostModal(target);
	//orientation slot
	}else if(target.matches('.meal:not(.admin, .selected)')){
		addCurrentUserAsHost(target);
	}else{
		checkIfValidSelection(target, selected, e); 
	}
}