import { getFieldValue } from './partials/field_value.js';

console.log("Table.js loaded");

function prepareInputs(cell){
	let inputs	= cell.querySelectorAll('input,select,textarea');
		
	inputs.forEach(inputnode => {
		//add old value
		cell.dataset.oldText.split(',').forEach(val=>{
			if(inputnode.type == 'checkbox' || inputnode.type == 'radio'){
				if(inputnode.value == val.trim()){
					inputnode.checked = true;
				}
			}else if(inputnode.type == 'select'){
				inputnode.querySelector('option[value="'+val+'"]').selected = true;
			}else{
				inputnode.value	= cell.dataset.oldText;
			}
		});
		
		if(inputnode.type == 'select-one' && inputnode._niceSelect == undefined){
			Main.attachNiceSelect(inputnode);
		}
		
		if(inputnode.type != 'checkbox' || inputs.length == 1){			
			inputnode.focus();
		}
	});
}

/**
 * function to change a cells contents
 * Overridable via the table-content-update-inputs-loaded event
 */
function editTd(target){
	target = target.closest('td');

	// First make sure we have processed all others
	document.querySelectorAll('td.editing').forEach(td => {
		if(td != target){
			processInput(td);
		}
	});
	
	target.classList.add('editing');

	//element is already edited
	target.dataset.oldHtml	= target.innerHTML;
	target.dataset.oldText	= target.textContent;
	if (target.textContent == "Click to update" || target.textContent == "X"){
		target.dataset.oldText 	= "";
		target.dataset.oldHtml	= "";
	}
	
	// insert an text input
	target.innerHTML = `<div class='override-wrapper'><input type="text" value="${target.textContent}"></div>`;

	let button = document.createElement('button');
    button.innerHTML = 'Save changes';
    button.classList.add('button', 'small','save', 'hidden');

	button.addEventListener('click', ev => processInput(ev.target.closest('td')));

	// Add the button
	target.appendChild(button);

	// Create an event so other scripts can change the input type if needed
	const event = new Event('table-content-update-inputs-loaded', {bubbles: true, cancelable: true});
    let result	= target.dispatchEvent(event);

	// Do not continue if a listener called preventDefault();
	if(!result){
		return;
	}

	button.classList.remove('hidden');

	prepareInputs(target);
}

/**
 * function to get the temp input value and save it using the rest api
 * 
 */
var running = false;
async function processInput(target){
	// target is an event
	if(target.target != undefined){
		target.stopImmediatePropagation();

		target = target.target;
	}

	// We are already working on this one, no need to continue
	if( running == target ){
		return;
	}

	// prevent doubles
	running = target;
	// Clear the running variable after half a second, 
	setTimeout(function(){ running = false;}, 500);	

	let cell 	= target.closest('td');	
	let value	= getFieldValue(target, cell, false);
	let table	= target.closest('table');
	
	//Only update when needed
	if (value != cell.dataset.oldText){

		// Create an event so other scripts can do their own change logic
		const event = new Event('table-content-before-update', {bubbles: true, cancelable: true});
		
		let result	= cell.dispatchEvent(event);

		// Do not continue if a listener called preventDefault();
		if(!result){
			return;
		}

		// No need for further processing
		if(cell.classList.contains('editing') == false){
			return;
		}
		
		let formData = new FormData();
		formData.append('value', JSON.stringify(value));

		for( let key in cell.dataset){
			formData.append(key, cell.dataset[key]);
		}
		for( let key in target.closest('tr').dataset){
			formData.append(key, target.closest('tr').dataset[key]);
		}
		
		Main.showLoader(cell.firstChild);
		
		let url			= table.dataset.url;
		if(url == undefined){
			console.error('No rest api url found');
			cell.innerHTML = target.dataset.oldHtml;

			return;
		}

		let response 	= await FormSubmit.fetchRestApi(url, formData);

		if(response){
			cell.innerHTML = value;
		}
	}
	
	// Restore cell contents
	else{
		cell.innerHTML = target.dataset.oldHtml;
	}

	cell.classList.remove('editing');
}

