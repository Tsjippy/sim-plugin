//Function to update the schedule table with the new values after the AJAX request was succesfull
window['schedule_host_added'] = function(result,responsdata){
	if (result.status >= 200 && result.status < 400) {
		get_target_cell(responsdata).outerHTML	= responsdata.cell_html;

		var target_cell			= get_target_cell(responsdata);
		if(target_cell.rowSpan>1){
			var row		= target_cell.closest('tr');
			var index	= target_cell.cellIndex;
			//Loop over the next cells to add the hidden attribute
			for (i = 1; i < target_cell.rowSpan; i++) {
				row = row.nextElementSibling;
				row.cells[index].classList.add('hidden');
			}
		}

		hide_modals();
	}
}

window['schedule_host_removed'] = function(result,responsdata){
	if (result.status >= 200 && result.status < 400) {
		var target_cell			= get_target_cell(responsdata);

		//Remove cell class
		target_cell.classList.remove('selected');
		target_cell.classList.remove('ui-selected');

		//Set cell text
		target_cell.innerHTML = 'Available';
		
		var index = target_cell.cellIndex;
		
		var row = target_cell.closest('tr');

		//only remove rowspan if the first cell of a row has no rowspan
		if(row.cells[0].rowSpan==1){
			//Loop over the next cells to remove the hidden attribute
			for (i = 1; i < target_cell.rowSpan; i++) {
				row = row.nextElementSibling;
				row.cells[index].classList.remove('hidden');
			}

			//Reset the rowSpan
			target_cell.rowSpan = 1;
		}
	}
}

window['schedule_add_menu'] = function(result,responsdata){
	if (result.status >= 200 && result.status < 400) {
		var target_cell										= get_target_cell(responsdata);
		target_cell.querySelector('.keyword').textContent	= responsdata.menu;
	}

	hide_modals();
}

window['add_schedule'] = function(result,responsdata){
	if (result.status >= 200 && result.status < 400) {
		document.querySelectorAll('.schedules_wrapper').forEach(el=>el.outerHTML=responsdata.html);

		add_selectable();
	}
}

function get_target_cell(responsdata){
	var table		= document.querySelector('table[data-id="'+responsdata.schedule_id+'"]');
	var cellIndex	= table.querySelector('[data-isodate="'+responsdata.date+'"]').cellIndex;
	var target_cell	= table.querySelector('tr[data-starttime="'+responsdata.starttime+'"]').cells[cellIndex];
	
	return target_cell;
}

function remove_schedule(target){
	schedule_id					= target.dataset["schedule_id"];
	var remove_schedule_nonce	= document.querySelector('[name="remove_schedule_nonce"]').value;
		
	var text 			= "Are you sure you want to remove this schedule?";
	var formdata 		= new FormData();
	formdata.append('action','remove_schedule');
	formdata.append('schedule_id', schedule_id);
	formdata.append('remove_schedule_nonce',remove_schedule_nonce);

	check_confirmation(text, formdata);
}

function publish_schedule(target){
	var modal			= target.closest('.schedules_div').querySelector('.publish_schedule');
	modal.querySelector('[name="schedule_id"]').value = target.dataset["schedule_id"];;
	if(target.dataset.target != null){
		modal.querySelector('[name="select_schedule_target"]').value = target.dataset.target;
		modal.querySelector('[name="publish_schedule"]').click();
		showLoader(target,true);
	}else{
		modal.classList.remove('hidden');
	}
}

function show_recipe_modal(target){
	table				= target.closest('table');
	schedule_id			= table.dataset["id"];
	var heading			= table.tHead.rows[0];
	var cell			= target.closest('td');
	var date			= heading.cells[cell.cellIndex].dataset.isodate;
	var starttime		= target.closest('tr').dataset.starttime;

	//Fill the modal with the values of the clickes schedule
	recipe_modal = document.querySelector('[name="add_recipe_keyword"]');
	recipe_modal.querySelector('[name="schedule_id"]').value 	= table.dataset["id"];
	recipe_modal.querySelector('[name="date"]').value 			= date;
	recipe_modal.querySelector('[name="starttime"]').value 		= starttime;
	if(target.textContent != 'Enter recipe keyword'){
		recipe_modal.querySelector('[name="recipe_keyword"]').value	= target.textContent;
	}
	
	recipe_modal.classList.remove('hidden');
}

function load_host_formdata(target){
	var update_nonce	= document.querySelector('[name="update_schedule_nonce"]').value;
	table				= target.closest('table');
	schedule_id			= table.dataset["id"];
	var heading			= table.tHead.rows[0];
	var cell			= target.closest('td');
	var date			= heading.cells[cell.cellIndex].dataset.isodate;
	var starttime		= target.closest('tr').dataset.starttime;

	var formdata		= new FormData();
	formdata.append('date',date);
	if(cell.dataset.host != null){
		formdata.append('host',cell.dataset.host);
	}else{
		formdata.append('host',sim.userid);
	}
	
	formdata.append('starttime',starttime);
	formdata.append('update_schedule_nonce',update_nonce);
	formdata.append('schedule_id',schedule_id);

	return formdata;
}

