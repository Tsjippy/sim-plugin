var oldValue;

function outsideClicked(event){
	if(event.target.closest('td') == null || !event.target.closest('td').matches('.editing')){
		event.stopPropagation();
		
		//remove as soon as we come here
		document.removeEventListener('click', outsideClicked);
		processInput(event, document.querySelector('.editing input, .editing select, .editing textarea'));
	}
}

function addInputEventListeners(cell){
	var inputs	= cell.querySelectorAll('input,select,textarea');
		
	inputs.forEach(inputnode=>{
		//add old value
		oldValue.split(',').forEach(val=>{
			if(inputnode.type == 'checkbox' || inputnode.type == 'radio'){
				if(inputnode.value == val.trim()){
					inputnode.checked = true;
				}
			}else if(inputnode.type == 'select'){
				inputnode.querySelector('option[value="'+val+'"]').selected = true;
			}else{
				inputnode.value	= oldValue;
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
		document.addEventListener('click', outsideClicked);
		
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

//function to change a cells contents
function editTd(target){
	target.closest('td').classList.add('editing');

	//element is already edited

	oldValue	= target.textContent;
	if (oldValue == "Click to update" || oldValue == "X"){
		oldValue = "";
	}
	
	target.innerHTML = `<input type="text" value="${oldValue}">`;

	addInputEventListeners(target);
}

//function to get the temp input value and save it using the rest api
async function processInput(event, target){
	if(typeof(target)=='undefined'){
		var target	= event.target;
	}
	
	var cell 			= target.closest('td');	
	var value			= FormFunctions.getFieldValue(target, cell, false);
	var table			= target.closest('table');
	
	//Only update when needed
	if (value != oldValue){		
		//get the updated fieldname from the column header
		var formData = new FormData();
		formData.append('value', value);

		for( var key in cell.dataset){
			formData.append(key, cell.dataset[key]);
		}
		for( var key in target.closest('tr').dataset){
			formData.append(key, target.closest('tr').dataset[key]);
		}
		
		Main.showLoader(cell.firstChild);
		
		response = await FormSubmit.fetchRestApi(table.dataset.url, formData);

		if(response){
			cell.innerHTML = value;

			//reset editing indicator
			editedel = '';
		}
	}else{
		console.log(value)
		target.closest('td').innerHTML = oldValue;
	}

	cell.classList.remove('editing');
}

//function to sort a table by column
function sortTable(target){
	//console.log(target);
	var table 			= target.closest('table');
	var switching	 	= true;
	var shouldSwitch 	= false;
	var x,y, rows;
	
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
		switching	= false;
		rows		= table.rows;
		/*Loop through all table rows (except the
		first, which contains table headers):*/
		for (var i = 1; i < (rows.length - 1); i++) {
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
		var tdLabels = [];
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

function showFullscreen(target){
	target.textContent	= 'Close full screen';
	target.classList.replace('show', 'close');

	var parent	= target.closest('.table-wrapper');

	//store current y position
	window.lastY	= window.pageYOffset;

	window.scrollTo(0,0);

	// remove scrollbars from body
	document.querySelector('body').style.overflow	= 'hidden';

	document.querySelector('header').style.zIndex	= 'unset';

	parent.classList.add('fullscreen');

	parent.style.marginLeft	= '0px';

	var url = new URL(window.location);

	url.searchParams.set('fullscreen', parent.querySelector('table').dataset.formid);

	window.history.pushState({}, '', url);
}

function closeFullscreen(target){
	var lastY	= 100;

	target.textContent	= 'Show full screen';
	target.classList.replace('close','show');

	if(window.lastY != undefined){
		var lastY	= window.lastY;
	}
	window.scrollTo(0, lastY);

	// remove scrollbars from body
	document.querySelector('body').style.overflow	= 'unset';

	document.querySelector('header').style.zIndex	= '99999';

	target.closest('.table-wrapper').classList.remove('fullscreen');

	positionTable();

	var url = new URL(window.location);

	url.searchParams.delete('fullscreen');

	window.history.pushState({}, '', url);
}

export function positionTable(){
	//use whole page width for tables
	document.querySelectorAll(".table-wrapper").forEach(wrapper=>{
		var offset		= '';
		var newX		= 0;

		var table	= wrapper.querySelector('table');
		if(table == null){
			return;
		}
		var width	= table.scrollWidth;
		if(width == 0){
			return;
		}
		
		// If on small width use full screen
		if(window.innerWidth < 570){
			offset	= wrapper.getBoundingClientRect().x
		}else{
			var diff	= window.innerWidth - width;
			
			//calculate if room for sidebar if one exists
			if((width/window.innerWidth) < 0.7){
				if(document.querySelector('.is-right-sidebar') != null){
					diff	= (window.innerWidth*0.7) - width;
				}
			}else{
				document.getElementById('primary').style.zIndex=1;
				//sidebar behind table
				document.querySelectorAll('#right-sidebar').forEach(el=>el.style.zIndex=0);
			}

			//Table needs full screen width
			if(diff<20){
				newX = 10;
			//center the table
			}else{
				newX = diff/2; 
			}			
			
			//first set it back to default
			if(wrapper.style.marginLeft != ''){
				wrapper.style.marginLeft = '-0px';
			}
			
			//then calculate the required offset
			offset	= parseInt(wrapper.getBoundingClientRect().x)-newX;
		}

		wrapper.style.marginLeft = `-${offset}px`;
	});
}

document.addEventListener("click", event=>{
	var target = event.target;
	
	if(target.tagName == 'TH'){
		sortTable(target);
	}
	
	//Edit data]
	var td = target.closest('td');
	if(target.matches('td.edit')){
		event.stopPropagation();
		editTd(target);
	}else if(td != null && td.matches('td.edit') && target.tagName != 'INPUT' && target.tagName != 'A' && target.tagName != 'TEXTAREA' && !target.closest('.nice-select') ){
		event.stopPropagation();
		editTd(target.closest('td'));
	}else if(target.matches('.show.fullscreenbutton')){
		showFullscreen(target);
	}else if(target.matches('.close.fullscreenbutton')){
		closeFullscreen(target);
	}
});

document.addEventListener("DOMContentLoaded",function() {
	console.log("Table.js loaded");
	
	positionTable();
	window.addEventListener('resize', positionTable);
	
	//add label attribute
	setTableLabel();

	const urlParams = new URLSearchParams(window.location.search);
	var	fullscreen	= urlParams.get('fullscreen');

	if(fullscreen != null){
		try{
			showFullscreen(document.querySelector(`table[data-formid="${fullscreen}"]`).closest('.table-wrapper').querySelector('.fullscreenbutton')); 
		}catch{
			console.error(`table[data-formid="${fullscreen}"]`);
		}
	}
});