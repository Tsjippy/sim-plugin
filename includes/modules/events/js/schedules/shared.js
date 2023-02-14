//shows the modal to select a user as host
export function showAddHostModal(target, date='', startTime=''){
	target.classList.add('active');
	let modal											= document.querySelector('[name="add_host"]');

	// Clear
	modal.querySelectorAll('input:not([type="hidden"], [type="radio"])').forEach(el=>el.value='');

    modal.querySelector('[name="date"]').value			= date;
    modal.querySelector('[name="starttime"]').value		= startTime;

	// Add new values
	modal.querySelector('[name="schedule_id"]').value	= target.closest('.schedules_div').dataset.id;

	modal.classList.remove('hidden');
}

// Add current user as host
export async function addCurrentUserAsHost(target, dateStr){
    let scheduleOwner	= target.closest('.schedules_div').dataset.target;   
	let text 			= `Please confirm you want to be the host for ${scheduleOwner} on ${dateStr}`;
	let confirmed		= await checkConfirmation(text, target.firstChild);

	if(confirmed){

		let formData		= loadHostFormdata(target);

		if(target.closest('table') == null){
			Main.showLoader(target);
		}

		let response	= await FormSubmit.fetchRestApi('events/add_host', formData);

		if(response){
			addHostHtml(response);
		}
	}
}

// Show the new host in the schedule
export function addHostHtml(response){
	let target			= document.querySelector('.active');

	target.classList.remove('active');
	target.outerHTML	= response.html;

	Main.displayMessage(response.message);

	Main.hideModals();
}

// Remove a host
export async function removeHost(target, dateStr){
	let text, confirmed;

    let scheduleOwner	= target.closest('.schedules_div').dataset.target;
    let formData		= loadHostFormdata(target);

	if(target.classList.contains('orientation')){
		text 	= `Are you sure you want to remove this orientation session`;
	}else if(target.classList.contains('admin')){
		text 	= `Please confirm host removal for ${scheduleOwner} on ${dateStr}`;
	}else{
		text 	= `Please confirm you do not want to be the host for ${scheduleOwner} on ${dateStr} anymore`;
	}

	confirmed	= await checkConfirmation(text, target.firstChild);

	if(confirmed){
		Main.showLoader(target);

		var response = await FormSubmit.fetchRestApi('events/remove_host', formData);

		Main.displayMessage(response);
	}

    return confirmed;
}

export function showTimeslotModal(target='', date='', startTime='', endTime=''){
	let hostId, oldTime, subject, location, hostName, eventId;
	let modal 			= document.querySelector('[name="add_session"]');

	// Clear
	modal.querySelectorAll('input:not([type="hidden"], [type="checkbox"], [type="radio"])').forEach(el=>el.value='');

	modal.querySelector('[name="schedule_id"]').value		= target.closest('.schedules_div').dataset.id;

	hostId			= target.dataset.host_id;
	oldTime			= target.dataset.old_time;
	subject			= target.dataset.subject;
	location		= target.dataset.location;
	hostName		= target.dataset.host;
	eventId			= target.dataset.event_id;

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

export async function editTimeSlot(target, date, dateStr){
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

	//swap and/or
	if (answer.isConfirmed) {
		// Show modal and prefill the default fields
		showTimeslotModal(target, date, target.dataset.starttime, target.dataset.endtime);
		
		//change button text
		var modal 			= document.querySelector('[name="add_session"]');
		modal.querySelector('[name="add_timeslot"]').textContent	= 'Update time slot';
	//add new rule after this one
	}else if (answer.isDenied) {
		return answer.isDenied;
	}
}

function loadHostFormdata(target){
    let scheduleId		= target.closest('.schedules_div').dataset.id;
	let table			= target.closest('table');

	let heading, cell, date, startTime, host;

	if(table == null){
		startTime		= target.dataset.starttime;
		host			= target.dataset.host_id;
		date			= target.dataset.isodate
	}else{
		heading			= table.tHead.rows[0];
		cell			= target.closest('td');
		startTime		= target.closest('tr').dataset.starttime;
		host			= cell.dataset.host;
		date			= heading.cells[cell.cellIndex].dataset.isodate;
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

export async function checkConfirmation(text, target){
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
		Main.showLoader(target.firstChild);
		
		return true;
	}

	return false;
}

document.addEventListener('click',function(event){
	let target = event.target;

	if(target.name == 'add-session'){
        console.log('click12')
		event.stopPropagation();
		showTimeslotModal(target);
	}else if(target.name == 'add-host'){
        console.log('click13')
		event.stopPropagation();

		Main.showModal(target.closest('.schedules_div').querySelector('.add-host-mobile-wrapper'));
	}	
});