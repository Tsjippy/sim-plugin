console.log('formsubmit loaded');

import {formReset, submitForm, markComplete, fetchRestApi} from './form_submit_functions.js';
export {formReset, submitForm, markComplete, fetchRestApi};

document.querySelectorAll('form.sim_form').forEach(form=>form.addEventListener("submit", markComplete));

// check for unsaved formdata
window.addEventListener("beforeunload", (event) => {
	if(document.querySelector('.loadergif:not(.hidden)') != null){
		return;
	}
	
	// check all pending
	document.querySelectorAll('[data-pending]').forEach(el=>{
		console.log(el);
		console.log(`${el.defaultValue} - ${el.value}`);
		event.preventDefault();
		event.returnValue = 'test2';
	});

	// check all inputs
	document.querySelectorAll('form.sim_form input:not([type=radio], [type=checkbox]), form textarea').forEach(el=>{
		if(el.defaultValue != el.value){
			console.log(el);
			console.log(`${el.defaultValue} - ${el.value}`);
			event.preventDefault();
			event.returnValue = 'test2';
		}
	});

	// check all checkboxes and radio
	document.querySelectorAll('form.sim_form input[type=radio], form input[type=checkbox]').forEach(el=>{
		if(el.defaultChecked != el.checked){
			console.log(el);
			console.log(`${el.defaultValue} - ${el.value}`);
			event.preventDefault();
			event.returnValue = 'test2';
		}
	});

	// check all dropdowns
	document.querySelectorAll('form.sim_form select').forEach(el=>{
		if(el.selectedIndex != 0 && el.options[el.selectedIndex] != undefined && !el.options[el.selectedIndex].defaultSelected){
			console.log(el)
			console.log(el.options[el.selectedIndex].defaultSelected);
			event.preventDefault();
			event.returnValue = 'test2';
		}
	});
});

let timer;

document.addEventListener('input', (ev)=>{
	if(ev.target.matches(`.datalistinput.multiple`)){
		// clear any previous set timers
		clearTimeout(timer);

		// 2 seconds
		let timeout	= 2000;

		// if the value is found in the datalist
		if(ev.target.list.querySelector(`[value="${ev.target.value}"]`) != null){
			doneTyping(ev.target);
		}else{
			timer = setTimeout(() => {
				doneTyping(ev.target);
			}, timeout);
		}
	}
});

document.addEventListener('click', (ev)=>{
	if(ev.target.matches(`.remove-list-selection`)){
		ev.target.closest('.listselection').remove();
	}
})

function doneTyping(el) {

	if(el.value	== ''){
		return;
	}

	let li	 		= document.createElement('li');
	li.classList.add('listselection');

	let html	= `<button type="button" class="small remove-list-selection"><span class='remove-list-selection'>×</span></button>`;

	// find the option in the datalist
	let option	= el.list.querySelector(`[value="${el.value}"]`);
	if(option != null && option.dataset.value != null){
		html   += `<input type='hidden' name='${el.id}[]' value='${option.dataset.value}'>`;
		html   += `<span class='selectedname'>${el.value}</span>`;
	}else{
		html   += `<span>`;
			html   += `<input type='text' name='${el.id}[]' value='${el.value}' readonly=readonly style='width:${el.value.length}ch'>`;
		html   += `</span>`;
	}

	li.innerHTML	= html;

	el.closest('.optionwrapper').querySelector('.listselectionlist').appendChild(li);

	el.value	= '';
}