//function to sort a table by column
function sortTable(target){
	let table 			= target.closest('table');
	let switching	 	= true;
	let x,y, rows;
	var sort 		= 'asc';
	
	//Check the sort order
	if (target.classList.contains('desc')){
		sort 		= 'desc';
	}
	
	/*Make a loop that will continue until
	no switching has been done:*/
	while (switching) {
		//start by saying: no switching is done:
		switching	= false;
		rows		= table.rows;
		/*Loop through all table rows (except the
		first, which contains table headers):*/
		for (let i = 1; i < (rows.length - 1); i++) {
			// Get the lowercase cell contents
			x = rows[i].getElementsByTagName("TD")[target.cellIndex].innerHTML.toLowerCase();
			y = rows[i + 1].getElementsByTagName("TD")[target.cellIndex].innerHTML.toLowerCase();
			
			//check if numeric
			if(!isNaN(x) && !isNaN(y)){
				x = parseFloat(x);
				y = parseFloat(y);
			}else{
				//check if these are dates
				let datex = new Date(x);
				let datey = new Date(y);
				if(datex !== "Invalid Date" && !isNaN(datex) && datex.getYear()!=70 && datey !== "Invalid Date" && !isNaN(datey) && datey.getYear()!=70){
					x = datex.getTime();
					y = datey.getTime();
				}
			}
			
			//check if the two rows should switch place ASC:
			if ((sort == 'asc' && x > y) || (sort == 'desc'  && y > x)) {
				//Switch positions of the rows and start over
				rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
				switching = true;
			}
		}
	}
	
	//Mark the row to sort desc the next time
	if (sort == 'asc'){
		target.classList.add('desc');
		target.classList.replace('asc', 'desc');
	//Mark the row to sort asc the next time
	}else{
		target.classList.replace('desc', 'asc');
	}

	// Create an event so other scripts can do their own sort logic
	const event = new Event('table-sorted', {bubbles: true, cancelable: true});
	
	let result	= target.dispatchEvent(event);
}

//Store the table headers as td attribute for use on smaller screens
export function setTableLabel() {
	//Loop over all tables
	document.querySelectorAll('.sim-table').forEach(function(table){
		//Get all heading elements
		let tdLabels = [];
		table.querySelectorAll('thead th').forEach((el,index) => {
			if(el.dataset.niceName != null){
				tdLabels[index]	= el.dataset.niceName;
			}else{
				tdLabels[index]	= el.textContent;
			}
		});
		
		//loop over all table rows
		table.querySelectorAll('tbody td').forEach( (td) => {
			if(!td.hasAttribute('label') && tdLabels[td.cellIndex] != undefined){
				//set the header text as label
				td.setAttribute('label', tdLabels[td.cellIndex]);
			}
			
			if(td.textContent == 'X' || td.textContent == ''){
				td.classList.add('mobile-hidden');
			}
		});
	});
}

async function showFullscreen(target){
	target.textContent	= 'Close full screen';
	target.classList.replace('show', 'close');

	let parent	= target.closest('.table-wrapper');

	//store current y position
	window.lastY	= window.pageYOffset;

	window.scrollTo(0,0);

	// remove scrollbars from body
	document.querySelector('body').style.overflow	= 'hidden';

	document.querySelector('header').style.zIndex	= 'unset';

	parent.classList.add('fullscreen');

	parent.style.marginLeft	= '0px';

	let url = new URL(window.location);

	url.searchParams.set('fullscreen', parent.querySelector('table').dataset.formId);

	window.history.pushState({}, '', url);

	setTableHeight();

	parent.requestFullscreen().catch((err) => {
		// does not work without user interaction
	});
}

function closeFullscreen(target){
	let lastY	= 100;

	target.textContent	= 'Show full screen';
	target.classList.replace('close','show');

	if(window.lastY != undefined){
		lastY	= window.lastY;
	}
	window.scrollTo(0, lastY);
	
	console.log('scrolling')

	// remove scrollbars from body
	document.querySelector('body').style.overflow	= 'unset';

	document.querySelector('header').style.zIndex	= '99999';

	target.closest('.table-wrapper').classList.remove('fullscreen');

	positionTable();

	let url = new URL(window.location);

	url.searchParams.delete('fullscreen');

	window.history.pushState({}, '', url);

	document.exitFullscreen();

	setTableHeight();
}

function setTableHeight(){
	let urlParams = new URLSearchParams(window.location.search);
	let	fullscreen	= urlParams.get('fullscreen');

	// calculate how heigh the table should be
	let footerHeight, headerHeight;
	document.querySelectorAll('.sim-table').forEach( table => {
		let wrapper	= table.closest('.table-wrapper');
		if(wrapper == null){
			return;
		}
		
		if(fullscreen != null){
			headerHeight	= 0;
			wrapper.querySelectorAll('.table-head').forEach(el=> headerHeight = el.offsetHeight);
		}else{
			headerHeight	= document.querySelector('header').offsetHeight;
		}
		
		footerHeight	= 0;
		wrapper.querySelectorAll('.sim-table-footer').forEach(el=> footerHeight	= el.offsetHeight);

		let px	= headerHeight + footerHeight + 40;
		table.style.maxHeight	= `calc(100vh - ${px}px)`;
	});	
}