function add_meal_host(target){
	var formdata		= load_host_formdata(target);
	table				= target.closest('table');
	var scheduleowner	= table.dataset.target;
	var cell			= target.closest('td');
	var date_str		= table.rows[0].cells[cell.cellIndex].dataset.date;

	if(target.textContent.toLowerCase().includes('available')){
		//Reserve for someone else
		if(target.classList.contains('admin')){
			var date			= table.rows[0].cells[cell.cellIndex].dataset.isodate;
			var starttime		= target.closest('tr').dataset.starttime;
			var modal			= target.closest('.schedules_wrapper').querySelector('[name="add_host"]');
			modal.querySelector('[name="schedule_id"]').value	= table.dataset["id"];
			modal.querySelector('[name="date"]').value			= date;
			modal.querySelector('[name="starttime"]').value		= starttime;
			modal.classList.remove('hidden');
		//Reserve for current user
		}else{
			var text 	= "Please confirm you want to be the host for "+scheduleowner+" on "+date_str;
			formdata.append('action','add_host');
			check_confirmation(text, formdata);
		}
	}
}

function remove_host(target){
	var formdata	= load_host_formdata(target);

	table				= target.closest('table');
	var scheduleowner	= table.dataset.target;
	var cell			= target.closest('td');
	var date_str		= table.rows[0].cells[cell.cellIndex].dataset.date;

	if(target.textContent.toLowerCase().includes('you')){
		var text 	= "Please confirm you do not want to be the host for "+scheduleowner+" on "+date_str+' anymore';
		formdata.append('action','remove_host');
		check_confirmation(text, formdata);
	}else if(target.classList.contains('admin')){
		var text 	= "Please confirm host removal for "+scheduleowner+" on "+date_str;
		formdata.append('action','remove_host');
		check_confirmation(text, formdata);
	}
}

function add_schedule_event(target){
	table				= target.closest('table');
	var first_table_row	= table.rows[0];
	var cell			= target.closest('td');
	var date			= first_table_row.cells[cell.cellIndex].dataset.isodate;
	var modal 			= document.querySelector('[name="add_session"]');
	modal.querySelector('[name="schedule_index"]').value 	= table.dataset["id"];
	modal.querySelector('[name="row_id"]').value 			= target.closest('tr').rowIndex;
	modal.querySelector('[name="cell_id"]').value			= target.closest('td').cellIndex;
	modal.querySelector('[name="date"]').value 				= date;
	modal.querySelector('[name="olddate"]').value 			= date;
	modal.querySelector('[name="add_timeslot"]').classList.add('add_schedule_row');
	modal.querySelector('[name="add_timeslot"]').classList.remove('update_schedule');
	modal.classList.remove('hidden');
}

function check_confirmation(text, formdata){
	Swal.fire({
		title: 'Are you sure?',
		text: text+"?",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: "#bd2919",
		cancelButtonColor: '#d33',
		confirmButtonText: 'Yes'
	}).then((result) => {
		if (result.isConfirmed) {
			document.querySelectorAll('.modal:not(.hidden)').forEach(modal=>modal.classList.add('hidden'));
			//display loading gif
			if(target.classList.contains('remove_schedule')){
				showLoader(target.closest('.schedules_div'));
			}else{
				showLoader(target.firstChild);
			}
			sendAJAX(formdata);
		}
	});
}

function remove_schedule_timeslot(target){
	modal_form			= target.closest('form');
	schedule_id			= modal_form.querySelector('[name="schedule_index"]').value;

	var date		= modal_form.querySelector('[name="date"]').value;
	var starttime	= modal_form.querySelector('[name="starttime"]').value;
	var update_nonce= document.querySelector('[name="update_schedule_nonce"]').value;
	
	var text 		= "Are you sure you want to remove this timeslot?";
	var formdata 	= new FormData();
	formdata.append('remove_timeslot','true');
	formdata.append('update_schedule_nonce',update_nonce);
	formdata.append('date',date);
	formdata.append('starttime',starttime);
	formdata.append('schedule_index',schedule_id);
	formdata.append('row_id',target.closest('row').rowIndex);
	formdata.append('cell_id',target.closest('td').cellIndex);

	check_confirmation(text, formdata);
}

