window['remove_form_table_row']	= function(result,responsdata){
	if (result.status >= 200 && result.status < 400) {
		var base_query	= '.form_data_table[data-id="'+responsdata.formid+'"] .table-row[data-id="'+responsdata.submissionid+'"]';
		if(responsdata.subid != -1){
			document.querySelectorAll(base_query+'[data-subid="'+responsdata.subid+'"]').forEach(
				row=>{row.remove();}
			);
		}else{
			document.querySelectorAll(base_query).forEach(
				row=>{row.remove();}
			);
		}
	}
}

window['changearchivebutton']	= function(result,responsdata){
	if (result.status >= 200 && result.status < 400) {
		//console.log(responsdata);
		
		if(responsdata.message.includes('unarchived')){
			var buttonclass = 'unarchive';
		}else{
			var buttonclass = 'archive';
		}
		
		if(responsdata.subid != -1){
			var buttons = document.querySelectorAll('.form_data_table[data-id="'+responsdata.formid+'"] .table-row[data-id="'+responsdata.submissionid+'"][data-subid="'+responsdata.subid+'"] .'+buttonclass);
		}else{
			var buttons = document.querySelectorAll('.form_data_table[data-id="'+responsdata.formid+'"] .table-row[data-id="'+responsdata.submissionid+'"] .'+buttonclass);
		}
		
		var loader = document.querySelector('.form_data_table[data-id="'+responsdata.formid+'"] .loadergif');
		var loaderhtml = loader.outerHTML;
		
		
		
		if(responsdata.message.includes('unarchived')){
			loader.parentNode.innerHTML = loader.parentNode.innerHTML.replace(loaderhtml,'<button class="archive button table_action" name="archive_action" value="">Archive</button>');
			console.log(loader.parentNode);
			
			
			buttons.forEach(
				button=>{
					button.textContent = 'Archive';
					button.classList.replace('unarchive','archive');
				}
			);
		}else{
			loader.parentNode.innerHTML = loader.parentNode.innerHTML.replace(loaderhtml,'<button class="unarchive button table_action" name="unarchive_action">Unarchive</button>');
			buttons.forEach(
				button=>{
					button.textContent = 'Unarchive';
					button.classList.replace('archive','unarchive');
				}
			);
		}
	}
}

window['showforminput']			= function(result,responsdata){
	var html		= responsdata.html; 
	
	//if there is a subid, make sure the input shows up on the right place
	if(isNaN(responsdata.subid)){
		var subid =  '';
	}else{
		var subid =  '[data-subid="'+responsdata.subid+'"]';
	}
	target_cell	= document.querySelector('table[data-id="'+responsdata.formid+'"] tr[data-id="'+responsdata.submissionid+'"]'+subid+' td[data-id="'+responsdata.cellid+'"]');
	
	if(target_cell != null){
		target_cell.innerHTML	 = html;
		
		var inputs	= target_cell.querySelectorAll('input,select,textarea');
		
		inputs.forEach(inputnode=>{
			//add old value
			old_value.split(',').forEach(val=>{
				if(inputnode.type == 'checkbox' || inputnode.type == 'radio'){
					if(inputnode.value == val.trim()){
						inputnode.checked = true;
					}
				}else if(inputnode.type == 'select'){
					inputnode.querySelector('option[value="'+val+'"]').selected = true;
				}else{
					inputnode.value	= old_value;
				}
			});
			
			if(inputnode.type == 'select-one'){
				inputnode._niceselect = NiceSelect.bind(inputnode,{searchable: true});
			}
			
			//Add a keyboard
			inputnode.addEventListener("keyup", function(event){
				if (event.keyCode === 13) {
					processInput(event);
				}
			});
			
			//add a listener for clicks outside the cell
			document.addEventListener('click',outsideclicked);
			
			if(inputnode.type != 'checkbox' || inputs.length == 1){
				if(inputnode.type == 'date'){
					inputnode.addEventListener("blur", function(event){
						//only process if we added a value
						if(event.target.value != ''){
							processInput(event);
						}
					});
				}else{
					inputnode.addEventListener("change", function(event){
						//only process if we added a value
						if(event.target.value != ''){
							processInput(event);
						}
					});
				}
				
				inputnode.focus();
			}
		});
	}
}

window['changetablevalue']		= function(result,responsdata){
	//console.log(responsdata);
	//if there is a subid, make sure the new value shows up on the right place
	if(isNaN(responsdata.subid)){
		var subid =  '';
	}else{
		var subid =  '[data-subid="'+responsdata.subid+'"]';
	}
	var target_cells	= document.querySelectorAll('table[data-id="'+responsdata.formid+'"] tr[data-id="'+responsdata.submissionid+'"]'+subid+' td[data-id="'+responsdata.cellid+'"]');
	
	target_cells.forEach(cell=>{
		var value = responsdata.newvalue;
		//Replace the input element with its value
		if(value == "") value = "X";
		cell.innerHTML = value;
	});
	
	//reset editing indicator
	editedel = '';
}