export function positionTable(){
	//use whole page width for tables
	document.querySelectorAll(".table-wrapper").forEach(wrapper=>{
		let offset		= '';
		let newX		= 0;

		let table	= wrapper.querySelector('table');
		if(table == null){
			return;
		}
		let width	= table.scrollWidth;
		if(width == 0){
			return;
		}
		
		// If on small width use full screen
		if(window.innerWidth < 570){
			offset	= wrapper.getBoundingClientRect().x
		}else{
			let diff	= window.innerWidth - width;
			
			//calculate if room for sidebar if one exists
			if((width/window.innerWidth) < 0.7){
				if(document.querySelector('.is-right-sidebar') != null){
					diff	= (window.innerWidth * 0.7) - width;
				}
			}else{
				document.getElementById('primary').style.zIndex = 1;

				//sidebar behind table
				document.querySelectorAll('#right-sidebar').forEach(el=>el.style.zIndex = 0);
			}

			//Table needs full screen width
			if(diff < 20){
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

export const hideColumn	= async (target) => {
	if(target.tagName == 'SPAN'){
		target = target.querySelector('img');
	}

	let cell;

	// Table itself
	if(target.parentNode.matches('th')){
		cell 	= target.parentNode;
	}else if(target.matches('th')){
		cell	= target;
	}

	// Create an event so other scripts can do their own change logic
	const event = new Event('table-content-before-column-hide', {bubbles: true, cancelable: true});
	
	let result	= target.dispatchEvent(event);

	if(result && cell){
		// Hide the column
		let table		= cell.closest('table');
		let tableRows	= table.rows;
		for (const element of tableRows) {
			element.cells[cell.cellIndex].classList.add('hidden')
		}

		// store the hidden column in a var
		if(sim.hidden	== undefined){
			sim.hidden	= [];
		}
		sim.hidden.push(cell.cellIndex);

		//show the reset button
		cell.closest('.table-wrapper').querySelectorAll('.reset-col-vis').forEach(el => el.classList.remove('hidden'));
	}
}

async function showHiddenColumns(target){
	//hiden the reset button
	target.closest('.table-wrapper').querySelector('.reset-col-vis').classList.add('hidden');

	// Show the columns again
	let table		= target.closest('.form.table-wrapper').querySelector('table');
	table.querySelectorAll('th.hidden, td.hidden').forEach(el=>el.classList.remove('hidden'));
}

document.addEventListener("click", event => {
	let target 	= event.target;

	// We are editing a cell value but we clicked somewhere outside the cell
	if(document.querySelector('.editing') && document.querySelector('.editing') != target.closest('td')){
		processInput(document.querySelector('.editing'));
	}
	
	if(target.tagName == 'TH'){
		sortTable(target);
	}
	
	//Edit data
	else if(target.matches('td.edit:not(.editing)') ){
		editTd(target);
	}else if(target.matches('.show.fullscreenbutton')){
		showFullscreen(target);
	}else if(target.matches('.close.fullscreenbutton')){
		closeFullscreen(target);
	}

	//Hide column
	else if(target.classList.contains('visibility-icon')){
		hideColumn(target);
	}

	// Show all columns again
	else if(target.matches('.reset-col-vis')){
		showHiddenColumns(target);
	}else{
		return;
	}
	
	event.stopImmediatePropagation();
});

document.addEventListener("DOMContentLoaded", function() {
	
	positionTable();
	window.addEventListener('resize', positionTable);
	
	//add label attribute
	setTableLabel();

	let urlParams = new URLSearchParams(window.location.search);
	let	fullscreen	= urlParams.get('fullscreen');

	if(fullscreen != null){
		try{
			showFullscreen(document.querySelector(`table[data-form-id="${fullscreen}"]`).closest('.table-wrapper').querySelector('.fullscreenbutton')); 
		}catch{
			console.error(`table[data-form-id="${fullscreen}"]`);
		}
	}

	setTableHeight();
});

/**
 * Add a keyboard listener
 */
// Keep track of which keys are pressed
let keysPressed = {};
document.addEventListener('keydown', (event) => {
   keysPressed[event.key] = true;
});

// Listen to enters but onlyif shift is not pressed
document.addEventListener("keyup", function(event){
	if (['Enter', 'NumpadEnter'].includes(event.key) && keysPressed.Shift == undefined) {

		processInput(event);
	}

	delete keysPressed[event.key];
});