//Do something with selected items
function afterSelect(e, selected, unselected){
	target = e.target;

	//remove a meal host
	if(target.matches('.meal.selected')){
		remove_host(target);
	//Add a host
	}else if(target.matches('.meal')){
		add_meal_host(target);
	//orientation slot
	}else{
		//we have selected something and there are no open modals
		var open_modals	= document.querySelectorAll('.modal:not(.hidden)');
		if((selected.length > 0 || target.classList.contains('orientation')) && (open_modals == null || open_modals.length == 0) ){	
			//check if selection is valid
			var column_nr	= selected[0].node.cellIndex;
			var first_cell	= selected[0].node;

			for (const selection of selected){
				if(column_nr != selection.node.cellIndex){
					Swal.fire({
						icon: 'error',
						title: "You can not select times on multiple days!",
						confirmButtonColor: "#bd2919",
					});
					e.target.closest('.sim-table')._selectable.clear();
					return;
				}
			};
			
			//We are dealing with a cell with a value
			if(first_cell.classList.contains('selected')){
				edit_time_slot(selected);
			}else{
				fill_timeslot_modal(selected);
			}
		} 
	}
};

function fill_timeslot_modal(selected){
	var modal 		= document.querySelector('[name="add_session"]');
	var first_cell	= selected[0].node;
	var last_cell	= selected[selected.length-1].node;
	var endtime		= last_cell.closest('tr').dataset.endtime;
	table			= first_cell.closest('table');
	var date		= table.rows[0].cells[first_cell.cellIndex].dataset.isodate;
	
	//Fill the modal values
	modal.querySelector('[name="schedule_id"]').value		= table.dataset["id"];
	modal.querySelector('[name="date"]').value				= date;
	modal.querySelector('[name="olddate"]').value 			= date;
	modal.querySelector('[name="starttime"]').value			= first_cell.closest('tr').dataset.starttime;
	modal.querySelector('[name="endtime"]').value			= endtime;
	modal.querySelector('[name="add_timeslot"]').classList.remove('add_schedule_row');
	modal.querySelector('[name="add_timeslot"]').classList.add('update_schedule');
	
	modal.classList.remove('hidden');
}

function edit_time_slot(selected){
	Swal.fire({
		title: 'What do you want to do?',
		text: "Do you want to edit or remove this timeslot?",
		showDenyButton: true,
		showCancelButton: true,
		confirmButtonText: 'Edit timeslot',
		denyButtonText: 'Remove timeslot',
	}).then((result) => {
		var first_cell	= selected[0].node;
		//swap and/or
		if (result.isConfirmed) {
			fill_timeslot_modal(selected);

			var modal 		= document.querySelector('[name="add_session"]');
			var endrow		= target.closest('tr').rowIndex + target.rowSpan - 1;
			var endtime		= target.closest('table').rows[endrow].dataset.endtime;			
			
			modal.querySelector('[name="subject"]').value			= first_cell.querySelector('.subject').textContent;
			
			modal.querySelector('[name="endtime"]').value			= endtime;

			//Fill locationfield
			var locationttext = first_cell.querySelector('.location').textContent;
			if(locationttext != 'Add location'){
				modal.querySelector('[name="location"]').value			= locationttext;
			}
			
			//Fill userfield
			var userid = first_cell.dataset.host;
			if(userid != null){
				el = modal.querySelector('[name="host"]');
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
		}else if (result.isDenied) {
			remove_host(first_cell);
		}
	});
}

//hide empty rows on mobile
function hideRows() {
	//only run for smaller screens
	if (window.innerWidth > 800) return false;
	
	//Loop over all tables
	document.querySelectorAll('.sim-table').forEach(function(table){
		var emptyrow = true;
		
		//DO not hide lunch or dinner rows
		if(tr.dataset.starttime != 'dinner' && tr.dataset.starttime != 'lunch'){
			//loop over all table cells to see if there is a cell with content
			tr.querySelectorAll('td').forEach(function(cell,index){
				if(index>0 && cell.textContent != 'Available' && cell.textContent != ' '){
					emptyrow = false;
				}
			});
			
			//If non of the cells have content
			if(emptyrow == true){
				//hide the row
				tr.classList.add('hidden');
			}
		}
	});
}

function add_selectable(){
	//Add selectable on non-mobile devices
	//if(!isMobileDevice()){
		//loop over all the schedule tables
		document.querySelectorAll('.sim-table.schedule').forEach(function(table){
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
	
	add_selectable();
});

document.addEventListener('click',function(event){
	target = event.target;
	//Direct table actions
	if(target.classList.contains('remove_schedule')){
		remove_schedule(target);
	}

	//publish the schedule
	if(target.classList.contains('publish')){
		publish_schedule(target);
	}

	if(target.classList.contains('keyword')){
		show_recipe_modal(target);
	}

	if(target.classList.contains('timeslot')){
		//add_schedule_event(target);
	}
});

document.addEventListener('change',function(event){
	target = event.target;
	//Direct table actions
	if(target.name=='target_name'){
		var userid = target.list.querySelector("[value='"+target.value+"' i]").dataset.value;

		target.closest('form').querySelector('[name="target_id"]').value	= userid;
	}
});