function outsideclicked(event){
	if(event.target.closest('td') != target_cell){
		//remove as soon as we come here
		this.removeEventListener("click", arguments.callee);
		processInput(event,target_cell);
	}
}

//function to change a cells contents
var editedel = '';
function edit_td(target){
	//element is already edited
	if(editedel == target){
		return;
	}
	editedel			= target;
	var table			= target.closest('table');
	var nonce			= table.dataset.nonce;
	var submission_id	= target.closest('tr').dataset.id;
	var sub_id			= target.closest('tr').dataset.subid;
	var cell_id			= target.dataset.id
	old_text			= target.textContent;
	old_value			= target.dataset.original;
	
	showLoader(target.firstChild);
	
	if (old_value == "Click to update" || old_value == "X"){
		old_value = "";
	}
	
	//Get original input via AJAX
	var formdata = new FormData();
	formdata.append('action','get_input_html');
	formdata.append('formname',table.dataset.id);
	formdata.append('updateforminput', nonce);
	formdata.append('submission_id',submission_id);
	formdata.append('sub_id',sub_id);
	formdata.append('fieldname',cell_id);
	formdata.append('old_value',old_value);
	sendAJAX(formdata);
}

//function to get the temp input value and save it over AJAX
var running = false;
function processInput(event,target){
	if(typeof(target)=='undefined'){
		var target	= event.target;
	}
	
	form = target.closest('td');
	
	if(running == target){
		return;
	}
	running = target;
	
	setTimeout(function(){ running = false;}, 500);	
	
	var value			= get_field_value(target,false);
	var table			= target.closest('table');
	var submission_id	= target.closest('tr').dataset.id;
	var subid			= target.closest('td').dataset.subid;
	var fieldname		= target.closest('td').dataset.id;
	
	//remove all event listeners
	document.removeEventListener("click", outsideclicked);
	
	//Only update when needed
	if (value != old_value){
		showLoader(target.closest('td').firstChild);
		
		//get the updated fieldname from the column header
		var formdata = new FormData();
		formdata.append('action','forms_table_update');
		formdata.append('table_id',table.dataset.id);
		formdata.append('shortcodeid',table.dataset["shortcodeid"]);
		formdata.append('submission_id',submission_id);
		formdata.append('subid',subid);
		formdata.append('fieldname',fieldname);
		formdata.append('newvalue',value);
		formdata.append('formurl',location.href);
		
		sendAJAX(formdata);
	}else{
		console.log(value)
		target.closest('td').innerHTML = old_text;
	}
}

//function to sort a table by column
function sort_table(target){
	//console.log(target);
	var table 			= target.closest('table');
	var switching	 	= true;
	var shouldSwitch 	= false;
	var x,y;
	
	//Check the sort order
	if (target.classList.contains('dsc')){
		var sort 			= 'dsc';
	}else{
		var sort 		= 'asc';
	}
	
	/*Make a loop that will continue until
	no switching has been done:*/
	while (switching) {
		//start by saying: no switching is done:
		switching = false;
		rows = table.rows;
		/*Loop through all table rows (except the
		first, which contains table headers):*/
		for (i = 1; i < (rows.length - 1); i++) {
			//start by saying there should be no switching:
			shouldSwitch = false;
			// Get the lowercase cell contents
			x = rows[i].getElementsByTagName("TD")[target.cellIndex].innerHTML.toLowerCase();
			y = rows[i + 1].getElementsByTagName("TD")[target.cellIndex].innerHTML.toLowerCase();
			
			//check if numeric
			if(!isNaN(x) && !isNaN(y)){
				x = parseFloat(x);
				y = parseFloat(y);
			}else{
				//check if these are dates
				var datex = new Date(x);
				var datey = new Date(y);
				if(datex !== "Invalid Date" && !isNaN(datex) && datex.getYear()!=70 && datey !== "Invalid Date" && !isNaN(datey) && datey.getYear()!=70){
					x = datex;
					y = datey;
				}
			}
			
			//check if the two rows should switch place ASC:
			if (sort == 'asc' && x > y) {
				//if so, mark as a switch and break the loop:
				shouldSwitch = true;
				break;
			//check if the two rows should switch place DSC:
			}else if (sort == 'dsc'  && y > x) {
				shouldSwitch = true;
				break;
			}
		}
		
		if (shouldSwitch) {
			//Switch positions of the rows and start over
			rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
			switching = true;
		}
	}
	
	//Mark the row to sort dsc the next time
	if (sort == 'asc'){
		target.classList.remove('asc');
		target.classList.add('dsc');
	//Mark the row to sort asc the next time
	}else{
		target.classList.remove('dsc');
		target.classList.add('asc');
	}
}

