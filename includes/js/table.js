window['updated_table_value']		= function(result,responsdata){
	//if there is a subid, make sure the new value shows up on the right place
	var loaders	= document.querySelectorAll('td .loadergif');
	
	loaders.forEach(img=>{
		var value = responsdata.new_value;
		//Replace the input element with its value
		if(value == "") value = "X";
		img.closest('td').innerHTML = value;
	});
	
	//reset editing indicator
	editedel = '';
}

function add_input_event_listeners(cell){
	var inputs	= cell.querySelectorAll('input,select,textarea');
		
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
		document.addEventListener('click', outsideclicked);
		
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

	old_value	= target.textContent;

	if (old_value == "Click to update" || old_value == "X"){
		old_value = "";
	}
	
	target.innerHTML = target.innerHTML='<input type="text" value="'+old_value+'">';

	add_input_event_listeners(target);
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
	
	//remove all event listeners
	document.removeEventListener("click", outsideclicked);
	
	//Only update when needed
	if (value != old_value || table.dataset.action == undefined){		
		//get the updated fieldname from the column header
		var formdata = new FormData();
		formdata.append('action', table.dataset.action);
		formdata.append('value', value);

		for( var key in target.closest('td').dataset){
			formdata.append(key, target.closest('td').dataset[key]);
		}
		for( var key in target.closest('tr').dataset){
			formdata.append(key, target.closest('tr').dataset[key]);
		}
		
		showLoader(target.closest('td').firstChild);
		
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
					x = datex.getTime();
					y = datey.getTime();
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

//Store the table headers as td attribute for use on smaller screens
function setTableLabel() {
	//Loop over all tables
	document.querySelectorAll('.sim-table').forEach(function(table){
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
					if(td.textContent=='X' || td.textContent==''){
						td.classList.add('mobile-hidden');
					}
				}
			);
		});
	});
}

function position_table(){
	//use whole page width for tables
	document.querySelectorAll(".form-table-wrapper").forEach(wrapper=>{
		var table	= wrapper.querySelector('table');
		var width	= table.scrollWidth;
		if(table != null && width != 0){
			// If on small width use full screen
			if(window.innerWidth < 570){
				var offset	= wrapper.getBoundingClientRect().x
			}else{
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
			}

			wrapper.style.marginLeft = '-'+offset+'px';
		}
	});
}

document.addEventListener("click", event=>{
	var target = event.target;
	
	//Actions
	if(target.classList.contains('table_action')){
		//processButtons(target);
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
});

document.addEventListener("DOMContentLoaded",function() {
	console.log("Table.js loaded");
	
	position_table();
	window.addEventListener('resize', position_table);
	
	//add label attribute
	setTableLabel();
	
	//sort the table
	document.querySelectorAll('th.defaultsort').forEach(column=>sort_table(column));

});