//Function to listen to table buttons
function processButtons(target){
	var formdata = new FormData();
	
	var table		= target.closest('table');
	var table_row	= target.closest('tr');
	var show_swal	= true;
	
	//Check which button is clicked
	if(target.classList.contains('delete')){
		formdata.append('remove','true');
		text 	= 'delete';
	}else if(target.classList.contains('archive')){
		formdata.append('archive',true);
		text 	= "archive";
		
		if(table_row.dataset.subid != undefined){
			show_swal = false;
			var fire = false;
			Swal.fire({
				title: 'What do you want to archive?',
				text: "Do you want to archive just this one or the whole request?",
				icon: 'question',
				showDenyButton: true,
				showCancelButton: true,
				confirmButtonText: 'Just this one',
				denyButtonText: 'The whole request',
				confirmButtonColor: "#bd2919",
			}).then((result) => {
				if (result.isConfirmed) {
					formdata.append('subid',table_row.dataset.subid);
				}
				if(result.isDismissed == false){
					//display loading gif
					showLoader(target);
					formdata.append('action','forms_table_update');
					formdata.append('table_id',table.dataset.id);
					formdata.append('submission_id',table_row.dataset.id);
					formdata.append('shortcodeid',table.dataset.shortcodeid);
					sendAJAX(formdata);
				}
			})
		}
		
	}else if(target.classList.contains('unarchive')){
		formdata.append('archive',false);
		text 	= "unarchive";
	}else if(target.classList.contains('print')){
		window.location.href = window.location.href.split('?')[0]+"?print=true&table_id="+table.dataset.id+"&submission_id="+table_row.querySelector("[id='id' i]").textContent;
	}
	
	if(show_swal == true){
		Swal.fire({
			title: 'Are you sure?',
			text: "Are you sure you want to "+text+" this?",
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: "#bd2919",
			cancelButtonColor: '#d33',
			confirmButtonText: "Yes, "+text+" it!"
		}).then((result) => {
			if (result.isConfirmed) {
				//display loading gif
				showLoader(target);
				formdata.append('action','forms_table_update');
				formdata.append('table_id',table.dataset.id);
				formdata.append('submission_id',table_row.dataset.id);
				formdata.append('shortcodeid',table.dataset.shortcodeid);
				sendAJAX(formdata);
			}
		})
	}
};

//Store the table headers as td attribute for use on smaller screens
function setTableLabel() {
	//Loop over all tables
	document.querySelectorAll('.table').forEach(function(table){
		//console.log(table);
		
		//Get all heading elements
		tdLabels = [];
		table.querySelectorAll('thead th').forEach((el,index) => {
			if(el.dataset.nicename != null){
				tdLabels[index]	= el.dataset.nicename;
			}else{
				tdLabels[index]	= el.textContent;
			}
		});
		
		//loop over all table rows
		table.querySelectorAll('tbody tr').forEach( tr => {
			//loop over all table cells
			Array.from(tr.children).forEach(
				//set the header text as label
				function(td, index){
					td.setAttribute('label', tdLabels[index]);
				}
			);
		});
	});
}

function position_table(){
	//use whole page width for tables
	document.querySelectorAll(".form_table_wrapper").forEach(wrapper=>{
		var width	= wrapper.querySelector('table').scrollWidth;
		var diff	= window.innerWidth - width;
		
		//calculate if room for sidebar
		if((width/window.innerWidth)<0.7){
			diff	= (window.innerWidth*0.7) - width;
		}else{
			document.getElementById('primary').style.zIndex=1;
			//sidebar behind table
			document.querySelectorAll('#right-sidebar').forEach(el=>el.style.zIndex=0);
			//Table needs full screen width
			if(diff<20){
				var new_x = 10;
			//center the table
			}else{
				var new_x = diff/2; 
			}
		}
		
		
		//first set it back to default
		if(wrapper.style.marginLeft != ''){
			wrapper.style.marginLeft = '-0px';
		}
		
		//then calculate the required offset
		var offset	= parseInt(wrapper.getBoundingClientRect().x)-new_x;

		wrapper.style.marginLeft = '-'+offset+'px';
	});
}

document.addEventListener("click", event=>{
	var target = event.target;
	
	//Actions
	if(target.classList.contains('table_action')){
		processButtons(target);
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
		target.parentNode.querySelector('.form_shortcode_settings').classList.remove('hidden');
	}
	
	if(target.tagName == 'TH'){
		sort_table(target);
	}
	
	//Edit data]
	var td = target.closest('td');
	if(target.matches('td.edit')){
		edit_td(target);
	}else if(td != null && td.matches('td.edit') && target.tagName != 'INPUT' && target.tagName != 'A' && target.tagName != 'TEXTAREA' && !target.closest('.nice-select') ){
		edit_td(target.closest('td'));
	}
	
	//Hide column
	if(target.classList.contains('visibilityicon')){
		if(target.tagName == 'SPAN'){
			target = target.querySelector('img');
		}
		
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
	
});

document.addEventListener("DOMContentLoaded",function() {
	console.log("Table.js loaded");
	
	position_table();
	window.addEventListener('resize', position_table);
	
	//add label attribute
	setTableLabel();
	
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
	
	//sort the table
	document.querySelectorAll('th.defaultsort').forEach(column=>sort_table(column));

});